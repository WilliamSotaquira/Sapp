<?php

namespace Database\Seeders;

use App\Models\Requester;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceRequestsDemoSeeder extends Seeder
{
    private bool $hasActualResolutionTime = false;

    public function run(): void
    {
        $alreadySeeded = ServiceRequest::query()
            ->where('title', 'like', 'DEMO - %')
            ->exists();

        if ($alreadySeeded) {
            $this->command?->info('ServiceRequestsDemoSeeder: ya existen solicitudes DEMO, se omite.');
            return;
        }

        $this->hasActualResolutionTime = Schema::hasColumn('service_requests', 'actual_resolution_time');

        $users = User::query()->orderBy('id')->get();
        if ($users->count() < 2) {
            $this->command?->warn('ServiceRequestsDemoSeeder: se requieren al menos 2 usuarios.');
            return;
        }

        $technicians = User::query()->inRandomOrder()->take(min(5, $users->count()))->get();
        $requestingUsers = User::query()->inRandomOrder()->take(min(5, $users->count()))->get();

        if (Requester::query()->count() < 10) {
            $this->seedRequesters();
        }

        $requesters = Requester::query()->inRandomOrder()->take(10)->get();

        $hasSlaSubServiceId = Schema::hasColumn('service_level_agreements', 'sub_service_id');

        $slas = ServiceLevelAgreement::query()
            ->where('is_active', true)
            ->with(['serviceSubservice.subService'])
            ->get();

        $subServicesById = collect();
        if ($hasSlaSubServiceId) {
            $slaSubServiceIds = $slas->pluck('sub_service_id')->filter()->unique()->values();
            $subServicesById = SubService::query()->whereIn('id', $slaSubServiceIds)->get()->keyBy('id');
        }

        $slaPairs = $slas
            ->map(function ($sla) use ($hasSlaSubServiceId, $subServicesById) {
                $subService = null;

                if ($hasSlaSubServiceId && !empty($sla->sub_service_id)) {
                    $subService = $subServicesById->get($sla->sub_service_id);
                }

                if (!$subService && $sla->serviceSubservice && $sla->serviceSubservice->subService) {
                    $subService = $sla->serviceSubservice->subService;
                }

                return [
                    'sla' => $sla,
                    'subService' => $subService,
                ];
            })
            ->filter(fn ($pair) => (bool) $pair['subService'])
            ->values();

        if ($slaPairs->isEmpty()) {
            $this->command?->warn('ServiceRequestsDemoSeeder: no hay SLAs activos con subservicio asociado (sub_service_id o relación). Ejecuta SubServiceSeeder + ServiceSubserviceSeeder + SLASeeder.');
            return;
        }

        $now = now();
        $datePrefix = $now->format('Ymd');
        $sequence = 1;

        $makeTicketNumber = function () use (&$sequence, $datePrefix) {
            $ticket = sprintf('DEMO-SR-%s-%04d', $datePrefix, $sequence);
            $sequence++;
            return $ticket;
        };

        $makeTitle = function (string $status, string $subject) {
            return "DEMO - {$status} - {$subject}";
        };

        $entryChannels = array_keys(ServiceRequest::getEntryChannelOptions());
        if (empty($entryChannels)) {
            $entryChannels = [
                ServiceRequest::ENTRY_CHANNEL_CORPORATE_EMAIL,
                ServiceRequest::ENTRY_CHANNEL_DIGITAL_EMAIL,
            ];
        }

        DB::transaction(function () use (
            $slaPairs,
            $requesters,
            $requestingUsers,
            $technicians,
            $makeTicketNumber,
            $makeTitle,
            $entryChannels,
            $now
        ) {
            ServiceRequest::withoutEvents(function () use (
                $slaPairs,
                $requesters,
                $requestingUsers,
                $technicians,
                $makeTicketNumber,
                $makeTitle,
                $entryChannels,
                $now
            ) {
                $definitions = [
                    ['status' => 'PENDIENTE', 'count' => 6],
                    ['status' => 'ACEPTADA', 'count' => 6],
                    ['status' => 'EN_PROCESO', 'count' => 6],
                    ['status' => 'PAUSADA', 'count' => 4],
                    ['status' => 'RESUELTA', 'count' => 4],
                    ['status' => 'CERRADA', 'count' => 3],
                    ['status' => 'CANCELADA', 'count' => 2],
                ];

                foreach ($definitions as $def) {
                    for ($i = 0; $i < $def['count']; $i++) {
                        $pair = $slaPairs->random();
                        $sla = $pair['sla'];
                        $subService = $pair['subService'];

                        $requestedBy = $requestingUsers->random();
                        $assignedTo = $technicians->random();
                        $requester = $requesters->random();

                        $status = $def['status'];
                        $criticality = $sla->criticality_level ?: collect(['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->random();

                        $createdAt = $now->copy()->subDays(rand(0, 12))->subHours(rand(0, 23));

                        $base = [
                            'ticket_number' => $makeTicketNumber(),
                            'sla_id' => $sla->id,
                            'sub_service_id' => $subService->id,
                            'requester_id' => $requester->id,
                            'requested_by' => $requestedBy->id,
                            'entry_channel' => collect($entryChannels)->random(),
                            'is_reportable' => true,
                            'title' => $makeTitle($status, $subService->name),
                            'description' => $this->buildDescription($subService->name),
                            'criticality_level' => $criticality,
                            'is_paused' => false,
                            'pause_reason' => null,
                            'paused_at' => null,
                            'paused_by' => null,
                            'resumed_at' => null,
                            'total_paused_minutes' => 0,
                            'resolution_notes' => null,
                            'resolved_at' => null,
                            'closed_at' => null,
                            'satisfaction_score' => null,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ];

                        // Variantes por estado
                        if (in_array($status, ['ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA'], true)) {
                            $base['assigned_to'] = $assignedTo->id;
                            $base['accepted_at'] = $createdAt->copy()->addMinutes(rand(5, 240));
                        }

                        if ($status === 'EN_PROCESO') {
                            $base['responded_at'] = $base['accepted_at']?->copy()->addMinutes(rand(5, 120));
                        }

                        if ($status === 'PAUSADA') {
                            $base['assigned_to'] = $assignedTo->id;
                            $base['is_paused'] = true;
                            $base['pause_reason'] = 'Esperando validación / acceso / dependencia externa.';
                            $base['paused_at'] = $createdAt->copy()->addHours(rand(1, 24));
                            $base['paused_by'] = $assignedTo->id;
                            $base['total_paused_minutes'] = rand(15, 180);
                        }

                        if (in_array($status, ['RESUELTA', 'CERRADA'], true)) {
                            $base['assigned_to'] = $assignedTo->id;
                            $base['resolution_notes'] = $this->buildResolutionNotes($subService->name);
                            $base['resolved_at'] = $createdAt->copy()->addHours(rand(2, 72));

                            if ($this->hasActualResolutionTime) {
                                $base['actual_resolution_time'] = rand(30, 360);
                            }
                        }

                        if ($status === 'CERRADA') {
                            $base['closed_at'] = $base['resolved_at']?->copy()->addHours(rand(1, 48));
                            $base['satisfaction_score'] = rand(3, 5);
                        }

                        // Importante: setear status al final para evitar que el mutator
                        // valide antes de que existan campos como assigned_to.
                        $base['status'] = $status;

                        // Campos opcionales que pueden existir
                        if (Schema::hasColumn('service_requests', 'web_routes')) {
                            $base['web_routes'] = [
                                '/service-requests',
                                '/service-requests/create',
                            ];
                        }
                        if (Schema::hasColumn('service_requests', 'main_web_route')) {
                            $base['main_web_route'] = '/service-requests';
                        }

                        $serviceRequest = ServiceRequest::query()->create($base);

                        $this->seedEvidencesFor($serviceRequest, $requestedBy->id, $serviceRequest->assigned_to ?: $requestedBy->id);
                    }
                }
            });
        });

        $this->command?->info('ServiceRequestsDemoSeeder: solicitudes DEMO creadas.');
    }

    private function seedRequesters(): void
    {
        $departments = ['Finanzas', 'Talento Humano', 'Operaciones', 'TI', 'Jurídica', 'Compras', 'Planeación'];
        $positions = ['Analista', 'Coordinador', 'Profesional', 'Auxiliar', 'Jefe'];

        for ($i = 1; $i <= 12; $i++) {
            Requester::query()->create([
                'name' => "Solicitante Demo {$i}",
                'email' => "solicitante.demo{$i}@example.local",
                'phone' => '300' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'department' => collect($departments)->random(),
                'position' => collect($positions)->random(),
                'is_active' => true,
            ]);
        }
    }

    private function buildDescription(string $subServiceName): string
    {
        $id = strtoupper(Str::random(6));

        return implode("\n", [
            "Solicitud generada para pruebas end-to-end.",
            "Subservicio: {$subServiceName}",
            "Referencia: {$id}",
            "\nReproducibilidad:",
            "- Abrir el detalle de la solicitud",
            "- Probar acciones según el estado (aceptar/iniciar/pausar/resolver/cerrar)",
        ]);
    }

    private function buildResolutionNotes(string $subServiceName): string
    {
        return implode("\n", [
            "Acciones realizadas:",
            "1) Diagnóstico inicial del requerimiento ({$subServiceName}).",
            "2) Aplicación de corrección / ajuste correspondiente.",
            "3) Validación con el solicitante / pruebas básicas.",
            "\nResultado: Solución aplicada y verificada.",
        ]);
    }

    private function seedEvidencesFor(ServiceRequest $serviceRequest, int $requesterUserId, int $technicianUserId): void
    {
        // Evidencia tipo comentario (visible en casi todos los estados)
        ServiceRequestEvidence::query()->create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Comentario inicial (DEMO)',
            'description' => 'Evidencia de prueba para validar UI y flujo.',
            'evidence_type' => 'COMENTARIO',
            'step_number' => null,
            'evidence_data' => [
                'source' => 'seeder',
                'tag' => 'demo',
            ],
            'user_id' => $requesterUserId,
        ]);

        if (in_array($serviceRequest->status, ['EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA'], true)) {
            ServiceRequestEvidence::query()->create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'Registro de acción (DEMO)',
                'description' => 'Acción simulada para pruebas (paso a paso).',
                'evidence_type' => 'PASO_A_PASO',
                'step_number' => 1,
                'evidence_data' => [
                    'action' => 'TEST_STEP',
                    'status' => $serviceRequest->status,
                ],
                'user_id' => $technicianUserId,
            ]);
        }

        if ($serviceRequest->status === 'PAUSADA') {
            ServiceRequestEvidence::query()->create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'Solicitud pausada (DEMO)',
                'description' => $serviceRequest->pause_reason ?: 'Pausa de prueba.',
                'evidence_type' => 'SISTEMA',
                'step_number' => null,
                'evidence_data' => [
                    'action' => 'PAUSED',
                ],
                'user_id' => $technicianUserId,
            ]);
        }
    }
}
