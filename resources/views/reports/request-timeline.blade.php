@extends('layouts.app')

@section('title', 'Reporte de Línea de Tiempo')

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-history text-xl"></i>
                <h1 class="text-xl font-bold">Reporte de Línea de Tiempo de Solicitudes</h1>
            </div>
            <div class="bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-medium">
                Total: {{ $requests->total() }} solicitudes
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Filtros -->
        <div class="bg-gray-50 rounded-lg border border-gray-200 mb-6">
            <div class="p-4">
                <form action="{{ route('reports.request-timeline') }}" method="GET" class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio:</label>
                        <input type="date" name="start_date" id="start_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin:</label>
                        <input type="date" name="end_date" id="end_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md font-medium transition-colors flex-1">
                            <i class="fas fa-filter mr-2"></i>Filtrar
                        </button>
                        <a href="{{ route('reports.request-timeline') }}" class="bg-gray-600 text-white hover:bg-gray-700 px-4 py-2 rounded-md font-medium transition-colors flex-1 text-center">
                            <i class="fas fa-redo mr-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen de Fechas -->
        @if(isset($dateRange))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                <div>
                    <span class="text-blue-800">Mostrando solicitudes del </span>
                    <strong class="text-blue-900">{{ $dateRange['start']->format('d/m/Y') }}</strong>
                    <span class="text-blue-800"> al </span>
                    <strong class="text-blue-900">{{ $dateRange['end']->format('d/m/Y') }}</strong>
                    <span class="text-blue-800"> ({{ $dateRange['start']->diffInDays($dateRange['end']) }} días)</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-4 shadow">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                        <i class="fas fa-tasks text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm opacity-90">Total Solicitudes</div>
                        <div class="text-2xl font-bold">{{ $requests->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-4 shadow">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm opacity-90">Cerradas</div>
                        <div class="text-2xl font-bold">{{ $requests->where('status', 'CERRADA')->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg p-4 shadow">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm opacity-90">En Proceso</div>
                        <div class="text-2xl font-bold">{{ $requests->whereIn('status', ['EN_PROCESO', 'ACEPTADA'])->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg p-4 shadow">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm opacity-90">Vencidas</div>
                        <div class="text-2xl font-bold">
                            {{ $requests->filter(function($request) {
                                return $request->isOverdue();
                            })->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Solicitudes - Versión Compacta para Escritorio -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Solicitud</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Solicitante</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Asignado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Nivel</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Creación</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Duración</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($requests as $request)
                            <tr class="{{ $request->isOverdue() ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                                <!-- Ticket # -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                        #{{ $request->ticket_number }}
                                    </span>
                                </td>

                                <!-- Título y Subservicio -->
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="flex flex-col">
                                        <div class="font-medium text-gray-900 text-sm leading-tight mb-1">
                                            {{ Str::limit($request->title, 35) }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            {{ $request->subService->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Solicitante -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-user text-blue-600 text-xs"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-medium text-gray-900 text-sm truncate">
                                                {{ $request->requester->name ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">
                                                {{ $request->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Asignado a -->
                                <td class="px-4 py-3">
                                    @if($request->assignee)
                                        <div class="flex items-center space-x-2 min-w-0">
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-user-tie text-green-600 text-xs"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-gray-900 text-sm truncate">
                                                    {{ $request->assignee->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">Técnico</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-500 italic text-sm">No asignado</span>
                                    @endif
                                </td>

                                <!-- Nivel de Criticidad -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $priorityColors = [
                                            'BAJA' => 'bg-green-100 text-green-800',
                                            'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                            'ALTA' => 'bg-orange-100 text-orange-800',
                                            'CRITICA' => 'bg-red-100 text-red-800'
                                        ];
                                        $priorityColor = $priorityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800';
                                        $priorityIcons = [
                                            'BAJA' => 'circle',
                                            'MEDIA' => 'circle',
                                            'ALTA' => 'exclamation-triangle',
                                            'CRITICA' => 'exclamation-triangle'
                                        ];
                                    @endphp
                                    <span class="{{ $priorityColor }} px-2 py-1 rounded text-xs font-medium inline-flex items-center">
                                        <i class="fas fa-{{ $priorityIcons[$request->criticality_level] ?? 'circle' }} mr-1 text-xs"></i>
                                        {{ $request->criticality_level }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        @php
                                            $statusColors = [
                                                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                                'ASIGNADA' => 'bg-blue-100 text-blue-800',
                                                'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                                'PAUSADA' => 'bg-gray-100 text-gray-800',
                                                'RESUELTA' => 'bg-green-100 text-green-800',
                                                'CERRADA' => 'bg-gray-200 text-gray-800',
                                                'CANCELADA' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusColor = $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="{{ $statusColor }} px-2 py-1 rounded text-xs font-medium inline-flex items-center whitespace-nowrap">
                                            <i class="fas fa-{{ getStatusIcon($request->status) }} mr-1 text-xs"></i>
                                            {{ $request->status }}
                                        </span>
                                        @if($request->isOverdue())
                                        <div class="text-red-600 text-xs whitespace-nowrap">
                                            <i class="fas fa-clock mr-1"></i>Vencida
                                        </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Fecha Creación -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="font-semibold text-gray-900 text-sm">{{ $request->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $request->created_at->format('H:i') }}</div>
                                    </div>
                                </td>

                                <!-- Duración -->
                                <td class="px-4 py-3">
                                    @php
                                        $totalTime = $request->getTotalResolutionTime();
                                        $timeStats = $request->getTimeStatistics();
                                    @endphp
                                    @if($totalTime)
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 text-xs leading-tight">
                                                {{ $totalTime->format('%d') }}d {{ $totalTime->format('%h') }}h {{ $totalTime->format('%i') }}m
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">
                                                {{ $timeStats['efficiency'] ?? 'N/A' }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-500 italic text-sm">En progreso</span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex space-x-1">
                                        <a href="{{ route('reports.timeline-detail', $request->id) }}"
                                           class="bg-blue-600 text-white hover:bg-blue-700 px-2 py-1 rounded text-xs font-medium transition-colors inline-flex items-center"
                                           title="Ver Línea de Tiempo">
                                            <i class="fas fa-history mr-1"></i>
                                            <span class="hidden sm:inline">Timeline</span>
                                        </a>
                                        <a href="{{ route('service-requests.show', $request->id) }}"
                                           class="bg-gray-600 text-white hover:bg-gray-700 px-2 py-1 rounded text-xs font-medium transition-colors inline-flex items-center"
                                           title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-inbox text-3xl mb-3"></i>
                                        <h4 class="text-lg font-medium mb-2">No hay solicitudes para mostrar</h4>
                                        <p class="mb-4 text-sm">No se encontraron solicitudes en el rango de fechas seleccionado.</p>
                                        <a href="{{ route('reports.request-timeline') }}"
                                           class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md font-medium transition-colors inline-block text-sm">
                                            <i class="fas fa-redo mr-2"></i>Ver Todas
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 space-y-4 sm:space-y-0">
            <div class="text-gray-600 text-sm">
                Mostrando {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }}
                de {{ $requests->total() }} registros
            </div>
            <div>
                {{ $requests->links() }}
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Haga clic en "Timeline" para ver la línea de tiempo detallada de cada solicitud.
                </div>
                <div>
                    <a href="{{ route('reports.index') }}"
                       class="bg-gray-600 text-white hover:bg-gray-700 px-4 py-2 rounded-md font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
function getStatusIcon($status) {
    $icons = [
        'PENDIENTE' => 'clock',
        'ACEPTADA' => 'check-circle',
        'EN_PROCESO' => 'cogs',
        'PAUSADA' => 'pause-circle',
        'RESUELTA' => 'check-double',
        'CERRADA' => 'lock',
        'CANCELADA' => 'times-circle'
    ];
    return $icons[$status] ?? 'question-circle';
}
@endphp

<style>
/* Estilos adicionales para mejorar la visualización en escritorio */
@media (min-width: 1024px) {
    .max-w-xs {
        max-width: 200px;
    }

    table {
        table-layout: fixed;
        width: 100%;
    }

    td, th {
        word-wrap: break-word;
    }
}
</style>
