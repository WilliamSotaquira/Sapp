<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\SlaBreachLog;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de detección y registro de brechas de SLA.
 *
 * Evalúa solicitudes activas y detecta incumplimientos en las tres fases:
 * - ACEPTACION: no se aceptó dentro del tiempo definido por el SLA
 * - RESPUESTA: no se inició trabajo dentro del tiempo definido
 * - RESOLUCION: no se resolvió dentro del tiempo definido
 *
 * Solo registra una brecha por fase por solicitud (idempotente).
 * Las pausas se descuentan del cálculo.
 */
class SlaBreachDetectionService
{
    private int $breachesDetected = 0;
    private array $summary = [];

    /**
     * Ejecutar detección de brechas para todas las solicitudes activas.
     *
     * @param int|null $companyId Filtrar por empresa
     * @return array Resumen de ejecución
     */
    public function detect(?int $companyId = null): array
    {
        $this->breachesDetected = 0;
        $this->summary = [];

        $activeStatuses = [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_PAUSED,
            ServiceRequest::STATUS_REOPENED,
            ServiceRequest::STATUS_RESOLVED,
        ];

        $query = ServiceRequest::withoutGlobalScopes()
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('sla_id')
            ->with(['sla', 'breachLogs']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->chunkById(100, function ($requests) {
            foreach ($requests as $request) {
                $this->evaluateRequest($request);
            }
        });

        return [
            'breaches_detected' => $this->breachesDetected,
            'summary' => $this->summary,
        ];
    }

    /**
     * Evaluar una solicitud individual para detectar brechas.
     */
    private function evaluateRequest(ServiceRequest $request): void
    {
        $sla = $request->sla;

        if (!$sla) {
            return;
        }

        $existingBreaches = $request->breachLogs->pluck('breach_type')->toArray();

        // Fase de aceptación
        if ($sla->acceptance_time_minutes > 0 && !in_array('ACEPTACION', $existingBreaches)) {
            $this->checkAcceptanceBreach($request, $sla->acceptance_time_minutes);
        }

        // Fase de respuesta
        if ($sla->response_time_minutes > 0 && !in_array('RESPUESTA', $existingBreaches)) {
            $this->checkResponseBreach($request, $sla->response_time_minutes);
        }

        // Fase de resolución
        if ($sla->resolution_time_minutes > 0 && !in_array('RESOLUCION', $existingBreaches)) {
            $this->checkResolutionBreach($request, $sla->resolution_time_minutes);
        }
    }

    /**
     * Verificar brecha de aceptación.
     */
    private function checkAcceptanceBreach(ServiceRequest $request, int $allowedMinutes): void
    {
        // Si ya fue aceptada, verificar si fue a tiempo
        if ($request->accepted_at) {
            $elapsed = (int) $request->created_at->diffInMinutes($request->accepted_at);

            if ($elapsed > $allowedMinutes) {
                $this->recordBreach($request, 'ACEPTACION', $elapsed - $allowedMinutes,
                    "Aceptada {$elapsed} min después de creada (SLA: {$allowedMinutes} min)");
            }
            return;
        }

        // Si no ha sido aceptada, verificar si ya excedió el tiempo
        if ($request->status === ServiceRequest::STATUS_PENDING) {
            $elapsed = (int) $request->created_at->diffInMinutes(now());

            if ($elapsed > $allowedMinutes) {
                $this->recordBreach($request, 'ACEPTACION', $elapsed - $allowedMinutes,
                    "Sin aceptar después de {$elapsed} min (SLA: {$allowedMinutes} min)");
            }
        }
    }

    /**
     * Verificar brecha de respuesta.
     */
    private function checkResponseBreach(ServiceRequest $request, int $allowedMinutes): void
    {
        $startFrom = $request->accepted_at ?? $request->created_at;
        $pausedMinutes = (int) ($request->total_paused_minutes ?? 0);

        // Si ya fue respondida, verificar si fue a tiempo
        if ($request->responded_at) {
            $elapsed = (int) $startFrom->diffInMinutes($request->responded_at);
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            if ($effectiveElapsed > $allowedMinutes) {
                $this->recordBreach($request, 'RESPUESTA', $effectiveElapsed - $allowedMinutes,
                    "Respondida en {$effectiveElapsed} min efectivos (SLA: {$allowedMinutes} min)");
            }
            return;
        }

        // Si no ha sido respondida y no está pausada
        if ($request->status !== ServiceRequest::STATUS_PAUSED) {
            $elapsed = (int) $startFrom->diffInMinutes(now());
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            if ($effectiveElapsed > $allowedMinutes) {
                $this->recordBreach($request, 'RESPUESTA', $effectiveElapsed - $allowedMinutes,
                    "Sin responder después de {$effectiveElapsed} min efectivos (SLA: {$allowedMinutes} min)");
            }
        }
    }

    /**
     * Verificar brecha de resolución.
     */
    private function checkResolutionBreach(ServiceRequest $request, int $allowedMinutes): void
    {
        $startFrom = $request->responded_at ?? $request->accepted_at ?? $request->created_at;
        $pausedMinutes = (int) ($request->total_paused_minutes ?? 0);

        // Si ya fue resuelta, verificar si fue a tiempo
        if ($request->resolved_at) {
            $elapsed = (int) $startFrom->diffInMinutes($request->resolved_at);
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            if ($effectiveElapsed > $allowedMinutes) {
                $this->recordBreach($request, 'RESOLUCION', $effectiveElapsed - $allowedMinutes,
                    "Resuelta en {$effectiveElapsed} min efectivos (SLA: {$allowedMinutes} min)");
            }
            return;
        }

        // Si no ha sido resuelta y no está pausada
        if ($request->status !== ServiceRequest::STATUS_PAUSED) {
            $elapsed = (int) $startFrom->diffInMinutes(now());
            $effectiveElapsed = max(0, $elapsed - $pausedMinutes);

            if ($effectiveElapsed > $allowedMinutes) {
                $this->recordBreach($request, 'RESOLUCION', $effectiveElapsed - $allowedMinutes,
                    "Sin resolver después de {$effectiveElapsed} min efectivos (SLA: {$allowedMinutes} min)");
            }
        }
    }

    /**
     * Registrar una brecha en la base de datos.
     */
    private function recordBreach(ServiceRequest $request, string $type, int $breachMinutes, string $notes): void
    {
        try {
            SlaBreachLog::create([
                'service_request_id' => $request->id,
                'breach_type' => $type,
                'breach_minutes' => max(1, $breachMinutes),
                'notes' => $notes,
            ]);

            $this->breachesDetected++;
            $this->summary[$type] = ($this->summary[$type] ?? 0) + 1;
        } catch (\Exception $e) {
            Log::warning("Error registrando brecha SLA para SR#{$request->id}: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de brechas para un período.
     */
    public static function getBreachStats(?int $companyId = null, ?int $days = 30): array
    {
        $query = SlaBreachLog::query()
            ->where('created_at', '>=', now()->subDays($days));

        if ($companyId) {
            $query->whereHas('serviceRequest', function ($q) use ($companyId) {
                $q->withoutGlobalScopes()->where('company_id', $companyId);
            });
        }

        $breaches = $query->get();

        return [
            'total' => $breaches->count(),
            'by_type' => [
                'ACEPTACION' => $breaches->where('breach_type', 'ACEPTACION')->count(),
                'RESPUESTA' => $breaches->where('breach_type', 'RESPUESTA')->count(),
                'RESOLUCION' => $breaches->where('breach_type', 'RESOLUCION')->count(),
            ],
            'avg_breach_minutes' => [
                'ACEPTACION' => (int) $breaches->where('breach_type', 'ACEPTACION')->avg('breach_minutes'),
                'RESPUESTA' => (int) $breaches->where('breach_type', 'RESPUESTA')->avg('breach_minutes'),
                'RESOLUCION' => (int) $breaches->where('breach_type', 'RESOLUCION')->avg('breach_minutes'),
            ],
            'period_days' => $days,
        ];
    }
}
