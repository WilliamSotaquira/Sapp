@extends('layouts.app')

@section('title', 'Reporte por Estado de Solicitudes')

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
                    <span class="text-gray-500">Solicitudes por Estado</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Solicitudes por Estado</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.export.pdf', 'requests-by-status') }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', 'requests-by-status') }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    @if($totalRequests > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Statistics Cards -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Resumen General</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Solicitudes:</span>
                        <span class="font-semibold">{{ $totalRequests }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solicitudes Activas:</span>
                        <span class="font-semibold text-orange-600">
                            {{ ($requestsByStatus['PENDIENTE']->count ?? 0) + ($requestsByStatus['ACEPTADA']->count ?? 0) + ($requestsByStatus['EN_PROCESO']->count ?? 0) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solicitudes Finalizadas:</span>
                        <span class="font-semibold text-green-600">
                            {{ ($requestsByStatus['CERRADA']->count ?? 0) + ($requestsByStatus['RESUELTA']->count ?? 0) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tasa de Finalización:</span>
                        <span class="font-semibold text-blue-600">
                            @php
                                $completed = ($requestsByStatus['CERRADA']->count ?? 0) + ($requestsByStatus['RESUELTA']->count ?? 0);
                                $rate = $totalRequests > 0 ? round(($completed / $totalRequests) * 100, 1) : 0;
                            @endphp
                            {{ $rate }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Distribución por Estado</h3>
                <div class="space-y-3">
                    @php
                        $statusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                            'RESUELTA' => 'bg-green-100 text-green-800',
                            'CERRADA' => 'bg-gray-100 text-gray-800',
                            'CANCELADA' => 'bg-red-100 text-red-800'
                        ];
                    @endphp

                    @foreach($requestsByStatus as $status => $data)
                        <div class="flex justify-between items-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $status }}
                            </span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold">{{ $data->count }}</span>
                                <span class="text-sm text-gray-500">
                                    ({{ $totalRequests > 0 ? round(($data->count / $totalRequests) * 100, 1) : 0 }}%)
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Porcentaje</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barra de Progreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($requestsByStatus as $status => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                {{ $data->count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                {{ $totalRequests > 0 ? round(($data->count / $totalRequests) * 100, 1) : 0 }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full
                                        @if($status === 'PENDIENTE') bg-yellow-500
                                        @elseif($status === 'ACEPTADA') bg-blue-500
                                        @elseif($status === 'EN_PROCESO') bg-purple-500
                                        @elseif($status === 'RESUELTA') bg-green-500
                                        @elseif($status === 'CERRADA') bg-gray-500
                                        @elseif($status === 'CANCELADA') bg-red-500
                                        @else bg-gray-400 @endif"
                                         style="width: {{ $totalRequests > 0 ? ($data->count / $totalRequests) * 100 : 0 }}%">
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
            <i class="fas fa-chart-pie text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron solicitudes de servicio en el período seleccionado.</p>
            <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Crear Primera Solicitud
            </a>
        </div>
    @endif
@endsection
