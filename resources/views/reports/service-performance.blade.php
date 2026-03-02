@extends('layouts.app')

@section('title', 'Reporte de Rendimiento de Servicios')

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
                    <span class="text-gray-500">Rendimiento de Servicios</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $selectedDateFrom = request('date_from');
        $selectedDateTo = request('date_to');
        $hasDateFilter = ($selectedDateFrom !== null && $selectedDateFrom !== '') || ($selectedDateTo !== null && $selectedDateTo !== '');
        $selectedRequesterId = request('requester_id');
        $selectedDepartment = request('department');

        $activeFilterCount = collect([
            $hasDateFilter ? '1' : null,
            $selectedRequesterId,
            $selectedDepartment,
        ])->filter(fn ($value) => $value !== null && $value !== '')->count();
    @endphp

    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rendimiento de Servicios</h1>
            @if($hasDateFilter)
                <p class="text-gray-600">
                    Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
                </p>
            @endif
        </div>
        @php
            $exportFilters = array_filter([
                'date_from' => $selectedDateFrom,
                'date_to' => $selectedDateTo,
                'requester_id' => $selectedRequesterId,
                'department' => $selectedDepartment,
            ], fn ($value) => $value !== null && $value !== '');
        @endphp
        <div class="flex flex-wrap gap-2">
            <button type="button" id="openFiltersSidebar"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                <i class="fas fa-sliders-h mr-2"></i>Filtros
                @if($activeFilterCount > 0)
                    <span class="ml-2 inline-flex items-center justify-center min-w-5 h-5 px-1 text-xs font-semibold bg-white text-blue-700 rounded-full">
                        {{ $activeFilterCount }}
                    </span>
                @endif
            </button>
            <a href="{{ route('reports.export.pdf', array_merge(['reportType' => 'service-performance'], $exportFilters)) }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', array_merge(['reportType' => 'service-performance'], $exportFilters)) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    @if($activeFilterCount > 0)
        <div class="mb-6 flex flex-wrap gap-2">
            @if($hasDateFilter)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
                </span>
            @endif
            @if($selectedRequesterId !== null && $selectedRequesterId !== '')
                @php
                    $selectedRequester = collect($requesters ?? [])->firstWhere('id', (int) $selectedRequesterId);
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <i class="fas fa-user mr-1"></i>
                    {{ $selectedRequester->name ?? 'Solicitante' }}
                </span>
            @endif
            @if($selectedDepartment)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fas fa-building mr-1"></i>
                    {{ $selectedDepartment }}
                </span>
            @endif
            <a href="{{ route('reports.service-performance') }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
                <i class="fas fa-times mr-1"></i>Limpiar filtros
            </a>
        </div>
    @endif

    <div id="filtersSidebar"
         class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
        <div class="flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-2"></i>Filtros Avanzados
                    </h3>
                    <button type="button" id="closeFiltersSidebar" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-blue-100 text-xs mt-1">Personaliza tu reporte</p>
            </div>

            <div class="flex-1 px-6 py-4 space-y-6">
                <form id="advancedFiltersForm" class="space-y-4">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Rango de Fechas</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="dateFromFilterAdv" class="block text-xs text-gray-600 mb-1">Desde</label>
                                <input id="dateFromFilterAdv" name="date_from" value="{{ $selectedDateFrom }}" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="dateToFilterAdv" class="block text-xs text-gray-600 mb-1">Hasta</label>
                                <input id="dateToFilterAdv" name="date_to" value="{{ $selectedDateTo }}" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="requesterFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Solicitante</label>
                        <select id="requesterFilterAdv" name="requester_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos los solicitantes</option>
                            @foreach(($requesters ?? collect()) as $requester)
                                <option value="{{ $requester->id }}" {{ (string)$selectedRequesterId === (string)$requester->id ? 'selected' : '' }}>
                                    {{ $requester->name }}{{ $requester->email ? ' - ' . $requester->email : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="departmentFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Dependencia</label>
                        <select id="departmentFilterAdv" name="department"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las dependencias</option>
                            @foreach(($departments ?? collect()) as $dep)
                                <option value="{{ $dep }}" {{ $selectedDepartment === $dep ? 'selected' : '' }}>
                                    {{ $dep }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

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

    @if($servicePerformance->count() > 0)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-list-alt text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Solicitudes</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $servicePerformance->sum('total_requests') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-clock text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tiempo Promedio Resolución</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ round($servicePerformance->avg('avg_resolution_hours') * 60, 1) }} min
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Solicitudes Resueltas</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $servicePerformance->sum('resolved_count') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Familia de Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo Resolución (horas)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solicitudes Resueltas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($servicePerformance as $performance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ $performance->family_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ $performance->service_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ $performance->total_requests }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold {{ $performance->avg_resolution_hours < 4 ? 'text-green-600' : ($performance->avg_resolution_hours < 8 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ round($performance->avg_resolution_hours, 1) }} hrs
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-semibold text-blue-600">
                                        {{ $performance->resolved_count }}
                                    </span>
                                    <span class="ml-1 text-sm text-gray-500">
                                        ({{ round(($performance->resolved_count / $performance->total_requests) * 100, 1) }}%)
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-cogs text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron datos de rendimiento en el período seleccionado.</p>
            <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Crear Primera Solicitud
            </a>
        </div>
    @endif
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
        const baseUrl = @json(route('reports.service-performance'));

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

        const buildParams = () => {
            const params = new URLSearchParams();
            const dateFrom = form.querySelector('[name=\"date_from\"]')?.value?.trim() || '';
            const dateTo = form.querySelector('[name=\"date_to\"]')?.value?.trim() || '';
            const requesterId = form.querySelector('[name=\"requester_id\"]')?.value?.trim() || '';
            const department = form.querySelector('[name=\"department\"]')?.value?.trim() || '';

            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            if (requesterId) params.set('requester_id', requesterId);
            if (department) params.set('department', department);

            return params;
        };

        const applyFilters = () => {
            const params = buildParams();
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
