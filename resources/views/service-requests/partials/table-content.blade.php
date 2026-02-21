@if(($slaAlerts['overdue'] ?? 0) > 0 || ($slaAlerts['dueSoon'] ?? 0) > 0)
    <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 text-amber-800 text-sm font-semibold">
            <i class="fas fa-bell"></i>
            Alertas SLA
        </div>
        @if(($slaAlerts['overdue'] ?? 0) > 0)
            <a href="{{ route('service-requests.index', array_merge(request()->except('page'), ['open' => 1])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                <i class="fas fa-exclamation-triangle"></i>
                {{ $slaAlerts['overdue'] }} vencidas
            </a>
        @endif
        @if(($slaAlerts['dueSoon'] ?? 0) > 0)
            <a href="{{ route('service-requests.index', array_merge(request()->except('page'), ['open' => 1])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                <i class="fas fa-clock"></i>
                {{ $slaAlerts['dueSoon'] }} por vencer (24h)
            </a>
        @endif
    </div>
@endif

<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 lg:gap-6">
    <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />
    <x-service-requests.index.stats-cards.create-action :count="$inCourseCount ?? 0" />
    <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />
    <x-service-requests.index.stats-cards.open-stats :count="$openCount ?? 0" />
    <x-service-requests.index.stats-cards.total-stats :count="$totalCount ?? $serviceRequests->total()" />
</div>

<!-- Filtros eliminados: ahora integrados en la barra superior de la tabla para evitar duplicados -->

<!-- Tabla -->
<x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" :services="$services ?? null" :savedFilters="$savedFilters ?? collect()" />

<!-- Paginación -->
@if ($serviceRequests->hasPages())
    <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
@endif
