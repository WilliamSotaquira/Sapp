@extends('layouts.app')

@section('title', 'Panorama Operativo')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('reports.index') }}" class="text-blue-600 hover:text-blue-700">Informes</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Panorama Operativo</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $hasStatusData = $statusData->isNotEmpty();
        $hasCriticalityData = $criticalityData->isNotEmpty();
        $hasTrendsData = is_countable($trendsData) ? count($trendsData) > 0 : $trendsData->isNotEmpty();
        $hasAnyData = $hasStatusData || $hasCriticalityData || $hasTrendsData;
    @endphp

    {{-- Header with title, filters button, and export buttons --}}
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Panorama Operativo</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
                &middot; Tendencias: últimos {{ $months }} meses
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="openFiltersSidebar"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                <i class="fas fa-sliders-h mr-2"></i>Filtros
            </button>
            <a href="{{ route('reports.operational-overview.export', ['format' => 'pdf', 'date_from' => $dateRange['start']->format('Y-m-d'), 'date_to' => $dateRange['end']->format('Y-m-d'), 'months' => $months]) }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 inline-flex items-center">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.operational-overview.export', ['format' => 'csv', 'date_from' => $dateRange['start']->format('Y-m-d'), 'date_to' => $dateRange['end']->format('Y-m-d'), 'months' => $months]) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 inline-flex items-center">
                <i class="fas fa-file-csv mr-2"></i>CSV
            </a>
        </div>
    </div>

    {{-- Active filter badges --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
            <i class="fas fa-calendar-alt mr-1"></i>{{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
            <i class="fas fa-chart-line mr-1"></i>Tendencias: {{ $months }} meses
        </span>
        <a href="{{ route('reports.operational-overview.index') }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
            <i class="fas fa-times mr-1"></i>Restablecer
        </a>
    </div>

    @if($hasAnyData)
        {{-- ================================================================= --}}
        {{-- Section 1: Status Distribution --}}
        {{-- ================================================================= --}}
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Distribución por Estado</h3>
                <p class="text-sm text-gray-500">Solicitudes agrupadas por estado actual</p>
            </div>

            @if($hasStatusData)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $statusColors = [
                                    'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                    'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                    'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                    'RESUELTA' => 'bg-green-100 text-green-800',
                                    'CERRADA' => 'bg-gray-100 text-gray-800',
                                    'CANCELADA' => 'bg-red-100 text-red-800',
                                    'RECHAZADA' => 'bg-gray-100 text-gray-800',
                                ];
                            @endphp
                            @foreach($statusData as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                        {{ $item->count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ number_format($item->percentage, 2) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No hay datos de estado disponibles para el período seleccionado.</p>
                </div>
            @endif
        </div>

        {{-- ================================================================= --}}
        {{-- Section 2: Criticality Distribution --}}
        {{-- ================================================================= --}}
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Distribución por Criticidad</h3>
                <p class="text-sm text-gray-500">Solicitudes agrupadas por nivel de criticidad con tiempo promedio de resolución</p>
            </div>

            @if($hasCriticalityData)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nivel de Criticidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Promedio Horas Resolución</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $criticalityColors = [
                                    'BAJA' => 'bg-green-100 text-green-800',
                                    'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                    'ALTA' => 'bg-orange-100 text-orange-800',
                                    'CRITICA' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            @foreach($criticalityData as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$item->criticality_level] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $item->criticality_level }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                        {{ $item->count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold {{ ($item->avg_resolution_hours ?? 0) <= 8 ? 'text-green-600' : (($item->avg_resolution_hours ?? 0) <= 24 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($item->avg_resolution_hours ?? 0, 1) }} h
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No hay datos de criticidad disponibles para el período seleccionado.</p>
                </div>
            @endif
        </div>

        {{-- ================================================================= --}}
        {{-- Section 3: Monthly Trends --}}
        {{-- ================================================================= --}}
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Tendencias Mensuales</h3>
                <p class="text-sm text-gray-500">Evolución de métricas en los últimos {{ $months }} meses</p>
            </div>

            @if($hasTrendsData)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resueltas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Completitud</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Promedio Horas Resolución</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($trendsData as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                        {{ $item['month_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $item['total_requests'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $item['resolved_requests'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold {{ $item['completion_rate'] >= 80 ? 'text-green-600' : ($item['completion_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($item['completion_rate'], 2) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold {{ $item['avg_resolution_hours'] <= 8 ? 'text-green-600' : ($item['avg_resolution_hours'] <= 24 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($item['avg_resolution_hours'], 1) }} h
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No hay datos de tendencias disponibles para los últimos {{ $months }} meses.</p>
                </div>
            @endif
        </div>
    @else
        {{-- Empty state when no data at all --}}
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-chart-bar text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron solicitudes de servicio para el período seleccionado.</p>
            <p class="text-sm text-gray-500">Intente ajustar el rango de fechas o el número de meses en los filtros.</p>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- Filters Sidebar --}}
    {{-- ===================================================================== --}}
    <div id="filtersSidebar"
         class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
        <div class="flex flex-col h-full">
            {{-- Sidebar Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-2"></i>Filtros
                    </h3>
                    <button type="button" id="closeFiltersSidebar" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-blue-100 text-xs mt-1">Configura el rango de fechas y meses de tendencias</p>
            </div>

            {{-- Sidebar Body --}}
            <div class="flex-1 px-6 py-4 space-y-4">
                <form id="advancedFiltersForm" class="space-y-4">
                    {{-- Date Range --}}
                    <div>
                        <label for="dateFromFilter" class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input type="date" id="dateFromFilter" name="date_from"
                               value="{{ $dateRange['start']->format('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="dateToFilter" class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input type="date" id="dateToFilter" name="date_to"
                               value="{{ $dateRange['end']->format('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Months Selector --}}
                    <div>
                        <label for="monthsFilter" class="block text-sm font-medium text-gray-700 mb-2">Meses de Tendencias</label>
                        <select id="monthsFilter" name="months"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($allowedMonths as $option)
                                <option value="{{ $option }}" {{ (int) $months === (int) $option ? 'selected' : '' }}>
                                    Últimos {{ $option }} meses
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- Sidebar Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex gap-3">
                <button type="button" id="clearSidebarFiltersBtn"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Limpiar
                </button>
                <button type="button" id="applySidebarFiltersBtn"
                        class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>
@endsection

@push('scripts')
<script>
    (function () {
        const sidebar = document.getElementById('filtersSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('openFiltersSidebar');
        const closeBtn = document.getElementById('closeFiltersSidebar');
        const applyBtn = document.getElementById('applySidebarFiltersBtn');
        const clearBtn = document.getElementById('clearSidebarFiltersBtn');
        const form = document.getElementById('advancedFiltersForm');
        const baseUrl = @json(route('reports.operational-overview.index'));

        if (!sidebar || !overlay || !openBtn || !closeBtn || !applyBtn || !clearBtn || !form) {
            return;
        }

        const openSidebar = () => {
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeSidebar = () => {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const applyFilters = () => {
            const params = new URLSearchParams();
            const dateFrom = form.querySelector('[name="date_from"]')?.value?.trim() || '';
            const dateTo = form.querySelector('[name="date_to"]')?.value?.trim() || '';
            const months = form.querySelector('[name="months"]')?.value?.trim() || '';

            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            if (months) params.set('months', months);

            window.location.href = params.toString() ? `${baseUrl}?${params}` : baseUrl;
        };

        const clearFilters = () => {
            window.location.href = baseUrl;
        };

        openBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
        applyBtn.addEventListener('click', applyFilters);
        clearBtn.addEventListener('click', clearFilters);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            applyFilters();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !sidebar.classList.contains('translate-x-full')) {
                closeSidebar();
            }
        });
    })();
</script>
@endpush
