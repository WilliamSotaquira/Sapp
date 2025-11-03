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
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rendimiento de Servicios</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.export.pdf', 'service-performance') }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', 'service-performance') }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

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
                            {{ round($servicePerformance->avg('avg_resolution_time'), 1) }} min
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-star text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Satisfacción Promedio</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ round($servicePerformance->avg('avg_satisfaction'), 1) }}/5
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo Resolución (min)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satisfacción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($servicePerformance as $performance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ $performance['family'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ $performance['services_count'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ $performance['total_requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold {{ $performance['avg_resolution_time'] < 240 ? 'text-green-600' : ($performance['avg_resolution_time'] < 480 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $performance['avg_resolution_time'] }} min
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-semibold {{ $performance['avg_satisfaction'] >= 4 ? 'text-green-600' : ($performance['avg_satisfaction'] >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $performance['avg_satisfaction'] }}/5
                                    </span>
                                    <div class="ml-2 flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $performance['avg_satisfaction'] ? 'text-yellow-400' : 'text-gray-300' }} text-sm"></i>
                                        @endfor
                                    </div>
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
