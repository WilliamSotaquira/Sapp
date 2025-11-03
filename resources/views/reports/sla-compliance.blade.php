@extends('layouts.app')

@section('title', 'Reporte de Cumplimiento SLA')

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
                    <span class="text-gray-500">Cumplimiento SLA</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cumplimiento de Acuerdos de Nivel de Servicio</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.export.pdf', 'sla-compliance') }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.export.excel', 'sla-compliance') }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
        </div>
    </div>

    @if($slaCompliance->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Overall Compliance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Cumplimiento General</h3>
                @php
                    $totalRequests = $slaCompliance->sum('total_requests');
                    $totalCompliant = $slaCompliance->sum('compliant');
                    $overallRate = $totalRequests > 0 ? round(($totalCompliant / $totalRequests) * 100, 2) : 0;
                @endphp
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

            <!-- Best Performing -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Mejor Desempeño</h3>
                @if($slaCompliance->first())
                    @php $best = $slaCompliance->first(); @endphp
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $best['compliance_rate'] }}%</div>
                        <p class="text-gray-800 font-medium mt-1">{{ $best['family'] }}</p>
                        <div class="mt-2 text-sm text-gray-500">
                            {{ $best['compliant'] }} de {{ $best['total_requests'] }} solicitudes
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-center">No hay datos</p>
                @endif
            </div>

            <!-- Worst Performing -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Menor Desempeño</h3>
                @if($slaCompliance->last())
                    @php $worst = $slaCompliance->last(); @endphp
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $worst['compliance_rate'] }}%</div>
                        <p class="text-gray-800 font-medium mt-1">{{ $worst['family'] }}</p>
                        <div class="mt-2 text-sm text-gray-500">
                            {{ $worst['compliant'] }} de {{ $worst['total_requests'] }} solicitudes
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-center">No hay datos</p>
                @endif
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Familia de Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cumplidas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Incumplidas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Cumplimiento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desempeño</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($slaCompliance as $compliance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ $compliance['family'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ $compliance['total_requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-green-600 font-semibold">
                                {{ $compliance['compliant'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold">
                                {{ $compliance['non_compliant'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-semibold {{ $compliance['compliance_rate'] >= 90 ? 'text-green-600' : ($compliance['compliance_rate'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $compliance['compliance_rate'] }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $compliance['compliance_rate'] >= 90 ? 'bg-green-600' : ($compliance['compliance_rate'] >= 80 ? 'bg-yellow-600' : 'bg-red-600') }}"
                                         style="width: {{ $compliance['compliance_rate'] }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-chart-line text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-600 mb-4">No se encontraron solicitudes de servicio en el período seleccionado.</p>
            <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Crear Primera Solicitud
            </a>
        </div>
    @endif
@endsection
