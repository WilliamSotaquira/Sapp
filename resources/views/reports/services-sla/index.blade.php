@extends('layouts.app')

@section('title', 'Servicios y SLA')

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
                    <span class="text-gray-500">Servicios y SLA</span>
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

        $exportFilters = array_filter([
            'date_from' => $selectedDateFrom,
            'date_to' => $selectedDateTo,
            'requester_id' => $selectedRequesterId,
            'department' => $selectedDepartment,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Servicios y SLA</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
        </div>
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
            <a href="{{ route('reports.services-sla.export', array_merge(['format' => 'pdf'], $exportFilters)) }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 inline-flex items-center">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.services-sla.export', array_merge(['format' => 'csv'], $exportFilters)) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 inline-flex items-center">
                <i class="fas fa-file-csv mr-2"></i>CSV
            </a>
        </div>
    </div>

    {{-- Active filter badges --}}
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
                    $selectedRequester = $requesters->firstWhere('id', (int) $selectedRequesterId);
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
            <a href="{{ route('reports.services-sla.index') }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
                <i class="fas fa-times mr-1"></i>Limpiar filtros
            </a>
        </div>
    @endif

    {{-- Filters Sidebar --}}
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
                            @foreach($requesters as $requester)
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
                            @foreach($departments as $dep)
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


    @if($slaData->count() > 0 || $performanceData->count() > 0)
        {{-- ================================================================= --}}
        {{-- SLA COMPLIANCE SECTION --}}
        {{-- ================================================================= --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>Cumplimiento de SLA
            </h2>

            @if($slaData->count() > 0)
                {{-- Summary cards --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    @php
                        $totalRequests = $slaData->sum('total_requests');
                        $totalCompliant = $slaData->sum('compliant');
                        $totalOverdue = $slaData->sum('overdue');
                        $overallRate = $totalRequests > 0 ? round(($totalCompliant / $totalRequests) * 100, 2) : 0;
                    @endphp
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Cumplimiento General</h3>
                        <div class="text-center">
                            <div class="text-4xl font-bold {{ $overallRate >= 90 ? 'text-green-600' : ($overallRate >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $overallRate }}%
                            </div>
                            <p class="text-gray-600 mt-2">Tasa de cumplimiento general</p>
                            <div class="mt-4 text-sm text-gray-500">
                                {{ $totalCompliant }} de {{ $totalRequests }} solicitudes cumplieron con el SLA
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Solicitudes Cumplidas</h3>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-green-600">{{ $totalCompliant }}</div>
                            <p class="text-gray-600 mt-2">Dentro del plazo SLA</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Solicitudes Vencidas</h3>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-red-600">{{ $totalOverdue }}</div>
                            <p class="text-gray-600 mt-2">Fuera del plazo SLA</p>
                        </div>
                    </div>
                </div>

                {{-- SLA Compliance Table --}}
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Familia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cumplidas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencidas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($slaData as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                        {{ $item['service_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        {{ $item['family'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                        {{ $item['total_requests'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-600 font-semibold">
                                        {{ $item['compliant'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold">
                                        {{ $item['overdue'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="font-semibold {{ $item['compliance_rate'] >= 90 ? 'text-green-600' : ($item['compliance_rate'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $item['compliance_rate'] }}%
                                            </span>
                                            <div class="ml-3 w-20 bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full {{ $item['compliance_rate'] >= 90 ? 'bg-green-600' : ($item['compliance_rate'] >= 80 ? 'bg-yellow-600' : 'bg-red-600') }}"
                                                     style="width: {{ $item['compliance_rate'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <p class="text-gray-500">No hay datos de cumplimiento SLA para los filtros seleccionados.</p>
                </div>
            @endif
        </div>

        {{-- ================================================================= --}}
        {{-- SERVICE PERFORMANCE SECTION --}}
        {{-- ================================================================= --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-tachometer-alt text-purple-600 mr-2"></i>Rendimiento de Servicios
            </h2>

            @if($performanceData->count() > 0)
                {{-- Summary cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-list-alt text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Solicitudes</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ $performanceData->sum('total_requests') }}
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
                                <p class="text-sm font-medium text-gray-600">Promedio Resolución</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ round($performanceData->avg('avg_resolution_hours') ?? 0, 1) }} hrs
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
                                    {{ $performanceData->sum('resolved_count') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Performance Table --}}
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Familia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Promedio Horas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resueltas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($performanceData as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                        {{ $item->service_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        {{ $item->family_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                        {{ $item->total_requests }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold {{ ($item->avg_resolution_hours ?? 0) < 4 ? 'text-green-600' : (($item->avg_resolution_hours ?? 0) < 8 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ round($item->avg_resolution_hours ?? 0, 1) }} hrs
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="font-semibold text-blue-600">
                                                {{ $item->resolved_count }}
                                            </span>
                                            @if($item->total_requests > 0)
                                                <span class="ml-1 text-sm text-gray-500">
                                                    ({{ round(($item->resolved_count / $item->total_requests) * 100, 1) }}%)
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <p class="text-gray-500">No hay datos de rendimiento para los filtros seleccionados.</p>
                </div>
            @endif
        </div>
    @else
        {{-- Empty state --}}
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-chart-bar text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron datos para los criterios seleccionados. Intente ajustar los filtros o el rango de fechas.</p>
            <a href="{{ route('reports.services-sla.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Ver sin filtros
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
        const baseUrl = @json(route('reports.services-sla.index'));

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
            const dateFrom = form.querySelector('[name="date_from"]')?.value?.trim() || '';
            const dateTo = form.querySelector('[name="date_to"]')?.value?.trim() || '';
            const requesterId = form.querySelector('[name="requester_id"]')?.value?.trim() || '';
            const department = form.querySelector('[name="department"]')?.value?.trim() || '';

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
