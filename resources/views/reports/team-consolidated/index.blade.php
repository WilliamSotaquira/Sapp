@extends('layouts.app')

@section('title', 'Consolidado del Equipo')

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
                    <span class="text-gray-500">Consolidado del Equipo</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:justify-between lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Consolidado del Equipo</h1>
            <p class="text-gray-600">
                Período: {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
                Separa lo <strong>resuelto por el equipo</strong> (ejecución) de lo <strong>verificado por el líder</strong> (control).
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reports.team-consolidated.export', ['format' => 'pdf', 'date_from' => $dateRange['start']->format('Y-m-d'), 'date_to' => $dateRange['end']->format('Y-m-d')]) }}"
               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 inline-flex items-center">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
            <a href="{{ route('reports.team-consolidated.export', ['format' => 'csv', 'date_from' => $dateRange['start']->format('Y-m-d'), 'date_to' => $dateRange['end']->format('Y-m-d')]) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 inline-flex items-center">
                <i class="fas fa-file-csv mr-2"></i>CSV
            </a>
        </div>
    </div>

    {{-- Filtro por fechas --}}
    <form method="GET" action="{{ route('reports.team-consolidated.index') }}" class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" name="date_from" value="{{ $dateRange['start']->format('Y-m-d') }}"
                       class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" name="date_to" value="{{ $dateRange['end']->format('Y-m-d') }}"
                       class="mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Aplicar
            </button>
        </div>
    </form>

    {{-- Tarjetas de totales --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $totals['technicians'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Técnicos con actividad</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $totals['total_assigned'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Solicitudes asignadas</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-700">{{ $totals['total_resolved'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Resueltas por el equipo</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-indigo-700">{{ $totals['control_tasks_completed'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Controles completados</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-indigo-700">{{ $totals['requests_verified'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Solicitudes verificadas</div>
        </div>
    </div>

    {{-- Ejecución por técnico --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-users text-green-600 mr-2"></i>Ejecución por técnico
            </h2>
            <p class="text-sm text-gray-500">Trabajo resuelto por cada técnico (su mérito).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Técnico</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Asignadas</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Resueltas</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cerradas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($executionByTechnician as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $row->technician_name }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $row->total_assigned }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-green-700">{{ $row->total_resolved }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $row->total_closed }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin actividad de técnicos en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Control del líder --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-user-shield text-indigo-600 mr-2"></i>Control del líder
            </h2>
            <p class="text-sm text-gray-500">Verificación y seguimiento ejecutados por el líder del proceso.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Líder</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Controles completados</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Solicitudes verificadas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($controlByLeader as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $row->leader_name }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-indigo-700">{{ $row->control_tasks_completed }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $row->requests_verified }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">Sin controles registrados en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
