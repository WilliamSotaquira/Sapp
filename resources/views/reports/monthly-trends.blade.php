@extends('layouts.app')

@section('title', 'Tendencias Mensuales')

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
                    <span class="text-gray-500">Tendencias Mensuales</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $selectedMonths = (int) ($months ?? request('months', 12));
        $availableMonths = collect($allowedMonths ?? [3, 6, 12, 24]);
        if (!$availableMonths->contains($selectedMonths)) {
            $selectedMonths = 12;
        }

        $maxTotalRequests = max(1, (int) $trends->max('total_requests'));
        $avgCompletionRate = round($trends->avg('completion_rate') ?? 0, 1);
        $avgResolutionHours = round($trends->avg('avg_resolution_hours') ?? 0, 1);
        $avgMonthlyRequests = $trends->count() > 0 ? round($trends->avg('total_requests'), 1) : 0;
        $exportFilters = ['months' => $selectedMonths];
    @endphp

    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tendencias Mensuales</h1>
            <p class="text-gray-600">Evolución de métricas en los últimos {{ $selectedMonths }} meses</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="openFiltersSidebar"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                <i class="fas fa-sliders-h mr-2"></i>Filtros
            </button>
            <a href="{{ route('reports.export.pdf', array_merge(['reportType' => 'monthly-trends'], $exportFilters)) }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', array_merge(['reportType' => 'monthly-trends'], $exportFilters)) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
            <i class="fas fa-calendar-alt mr-1"></i>Rango: {{ $selectedMonths }} meses
        </span>
        <a href="{{ route('reports.monthly-trends') }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
            <i class="fas fa-times mr-1"></i>Restablecer
        </a>
    </div>

    @if($trends->count() > 0)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-blue-600">
                    {{ $trends->sum('total_requests') }}
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Total Solicitudes</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600">
                    {{ $avgCompletionRate }}%
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Cumplimiento Promedio</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-indigo-600">
                    {{ $avgMonthlyRequests }}
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Promedio Solicitudes / Mes</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-purple-600">
                    {{ $avgResolutionHours }} h
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Tiempo Promedio Resolución</p>
            </div>
        </div>

        <!-- Trends Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Detalle por mes</h3>
                <p class="text-sm text-gray-500">Comparativo de carga y cierre mensual</p>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solicitudes Completadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Finalización</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo Resolución</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($trends as $trend)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ $trend['month_name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $trend['total_requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $trend['closed_requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold {{ $trend['completion_rate'] >= 80 ? 'text-green-600' : ($trend['completion_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $trend['completion_rate'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold {{ $trend['avg_resolution_hours'] <= 8 ? 'text-green-600' : ($trend['avg_resolution_hours'] <= 16 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $trend['avg_resolution_hours'] }} h
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <!-- Simple Chart Visualization -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Evolución Mensual</h3>
            <div class="space-y-4">
                @foreach($trends as $trend)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium">{{ $trend['month_name'] }}</span>
                            <span>{{ $trend['closed_requests'] }}/{{ $trend['total_requests'] }} completadas</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full"
                                 style="width: {{ ($trend['total_requests'] / $maxTotalRequests) * 100 }}%">
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                            <div class="bg-emerald-500 h-2 rounded-full"
                                 style="width: {{ $trend['completion_rate'] }}%">
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Cumplimiento: {{ $trend['completion_rate'] }}%</div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-chart-line text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron datos históricos para mostrar tendencias.</p>
            <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Crear Primera Solicitud
            </a>
        </div>
    @endif

    <div id="filtersSidebar"
         class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
        <div class="flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-2"></i>Filtros
                    </h3>
                    <button type="button" id="closeFiltersSidebar" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-blue-100 text-xs mt-1">Configura la ventana de análisis</p>
            </div>

            <div class="flex-1 px-6 py-4 space-y-4">
                <form id="advancedFiltersForm" class="space-y-4">
                    <div>
                        <label for="monthsFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Rango de tiempo</label>
                        <select id="monthsFilterAdv" name="months"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($availableMonths as $option)
                                <option value="{{ $option }}" {{ (int) $selectedMonths === (int) $option ? 'selected' : '' }}>
                                    Últimos {{ $option }} meses
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
        const baseUrl = @json(route('reports.monthly-trends'));

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
            const months = form.querySelector('[name="months"]')?.value?.trim() || '';
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
