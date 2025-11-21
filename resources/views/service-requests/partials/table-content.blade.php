<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 lg:gap-6">
    <x-service-requests.index.stats-cards.create-action />
    <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />
    <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />
    <x-service-requests.index.stats-cards.closed-stats :count="$closedCount ?? 0" />
    <x-service-requests.index.stats-cards.total-stats :count="$serviceRequests->total()" />
</div>

<!-- Filtros eliminados: ahora integrados en la barra superior de la tabla para evitar duplicados -->

<!-- Tabla -->
<x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" />

<!-- Paginación -->
@if ($serviceRequests->hasPages())
    <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
@endif
