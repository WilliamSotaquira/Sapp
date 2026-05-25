<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\SubService;
use App\Services\SmartParser\LlmTextInterpreter;
use App\Services\SmartParser\SmartParserPipeline;
use App\Services\SmartParser\StructuredFormatDetector;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceRequestPlainTextImportService
{
    public function __construct(
        private readonly ServiceRequestService $serviceRequestService,
        private readonly SmartParserPipeline $smartPipeline,
        private readonly StructuredFormatDetector $formatDetector,
    ) {
    }

    public function parseToFormData(string $plainText, int $companyId, ?int $requestedBy = null): array
    {
        $text = trim($plainText);
        if ($text === '') {
            throw ValidationException::withMessages([
                'plain_text' => 'Pega un texto para poder interpretarlo.',
            ]);
        }

        // Validación de longitud mínima
        if (mb_strlen($text) < 20) {
            throw ValidationException::withMessages([
                'plain_text' => 'El texto es demasiado corto para identificar una solicitud.',
            ]);
        }

        // Validación de longitud máxima
        if (mb_strlen($text) > 50000) {
            throw ValidationException::withMessages([
                'plain_text' => 'El texto excede el límite máximo permitido (50000 caracteres).',
            ]);
        }

        $company = Company::query()->with('activeContract')->find($companyId);
        $activeContractId = (int) ($company?->active_contract_id ?? 0);

        if ($company && $activeContractId <= 0) {
            $activeContractId = (int) (Contract::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->value('id') ?? 0);

            if ($activeContractId > 0) {
                $company->forceFill(['active_contract_id' => $activeContractId])->saveQuietly();
            }
        }

        if (!$company || $activeContractId <= 0) {
            throw ValidationException::withMessages([
                'plain_text' => 'El espacio actual no tiene contrato activo para resolver el subservicio.',
            ]);
        }

        // Detectar si el texto sigue el formato estructurado
        if (!$this->formatDetector->isStructuredFormat($text)) {
            // Try LLM interpretation first (if enabled)
            $llmResult = $this->tryLlmInterpretation($text, $company, $requestedBy);
            if ($llmResult !== null) {
                return $llmResult;
            }

            // Fallback: formato libre con SmartParserPipeline heurístico
            return $this->parseWithSmartPipeline($text, $companyId, $requestedBy);
        }

        // Formato estructurado: usar algoritmo original
        $parsed = $this->extractStructuredData($text);

        if ($parsed['requester_name'] === '') {
            throw ValidationException::withMessages([
                'plain_text' => 'No se pudo identificar el nombre del solicitante en el texto pegado.',
            ]);
        }

        if ($parsed['sub_service_name'] === '') {
            throw ValidationException::withMessages([
                'plain_text' => 'No se pudo identificar el subservicio en el texto pegado.',
            ]);
        }

        $subService = $this->resolveBestSubService($parsed, $activeContractId, $text);
        if (!$subService) {
            throw ValidationException::withMessages([
                'plain_text' => 'No se encontró un subservicio activo que coincida con "' . $parsed['sub_service_name'] . '".',
            ]);
        }

        $requesterResult = $this->resolveRequester(
            $companyId,
            $parsed['requester_name'],
            $parsed['requester_email'],
        );

        $createdAt = $parsed['created_at']?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $criticalityLevel = $parsed['criticality_level'] ?: 'MEDIA';

        $context = $this->serviceRequestService->resolveCreationContext(
            $companyId,
            (int) $subService->id,
            $criticalityLevel,
            $parsed['created_at'],
        );

        $tasks = $parsed['tasks'];
        if ($tasks === []) {
            $tasks[] = [
                'title' => Str::limit($parsed['title'] ?: $parsed['sub_service_name'], 255, ''),
                'description' => Str::limit($parsed['description'], 500, ''),
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_minutes' => 30,
            ];
        }

        $payload = [
            'company_id' => $companyId,
            'requester_id' => $requesterResult['id'],
            'title' => Str::limit($parsed['title'] ?: $parsed['sub_service_name'], 255, ''),
            'description' => $parsed['description'] !== '' ? $parsed['description'] : $parsed['title'],
            'sub_service_id' => (int) $subService->id,
            'service_id' => (int) $context['service_id'],
            'family_id' => (int) $context['family_id'],
            'sla_id' => (int) $context['sla_id'],
            'requested_by' => $requestedBy,
            'entry_channel' => $parsed['entry_channel'],
            'criticality_level' => $criticalityLevel,
            'created_at' => $createdAt,
            'due_date' => $parsed['due_date'] ?? null,
            'web_routes' => json_encode($parsed['web_routes'], JSON_UNESCAPED_UNICODE),
            'is_reportable' => true,
            'tasks_template' => 'none',
            'tasks' => $tasks,
        ];

        // Si el solicitante no existe, pasar datos pendientes para creación diferida
        if (!empty($requesterResult['pending'])) {
            $payload['__pending_requester_name'] = $requesterResult['name'];
            $payload['__pending_requester_email'] = $requesterResult['email'] ?? '';
        }

        return [
            'payload' => $payload,
            'meta' => [
                'requester_name' => $requesterResult['name'],
                'requester_created' => false,
                'requester_pending' => !empty($requesterResult['pending']),
                'sub_service_name' => $subService->name,
                'task_count' => count($tasks),
                'web_route_count' => count($parsed['web_routes']),
            ],
        ];
    }

    /**
     * Maximum allowed time in seconds for the smart pipeline execution.
     */
    private const PIPELINE_TIMEOUT_SECONDS = 30;

    /**
     * Attempts to interpret the text using an LLM with ITIL prompts.
     * If the LLM returns valid structured text, parses it with the existing algorithm.
     * Returns null if LLM is unavailable, disabled, or returns invalid output.
     */
    private function tryLlmInterpretation(string $text, Company $company, ?int $requestedBy = null): ?array
    {
        if (! config('services.llm.enabled', false)) {
            return null;
        }

        try {
            $interpreter = app(LlmTextInterpreter::class);
            $workspaceName = $company->name ?? '';

            // Validate workspace-entity consistency BEFORE calling the LLM
            $this->validateTextBelongsToWorkspace($text, $workspaceName);

            // Validate that the workspace has a matching ITIL prompt
            if (!$interpreter->hasPromptForWorkspace($workspaceName)) {
                throw ValidationException::withMessages([
                    'plain_text' => "El espacio de trabajo \"{$workspaceName}\" no tiene un prompt ITIL configurado. Configura el archivo de prompt correspondiente en storage/app/prompts/.",
                ]);
            }

            $structuredText = $interpreter->interpret($text, $workspaceName);

            if ($structuredText === null) {
                return null;
            }

            // Validate workspace-entity consistency: check if the LLM response
            // contains a sub-service that belongs to a different entity's catalog
            $this->validateWorkspaceConsistency($structuredText, $workspaceName);

            // First try: check if LLM output passes the strict structured format detector
            if ($this->formatDetector->isStructuredFormat($structuredText)) {
                return $this->parseToFormData($structuredText, (int) $company->id, $requestedBy);
            }

            // Second try: parse the LLM ITIL output directly (more flexible)
            $parsed = $this->parseLlmItilOutput($structuredText, $company, $requestedBy);
            if ($parsed !== null) {
                return $parsed;
            }

            \Illuminate\Support\Facades\Log::info('LlmTextInterpreter: Could not parse LLM output');

            return null;
        } catch (ValidationException $e) {
            throw $e; // Re-throw validation exceptions to show to user
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::info('LlmTextInterpreter: Structured parse of LLM output failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validates that the interpreted text is consistent with the active workspace.
     * Throws a ValidationException if the content appears to belong to a different entity.
     */
    private function validateWorkspaceConsistency(string $llmOutput, string $workspaceName): void
    {
        // This method validates the LLM output (kept as secondary check)
        $normalizedWorkspace = mb_strtolower(trim($workspaceName));
        $normalizedOutput = mb_strtolower($llmOutput);

        $minCulturaSubServices = [
            'ejecución de envío de comunicaciones masivas',
            'actualización de contenidos en portal principal',
            'gestión de secciones especiales',
            'desarrollo de nuevas funcionalidades web',
            'mantenimiento de páginas y micrositios',
            'gestión técnica de aplicativos web',
            'ejecución de tareas técnicas o digitales específicas',
            'coordinación de actualizaciones por área',
        ];

        $movilidadSubServices = [
            'publicación de noticia pmt o artículo',
            'publicación de documento',
            'solicitud de edición o ajuste de contenido',
            'solicitud de diseño gráfico',
            'desarrollo configuración e implementación técnica',
            'reporte de enlace roto o contenido obsoleto',
            'asignación de tarea no especificada',
        ];

        $isWorkspaceCultura = str_contains($normalizedWorkspace, 'cultura');
        $isWorkspaceMovilidad = str_contains($normalizedWorkspace, 'movilidad');

        if ($isWorkspaceCultura) {
            foreach ($movilidadSubServices as $ss) {
                if (str_contains($normalizedOutput, $ss)) {
                    throw ValidationException::withMessages([
                        'plain_text' => 'La solicitud parece pertenecer a Movilidad, pero el espacio de trabajo activo es Cultura. Cambia al espacio de trabajo correcto antes de interpretar esta solicitud.',
                    ]);
                }
            }
        } elseif ($isWorkspaceMovilidad) {
            foreach ($minCulturaSubServices as $ss) {
                if (str_contains($normalizedOutput, $ss)) {
                    throw ValidationException::withMessages([
                        'plain_text' => 'La solicitud parece pertenecer a MinCultura, pero el espacio de trabajo activo es Movilidad. Cambia al espacio de trabajo correcto antes de interpretar esta solicitud.',
                    ]);
                }
            }
        }
    }

    /**
     * Validates that the raw text belongs to the active workspace.
     *
     * Strategy (in order):
     * 1. Extract the requester name from the text (email sender or WhatsApp contact)
     * 2. Check if that requester exists in the active workspace
     * 3. If not found in active workspace but found in another → reject
     * 4. Check email domains as additional signal
     * 5. Check entity mentions in signatures/text
     */
    private function validateTextBelongsToWorkspace(string $text, string $workspaceName): void
    {
        $normalizedWorkspace = mb_strtolower(trim($workspaceName));
        $normalizedText = mb_strtolower($text);

        $isWorkspaceCultura = str_contains($normalizedWorkspace, 'cultura');
        $isWorkspaceMovilidad = str_contains($normalizedWorkspace, 'movilidad');

        if (!$isWorkspaceCultura && !$isWorkspaceMovilidad) {
            return;
        }

        $activeCompanyId = (int) session('current_company_id', 0);
        if ($activeCompanyId <= 0) {
            return;
        }

        // Step 1: Extract requester name from text
        $requesterName = $this->extractRequesterNameFromRawText($text);

        // Step 2: If we found a name, check if it exists in the active workspace
        if ($requesterName !== null && mb_strlen($requesterName) >= 3) {
            // Normalize: remove accents, lowercase, collapse spaces
            $normalizedName = $this->normalizeNameForSearch($requesterName);

            // Split into individual words for matching (at least 2 words needed)
            $nameWords = array_filter(explode(' ', $normalizedName), fn($w) => mb_strlen($w) >= 3);

            if (count($nameWords) >= 2) {
                // Build a query that matches ALL significant words of the name
                $existsInActiveWorkspace = \App\Models\Requester::withoutGlobalScopes()
                    ->where('company_id', $activeCompanyId)
                    ->where('is_active', true)
                    ->where(function ($q) use ($nameWords) {
                        foreach ($nameWords as $word) {
                            $q->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        }
                    })
                    ->exists();

                if (!$existsInActiveWorkspace) {
                    // Check if the requester exists in ANY other workspace
                    $existsInOtherWorkspace = \App\Models\Requester::withoutGlobalScopes()
                        ->where('company_id', '!=', $activeCompanyId)
                        ->where('is_active', true)
                        ->where(function ($q) use ($nameWords) {
                            foreach ($nameWords as $word) {
                                $q->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                            }
                        })
                        ->first();

                    if ($existsInOtherWorkspace) {
                        $otherCompany = \App\Models\Company::find($existsInOtherWorkspace->company_id);
                        $otherName = $otherCompany?->name ?? 'otro espacio';
                        throw ValidationException::withMessages([
                            'plain_text' => "El solicitante \"{$requesterName}\" no pertenece al espacio de trabajo activo ({$workspaceName}). Se encontró en \"{$otherName}\". Cambia al espacio de trabajo correcto antes de interpretar esta solicitud.",
                        ]);
                    }
                }
            }
        }

        // Step 3: Check email domains
        preg_match_all('/[\w.+-]+@([\w-]+\.[\w.-]+)/i', $text, $emailMatches);
        $foundDomains = array_unique(array_map('strtolower', $emailMatches[1] ?? []));

        $culturaDomains = ['mincultura.gov.co'];
        $movilidadDomains = ['movilidadbogota.gov.co', 'transmilenio.gov.co', 'sdm.gov.co'];

        if (!empty($foundDomains)) {
            if ($isWorkspaceMovilidad) {
                foreach ($foundDomains as $domain) {
                    if (in_array($domain, $culturaDomains)) {
                        throw ValidationException::withMessages([
                            'plain_text' => 'Esta solicitud contiene correos de @mincultura.gov.co pero el espacio de trabajo activo es Movilidad. Cambia al espacio de trabajo "Cultura" para interpretar esta solicitud.',
                        ]);
                    }
                }
            } elseif ($isWorkspaceCultura) {
                foreach ($foundDomains as $domain) {
                    if (in_array($domain, $movilidadDomains)) {
                        throw ValidationException::withMessages([
                            'plain_text' => 'Esta solicitud contiene correos de @' . $domain . ' pero el espacio de trabajo activo es Cultura. Cambia al espacio de trabajo "Movilidad" para interpretar esta solicitud.',
                        ]);
                    }
                }
            }
        }

        // Step 4: Check entity mentions in signatures/text (flexible matching)
        if ($isWorkspaceCultura) {
            // Movilidad indicators - check for any of these patterns
            $movilidadPatterns = [
                'secretaría distrital de movilidad',
                'secretaria distrital de movilidad',
                'secretaría de movilidad',
                'secretaria de movilidad',
                'cultura para la movilidad',
                'movilidadtel:',
                'movilidad bogota',
                'movilidad bogotá',
            ];
            foreach ($movilidadPatterns as $pattern) {
                if (str_contains($normalizedText, $pattern)) {
                    throw ValidationException::withMessages([
                        'plain_text' => 'Esta solicitud pertenece a Movilidad (se detectó "' . $pattern . '"). El espacio de trabajo activo es Cultura. Cambia al espacio de trabajo "Movilidad" para interpretar esta solicitud.',
                    ]);
                }
            }
            // Also check if "movilidad" appears near "secretar" (handles broken line breaks)
            if (str_contains($normalizedText, 'movilidad') && str_contains($normalizedText, 'secretar')) {
                throw ValidationException::withMessages([
                    'plain_text' => 'Esta solicitud parece pertenecer a la Secretaría de Movilidad. El espacio de trabajo activo es Cultura. Cambia al espacio de trabajo "Movilidad" para interpretar esta solicitud.',
                ]);
            }
        } elseif ($isWorkspaceMovilidad) {
            $culturaPatterns = [
                'ministerio de las culturas',
                'ministerio de cultura',
                'mincultura.gov.co',
                'ministerio de las culturas, las artes',
            ];
            foreach ($culturaPatterns as $pattern) {
                if (str_contains($normalizedText, $pattern)) {
                    throw ValidationException::withMessages([
                        'plain_text' => 'Esta solicitud pertenece a MinCultura (se detectó "' . $pattern . '"). El espacio de trabajo activo es Movilidad. Cambia al espacio de trabajo "Cultura" para interpretar esta solicitud.',
                    ]);
                }
            }
        }
    }

    /**
     * Extracts the requester name from raw text.
     * Looks for email sender, WhatsApp contact, or first name-like line.
     */
    private function extractRequesterNameFromRawText(string $text): ?string
    {
        // Try email "De:" or "From:" header
        if (preg_match('/^(?:De|From)\s*:\s*(.+?)(?:\s*<[^>]+>)?$/mi', $text, $match)) {
            $name = trim($match[1]);
            if (mb_strlen($name) >= 3 && !str_contains($name, '@')) {
                return $name;
            }
        }

        // Try "Nombre Apellido <email>" pattern
        if (preg_match('/^([A-ZÁÉÍÓÚÑa-záéíóúñ\s]{3,50})\s*<[^>]+@/m', $text, $match)) {
            return trim($match[1]);
        }

        // Try WhatsApp format: "[fecha, hora] Nombre:"
        if (preg_match('/\[\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4},?\s+\d{1,2}:\d{2}\]\s*([^:]+):/m', $text, $match)) {
            return trim($match[1]);
        }

        // Try WhatsApp format: "hora - Nombre:"
        if (preg_match('/\d{1,2}:\d{2}\s*[ap]\.?\s*m\.?,?\s*\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\s*-\s*([^:]+):/m', $text, $match)) {
            return trim($match[1]);
        }

        // Try: first line that looks like a person's name (Gmail paste format)
        $lines = preg_split('/\n/', $text);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (mb_strlen($trimmed) < 5 || mb_strlen($trimmed) > 60) continue;
            if (preg_match('/[@<>\d:\/\\\\]/', $trimmed)) continue;
            if (preg_match('/^[\p{L}\s]+$/u', $trimmed) && str_word_count($trimmed) >= 2) {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * Normalizes a name for database search: removes accents, lowercases, collapses spaces.
     */
    private function normalizeNameForSearch(string $name): string
    {
        $name = mb_strtolower(trim($name));
        // Remove common accents
        $name = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $name
        );
        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * Parses the LLM ITIL output format directly.
     * The format is:
     *   Line 1: Title/Subject
     *   Line 2: Description
     *   Line 3: Date (or empty)
     *   Line 4: Requester name
     *   Line 5: Sub-service name
     *   Line 6: URLs (comma-separated, or empty)
     *   Line 7: Task title with "(X subtareas)"
     *   Lines 8+: Subtasks with "- action (XX min)"
     *
     * Lines are separated by blank lines between each field.
     */
    private function parseLlmItilOutput(string $llmOutput, Company $company, ?int $requestedBy): ?array
    {
        // Split by blank lines to get logical blocks
        $blocks = preg_split('/\n\s*\n/', trim($llmOutput));
        $blocks = array_values(array_filter(array_map('trim', $blocks), fn ($b) => $b !== ''));

        if (count($blocks) < 3) {
            // Fallback: split into non-empty lines
            $blocks = array_values(array_filter(
                array_map('trim', preg_split('/\n/', $llmOutput)),
                fn (string $line) => $line !== ''
            ));
        }

        if (count($blocks) < 3) {
            return null;
        }

        $knownSubServices = [
            // MinCultura
            'Ejecución de envío de comunicaciones masivas',
            'Actualización de Contenidos en Portal Principal',
            'Gestión de Secciones Especiales',
            'Desarrollo de Nuevas Funcionalidades Web',
            'Mantenimiento de Páginas y Micrositios',
            'Gestión Técnica de Aplicativos Web',
            'Ejecución de Tareas Técnicas o Digitales Específicas',
            'Coordinación de Actualizaciones por Área',
            // Movilidad
            'Publicación de Noticia, PMT o Artículo',
            'Publicación de Documento',
            'Solicitud de Edición o Ajuste de Contenido',
            'Solicitud de Diseño Gráfico',
            'Desarrollo, Configuración e Implementación Técnica',
            'Reporte de Enlace Roto o Contenido Obsoleto',
            'Asignación de Tarea No Especificada',
            'Solicitud de Desarrollo de Micrositio Web',
            'Solicitud de Creación de un Nuevo Portal Web',
            'Publicacion de Banner en el Home Principal',
            'Acompañamiento actividades desarrollo externo',
        ];

        // Parse each block by its semantic role
        $title = '';
        $description = '';
        $requesterName = '';
        $subServiceName = '';
        $dateStr = '';
        $urls = [];
        $taskTitleLine = '';
        $taskLines = [];

        foreach ($blocks as $block) {
            $blockLines = array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== '');

            // Check if this block contains task lines (starts with -)
            $hasTaskLines = false;
            foreach ($blockLines as $bl) {
                if (str_starts_with($bl, '- ') || str_starts_with($bl, '-')) {
                    $hasTaskLines = true;
                    break;
                }
            }

            if ($hasTaskLines) {
                foreach ($blockLines as $bl) {
                    if (str_starts_with($bl, '- ') || str_starts_with($bl, '-')) {
                        $taskLines[] = $bl;
                    } elseif (preg_match('/\(\d+\s*subtareas?\)/iu', $bl)) {
                        $taskTitleLine = $bl;
                    }
                }
                continue;
            }

            // Single-line block analysis
            $singleLine = implode(' ', $blockLines);

            // Check if it's a task title (contains "subtareas")
            if (preg_match('/\(\d+\s*subtareas?\)/iu', $singleLine)) {
                $taskTitleLine = $singleLine;
                continue;
            }

            // Check if it's a sub-service
            $isSubService = false;
            foreach ($knownSubServices as $ss) {
                if (mb_strtolower(trim($singleLine)) === mb_strtolower($ss)) {
                    $subServiceName = $ss;
                    $isSubService = true;
                    break;
                }
            }
            if ($isSubService) continue;

            // Check if it contains URLs
            if (preg_match('/https?:\/\//', $singleLine)) {
                preg_match_all('/https?:\/\/[^\s,]+/', $singleLine, $urlMatches);
                $urls = array_merge($urls, $urlMatches[0] ?? []);
                continue;
            }

            // Check if it's a date
            if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $singleLine) ||
                preg_match('/\d{1,2}\s+de\s+\w+\s+de\s+\d{4}/iu', $singleLine)) {
                $dateStr = $singleLine;
                continue;
            }

            // Assign to title, description, or requester based on order
            if ($title === '') {
                $title = $singleLine;
            } elseif ($description === '') {
                $description = $singleLine;
            } elseif ($requesterName === '' && preg_match('/^[\p{L}\s]+$/u', $singleLine) && mb_strlen($singleLine) <= 80) {
                $requesterName = $singleLine;
            }
        }

        if (empty($title)) {
            return null;
        }

        if (empty($description)) {
            $description = $title;
        }

        // Resolve requester
        $requesterId = $requestedBy;
        $requesterPending = false;
        if ($requesterName !== '') {
            $resolver = app(\App\Services\SmartParser\Resolvers\RequesterResolver::class);
            $resolved = $resolver->resolve((int) $company->id, $requesterName, null);
            $requesterId = $resolved['id'] ?? $requestedBy;
            $requesterPending = $resolved['pending'] ?? false;
        }

        // Resolve sub-service
        $subServiceId = null;
        $serviceId = null;
        $familyId = null;
        $slaId = null;
        if ($subServiceName !== '') {
            $subService = SubService::query()
                ->where('is_active', true)
                ->where(function ($q) use ($subServiceName) {
                    $lower = mb_strtolower($subServiceName);
                    $q->whereRaw('LOWER(name) = ?', [$lower])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]);
                })
                ->first();

            if ($subService) {
                $subServiceId = $subService->id;
                $serviceId = $subService->service_id;
                $familyId = $subService->service?->service_family_id;

                $sla = \App\Models\ServiceLevelAgreement::query()
                    ->where('is_active', true)
                    ->whereHas('serviceSubservice', function ($q) use ($subServiceId) {
                        $q->where('sub_service_id', $subServiceId);
                    })
                    ->first();
                $slaId = $sla?->id;
            }
        }

        // Parse tasks
        $tasks = $this->parseLlmTasks($taskTitleLine, $taskLines, $title);

        // Parse date
        $createdAt = now();
        if ($dateStr !== '') {
            try {
                $createdAt = \Carbon\Carbon::parse($dateStr);
            } catch (\Exception $e) {
                // Keep current date
            }
        }

        // Determine entry channel from original text
        $channel = 'email_corporativo';
        if (preg_match('/\[\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4},?\s+\d{1,2}:\d{2}/', $text ?? '') ||
            preg_match('/\d{1,2}:\d{2}\s*[ap]\.?\s*m\.?,?\s*\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $text ?? '')) {
            $channel = 'whatsapp';
        }

        // Determine criticality
        $criticality = 'MEDIA';

        // Deduplicate URLs
        $urls = array_values(array_unique($urls));

        return [
            'payload' => [
                'company_id' => (int) $company->id,
                'requester_id' => $requesterId,
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr($description, 0, 5000),
                'sub_service_id' => $subServiceId,
                'service_id' => $serviceId,
                'family_id' => $familyId,
                'sla_id' => $slaId,
                'requested_by' => $requestedBy,
                'entry_channel' => $channel,
                'criticality_level' => $criticality,
                'created_at' => $createdAt->format('Y-m-d\TH:i'),
                'due_date' => null,
                'web_routes' => json_encode(array_slice($urls, 0, 8)),
                'is_reportable' => true,
                'tasks_template' => 'none',
                'tasks' => $tasks,
                '__pending_requester_name' => $requesterPending ? $requesterName : null,
                '__pending_requester_email' => null,
            ],
            'meta' => [
                'requester_name' => $requesterName,
                'requester_created' => false,
                'requester_pending' => $requesterPending,
                'sub_service_name' => $subServiceName ?: null,
                'task_count' => count($tasks),
                'web_route_count' => count($urls),
                'confidences' => ['llm' => 90],
            ],
        ];
    }

    /**
     * Parses task lines from LLM ITIL output.
     */
    private function parseLlmTasks(string $taskTitleLine, array $taskLines, string $fallbackTitle): array
    {
        $subtasks = [];

        foreach ($taskLines as $line) {
            $cleanLine = preg_replace('/^\s*-\s*/', '', $line);
            $estimatedMinutes = 25;

            // Extract duration: (XX min), (X h), (XX minutos)
            if (preg_match('/\((\d+)\s*(?:min(?:utos?)?|m)\)/iu', $cleanLine, $match)) {
                $estimatedMinutes = max(5, min(480, (int) $match[1]));
                $cleanLine = trim(preg_replace('/\(\d+\s*(?:min(?:utos?)?|m)\)/iu', '', $cleanLine));
            } elseif (preg_match('/\((\d+)\s*(?:horas?|hrs?|h)\)/iu', $cleanLine, $match)) {
                $estimatedMinutes = max(5, min(480, (int) $match[1] * 60));
                $cleanLine = trim(preg_replace('/\(\d+\s*(?:horas?|hrs?|h)\)/iu', '', $cleanLine));
            }

            if (mb_strlen($cleanLine) >= 3 && mb_strlen($cleanLine) <= 255) {
                $subtasks[] = [
                    'title' => $cleanLine,
                    'priority' => 'medium',
                    'estimated_minutes' => $estimatedMinutes,
                ];
            }
        }

        $taskTitle = $taskTitleLine ?: $fallbackTitle;
        // Remove the "(X subtareas)" suffix from task title
        $taskTitle = trim(preg_replace('/\(\d+\s*subtareas?\)/iu', '', $taskTitle));

        $totalMinutes = array_sum(array_column($subtasks, 'estimated_minutes'));

        return [
            [
                'title' => mb_substr($taskTitle, 0, 255),
                'description' => null,
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_minutes' => $totalMinutes ?: 25,
                'estimated_hours' => $totalMinutes > 0 ? round($totalMinutes / 60, 2) : 0.42,
                'subtasks' => array_slice($subtasks, 0, 20),
            ],
        ];
    }

    /**
     * Extracts URLs from a line of text.
     */
    private function extractUrlsFromLine(string $line): array
    {
        preg_match_all('/https?:\/\/[^\s,]+/', $line, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Procesa texto en formato libre usando el SmartParserPipeline.
     *
     * @return array{payload: array, meta: array}
     *
     * @throws \App\Services\SmartParser\Exceptions\ParsingTimeoutException
     */
    private function parseWithSmartPipeline(string $text, int $companyId, ?int $requestedBy): array
    {
        $startTime = microtime(true);

        $parsedResult = $this->smartPipeline->parse($text, $companyId);

        $elapsed = microtime(true) - $startTime;
        if ($elapsed > self::PIPELINE_TIMEOUT_SECONDS) {
            throw new \App\Services\SmartParser\Exceptions\ParsingTimeoutException(self::PIPELINE_TIMEOUT_SECONDS);
        }

        return $parsedResult->toPayload($companyId, $requestedBy);
    }

    private function resolveBestSubService(array $parsed, int $contractId, string $plainText): ?SubService
    {
        $candidates = [];

        $pushCandidate = function (?string $candidate) use (&$candidates): void {
            $clean = trim((string) $candidate);
            if ($clean === '' || $this->isUnavailableMarker($clean)) {
                return;
            }

            $candidates[] = $clean;
        };

        $pushCandidate($parsed['sub_service_name'] ?? null);
        $pushCandidate($parsed['title'] ?? null);
        $pushCandidate($parsed['description'] ?? null);
        $pushCandidate(trim(((string) ($parsed['title'] ?? '')) . ' ' . ((string) ($parsed['description'] ?? ''))));
        $pushCandidate($this->inferFallbackSubServiceName($plainText, $parsed));
        $pushCandidate('Reporte de Enlace Roto o Contenido Obsoleto');
        $pushCandidate('Solicitud de Apoyo General');
        $pushCandidate('Acompañamiento actividades desarrollo externo');
        $pushCandidate('Actualización de Sección de Transparencia');

        foreach (array_values(array_unique($candidates)) as $candidate) {
            $subService = $this->resolveSubService($candidate, $contractId);
            if ($subService) {
                return $subService;
            }
        }

        return null;
    }

    private function inferFallbackSubServiceName(string $plainText, array $parsed): ?string
    {
        $haystack = $this->normalizeForComparison(
            trim(implode(' ', [
                (string) ($parsed['title'] ?? ''),
                (string) ($parsed['description'] ?? ''),
                $plainText,
            ]))
        );

        if ($haystack === '') {
            return null;
        }

        $keywordMap = [
            'Reporte de Enlace Roto o Contenido Obsoleto' => [
                'enlace roto',
                'enlace anterior',
                'portal antiguo',
                'repositorio',
                'contenido obsoleto',
                'acceso al repositorio',
            ],
            'Actualización de Sección de Transparencia' => [
                'transparencia',
                'acceso a la informacion',
                'ley de transparencia',
                'mipg',
            ],
            'Acompañamiento actividades desarrollo externo' => [
                'terceros',
                'externo',
                'desarrollo externo',
                'acompanamiento',
            ],
            'Solicitud de Apoyo General' => [
                'reunion',
                'presentacion',
                'presentación',
                'convoc',
                'coordinacion',
                'coordinación',
                'apoyo',
                'mesa de trabajo',
                'socializacion',
                'socialización',
                'participacion',
                'participación',
            ],
        ];

        foreach ($keywordMap as $candidate => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $this->normalizeForComparison($keyword))) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function extractStructuredData(string $plainText): array
    {
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $plainText);

        $exactParsed = $this->extractStructuredDataByExactFormat($normalizedText);
        if ($exactParsed !== null) {
            return $exactParsed;
        }

        $emailThreadParsed = $this->extractStructuredDataFromEmailThread($normalizedText);
        if ($emailThreadParsed !== null) {
            return $emailThreadParsed;
        }

        $lines = $this->extractLines($normalizedText);
        $blocks = $this->extractBlocks($normalizedText);

        $createdAt = null;
        foreach ($lines as $line) {
            $createdAt = $this->parseSpanishDateTime($line);
            if ($createdAt) {
                break;
            }
        }

        $templateParsed = $this->extractStructuredDataByTemplate($normalizedText, $blocks, $createdAt);
        if ($templateParsed['requester_name'] !== '' && $templateParsed['sub_service_name'] !== '') {
            return $templateParsed;
        }

        return $this->extractStructuredDataByHeuristics($normalizedText, $blocks, $createdAt);
    }

    private function extractStructuredDataByExactFormat(string $normalizedText): ?array
    {
        $lines = array_values(array_filter(
            array_map(
                fn ($line) => trim($this->normalizeMarkdownLinks((string) $line)),
                $this->extractExactLines($normalizedText)
            ),
            fn (string $line) => $line !== ''
        ));

        if (!$this->looksLikeExactStructuredFormat($lines)) {
            return null;
        }

        $subject = $this->normalizeUnavailableLine($this->cleanSubject((string) ($lines[0] ?? '')));
        $description = $this->normalizeUnavailableLine(trim((string) ($lines[1] ?? '')));
        $createdAt = $this->parseFlexibleDate((string) ($lines[2] ?? ''));
        $dueDate = $this->parseFlexibleDate((string) ($lines[3] ?? ''));
        $requesterName = $this->normalizeUnavailableLine($this->cleanPersonLine((string) ($lines[4] ?? '')));
        $entryChannel = trim((string) ($lines[5] ?? ''));
        $subServiceName = Str::limit($this->normalizeUnavailableLine(trim((string) ($lines[6] ?? ''))), 255, '');
        $linksLine = $this->normalizeUnavailableLine(trim((string) ($lines[7] ?? '')));
        $criticalityLevel = trim((string) ($lines[8] ?? ''));
        $taskTitle = $this->normalizeUnavailableLine($this->cleanTaskTitle((string) ($lines[9] ?? '')));

        // El bloque de acciones empieza después del título de actividad y puede contener varias viñetas.
        $taskBlock = trim(implode("\n", array_slice($lines, 9)));
        $tasks = $this->extractTasksFromBlocks([$taskBlock], 0, $taskTitle, $subject);

        return [
            'title' => $subject !== '' && !$this->isUnavailableMarker($subject)
                ? $subject
                : ($taskTitle !== '' && !$this->isUnavailableMarker($taskTitle)
                    ? $taskTitle
                    : Str::limit($description !== '' ? $description : 'Nueva solicitud', 255, '')),
            'description' => $description,
            'created_at' => $createdAt,
            'due_date' => $dueDate?->format('Y-m-d'),
            'requester_name' => $requesterName,
            'requester_email' => $this->extractEmail($normalizedText),
            'sub_service_name' => $subServiceName,
            'entry_channel' => $this->normalizeEntryChannelLine($entryChannel, $normalizedText),
            'criticality_level' => $this->normalizeCriticalityLine($criticalityLevel, $normalizedText),
            'web_routes' => $linksLine !== '' && !$this->isUnavailableMarker($linksLine)
                ? collect(preg_split('/\s*,\s*/u', $linksLine) ?: [])
                    ->flatMap(fn (string $part) => $this->extractUrls($part))
                    ->unique()
                    ->slice(0, 8)
                    ->values()
                    ->all()
                : [],
            'tasks' => $tasks,
        ];
    }

    private function extractStructuredDataByTemplate(string $normalizedText, array $blocks, ?Carbon $createdAt): array
    {
        if (count($blocks) < 4) {
            return $this->emptyParsedData($createdAt);
        }

        $subject = $this->cleanSubject($blocks[0] ?? '');
        $description = trim($blocks[1] ?? '');

        $cursor = 2;
        $templateCreatedAt = $createdAt;
        if (isset($blocks[$cursor])) {
            $blockDate = $this->parseFlexibleDate($blocks[$cursor]);
            if ($blockDate) {
                $templateCreatedAt = $blockDate;
                $cursor++;
            }
        }

        // Saltar bloques "No disponible" antes del solicitante (ej: fecha de vencimiento)
        if (isset($blocks[$cursor]) && $this->isUnavailableMarker(trim($blocks[$cursor]))) {
            $cursor++;
        }

        $requesterName = isset($blocks[$cursor]) ? $this->cleanPersonLine($blocks[$cursor]) : '';
        // Si el solicitante es "No disponible", dejarlo vacío
        if ($this->isUnavailableMarker($requesterName)) {
            $requesterName = '';
        }
        $cursor++;

        $subServiceName = isset($blocks[$cursor]) ? Str::limit(trim($blocks[$cursor]), 255, '') : '';
        $cursor++;

        $taskBlockIndex = null;
        for ($i = $cursor; $i < count($blocks); $i++) {
            if ($this->blockContainsTaskTitle($blocks[$i])) {
                $taskBlockIndex = $i;
                break;
            }
        }

        $taskTitle = $taskBlockIndex !== null ? $this->extractTaskTitleFromBlock($blocks[$taskBlockIndex]) : '';
        $tasks = $this->extractTasksFromBlocks($blocks, $taskBlockIndex, $taskTitle, $subject);

        return [
            'title' => $subject !== '' ? $subject : ($taskTitle !== '' ? $taskTitle : Str::limit($description ?: 'Nueva solicitud', 255, '')),
            'description' => $description,
            'created_at' => $templateCreatedAt,
            'requester_name' => $requesterName,
            'requester_email' => $this->extractEmail($normalizedText),
            'sub_service_name' => $subServiceName,
            'entry_channel' => $this->detectEntryChannel($normalizedText),
            'criticality_level' => $this->detectCriticality($normalizedText),
            'web_routes' => $this->extractUrls($normalizedText)->slice(0, 8)->values()->all(),
            'tasks' => $tasks,
        ];
    }

    private function extractStructuredDataByHeuristics(string $normalizedText, array $blocks, ?Carbon $createdAt): array
    {
        $subjectIndex = null;
        if ($blocks !== [] && $this->looksLikeSubjectBlock($blocks[0])) {
            $subjectIndex = 0;
        }

        $taskBlockIndex = null;
        foreach ($blocks as $index => $block) {
            if ($this->blockContainsTaskTitle($block)) {
                $taskBlockIndex = $index;
                break;
            }
        }

        $descriptionBlocks = [];
        $descriptionStart = $subjectIndex !== null ? 1 : 0;
        $metadataStartIndex = $descriptionStart;

        for ($i = $descriptionStart; $i < count($blocks); $i++) {
            $block = $blocks[$i];

            if ($this->looksLikeUrlBlock($block) || $this->parseSpanishDateTime($block) || $this->blockContainsTaskTitle($block)) {
                $metadataStartIndex = $i;
                break;
            }

            if ($descriptionBlocks !== [] && $this->looksLikeMetadataBlock($block)) {
                $metadataStartIndex = $i;
                break;
            }

            $descriptionBlocks[] = $block;
            $metadataStartIndex = $i + 1;
        }

        $metadataBlocks = [];
        $metadataEnd = $taskBlockIndex ?? count($blocks);

        for ($i = $metadataStartIndex; $i < $metadataEnd; $i++) {
            $block = $blocks[$i];
            if ($this->looksLikeUrlBlock($block)) {
                continue;
            }

            $metadataBlocks[] = $block;
        }

        $requesterOffset = 0;
        if (isset($metadataBlocks[0]) && $this->parseSpanishDateTime($metadataBlocks[0])) {
            $requesterOffset = 1;
        }

        $subject = $subjectIndex !== null ? $this->cleanSubject($blocks[$subjectIndex]) : '';
        $requesterName = isset($metadataBlocks[$requesterOffset])
            ? $this->cleanPersonLine($metadataBlocks[$requesterOffset])
            : '';
        $requesterEmail = $this->extractEmail($normalizedText);
        $subServiceName = isset($metadataBlocks[$requesterOffset + 1])
            ? Str::limit(trim($metadataBlocks[$requesterOffset + 1]), 255, '')
            : '';
        $taskTitle = $taskBlockIndex !== null ? $this->extractTaskTitleFromBlock($blocks[$taskBlockIndex]) : '';
        $webRoutes = $this->extractUrls($normalizedText)->slice(0, 8)->values()->all();
        $tasks = $this->extractTasksFromBlocks($blocks, $taskBlockIndex, $taskTitle, $subject);

        return [
            'title' => $subject !== '' ? $subject : ($taskTitle !== '' ? $taskTitle : Str::limit($descriptionBlocks[0] ?? 'Nueva solicitud', 255, '')),
            'description' => trim(implode("\n\n", $descriptionBlocks)),
            'created_at' => $createdAt,
            'requester_name' => $requesterName,
            'requester_email' => $requesterEmail,
            'sub_service_name' => $subServiceName,
            'entry_channel' => $this->detectEntryChannel($normalizedText),
            'criticality_level' => $this->detectCriticality($normalizedText),
            'web_routes' => $webRoutes,
            'tasks' => $tasks,
        ];
    }

    private function extractStructuredDataFromEmailThread(string $normalizedText): ?array
    {
        $lines = array_map(
            fn ($line) => trim($this->normalizeMarkdownLinks((string) $line)),
            preg_split('/\n/u', $normalizedText) ?: []
        );

        $lines = $this->stripWebmailChromeLines($lines);
        if ($lines === []) {
            return null;
        }

        foreach ($lines as $index => $line) {
            if ($this->isQuotedReplyMarker($line)) {
                $lines = array_slice($lines, 0, $index);
                break;
            }
        }

        $subjectIndex = $this->findFirstLineIndex(
            $lines,
            fn (string $line) => preg_match('/^(re|rv|fw|fwd)\s*:/iu', $line) === 1
        );

        if ($subjectIndex === null) {
            return null;
        }

        $senderNameIndex = $this->nextNonEmptyLineIndex($lines, $subjectIndex + 1);
        $dateIndex = $senderNameIndex !== null ? $this->nextNonEmptyLineIndex($lines, $senderNameIndex + 1) : null;
        $recipientsIndex = $dateIndex !== null ? $this->nextNonEmptyLineIndex($lines, $dateIndex + 1) : null;

        if ($senderNameIndex === null || $dateIndex === null || $recipientsIndex === null) {
            return null;
        }

        $requesterName = $this->cleanPersonLine((string) ($lines[$senderNameIndex] ?? ''));
        if ($requesterName === '') {
            return null;
        }

        $recipientsLine = (string) ($lines[$recipientsIndex] ?? '');
        if (!preg_match('/\b(?:para|to|cc|bcc)\b/iu', $recipientsLine)) {
            return null;
        }

        $createdAt = $this->parseEmailHeaderDate((string) ($lines[$dateIndex] ?? ''))
            ?? $this->parseFlexibleDate((string) ($lines[$dateIndex] ?? ''));

        $bodyStartIndex = $this->nextNonEmptyLineIndex($lines, $recipientsIndex + 1);
        if ($bodyStartIndex === null) {
            return null;
        }

        $bodyLines = $this->trimEmptyEdges(array_slice($lines, $bodyStartIndex));
        $bodyText = trim(implode("\n", $bodyLines));
        if ($bodyText === '') {
            return null;
        }

        $subject = $this->cleanSubject((string) ($lines[$subjectIndex] ?? ''));
        $searchText = trim(implode("\n\n", array_filter([$subject, $bodyText])));
        $parsed = [
            'title' => $subject !== '' ? $subject : Str::limit($bodyText, 255, ''),
            'description' => $bodyText,
            'created_at' => $createdAt,
            'requester_name' => $requesterName,
            'requester_email' => $this->extractEmail(
                implode("\n", array_filter([
                    (string) ($lines[$senderNameIndex] ?? ''),
                    (string) ($lines[$dateIndex] ?? ''),
                    (string) ($lines[$recipientsIndex] ?? ''),
                ]))
            ),
            'sub_service_name' => '',
            'entry_channel' => $this->detectEntryChannel($searchText),
            'criticality_level' => $this->detectCriticality($searchText),
            'web_routes' => $this->extractUrls($bodyText)->slice(0, 8)->values()->all(),
            'tasks' => [],
        ];

        $parsed['sub_service_name'] = $this->inferFallbackSubServiceName($searchText, $parsed)
            ?? 'Solicitud de Apoyo General';

        return $parsed;
    }

    private function emptyParsedData(?Carbon $createdAt): array
    {
        return [
            'title' => '',
            'description' => '',
            'created_at' => $createdAt,
            'requester_name' => '',
            'requester_email' => null,
            'sub_service_name' => '',
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'web_routes' => [],
            'tasks' => [],
        ];
    }

    private function resolveRequester(int $companyId, string $name, ?string $email): array
    {
        $normalizedName = $this->normalizeForComparison($name);

        $existing = Requester::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get(['id', 'name', 'email'])
            ->first(function (Requester $requester) use ($normalizedName, $email) {
                if ($email && mb_strtolower((string) $requester->email) === mb_strtolower($email)) {
                    return true;
                }

                return $this->normalizeForComparison($requester->name) === $normalizedName;
            });

        if ($existing) {
            return [
                'id' => (int) $existing->id,
                'name' => $existing->name,
                'created' => false,
            ];
        }

        // No crear automáticamente — devolver datos pendientes para que el usuario confirme
        return [
            'id' => null,
            'name' => $name,
            'created' => false,
            'pending' => true,
            'email' => $email,
        ];
    }

    private function resolveSubService(string $candidate, int $contractId): ?SubService
    {
        $normalizedCandidate = $this->normalizeForComparison($candidate);
        if ($normalizedCandidate === '') {
            return null;
        }

        /** @var Collection<int, SubService> $subServices */
        $subServices = SubService::query()
            ->active()
            ->with(['service.family'])
            ->whereHas('service.family', function ($query) use ($contractId) {
                $query->where('contract_id', $contractId)
                    ->where('is_active', true);
            })
            ->get();

        $match = $subServices
            ->map(function (SubService $subService) use ($normalizedCandidate) {
                $parts = array_filter([
                    $subService->name,
                    $subService->code,
                    $subService->service?->name,
                    $subService->service?->family?->name,
                ]);

                $searchSpace = $this->normalizeForComparison(implode(' ', $parts));
                $nameOnly = $this->normalizeForComparison($subService->name);
                similar_text($normalizedCandidate, $nameOnly, $namePercent);
                similar_text($normalizedCandidate, $searchSpace, $fullPercent);

                $score = max($namePercent, $fullPercent);
                if ($nameOnly === $normalizedCandidate) {
                    $score = 100.0;
                } elseif (str_contains($nameOnly, $normalizedCandidate) || str_contains($normalizedCandidate, $nameOnly)) {
                    $score = max($score, 92.0);
                } elseif (str_contains($searchSpace, $normalizedCandidate)) {
                    $score = max($score, 88.0);
                }

                return [
                    'sub_service' => $subService,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->first(fn (array $item) => $item['score'] >= 55.0);

        return $match['sub_service'] ?? null;
    }

    private function extractTasksFromBlocks(array $blocks, ?int $taskBlockIndex, string $taskTitle, string $fallbackTitle): array
    {
        if ($taskBlockIndex === null) {
            return [];
        }

        $lines = [];
        foreach (array_slice($blocks, $taskBlockIndex) as $block) {
            array_push($lines, ...$this->splitBlockLines($block));
        }

        $taskTitleIndex = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/\bsubtareas?\b/iu', $line)) {
                $taskTitleIndex = $index;
                break;
            }
        }

        $subtasks = [];
        if ($taskTitleIndex !== null) {
            for ($i = $taskTitleIndex + 1; $i < count($lines); $i++) {
                $parsed = $this->parseTaskLine($lines[$i]);
                if ($parsed) {
                    $subtasks[] = $parsed;
                }
            }
        }

        if ($taskTitle === '' && $subtasks === []) {
            return [];
        }

        $estimatedMinutes = array_sum(array_map(
            fn (array $subtask) => (int) ($subtask['estimated_minutes'] ?? 0),
            $subtasks
        ));

        return [[
            'title' => Str::limit($taskTitle !== '' ? $taskTitle : $fallbackTitle, 255, ''),
            'type' => 'regular',
            'priority' => 'medium',
            'estimated_minutes' => $estimatedMinutes > 0 ? $estimatedMinutes : 30,
            'subtasks' => $subtasks,
        ]];
    }

    private function parseTaskLine(string $line): ?array
    {
        if ($this->looksLikeUrl($line) || $this->parseSpanishDateTime($line)) {
            return null;
        }

        $isBullet = $this->looksLikeBullet($line);
        if (!$isBullet && $this->extractDurationMinutes($line) <= 0) {
            return null;
        }

        $clean = trim(preg_replace('/^(?:[-*•]\s+)/u', '', $line) ?? $line);
        if ($clean === '') {
            return null;
        }

        $minutes = $this->extractDurationMinutes($clean);
        $title = trim(preg_replace('/\s*\((?:[^()]*)\)\s*$/u', '', $clean) ?? $clean);

        return [
            'title' => Str::limit($title, 400, ''),
            'priority' => 'medium',
            'estimated_minutes' => $minutes > 0 ? $minutes : 25,
        ];
    }

    private function extractDurationMinutes(string $text): int
    {
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(min|minutos?|h|hr|hrs|hora|horas)\b/iu', $text, $matches)) {
            return 0;
        }

        $value = (float) str_replace(',', '.', $matches[1]);
        $unit = mb_strtolower($matches[2]);

        if (str_starts_with($unit, 'h')) {
            return (int) round($value * 60);
        }

        return (int) round($value);
    }

    private function parseSpanishDateTime(string $text): ?Carbon
    {
        if (!preg_match('/(\d{1,2})\s+de\s+([[:alpha:]áéíóúñ]+)\s+de\s+(\d{4})(?:\s+(\d{1,2}):(\d{2})\s*([ap])\.?\s*m\.?)?/iu', $text, $matches)) {
            return null;
        }

        $months = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
        ];

        $monthName = $this->normalizeForComparison($matches[2]);
        $month = $months[$monthName] ?? null;
        if (!$month) {
            return null;
        }

        $hour = isset($matches[4]) ? (int) $matches[4] : 0;
        $minute = isset($matches[5]) ? (int) $matches[5] : 0;
        $meridiem = isset($matches[6]) ? mb_strtolower($matches[6]) : null;

        if ($meridiem === 'p' && $hour < 12) {
            $hour += 12;
        }
        if ($meridiem === 'a' && $hour === 12) {
            $hour = 0;
        }

        return Carbon::create(
            (int) $matches[3],
            $month,
            (int) $matches[1],
            $hour,
            $minute,
            0,
            config('app.timezone')
        );
    }

    private function parseFlexibleDate(string $text): ?Carbon
    {
        $clean = trim($text);
        if ($clean === '' || $this->isUnavailableMarker($clean)) {
            return null;
        }

        $parsed = $this->parseSpanishDateTime($clean);
        if ($parsed) {
            return $parsed;
        }

        // Formato corto: "16 may", "16 mayo", "16 may 2025", "16 mayo 2025"
        $shortMonths = [
            'ene' => 1, 'enero' => 1,
            'feb' => 2, 'febrero' => 2,
            'mar' => 3, 'marzo' => 3,
            'abr' => 4, 'abril' => 4,
            'may' => 5, 'mayo' => 5,
            'jun' => 6, 'junio' => 6,
            'jul' => 7, 'julio' => 7,
            'ago' => 8, 'agosto' => 8,
            'sep' => 9, 'sept' => 9, 'septiembre' => 9, 'setiembre' => 9,
            'oct' => 10, 'octubre' => 10,
            'nov' => 11, 'noviembre' => 11,
            'dic' => 12, 'diciembre' => 12,
        ];

        if (preg_match('/^(\d{1,2})\s+([[:alpha:]áéíóúñ]+)(?:\s+(\d{4}))?$/iu', $clean, $shortMatch)) {
            $monthKey = $this->normalizeForComparison($shortMatch[2]);
            $month = $shortMonths[$monthKey] ?? null;
            if ($month) {
                $year = !empty($shortMatch[3]) ? (int) $shortMatch[3] : (int) now()->year;
                return Carbon::create(
                    $year,
                    $month,
                    (int) $shortMatch[1],
                    0, 0, 0,
                    config('app.timezone')
                );
            }
        }

        if (preg_match(
            '/^(?:[[:alpha:]áéíóúñ]{2,}\s+)?(\d{1,2})\/(\d{1,2})\/(\d{4})(?:[,\s]+(\d{1,2}):(\d{2})(?:\s*([ap])\.?\s*m\.?)?)?$/iu',
            $clean,
            $matches
        )) {
            $hour = isset($matches[4]) ? (int) $matches[4] : 0;
            $minute = isset($matches[5]) ? (int) $matches[5] : 0;
            $meridiem = isset($matches[6]) ? mb_strtolower($matches[6]) : null;

            if ($meridiem === 'p' && $hour < 12) {
                $hour += 12;
            }
            if ($meridiem === 'a' && $hour === 12) {
                $hour = 0;
            }

            return Carbon::create(
                (int) $matches[3],
                (int) $matches[2],
                (int) $matches[1],
                $hour,
                $minute,
                0,
                config('app.timezone')
            );
        }

        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                $carbon = Carbon::createFromFormat($format, $clean, config('app.timezone'));
                if ($carbon !== false) {
                    return $carbon;
                }
            } catch (\Throwable) {
                // Intentar el siguiente formato.
            }
        }

        return null;
    }

    private function isUnavailableMarker(string $text): bool
    {
        return $this->normalizeForComparison($text) === 'no disponible';
    }

    private function normalizeUnavailableLine(string $text): string
    {
        $clean = trim($text);
        return $this->isUnavailableMarker($clean) ? 'No disponible' : $clean;
    }

    private function normalizeEntryChannelLine(string $text, string $fallbackText = ''): string
    {
        $clean = trim($text);
        if ($clean === '' || $this->isUnavailableMarker($clean)) {
            return $this->detectEntryChannel($fallbackText);
        }

        $normalized = $this->normalizeForComparison($clean);
        if (str_contains($normalized, 'reunion')) {
            return 'reunion';
        }
        if (str_contains($normalized, 'whatsapp') || str_contains($normalized, 'wasap')) {
            return 'whatsapp';
        }
        if (str_contains($normalized, 'telefono') || str_contains($normalized, 'llamada') || str_contains($normalized, 'telefon')) {
            return 'telefono';
        }
        if (str_contains($normalized, 'memorando') || str_contains($normalized, 'digital')) {
            return 'email_digital';
        }
        if (str_contains($normalized, 'correo') || str_contains($normalized, 'email') || str_contains($normalized, 'corporativo')) {
            return 'email_corporativo';
        }

        return $this->detectEntryChannel($clean ?: $fallbackText);
    }

    private function normalizeCriticalityLine(string $text, string $fallbackText = ''): string
    {
        $clean = trim($text);
        if ($clean === '' || $this->isUnavailableMarker($clean)) {
            return $this->detectCriticality($fallbackText);
        }

        $normalized = $this->normalizeForComparison($clean);
        if (str_contains($normalized, 'critica')) {
            return 'CRITICA';
        }
        if (str_contains($normalized, 'urgente')) {
            return 'URGENTE';
        }
        if (str_contains($normalized, 'alta')) {
            return 'ALTA';
        }
        if (str_contains($normalized, 'baja')) {
            return 'BAJA';
        }

        return 'MEDIA';
    }

    private function extractUrls(string $text): Collection
    {
        preg_match_all('/https?:\/\/[^\s)\]]+/iu', $this->normalizeMarkdownLinks($text), $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->unique()
            ->values();
    }

    private function stripWebmailChromeLines(array $lines): array
    {
        $filtered = [];
        $started = false;

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);

            if (!$started) {
                if ($trimmed === '' || $this->isWebmailChromeLine($trimmed)) {
                    continue;
                }

                $started = true;
            }

            if ($this->isWebmailChromeLine($trimmed)) {
                continue;
            }

            $filtered[] = $trimmed;
        }

        return $this->trimEmptyEdges($filtered);
    }

    private function trimEmptyEdges(array $lines): array
    {
        while ($lines !== [] && trim((string) $lines[0]) === '') {
            array_shift($lines);
        }

        while ($lines !== [] && trim((string) $lines[array_key_last($lines)]) === '') {
            array_pop($lines);
        }

        return array_values($lines);
    }

    private function isWebmailChromeLine(string $line): bool
    {
        $normalized = $this->normalizeForComparison($line);

        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, [
            'ninguno seleccionado',
            'ir al contenido',
            'recibidos',
            'vista creada con ia',
        ], true)) {
            return true;
        }

        if (preg_match('/^\d+\s+de\s+[\d.,]+$/u', $normalized) === 1) {
            return true;
        }

        return str_contains($normalized, 'lectores de pantalla')
            || str_contains($normalized, 'correo de bogota')
            || str_contains($normalized, 'correo de bogotá');
    }

    private function isQuotedReplyMarker(string $line): bool
    {
        $normalized = $this->normalizeForComparison($line);

        if ($normalized === '') {
            return false;
        }

        return preg_match('/^(?:el\s+)?(?:lun|mar|mie|mi[eé]|jue|vie|s[áa]b|sab|dom).+escribi[oó]\s*:?\s*$/iu', $line) === 1
            || preg_match('/^on\s+.+\s+wrote$/iu', $normalized) === 1
            || preg_match('/^(?:from|sent|subject)\s*:/iu', $line) === 1
            || preg_match('/^-{2,}\s*original message\s*-{2,}$/iu', $normalized) === 1;
    }

    private function nextNonEmptyLineIndex(array $lines, int $startIndex): ?int
    {
        for ($i = $startIndex; $i < count($lines); $i++) {
            if (trim((string) ($lines[$i] ?? '')) !== '') {
                return $i;
            }
        }

        return null;
    }

    private function findFirstLineIndex(array $lines, callable $predicate, int $startIndex = 0): ?int
    {
        for ($i = $startIndex; $i < count($lines); $i++) {
            if ($predicate((string) ($lines[$i] ?? ''))) {
                return $i;
            }
        }

        return null;
    }

    private function parseEmailHeaderDate(string $text): ?Carbon
    {
        $clean = trim($text);
        if ($clean === '') {
            return null;
        }

        if (!preg_match('/^(?:[[:alpha:]áéíóúñ]{3,},?\s*)?(\d{1,2})\s+([[:alpha:]áéíóúñ]{3,})(?:\s+de\s+(\d{4}))?(?:,\s*|\s+)(?:a\s+las\s+)?(\d{1,2}):(\d{2})(?:\s*([ap])\.?\s*m\.?)?(?:\s*\(.*\))?$/iu', $clean, $matches)) {
            return null;
        }

        $monthName = $this->normalizeForComparison($matches[2]);
        $months = [
            'ene' => 1,
            'enero' => 1,
            'feb' => 2,
            'febrero' => 2,
            'mar' => 3,
            'marzo' => 3,
            'abr' => 4,
            'abril' => 4,
            'may' => 5,
            'mayo' => 5,
            'jun' => 6,
            'junio' => 6,
            'jul' => 7,
            'julio' => 7,
            'ago' => 8,
            'agosto' => 8,
            'sep' => 9,
            'sept' => 9,
            'septiembre' => 9,
            'setiembre' => 9,
            'oct' => 10,
            'octubre' => 10,
            'nov' => 11,
            'noviembre' => 11,
            'dic' => 12,
            'diciembre' => 12,
        ];

        $month = $months[$monthName] ?? $months[substr($monthName, 0, 3)] ?? null;
        if (!$month) {
            return null;
        }

        $year = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : (int) now()->year;
        $hour = (int) $matches[4];
        $minute = (int) $matches[5];
        $meridiem = isset($matches[6]) ? mb_strtolower($matches[6]) : null;

        if ($meridiem === 'p' && $hour < 12) {
            $hour += 12;
        }

        if ($meridiem === 'a' && $hour === 12) {
            $hour = 0;
        }

        return Carbon::create(
            $year,
            $month,
            (int) $matches[1],
            $hour,
            $minute,
            0,
            config('app.timezone')
        );
    }

    private function normalizeMarkdownLinks(string $text): string
    {
        return preg_replace('/\[(.*?)\]\((https?:\/\/[^\s)]+)\)/iu', '$2', $text) ?? $text;
    }

    private function extractLines(string $text): array
    {
        return array_values(array_filter(array_map(
            fn ($line) => trim($this->normalizeMarkdownLinks($line)),
            preg_split('/\n+/', $text) ?: []
        )));
    }

    private function extractExactLines(string $text): array
    {
        return array_map(
            fn ($line) => trim($this->normalizeMarkdownLinks($line)),
            preg_split('/\n/u', $text) ?: []
        );
    }

    private function extractBlocks(string $text): array
    {
        return array_values(array_filter(array_map(
            fn ($block) => trim($this->normalizeMarkdownLinks($block)),
            preg_split('/\n\s*\n+/u', $text) ?: []
        )));
    }

    private function splitBlockLines(string $block): array
    {
        return array_values(array_filter(array_map(
            fn ($line) => trim($line),
            preg_split('/\n+/', $block) ?: []
        )));
    }

    private function looksLikeExactStructuredFormat(array $lines): bool
    {
        if (count($lines) < 10) {
            return false;
        }

        $line2 = trim((string) ($lines[2] ?? ''));
        if (!$this->isUnavailableMarker($line2) && $this->parseFlexibleDate($line2) === null) {
            return false;
        }

        $line3 = trim((string) ($lines[3] ?? ''));
        if (!$this->isUnavailableMarker($line3) && $this->parseFlexibleDate($line3) === null) {
            return false;
        }

        if (trim((string) ($lines[5] ?? '')) === '') {
            return false;
        }

        if (trim((string) ($lines[6] ?? '')) === '') {
            return false;
        }

        if (trim((string) ($lines[8] ?? '')) === '') {
            return false;
        }

        return preg_match('/\bsubtareas?\b/iu', (string) ($lines[9] ?? '')) === 1;
    }

    private function looksLikeSubjectBlock(string $block): bool
    {
        $lines = $this->splitBlockLines($block);
        if ($lines === []) {
            return false;
        }

        $firstLine = $lines[0];
        if ($this->looksLikeUrl($firstLine) || $this->looksLikeBullet($firstLine) || $this->parseSpanishDateTime($firstLine)) {
            return false;
        }

        if ($this->blockContainsTaskTitle($block)) {
            return false;
        }

        if (preg_match('/^(re|rv|fw|fwd)\s*:/iu', $firstLine) === 1) {
            return true;
        }

        return count($lines) === 1 && mb_strlen($firstLine) <= 180;
    }

    private function looksLikeMetadataBlock(string $block): bool
    {
        $lines = $this->splitBlockLines($block);
        if (count($lines) !== 1) {
            return false;
        }

        $line = $lines[0];
        if ($this->looksLikeUrl($line) || $this->parseSpanishDateTime($line) || $this->blockContainsTaskTitle($block)) {
            return false;
        }

        return mb_strlen($line) <= 180 && !preg_match('/[.!?]\s*$/u', $line);
    }

    private function looksLikeUrlBlock(string $block): bool
    {
        $lines = $this->splitBlockLines($block);

        return $lines !== [] && collect($lines)->every(fn (string $line) => $this->looksLikeUrl($line));
    }

    private function blockContainsTaskTitle(string $block): bool
    {
        foreach ($this->splitBlockLines($block) as $line) {
            if (preg_match('/\bsubtareas?\b/iu', $line)) {
                return true;
            }
        }

        return false;
    }

    private function extractTaskTitleFromBlock(string $block): string
    {
        foreach ($this->splitBlockLines($block) as $line) {
            if (preg_match('/\bsubtareas?\b/iu', $line)) {
                return $this->cleanTaskTitle($line);
            }
        }

        return '';
    }

    private function cleanSubject(string $line): string
    {
        $clean = preg_replace('/^(re|rv|fw|fwd)\s*:\s*/iu', '', trim($line)) ?? trim($line);

        return Str::limit($clean, 255, '');
    }

    private function cleanPersonLine(string $line): string
    {
        $withoutEmail = preg_replace('/<[^>]+>|\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu', '', $line) ?? $line;

        return trim($withoutEmail, " \t\n\r\0\x0B-");
    }

    private function cleanTaskTitle(string $line): string
    {
        $clean = trim(preg_replace('/\(\d+\s+subtareas?\)\s*$/iu', '', trim($line)) ?? trim($line));

        return Str::limit($clean, 255, '');
    }

    private function detectEntryChannel(string $text): string
    {
        $normalized = $this->normalizeForComparison($text);

        if (str_contains($normalized, 'whatsapp')) {
            return 'whatsapp';
        }

        if (str_contains($normalized, 'telefono') || str_contains($normalized, 'llamada')) {
            return 'telefono';
        }

        if (str_contains($normalized, 'reunion')) {
            return 'reunion';
        }

        return 'email_corporativo';
    }

    private function detectCriticality(string $text): string
    {
        $normalized = $this->normalizeForComparison($text);

        if (str_contains($normalized, 'critica') || str_contains($normalized, 'critico')) {
            return 'CRITICA';
        }

        if (str_contains($normalized, 'urgente')) {
            return 'URGENTE';
        }

        if (preg_match('/\balta\b/u', $normalized)) {
            return 'ALTA';
        }

        if (preg_match('/\bbaja\b/u', $normalized)) {
            return 'BAJA';
        }

        return 'MEDIA';
    }

    private function extractEmail(string $text): ?string
    {
        preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu', $text, $matches);

        return isset($matches[0]) ? mb_strtolower(trim($matches[0])) : null;
    }

    private function looksLikeUrl(string $line): bool
    {
        return preg_match('/^https?:\/\//iu', trim($line)) === 1;
    }

    private function looksLikeBullet(string $line): bool
    {
        return preg_match('/^(?:[-*•]\s+)/u', trim($line)) === 1;
    }

    private function normalizeForComparison(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();
    }
}
