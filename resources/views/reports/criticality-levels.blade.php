@extends('layouts.app')

@section('title', 'Reporte por Niveles de Criticidad')

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
                    <span class="text-gray-500">Niveles de Criticidad</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Niveles de Criticidad</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.export.pdf', 'criticality-levels') }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', 'criticality-levels') }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    @php
        $totalRequests = $criticalityData->isNotEmpty() ? $criticalityData->sum('count') : 0;
        $criticalityColors = [
            'BAJA' => 'bg-green-100 text-green-800',
            'MEDIA' => 'bg-yellow-100 text-yellow-800',
            'ALTA' => 'bg-orange-100 text-orange-800',
            'CRITICA' => 'bg-red-100 text-red-800'
        ];
    @endphp

    @if($criticalityData->isNotEmpty() && $totalRequests > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            @foreach($criticalityData as $level => $data)
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-2xl font-bold {{
                        $level === 'BAJA' ? 'text-green-600' :
                        ($level === 'MEDIA' ? 'text-yellow-600' :
                        ($level === 'ALTA' ? 'text-orange-600' : 'text-red-600'))
                    }}">
                        {{ $data->count }}
                    </div>
                    <div class="mt-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$level] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $level }}
                        </span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        {{ $totalRequests > 0 ? round(($data->count / $totalRequests) * 100, 1) : 0 }}%
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nivel de Criticidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Porcentaje</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Distribución</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($criticalityData as $level => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$level] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $level }}
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
                                        @if($level === 'BAJA') bg-green-500
                                        @elseif($level === 'MEDIA') bg-yellow-500
                                        @elseif($level === 'ALTA') bg-orange-500
                                        @elseif($level === 'CRITICA') bg-red-500
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
            <i class="fas fa-exclamation-triangle text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron solicitudes de servicio en el período seleccionado.</p>
            <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Crear Primera Solicitud
            </a>
        </div>
    @endif
@endsection
