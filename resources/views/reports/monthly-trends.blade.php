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
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tendencias Mensuales</h1>
            <p class="text-gray-600">Evolución de métricas en los últimos 6 meses</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.export.pdf', 'monthly-trends') }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', 'monthly-trends') }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    @if($trends->count() > 0)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-blue-600">
                    {{ $trends->sum('total_requests') }}
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Total Solicitudes (6 meses)</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600">
                    {{ round($trends->avg('completion_rate'), 1) }}%
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Tasa Finalización Promedio</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-purple-600">
                    {{ round($trends->avg('avg_satisfaction'), 1) }}/5
                </div>
                <p class="text-sm font-medium text-gray-600 mt-1">Satisfacción Promedio</p>
            </div>
        </div>

        <!-- Trends Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solicitudes Cerradas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Finalización</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satisfacción</th>
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
                                <div class="flex items-center">
                                    <span class="font-semibold {{ $trend['avg_satisfaction'] >= 4 ? 'text-green-600' : ($trend['avg_satisfaction'] >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $trend['avg_satisfaction'] }}/5
                                    </span>
                                    <div class="ml-2 flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $trend['avg_satisfaction'] ? 'text-yellow-400' : 'text-gray-300' }} text-sm"></i>
                                        @endfor
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Simple Chart Visualization -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Evolución de Solicitudes</h3>
            <div class="space-y-4">
                @foreach($trends as $trend)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium">{{ $trend['month_name'] }}</span>
                            <span>{{ $trend['total_requests'] }} solicitudes</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full"
                                 style="width: {{ ($trend['total_requests'] / max($trends->max('total_requests'), 1)) * 100 }}%">
                            </div>
                        </div>
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
@endsection
