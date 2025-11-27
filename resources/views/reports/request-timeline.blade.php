@extends('layouts.app')

@section('title', 'Reporte de Línea de Tiempo')

@section('content')
<div class="bg-white shadow rounded-lg">
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
                <form action="{{ route('reports.timeline.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio:</label>
                        <input type="date" name="start_date" id="start_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                    </div>
                    <div class="flex-1">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin:</label>
                        <input type="date" name="end_date" id="end_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filtrar
                        </button>
                        <a href="{{ route('reports.timeline.index') }}" class="bg-gray-600 text-white hover:bg-gray-700 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-redo mr-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        @if(isset($dateRange))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                <div>
                    <span class="text-blue-800">Mostrando solicitudes del </span>
                    <strong class="text-blue-900">{{ $dateRange['start']->format('d/m/Y') }}</strong>
                    <span class="text-blue-800"> al </span>
                    <strong class="text-blue-900">{{ $dateRange['end']->format('d/m/Y') }}</strong>
                </div>
            </div>
        </div>
        @endif

        <!-- Tabla simplificada -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Título</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Solicitante</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($requests as $request)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                    {{ $request->ticket_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 text-sm">{{ Str::limit($request->title, 50) }}</div>
                                <div class="text-xs text-gray-500">{{ $request->subService->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $request->requester->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                    {{ $request->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('reports.timeline.detail', $request->id) }}" 
                                   class="bg-blue-600 text-white hover:bg-blue-700 px-2 py-1 rounded text-xs font-medium transition-colors inline-flex items-center"
                                   title="Ver Línea de Tiempo">
                                    <i class="fas fa-history mr-1"></i>Timeline
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay solicitudes para mostrar
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
