<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\SubService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceRequestPlainTextImportService
{
    public function __construct(
        private readonly ServiceRequestService $serviceRequestService,
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

        return [
            'payload' => [
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
            ],
            'meta' => [
                'requester_name' => $requesterResult['name'],
                'requester_created' => $requesterResult['created'],
                'sub_service_name' => $subService->name,
                'task_count' => count($tasks),
                'web_route_count' => count($parsed['web_routes']),
            ],
        ];
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
            $blockDate = $this->parseSpanishDateTime($blocks[$cursor]);
            if ($blockDate) {
                $templateCreatedAt = $blockDate;
                $cursor++;
            }
        }

        $requesterName = isset($blocks[$cursor]) ? $this->cleanPersonLine($blocks[$cursor]) : '';
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

        $createdId = $this->serviceRequestService->findOrCreateRequesterForCompany(
            $companyId,
            $name,
            $email,
        );

        return [
            'id' => $createdId,
            'name' => $name,
            'created' => true,
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
