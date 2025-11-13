@extends('layouts.app')

@section('title', 'Solicitudes de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitudes de Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <!-- Header Principal -->
    <x-service-requests.index.header.main-header />

    <div class="space-y-6">
        <!-- Fila 1: Estadísticas y Acción Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            <!-- Tarjeta 1: Nueva Solicitud -->
            <x-service-requests.index.stats-cards.create-action />

            <!-- Tarjeta 2: Críticas -->
            <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />

            <!-- Tarjeta 3: Pendientes -->
            <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />

            <!-- Tarjeta 4: Cerradas -->
            <x-service-requests.index.stats-cards.closed-stats :count="$closedCount ?? 0" />

            <!-- Tarjeta 5: Total -->
            <x-service-requests.index.stats-cards.total-stats :count="$serviceRequests->total()" />

        </div>

        <!-- Fila 2: Filtros y Búsqueda -->
        <x-service-requests.index.filters.filters-panel />

        <!-- Fila 3: Lista de Solicitudes -->
        <x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" />

        <!-- Fila 4: Paginación -->
        @if ($serviceRequests->hasPages())
            <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const statusFilter = document.getElementById('statusFilter');
            const criticalityFilter = document.getElementById('criticalityFilter');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const clearFiltersBtn = document.getElementById('clearFilters');

            // Aplicar filtros
            function applyFilters() {
                const status = statusFilter.value;
                const criticality = criticalityFilter.value;
                const rows = document.querySelectorAll('tbody tr[data-status]');

                let visibleCount = 0;

                rows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const rowCriticality = row.getAttribute('data-criticality');

                    const statusMatch = !status || rowStatus === status;
                    const criticalityMatch = !criticality || rowCriticality === criticality;

                    if (statusMatch && criticalityMatch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Actualizar contador
                const counter = document.querySelector('.bg-blue-100');
                if (counter) {
                    counter.textContent = `${visibleCount} resultados`;
                }
            }

            // Limpiar filtros
            function clearFilters() {
                statusFilter.value = '';
                criticalityFilter.value = '';
                applyFilters();
                sessionStorage.removeItem('serviceRequests_statusFilter');
                sessionStorage.removeItem('serviceRequests_criticalityFilter');
            }

            // Event Listeners
            applyFiltersBtn.addEventListener('click', applyFilters);
            clearFiltersBtn.addEventListener('click', clearFilters);

            // Restaurar valores de filtro si existen
            const savedStatus = sessionStorage.getItem('serviceRequests_statusFilter');
            const savedCriticality = sessionStorage.getItem('serviceRequests_criticalityFilter');

            if (savedStatus) statusFilter.value = savedStatus;
            if (savedCriticality) criticalityFilter.value = savedCriticality;

            // Guardar valores de filtro
            statusFilter.addEventListener('change', () => {
                sessionStorage.setItem('serviceRequests_statusFilter', statusFilter.value);
            });

            criticalityFilter.addEventListener('change', () => {
                sessionStorage.setItem('serviceRequests_criticalityFilter', criticalityFilter.value);
            });

            // Aplicar filtros al cargar la página
            applyFilters();
        });
    </script>
@endpush

<style>
    .bg-gradient-to-br {
        background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
    }

    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
    }

    .transition {
        transition: all 0.2s ease-in-out;
    }

    .hover\:bg-gray-50:hover {
        background-color: rgba(249, 250, 251, 0.8);
    }

    .font-mono {
        font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, monospace;
    }
</style>
