<?php

namespace App\Services;

use App\Models\OperationalAlert;
use App\Models\ServiceRequest;
use App\Models\SlaBreachLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de indicadores de rendimiento operativo.
 *
 * Calcula métricas a partir de los datos de solicitudes, alertas y brechas SLA
 * para identificar cuellos de botella, tendencias y áreas de mejora.
 */
class PerformanceMetricsService
{
    /**
     * Obtener todos los indicadores principales para el dashboard.
     */
    public function getDashboardMetrics(?int $companyId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'period_days' => $days,
            'phase_times' => $this->getAveragePhaseTimesMinutes($companyId, $startDate),
            'bottleneck' => $this->identifyBottleneck($companyId, $startDate),
            'sla_compliance' => $this->getSlaComplianceRate($companyId, $startDate),
            'priority_effectiveness' => $this->getPriorityEffectiveness($companyId, $startDate),
            'complexity_impact' => $this->getComplexityImpact($companyId, $startDate),
            'volume' => $this->getVolumeMetrics($companyId, $startDate),
            'breach_summary' => SlaBreachDetectionService::getBreachStats($companyId, $days),
            'alert_summary' => app(OperationalAlertService::class)->getActiveSummary($companyId),
            'trends' => $this->getWeeklyTrends($companyId, $days),
        ];
    }

    /**
     * Tiempo promedio por fase (en minutos).
     * Identifica en qué parte del proceso se demora más.
     */
    public function getAveragePhaseTimesMinutes(?int $companyId, Carbon $since): array
    {
        $query = ServiceRequest::withoutGlobalScopes()
            ->whereNotNull('accepted_at')
            ->where('created_at', '>=', $since);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $requests = $query->select([
            'created_at', 'accepted_at', 'responded_at', 'resolved_at',
            'closed_at', 'total_paused_minutes',
        ])->get();

        if ($requests->isEmpty()) {
            return [
                'acceptance' => ['avg' => 0, 'median' => 0, 'count' => 0],
                'response' => ['avg' => 0, 'median' => 0, 'count' => 0],
                'resolution' => ['avg' => 0, 'median' => 0, 'count' => 0],
                'total' => ['avg' => 0, 'median' => 0, 'count' => 0],
            ];
        }

        // Fase de aceptación: created_at → accepted_at
        $acceptanceTimes = $requests->filter(fn ($r) => $r->accepted_at)
            ->map(fn ($r) => (int) $r->created_at->diffInMinutes($r->accepted_at));

        // Fase de respuesta: accepted_at → responded_at
        $responseTimes = $requests->filter(fn ($r) => $r->responded_at && $r->accepted_at)
            ->map(fn ($r) => max(0, (int) $r->accepted_at->diffInMinutes($r->responded_at) - ($r->total_paused_minutes ?? 0)));

        // Fase de resolución: responded_at → resolved_at
        $resolutionTimes = $requests->filter(fn ($r) => $r->resolved_at && $r->responded_at)
            ->map(fn ($r) => max(0, (int) $r->responded_at->diffInMinutes($r->resolved_at) - ($r->total_paused_minutes ?? 0)));

        // Total: created_at → resolved_at o closed_at
        $totalTimes = $requests->filter(fn ($r) => $r->resolved_at || $r->closed_at)
            ->map(function ($r) {
                $end = $r->resolved_at ?? $r->closed_at;
                return max(0, (int) $r->created_at->diffInMinutes($end) - ($r->total_paused_minutes ?? 0));
            });

        return [
            'acceptance' => $this->computeStats($acceptanceTimes),
            'response' => $this->computeStats($responseTimes),
            'resolution' => $this->computeStats($resolutionTimes),
            'total' => $this->computeStats($totalTimes),
        ];
    }

    /**
     * Identificar el cuello de botella (fase más lenta).
     */
    public function identifyBottleneck(?int $companyId, Carbon $since): array
    {
        $phaseTimes = $this->getAveragePhaseTimesMinutes($companyId, $since);

        $phases = [
            'acceptance' => ['label' => 'Aceptación', 'avg' => $phaseTimes['acceptance']['avg']],
            'response' => ['label' => 'Respuesta (inicio de trabajo)', 'avg' => $phaseTimes['response']['avg']],
            'resolution' => ['label' => 'Resolución', 'avg' => $phaseTimes['resolution']['avg']],
        ];

        $maxPhase = collect($phases)->sortByDesc('avg')->first();
        $maxKey = collect($phases)->sortByDesc('avg')->keys()->first();

        return [
            'phase' => $maxKey,
            'label' => $maxPhase['label'],
            'avg_minutes' => $maxPhase['avg'],
            'avg_hours' => round($maxPhase['avg'] / 60, 1),
        ];
    }

    /**
     * Tasa de cumplimiento SLA global.
     */
    public function getSlaComplianceRate(?int $companyId, Carbon $since): array
    {
        $query = ServiceRequest::withoutGlobalScopes()
            ->whereNotNull('sla_id')
            ->where('created_at', '>=', $since)
            ->whereIn('status', [
                ServiceRequest::STATUS_RESOLVED,
                ServiceRequest::STATUS_CLOSED,
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            return ['rate' => 100, 'compliant' => 0, 'non_compliant' => 0, 'total' => 0];
        }

        // Contar solicitudes con al menos una brecha
        $breachedIds = SlaBreachLog::where('created_at', '>=', $since)
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('serviceRequest', fn ($sr) => $sr->withoutGlobalScopes()->where('company_id', $companyId));
            })
            ->distinct('service_request_id')
            ->count('service_request_id');

        $compliant = $total - $breachedIds;

        return [
            'rate' => $total > 0 ? round(($compliant / $total) * 100, 1) : 100,
            'compliant' => $compliant,
            'non_compliant' => $breachedIds,
            'total' => $total,
        ];
    }

    /**
     * Efectividad de la priorización: ¿los P0 se resuelven más rápido que los P3?
     */
    public function getPriorityEffectiveness(?int $companyId, Carbon $since): array
    {
        $query = ServiceRequest::withoutGlobalScopes()
            ->whereNotNull('resolved_at')
            ->where('created_at', '>=', $since);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $results = [];
        $priorities = ['P0', 'P1', 'P2', 'P3', 'P4'];

        foreach ($priorities as $priority) {
            $requests = (clone $query)->where('priority_level', $priority)
                ->select(['created_at', 'resolved_at', 'total_paused_minutes'])
                ->get();

            $times = $requests->map(fn ($r) => max(0, (int) $r->created_at->diffInMinutes($r->resolved_at) - ($r->total_paused_minutes ?? 0)));

            $results[$priority] = [
                'count' => $requests->count(),
                'avg_minutes' => $times->isNotEmpty() ? (int) $times->avg() : 0,
                'avg_hours' => $times->isNotEmpty() ? round($times->avg() / 60, 1) : 0,
            ];
        }

        // Verificar si el orden es correcto (P0 < P1 < P2...)
        $avgTimes = array_column($results, 'avg_minutes');
        $nonZero = array_filter($avgTimes, fn ($v) => $v > 0);
        $isEffective = count($nonZero) >= 2 &&
            ($results['P0']['avg_minutes'] <= $results['P2']['avg_minutes'] || $results['P0']['count'] === 0);

        return [
            'by_priority' => $results,
            'is_effective' => $isEffective,
        ];
    }

    /**
     * Impacto de la complejidad en el tiempo de resolución.
     */
    public function getComplexityImpact(?int $companyId, Carbon $since): array
    {
        $query = ServiceRequest::withoutGlobalScopes()
            ->whereNotNull('resolved_at')
            ->where('created_at', '>=', $since);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $results = [];
        $levels = ['BAJA', 'MEDIA', 'ALTA'];

        foreach ($levels as $level) {
            $requests = (clone $query)->where('complexity_level', $level)
                ->select(['created_at', 'resolved_at', 'total_paused_minutes'])
                ->get();

            $times = $requests->map(fn ($r) => max(0, (int) $r->created_at->diffInMinutes($r->resolved_at) - ($r->total_paused_minutes ?? 0)));

            $results[$level] = [
                'count' => $requests->count(),
                'avg_minutes' => $times->isNotEmpty() ? (int) $times->avg() : 0,
                'avg_hours' => $times->isNotEmpty() ? round($times->avg() / 60, 1) : 0,
                'avg_days' => $times->isNotEmpty() ? round($times->avg() / 1440, 1) : 0,
            ];
        }

        // Factor de impacto: cuántas veces más se tarda una solicitud alta vs baja
        $impactFactor = ($results['BAJA']['avg_minutes'] > 0 && $results['ALTA']['avg_minutes'] > 0)
            ? round($results['ALTA']['avg_minutes'] / $results['BAJA']['avg_minutes'], 1)
            : null;

        return [
            'by_complexity' => $results,
            'impact_factor' => $impactFactor,
        ];
    }

    /**
     * Métricas de volumen: cuántas entran, cuántas se resuelven.
     */
    public function getVolumeMetrics(?int $companyId, Carbon $since): array
    {
        $baseQuery = ServiceRequest::withoutGlobalScopes()->where('created_at', '>=', $since);

        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $created = (clone $baseQuery)->count();
        $resolved = (clone $baseQuery)->whereNotNull('resolved_at')->count();
        $closed = (clone $baseQuery)->whereNotNull('closed_at')->count();
        $active = ServiceRequest::withoutGlobalScopes()
            ->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'REABIERTO'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->count();

        return [
            'created' => $created,
            'resolved' => $resolved,
            'closed' => $closed,
            'active_now' => $active,
            'resolution_rate' => $created > 0 ? round(($resolved / $created) * 100, 1) : 0,
            'backlog_ratio' => $resolved > 0 ? round($active / max(1, $resolved), 2) : $active,
        ];
    }

    /**
     * Tendencias semanales: creadas vs resueltas por semana.
     */
    public function getWeeklyTrends(?int $companyId, int $days): array
    {
        $weeks = (int) ceil($days / 7);
        $trends = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $baseQuery = ServiceRequest::withoutGlobalScopes()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

            $created = (clone $baseQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->count();
            $resolved = (clone $baseQuery)->whereBetween('resolved_at', [$weekStart, $weekEnd])->count();

            $trends[] = [
                'week_label' => $weekStart->format('d/m'),
                'week_start' => $weekStart->toDateString(),
                'created' => $created,
                'resolved' => $resolved,
                'net' => $resolved - $created,
            ];
        }

        return $trends;
    }

    /**
     * Calcular estadísticas (promedio, mediana, conteo) de una colección de valores.
     */
    private function computeStats($collection): array
    {
        if ($collection->isEmpty()) {
            return ['avg' => 0, 'median' => 0, 'count' => 0];
        }

        $sorted = $collection->sort()->values();
        $count = $sorted->count();
        $median = $count % 2 === 0
            ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
            : $sorted[intdiv($count, 2)];

        return [
            'avg' => (int) round($collection->avg()),
            'median' => (int) round($median),
            'count' => $count,
        ];
    }
}
