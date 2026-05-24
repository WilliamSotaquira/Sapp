@extends('layouts.app')

@section('title', 'Informes')

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
    <p class="text-gray-600 text-sm sm:text-base">Genera reportes y análisis de los servicios y solicitudes</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- 1. Cortes -->
    <a href="{{ route('reports.cuts.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-blue-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Cortes</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Agrupa solicitudes por periodo según actividad</p>
            </div>
            <div class="bg-blue-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-layer-group text-blue-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Incluye exportación PDF por corte</span>
        </div>
    </a>

    <!-- 2. Informe Analítico por Corte -->
    <a href="{{ route('reports.cuts.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-purple-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Informe Analítico por Corte</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Resumen, distribuciones, hallazgos y recomendaciones por corte</p>
            </div>
            <div class="bg-purple-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-chart-column text-purple-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Ingresa por Cortes y luego abre el informe del corte deseado</span>
        </div>
    </a>

    <!-- 3. Línea de Tiempo -->
    <a href="{{ route('reports.timeline.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-green-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Línea de Tiempo</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Cronología de eventos y métricas de tiempo por solicitud</p>
            </div>
            <div class="bg-green-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-history text-green-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Búsqueda por ticket y exportación PDF/Excel</span>
        </div>
    </a>

    <!-- 4. Reporte por Rango de Tiempo -->
    <a href="{{ route('reports.time-range.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-orange-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Reporte por Rango de Tiempo</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Análisis detallado por periodo con evidencias incluidas</p>
            </div>
            <div class="bg-orange-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-calendar-alt text-orange-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Soporte de cortes y familias, exporta PDF/Excel/ZIP</span>
        </div>
    </a>

    <!-- 5. Servicios y SLA -->
    <a href="{{ route('reports.services-sla.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-teal-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Servicios y SLA</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Cumplimiento de SLA y rendimiento por servicio</p>
            </div>
            <div class="bg-teal-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-chart-line text-teal-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Filtros por fecha, solicitante y departamento</span>
        </div>
    </a>

    <!-- 6. Panorama Operativo -->
    <a href="{{ route('reports.operational-overview.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-red-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Panorama Operativo</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Estado, criticidad y tendencias mensuales en un solo lugar</p>
            </div>
            <div class="bg-red-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-chart-pie text-red-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Distribución por estado, criticidad y tendencias</span>
        </div>
    </a>

    <!-- 7. Búsqueda y Análisis -->
    <a href="{{ route('reports.search-analysis.index') }}" class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-indigo-500">
        <div class="flex items-center justify-between flex-wrap sm:flex-nowrap gap-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Búsqueda y Análisis</h3>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">Busca solicitudes por términos y tipos de servicio</p>
            </div>
            <div class="bg-indigo-100 p-2.5 sm:p-3 rounded-full flex-shrink-0">
                <i class="fas fa-search text-indigo-700 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="mt-3 sm:mt-4">
            <span class="text-xs sm:text-sm text-gray-500">Búsqueda por términos con resumen y exportación</span>
        </div>
    </a>
</div>
@endsection
