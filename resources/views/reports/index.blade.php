@extends('layouts.app')

@section('title', 'Módulo de Informes')

@section('breadcrumb')
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Informes</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mb-4 sm:mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Módulo de Informes</h1>
    <p class="text-gray-600 mt-1 sm:mt-2 text-sm sm:text-base">Genera reportes y análisis de los servicios y solicitudes</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    <!-- Cortes Card - NUEVO -->
    <a href="{{ route('reports.cuts.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-slate-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Cortes</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Agrupa solicitudes por periodo según actividad</p>
            </div>
            <div class="bg-slate-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-layer-group text-slate-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Incluye exportación PDF por corte</span>
        </div>
    </a>

    <!-- Timeline por Ticket Card - NUEVO -->
    <a href="{{ route('reports.timeline.by-ticket') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-red-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Timeline por Ticket</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Descarga timeline ingresando número de solicitud</p>
            </div>
            <div class="bg-red-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-ticket-alt text-red-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Búsqueda rápida por número</span>
        </div>
    </a>

    <!-- Time Range Report Card - NUEVO -->
    <a href="{{ route('reports.time-range.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-teal-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Reporte por Rango de Tiempo</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Análisis detallado por periodo con evidencias incluidas</p>
            </div>
            <div class="bg-teal-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-calendar-alt text-teal-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <span class="text-xs sm:text-sm text-gray-500">Incluye ZIP con evidencias</span>
                <div class="flex flex-wrap gap-1">
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-file-pdf mr-0.5 sm:mr-1"></i>PDF
                    </span>
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-file-excel mr-0.5 sm:mr-1"></i>Excel
                    </span>
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-file-archive mr-0.5 sm:mr-1"></i>ZIP
                    </span>
                </div>
            </div>
        </div>
    </a>

    <!-- SLA Compliance Card -->
    <a href="{{ route('reports.sla-compliance') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Cumplimiento de SLA</h3>
                <p class="text-gray-600 text-sm mt-1">Tasas de cumplimiento de acuerdos de nivel de servicio</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-chart-line text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Análisis por familia de servicio</span>
        </div>
    </a>

    <!-- Requests by Status Card -->
    <a href="{{ route('reports.requests-by-status') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Solicitudes por Estado</h3>
                <p class="text-gray-600 text-sm mt-1">Distribución de solicitudes según su estado actual</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-chart-pie text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Vista general del workflow</span>
        </div>
    </a>

    <!-- Criticality Levels Card -->
    <a href="{{ route('reports.criticality-levels') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Niveles de Criticidad</h3>
                <p class="text-gray-600 text-sm mt-1">Análisis por nivel de urgencia y tiempos de respuesta</p>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
                <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Métricas de tiempo por criticidad</span>
        </div>
    </a>

    <!-- Service Performance Card -->
    <a href="{{ route('reports.service-performance') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Rendimiento de Servicios</h3>
                <p class="text-gray-600 text-sm mt-1">Desempeño por familia y servicio</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-cogs text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Tiempos y satisfacción</span>
        </div>
    </a>

    <!-- Línea de Tiempo Card - NUEVO -->
    <a href="{{ route('reports.timeline.index') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-teal-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Línea de Tiempo</h3>
                <p class="text-gray-600 text-sm mt-1">Análisis temporal de solicitudes y eventos</p>
            </div>
            <div class="bg-teal-100 p-3 rounded-full">
                <i class="fas fa-history text-teal-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Cronología y métricas de tiempo</span>
        </div>
    </a>

    <!-- Monthly Trends Card -->
    <a href="{{ route('reports.monthly-trends') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Tendencias Mensuales</h3>
                <p class="text-gray-600 text-sm mt-1">Evolución histórica de métricas clave</p>
            </div>
            <div class="bg-indigo-100 p-3 rounded-full">
                <i class="fas fa-chart-bar text-indigo-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <span class="text-sm text-gray-500">Análisis de últimos 6 meses</span>
        </div>
    </a>

    <!-- Quick Stats Card -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Estadísticas Rápidas</h3>
                <p class="text-gray-600 text-sm mt-1">Resumen general del sistema</p>
            </div>
            <div class="bg-gray-100 p-3 rounded-full">
                <i class="fas fa-tachometer-alt text-gray-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 space-y-2">
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Total Solicitudes:</span>
                <span class="font-semibold">{{ \App\Models\ServiceRequest::count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Solicitudes Activas:</span>
                <span class="font-semibold text-orange-600">
                    {{ \App\Models\ServiceRequest::whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO'])->count() }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Tasa de Finalización:</span>
                <span class="font-semibold text-green-600">
                    @php
                    $total = \App\Models\ServiceRequest::count();
                    $completed = \App\Models\ServiceRequest::whereIn('status', ['CERRADA', 'RESUELTA'])->count();
                    $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                    @endphp
                    {{ $rate }}%
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Solicitudes con Timeline:</span>
                <span class="font-semibold text-teal-600">
                    {{ \App\Models\ServiceRequest::whereNotNull('accepted_at')->orWhereNotNull('resolved_at')->orWhereNotNull('closed_at')->count() }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="mt-6 sm:mt-8 bg-white rounded-lg shadow-md p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Filtros Globales</h3>
    <form id="globalFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <div>
            <label for="start_date" class="block text-xs sm:text-sm font-medium text-gray-700">Fecha Inicio</label>
            <input type="date" id="start_date" name="start_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-1.5 sm:p-2 text-sm">
        </div>
        <div>
            <label for="end_date" class="block text-xs sm:text-sm font-medium text-gray-700">Fecha Fin</label>
            <input type="date" id="end_date" name="end_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-1.5 sm:p-2 text-sm">
        </div>
        <div class="flex items-end">
            <button type="button" onclick="applyGlobalFilter()" class="w-full sm:w-auto bg-blue-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded hover:bg-blue-700 text-sm sm:text-base">
                Aplicar Filtro
            </button>
        </div>
    </form>
</div>

<!-- Nuevas Funcionalidades Section -->
<div class="mt-6 sm:mt-8 bg-white rounded-lg shadow-md p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-teal-700">
        <i class="fas fa-star mr-2"></i>Nueva Funcionalidad - Línea de Tiempo
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <div class="bg-teal-50 rounded-lg p-3 sm:p-4 border border-teal-200">
            <h4 class="font-semibold text-teal-800 mb-2 text-sm sm:text-base">
                <i class="fas fa-chart-line mr-2"></i>Análisis Temporal Avanzado
            </h4>
            <ul class="text-xs sm:text-sm text-teal-700 space-y-1">
                <li>• Cronología completa de eventos por solicitud</li>
                <li>• Métricas de tiempo y eficiencia</li>
                <li>• Distribución de tiempos por estado</li>
                <li>• Cumplimiento de tiempos vs SLA</li>
            </ul>
        </div>
        <div class="bg-blue-50 rounded-lg p-3 sm:p-4 border border-blue-200">
            <h4 class="font-semibold text-blue-800 mb-2 text-sm sm:text-base">
                <i class="fas fa-download mr-2"></i>Exportación de Reportes
            </h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Exportación a PDF con formato profesional</li>
                <li>• Exportación a Excel/CSV para análisis</li>
                <li>• Reportes individuales por solicitud</li>
                <li>• Filtros por rango de fechas</li>
            </ul>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="{{ route('reports.timeline.index') }}" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition font-semibold">
            <i class="fas fa-history mr-2"></i>Explorar Línea de Tiempo
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function applyGlobalFilter() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Store in sessionStorage for use in other reports
        if (startDate) sessionStorage.setItem('report_start_date', startDate);
        if (endDate) sessionStorage.setItem('report_end_date', endDate);

        alert('Filtro aplicado. Los reportes usarán este rango de fechas.');
    }

    // Load stored dates on page load
    document.addEventListener('DOMContentLoaded', function() {
        const storedStart = sessionStorage.getItem('report_start_date');
        const storedEnd = sessionStorage.getItem('report_end_date');

        if (storedStart) document.getElementById('start_date').value = storedStart;
        if (storedEnd) document.getElementById('end_date').value = storedEnd;
    });
</script>
@endpush
