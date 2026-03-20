<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Requester;
use App\Models\ServiceRequest;
use App\Models\StandardTask;
use App\Models\SubService;
use App\Services\ServiceRequestService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateFastServiceRequest extends Command
{
    protected $signature = 'service-requests:create-fast
        {--file= : Ruta a archivo JSON con el payload}
        {--json= : Payload JSON inline}
        {--dry-run : Valida y resuelve contexto sin crear}
        {--allow-duplicate : Permite crear aunque exista un posible duplicado}';

    protected $description = 'Crea solicitudes de servicio de forma rapida a partir de un payload JSON';

    public function handle(ServiceRequestService $serviceRequestService): int
    {
        try {
            $payload = $this->loadPayload();
            $normalized = $this->normalizePayload($payload);
            $normalized['company_name'] = $this->inferCompanyName($normalized['company_name'], $normalized['requester_email']);
            $normalized['sub_service_code'] = $this->inferSubServiceCode(
                $normalized['sub_service_code'],
                $normalized['title'],
                $normalized['description']
            );

            $company = $this->resolveCompany($normalized);
            $subService = $this->resolveSubService($normalized, $company);
            $requesterId = $serviceRequestService->findOrCreateRequesterForCompany(
                $company->id,
                $normalized['requester_name'],
                $normalized['requester_email'],
                $normalized['requester_department'],
                $normalized['requester_position'],
            );

            $requester = Requester::withoutGlobalScopes()->findOrFail($requesterId);
            $duplicate = $this->findDuplicate($company->id, $requester->id, $normalized['title'], $normalized['source_date']);

            if ($duplicate && !$this->option('allow-duplicate')) {
                $this->line($this->toJson([
                    'created' => false,
                    'duplicate' => true,
                    'message' => 'Ya existe una solicitud similar para el mismo solicitante.',
                    'service_request_id' => $duplicate->id,
                    'ticket_number' => $duplicate->ticket_number,
                    'status' => $duplicate->status,
                ]));

                return self::SUCCESS;
            }

            $context = $serviceRequestService->resolveCreationContext(
                $company->id,
                $subService->id,
                $normalized['criticality_level'],
            );
            $normalized['tasks'] = $this->ensureTasks(
                $normalized['tasks'],
                $normalized['title'],
                $normalized['description']
            );

            $data = [
                'company_id' => $company->id,
                'requester_id' => $requester->id,
                'title' => $normalized['title'],
                'description' => $normalized['description'],
                'family_id' => $context['family_id'],
                'service_id' => $context['service_id'],
                'sub_service_id' => $context['sub_service_id'],
                'sla_id' => $context['sla_id'],
                'criticality_level' => $normalized['criticality_level'],
                'cut_id' => $context['cut_id'],
                'requested_by' => $normalized['requested_by'],
                'assigned_to' => $normalized['assigned_to'],
                'entry_channel' => $normalized['entry_channel'],
                'web_routes' => json_encode($normalized['web_routes']),
                'is_reportable' => $normalized['is_reportable'],
                'tasks_template' => $normalized['tasks_template'],
                'tasks' => $normalized['tasks'],
            ];

            $this->validateNormalizedData($data, $company->id);

            if ($this->option('dry-run')) {
                $this->line($this->toJson([
                    'dry_run' => true,
                    'created' => false,
                    'duplicate' => false,
                    'company_id' => $company->id,
                    'requester_id' => $requester->id,
                    'sub_service_id' => $context['sub_service_id'],
                    'sla_id' => $context['sla_id'],
                    'cut_id' => $context['cut_id'],
                    'task_count' => count($normalized['tasks']),
                    'tasks_template' => $normalized['tasks_template'],
                ]));

                return self::SUCCESS;
            }

            $serviceRequest = $serviceRequestService->createServiceRequest($data);
            $recordUrl = route('service-requests.show', $serviceRequest);
            $clipboardCopied = $this->copyToClipboard($recordUrl);

            $this->line($this->toJson([
                'created' => true,
                'service_request_id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'status' => $serviceRequest->status,
                'clipboard_copied' => $clipboardCopied,
                'sub_service_id' => $serviceRequest->sub_service_id,
                'sla_id' => $serviceRequest->sla_id,
                'cut_id' => $context['cut_id'],
                'task_count' => $serviceRequest->tasks()->count(),
                'subtask_count' => $serviceRequest->tasks()->withCount('subtasks')->get()->sum('subtasks_count'),
            ]));

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->error($this->toJson([
                'error' => 'validation_error',
                'messages' => $e->errors(),
            ]));

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($this->toJson([
                'error' => 'command_error',
                'message' => $e->getMessage(),
            ]));

            return self::FAILURE;
        }
    }

    private function loadPayload(): array
    {
        $json = $this->option('json');
        $file = $this->option('file');

        if (!$json && !$file) {
            throw ValidationException::withMessages([
                'payload' => 'Debes enviar --json o --file.',
            ]);
        }

        if ($json && $file) {
            throw ValidationException::withMessages([
                'payload' => 'Usa solo una fuente de entrada: --json o --file.',
            ]);
        }

        $raw = $json;

        if ($file) {
            if (!is_string($file) || !is_file($file)) {
                throw ValidationException::withMessages([
                    'file' => 'El archivo indicado no existe.',
                ]);
            }

            $raw = file_get_contents($file);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'payload' => 'El payload debe ser un JSON valido.',
            ]);
        }

        return $decoded;
    }

    private function normalizePayload(array $payload): array
    {
        $requester = is_array($payload['requester'] ?? null) ? $payload['requester'] : [];
        $channel = $this->firstPresent($payload, ['entry_channel', 'channel', 'canal', 'origin']);
        $tasksTemplate = $this->firstPresent($payload, ['tasks_template', 'task_template']);
        $tasks = $payload['tasks'] ?? [];
        $webRoutes = $this->firstPresent($payload, ['web_routes', 'routes', 'rutas_web'], []);

        if (is_string($webRoutes)) {
            $decoded = json_decode($webRoutes, true);
            if (is_array($decoded)) {
                $webRoutes = $decoded;
            } elseif (trim($webRoutes) !== '') {
                $webRoutes = [trim($webRoutes)];
            } else {
                $webRoutes = [];
            }
        }

        $criticality = strtoupper(trim((string) ($payload['criticality_level'] ?? $payload['criticality'] ?? 'MEDIA')));
        if ($criticality === 'URGENT') {
            $criticality = 'URGENTE';
        }

        $sourceDate = $this->firstPresent($payload, ['source_date', 'fecha_contexto', 'date']);

        return [
            'company_id' => isset($payload['company_id']) ? (int) $payload['company_id'] : null,
            'company_name' => $this->firstPresent($payload, ['company_name', 'company', 'entidad']),
            'sub_service_id' => isset($payload['sub_service_id']) ? (int) $payload['sub_service_id'] : null,
            'sub_service_code' => $this->firstPresent($payload, ['sub_service_code', 'sub_service', 'codigo_subservicio']),
            'requester_name' => trim((string) ($this->firstPresent($requester, ['name']) ?? $this->firstPresent($payload, ['requester_name', 'solicitante']) ?? '')),
            'requester_email' => $this->normalizeNullableString($this->firstPresent($requester, ['email']) ?? $this->firstPresent($payload, ['requester_email', 'email'])),
            'requester_department' => $this->normalizeNullableString($this->firstPresent($requester, ['department']) ?? $this->firstPresent($payload, ['requester_department', 'department'])),
            'requester_position' => $this->normalizeNullableString($this->firstPresent($requester, ['position']) ?? $this->firstPresent($payload, ['requester_position', 'position'])),
            'title' => trim((string) ($this->firstPresent($payload, ['title', 'titulo']) ?? '')),
            'description' => trim((string) ($this->firstPresent($payload, ['description', 'descripcion']) ?? '')),
            'criticality_level' => $criticality !== '' ? $criticality : 'MEDIA',
            'entry_channel' => $this->inferEntryChannel($channel),
            'web_routes' => $this->normalizeWebRoutes($webRoutes),
            'requested_by' => (int) ($payload['requested_by'] ?? 3),
            'assigned_to' => (int) ($payload['assigned_to'] ?? 3),
            // Las solicitudes creadas por flujo rápido deben quedar excluidas de reportes.
            'is_reportable' => false,
            'tasks_template' => $tasksTemplate ? trim((string) $tasksTemplate) : null,
            'tasks' => is_array($tasks) ? $tasks : [],
            'source_date' => $this->normalizeDate($sourceDate),
        ];
    }

    private function resolveCompany(array $normalized): Company
    {
        if (!empty($normalized['company_id'])) {
            return Company::query()->findOrFail($normalized['company_id']);
        }

        $companyName = trim((string) ($normalized['company_name'] ?? ''));
        if ($companyName === '') {
            throw ValidationException::withMessages([
                'company' => 'Debes indicar company_id o company_name.',
            ]);
        }

        $company = Company::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($companyName)])
            ->first();

        if (!$company) {
            $company = Company::query()
                ->where('name', 'like', '%' . $companyName . '%')
                ->first();
        }

        if (!$company) {
            throw ValidationException::withMessages([
                'company' => 'No se encontro la entidad indicada.',
            ]);
        }

        return $company;
    }

    private function resolveSubService(array $normalized, ?Company $company = null): SubService
    {
        if (!empty($normalized['sub_service_id'])) {
            return SubService::query()->findOrFail($normalized['sub_service_id']);
        }

        $code = trim((string) ($normalized['sub_service_code'] ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'sub_service' => 'Debes indicar sub_service_id o sub_service_code.',
            ]);
        }

        $codesToTry = array_unique(array_filter([
            Str::upper($code),
            ...$this->fallbackSubServiceCodes(Str::upper($code)),
        ]));

        $subService = null;
        foreach ($codesToTry as $candidateCode) {
            $query = SubService::query()
                ->whereRaw('UPPER(code) = ?', [$candidateCode]);

            if ($company && $company->active_contract_id) {
                $query->whereHas('service.family', function ($familyQuery) use ($company) {
                    $familyQuery->where('contract_id', $company->active_contract_id);
                });
            }

            $subService = $query->first();

            if ($subService) {
                break;
            }
        }

        if (!$subService) {
            throw ValidationException::withMessages([
                'sub_service' => 'No se encontro el subservicio indicado.',
            ]);
        }

        return $subService;
    }

    private function inferCompanyName(?string $companyName, ?string $requesterEmail): ?string
    {
        if (is_string($companyName) && trim($companyName) !== '') {
            return trim($companyName);
        }

        $email = Str::lower(trim((string) $requesterEmail));
        if (str_contains($email, '@movilidadbogota.gov.co')) {
            return 'Movilidad';
        }

        return $companyName;
    }

    private function inferSubServiceCode(?string $subServiceCode, string $title, string $description): ?string
    {
        if (is_string($subServiceCode) && trim($subServiceCode) !== '') {
            return trim($subServiceCode);
        }

        $haystack = Str::lower($title . ' ' . $description);

        $containsAny = function (array $patterns) use ($haystack): bool {
            foreach ($patterns as $pattern) {
                if (str_contains($haystack, $pattern)) {
                    return true;
                }
            }

            return false;
        };

        if ($containsAny(['seo', 'buscador', 'google', 'index', 'indexacion', 'metadato', 'metadatos', 'posicionamiento'])) {
            return 'SEO_TEC';
        }

        if ($containsAny(['enlace roto', 'link roto', 'url rota', 'contenido obsoleto'])) {
            return 'ENLACE_ROTO';
        }

        if ($containsAny(['error', 'no aparece', 'no carga', 'falla', 'problema'])) {
            return 'ERROR_CONTENIDO';
        }

        if ($containsAny(['radicado', 'competencia', 'orientacion', 'apoyo', 'consulta'])) {
            return 'APOYO_GENERAL';
        }

        return 'APOYO_GENERAL';
    }

    private function fallbackSubServiceCodes(string $code): array
    {
        return match ($code) {
            'SEO_TEC' => ['INCONSISTENCIA_CALIDAD', 'ANALITICA_WEB'],
            'AB_SEO' => ['INCONSISTENCIA_CALIDAD', 'ANALITICA_WEB'],
            default => [],
        };
    }

    private function validateNormalizedData(array $data, int $companyId): void
    {
        $validator = Validator::make($data, [
            'company_id' => 'required|integer|exists:companies,id',
            'requester_id' => 'required|integer|exists:requesters,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'family_id' => 'required|integer|exists:service_families,id',
            'service_id' => 'required|integer|exists:services,id',
            'sub_service_id' => 'required|integer|exists:sub_services,id',
            'sla_id' => 'required|integer|exists:service_level_agreements,id',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,URGENTE,CRITICA',
            'requested_by' => 'required|integer|exists:users,id',
            'assigned_to' => 'required|integer|exists:users,id',
            'entry_channel' => 'required|in:' . implode(',', ServiceRequest::getEntryChannelValidationValues()),
            'web_routes' => 'required|string',
            'tasks_template' => 'nullable|in:none,subservice_standard',
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'nullable|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.type' => 'nullable|in:impact,regular',
            'tasks.*.priority' => 'nullable|in:urgent,high,medium,low',
            'tasks.*.estimated_minutes' => 'nullable|integer|min:0|max:9999',
            'tasks.*.estimated_hours' => 'nullable|numeric|min:0|max:99.9',
            'tasks.*.subtasks' => 'nullable|array',
            'tasks.*.subtasks.*.title' => 'nullable|string|max:400',
            'tasks.*.subtasks.*.notes' => 'nullable|string',
            'tasks.*.subtasks.*.priority' => 'nullable|in:high,medium,low',
            'tasks.*.subtasks.*.estimated_minutes' => 'nullable|integer|min:0|max:9999',
        ]);

        $validator->after(function ($validator) use ($data, $companyId) {
            $tasks = $data['tasks'] ?? [];
            $tasksTemplate = $data['tasks_template'] ?? null;
            $hasValidTask = false;

            foreach ($tasks as $task) {
                if (!is_array($task)) {
                    continue;
                }

                $title = trim((string) ($task['title'] ?? ''));
                if ($title !== '') {
                    $hasValidTask = true;
                }

                if ($title !== '' && $this->looksGenericTaskText($title)) {
                    $validator->errors()->add('tasks', 'Las tareas deben ser realistas y específicas para administración web; evita títulos genéricos.');
                }

                $description = trim((string) ($task['description'] ?? ''));
                if ($description !== '' && $this->looksGenericTaskText($description)) {
                    $validator->errors()->add('tasks', 'La descripción de la tarea debe indicar una acción web concreta y no una plantilla genérica.');
                }

                foreach (($task['subtasks'] ?? []) as $subtask) {
                    if (!is_array($subtask)) {
                        continue;
                    }

                    $subtaskTitle = trim((string) ($subtask['title'] ?? ''));
                    $subtaskNotes = trim((string) ($subtask['notes'] ?? ''));

                    if ($subtaskTitle !== '' && $this->looksGenericTaskText($subtaskTitle)) {
                        $validator->errors()->add('tasks', 'Las subtareas deben reflejar acciones concretas de webmaster y no textos genéricos.');
                    }

                    if ($subtaskNotes !== '' && $this->looksGenericTaskText($subtaskNotes)) {
                        $validator->errors()->add('tasks', 'Las notas de subtareas deben describir una ejecución web concreta y verificable.');
                    }
                }
            }

            if (!$hasValidTask && $tasksTemplate !== 'subservice_standard') {
                $validator->errors()->add('tasks', 'Debes agregar al menos una tarea o usar tasks_template=subservice_standard.');
            }

            if (!$hasValidTask && $tasksTemplate === 'subservice_standard') {
                $standardCount = StandardTask::query()
                    ->where('sub_service_id', $data['sub_service_id'])
                    ->active()
                    ->count();

                if ($standardCount === 0) {
                    $validator->errors()->add('tasks_template', 'El subservicio no tiene tareas estandar configuradas.');
                }
            }

            $requesterCompanyId = (int) (Requester::withoutGlobalScopes()
                ->where('id', $data['requester_id'])
                ->value('company_id') ?? 0);

            if ($requesterCompanyId !== $companyId) {
                $validator->errors()->add('requester_id', 'El solicitante no pertenece a la entidad seleccionada.');
            }
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    private function inferEntryChannel(mixed $value): string
    {
        $raw = Str::lower(trim((string) $value));

        return match (true) {
            $raw === '',
            str_contains($raw, 'correo'),
            str_contains($raw, 'mail'),
            str_contains($raw, 'email') => ServiceRequest::ENTRY_CHANNEL_CORPORATE_EMAIL,
            str_contains($raw, 'whatsapp') => ServiceRequest::ENTRY_CHANNEL_WHATSAPP,
            str_contains($raw, 'telefon') || str_contains($raw, 'llamada') => ServiceRequest::ENTRY_CHANNEL_PHONE,
            str_contains($raw, 'reunion') || str_contains($raw, 'meet') => ServiceRequest::ENTRY_CHANNEL_MEETING,
            in_array($raw, ServiceRequest::getEntryChannelValidationValues(), true) => $raw,
            default => ServiceRequest::ENTRY_CHANNEL_CORPORATE_EMAIL,
        };
    }

    private function findDuplicate(int $companyId, int $requesterId, string $title, ?string $sourceDate): ?ServiceRequest
    {
        $query = ServiceRequest::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('requester_id', $requesterId)
            ->whereRaw('LOWER(title) = ?', [Str::lower(trim($title))]);

        if ($sourceDate) {
            $query->whereDate('created_at', $sourceDate);
        } else {
            $query->where('created_at', '>=', now()->subDays(15));
        }

        return $query->latest('id')->first();
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'source_date' => 'La fecha del contexto no tiene un formato valido.',
            ]);
        }
    }

    private function firstPresent(array $source, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                return $source[$key];
            }
        }

        return $default;
    }

    private function normalizeWebRoutes(mixed $webRoutes): array
    {
        if (!is_array($webRoutes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($route) => is_string($route) ? trim($route) : '',
            $webRoutes
        )));
    }

    private function ensureTasks(array $tasks, string $title, string $description): array
    {
        foreach ($tasks as $task) {
            if (is_array($task) && trim((string) ($task['title'] ?? '')) !== '') {
                return $tasks;
            }
        }

        return $this->buildRealisticWebAdminTasks($title, $description);
    }

    private function limitSentence(string $value, int $limit): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $clean !== '' ? Str::limit($clean, $limit, '') : 'la solicitud';
    }

    private function copyToClipboard(string $value): bool
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            return true;
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            return false;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'sapp_clip_');
        if ($tempFile === false) {
            return false;
        }

        file_put_contents($tempFile, $value);

        $escapedPath = str_replace("'", "''", $tempFile);
        $command = 'powershell -NoProfile -Command "Set-Clipboard -Value ([System.IO.File]::ReadAllText('
            . "'" . $escapedPath . "'"
            . '))"';

        exec($command, $output, $exitCode);
        @unlink($tempFile);

        return $exitCode === 0;
    }

    private function toJson(array $data): string
    {
        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function looksGenericTaskText(string $text): bool
    {
        $normalized = Str::lower(trim(preg_replace('/\s+/', ' ', $text) ?? ''));
        if ($normalized === '') {
            return false;
        }

        $genericSnippets = [
            'atender la solicitud recibida',
            'revisión inicial',
            'revision inicial',
            'hallazgos o acciones recomendadas',
            'revisar contexto y alcance de la solicitud',
            'validar hallazgos iniciales',
            'elementos relevantes del caso',
            'documentar acciones y resultado esperado para seguimiento',
            'orientar la atención técnica del caso',
            'trazabilidad clara para continuidad del servicio',
        ];

        foreach ($genericSnippets as $snippet) {
            if (str_contains($normalized, $snippet)) {
                return true;
            }
        }

        return false;
    }

    private function buildRealisticWebAdminTasks(string $title, string $description): array
    {
        $baseText = trim($title . ' ' . $description);
        $baseSummary = $this->limitSentence($description !== '' ? $description : $title, 220);
        $subject = $this->limitSentence($title !== '' ? $title : $description, 120);
        $haystack = Str::lower($baseText);

        $containsAny = function (array $patterns) use ($haystack): bool {
            foreach ($patterns as $pattern) {
                if (str_contains($haystack, $pattern)) {
                    return true;
                }
            }

            return false;
        };

        if ($containsAny(['feria', 'evento', 'pieza', 'piezas', 'banner', 'divulg', 'campa', 'jornada'])) {
            return [[
                'title' => 'Publicar y coordinar la divulgación web de ' . Str::lower($subject),
                'description' => 'Gestionar la divulgación solicitada en canales institucionales, verificando insumos, preparando la publicación y dejando trazabilidad funcional de lo publicado para asegurar una salida coherente y útil para la ciudadanía. Tiempo total estimado: 90 min.',
                'priority' => 'medium',
                'type' => 'regular',
                'subtasks' => [
                    [
                        'title' => 'Verificar piezas gráficas y datos del contenido a publicar (20 min)',
                        'notes' => 'Revisar el material recibido, confirmar fechas, nombres, enlaces y demás datos visibles para asegurar que el contenido sea publicable sin inconsistencias desde la gestión de contenidos web. Contexto base: ' . $baseSummary,
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                    [
                        'title' => 'Preparar y cargar la divulgación en el canal web aplicable (30 min)',
                        'notes' => 'Adecuar el contenido al espacio institucional que corresponda, cargar piezas, ajustar texto de apoyo y validar presentación visual antes de publicar, con enfoque de publicación web y habilitación controlada del cambio.',
                        'priority' => 'medium',
                        'estimated_minutes' => 30,
                    ],
                    [
                        'title' => 'Validar referencias de atención y mensaje complementario del contenido (20 min)',
                        'notes' => 'Comprobar la información operativa que deba acompañar la publicación, como sedes, ventanillas, enlaces o indicaciones de servicio, para que el contenido publicado oriente correctamente al usuario final.',
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                    [
                        'title' => 'Comprobar publicación y registrar evidencia para seguimiento (20 min)',
                        'notes' => 'Verificar que la salida quede visible en el canal intervenido, registrar evidencia de publicación y dejar trazabilidad del resultado esperado para continuidad o cierre del caso.',
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                ],
            ]];
        }

        if ($containsAny(['seo', 'index', 'indexacion', 'metadato', 'metadatos', 'buscador', 'google', 'posicionamiento'])) {
            return [[
                'title' => 'Revisar y ajustar visibilidad orgánica de ' . Str::lower($subject),
                'description' => 'Atender la solicitud mediante diagnóstico y ajuste de elementos SEO del contenido o sección reportada, dejando trazabilidad técnica y funcional sobre la mejora aplicada. Tiempo total estimado: 90 min.',
                'priority' => 'medium',
                'type' => 'regular',
                'subtasks' => [
                    [
                        'title' => 'Verificar indexación y presencia actual del contenido (25 min)',
                        'notes' => 'Comprobar cómo aparece el contenido en buscadores, identificar síntomas de visibilidad y registrar hallazgos iniciales para orientar la intervención SEO.',
                        'priority' => 'medium',
                        'estimated_minutes' => 25,
                    ],
                    [
                        'title' => 'Revisar títulos, descripciones y señales técnicas de la página (25 min)',
                        'notes' => 'Validar metadatos, encabezados y señales técnicas relacionadas con la página o sección reportada para detectar ajustes concretos desde la gestión SEO técnica.',
                        'priority' => 'medium',
                        'estimated_minutes' => 25,
                    ],
                    [
                        'title' => 'Aplicar ajuste priorizado o dejar plan de corrección validado (20 min)',
                        'notes' => 'Ejecutar el cambio viable en el contenido o documentar el ajuste requerido con criterio de implementación para que la solicitud avance sin ambigüedad.',
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                    [
                        'title' => 'Registrar evidencia y resultado esperado del ajuste SEO (20 min)',
                        'notes' => 'Dejar trazabilidad de hallazgos, cambio aplicado o siguiente acción recomendada, con evidencia suficiente para seguimiento del caso.',
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                ],
            ]];
        }

        if ($containsAny(['enlace', 'link', 'url', 'boton', 'menú', 'menu', 'redir', 'formulario'])) {
            return [[
                'title' => 'Corregir comportamiento funcional solicitado en ' . Str::lower($subject),
                'description' => 'Gestionar la actualización funcional reportada sobre enlaces, botones o rutas del sitio, verificando el comportamiento esperado y dejando evidencia de validación posterior al cambio. Tiempo total estimado: 75 min.',
                'priority' => 'medium',
                'type' => 'regular',
                'subtasks' => [
                    [
                        'title' => 'Reproducir el comportamiento reportado y confirmar alcance (20 min)',
                        'notes' => 'Validar el enlace, botón o ruta involucrada para confirmar el síntoma y definir con precisión qué parte del sitio debe ajustarse.',
                        'priority' => 'medium',
                        'estimated_minutes' => 20,
                    ],
                    [
                        'title' => 'Actualizar la referencia o configuración afectada en el sitio (25 min)',
                        'notes' => 'Aplicar el ajuste requerido en el contenido, menú, enlace o configuración relacionada, manteniendo coherencia con la estructura y navegación institucional.',
                        'priority' => 'medium',
                        'estimated_minutes' => 25,
                    ],
                    [
                        'title' => 'Validar funcionamiento posterior al cambio en el canal intervenido (15 min)',
                        'notes' => 'Comprobar que el comportamiento corregido responda como se espera y que no se afecten rutas o accesos relacionados.',
                        'priority' => 'medium',
                        'estimated_minutes' => 15,
                    ],
                    [
                        'title' => 'Registrar evidencia y cierre operativo del ajuste (15 min)',
                        'notes' => 'Documentar el cambio realizado, la validación ejecutada y el resultado funcional para continuidad o cierre de la solicitud.',
                        'priority' => 'medium',
                        'estimated_minutes' => 15,
                    ],
                ],
            ]];
        }

        return [[
            'title' => 'Atender requerimiento web sobre ' . Str::lower($subject),
            'description' => 'Gestionar la solicitud con acciones concretas de administración web, validando insumos, ejecutando el ajuste o publicación que aplique y dejando evidencia verificable del resultado. Tiempo total estimado: 80 min.',
            'priority' => 'medium',
            'type' => 'regular',
            'subtasks' => [
                [
                    'title' => 'Validar insumos, alcance y dependencia del requerimiento web (20 min)',
                    'notes' => 'Revisar el caso recibido, confirmar qué contenido, sección o funcionalidad del sitio está involucrada y precisar restricciones antes de intervenir. Contexto base: ' . $baseSummary,
                    'priority' => 'medium',
                    'estimated_minutes' => 20,
                ],
                [
                    'title' => 'Ejecutar el ajuste o publicación correspondiente en el sitio (30 min)',
                    'notes' => 'Realizar la actualización en el contenido, estructura, metadatos o canal institucional que corresponda, con enfoque de administración web y control del cambio.',
                    'priority' => 'medium',
                    'estimated_minutes' => 30,
                ],
                [
                    'title' => 'Verificar resultado funcional y visual de la intervención (15 min)',
                    'notes' => 'Comprobar que el cambio aplicado responda al objetivo de la solicitud y que el contenido o funcionalidad intervenida quede operativa.',
                    'priority' => 'medium',
                    'estimated_minutes' => 15,
                ],
                [
                    'title' => 'Registrar evidencia y trazabilidad para seguimiento del caso (15 min)',
                    'notes' => 'Dejar evidencia de la atención realizada, el resultado obtenido y cualquier observación necesaria para continuidad o cierre.',
                    'priority' => 'medium',
                    'estimated_minutes' => 15,
                ],
            ],
        ]];
    }
}
