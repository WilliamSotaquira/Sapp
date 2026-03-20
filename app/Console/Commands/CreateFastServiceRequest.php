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
            'is_reportable' => (bool) ($payload['is_reportable'] ?? false),
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
                    break;
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

        $subject = $this->limitSentence($title !== '' ? $title : $description, 90);
        $baseDescription = $description !== '' ? $description : $title;

        return [[
            'title' => 'Gestionar ' . Str::lower($subject),
            'description' => 'Atender la solicitud recibida, realizar revisión inicial y consolidar hallazgos o acciones recomendadas con trazabilidad del servicio. Tiempo total estimado: 75 min.',
            'priority' => 'medium',
            'type' => 'regular',
            'subtasks' => [
                [
                    'title' => 'Revisar contexto y alcance de la solicitud (20 min)',
                    'notes' => 'Analizar el requerimiento recibido, identificar objetivo, alcance y posibles restricciones para orientar la atención técnica del caso. Contexto base: ' . $this->limitSentence($baseDescription, 180),
                    'priority' => 'medium',
                    'estimated_minutes' => 20,
                ],
                [
                    'title' => 'Validar hallazgos iniciales y elementos relevantes del caso (30 min)',
                    'notes' => 'Realizar la verificación inicial de los elementos funcionales o técnicos relacionados con la solicitud y registrar hallazgos priorizados para su atención.',
                    'priority' => 'medium',
                    'estimated_minutes' => 30,
                ],
                [
                    'title' => 'Documentar acciones y resultado esperado para seguimiento (25 min)',
                    'notes' => 'Consolidar el resultado de la revisión, definir acciones recomendadas o siguiente paso y dejar trazabilidad clara para continuidad del servicio.',
                    'priority' => 'medium',
                    'estimated_minutes' => 25,
                ],
            ],
        ]];
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
}
