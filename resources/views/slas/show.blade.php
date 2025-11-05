@extends('layouts.app')

@section('title', $sla->name)

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('slas.index') }}" class="text-blue-600 hover:text-blue-700">SLAs</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">{{ $sla->name }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">{{ $sla->name }}</h2>
                    <p class="text-blue-100 opacity-90">{{ $sla->serviceSubservice->serviceFamily->name ?? 'N/A' }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('slas.edit', $sla) }}"
                       class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded transition">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </a>
                    <a href="{{ route('service-requests.create') }}?sla={{ $sla->id }}"
                       class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded transition">
                        <i class="fas fa-plus mr-2"></i>Nueva Solicitud
                    </a>
                </div>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="p-6 border-b">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Información Básica -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del SLA</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Familia de Servicio:</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $sla->serviceSubservice->serviceFamily->name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Servicio:</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $sla->serviceSubservice->service->name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subservicio:</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $sla->serviceSubservice->subService->name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nivel de Criticidad:</label>
                            @php
                                $criticalityColors = [
                                    'BAJA' => 'bg-green-100 text-green-800 border-green-200',
                                    'MEDIA' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'ALTA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'CRITICA' => 'bg-red-100 text-red-800 border-red-200'
                                ];
                                $criticalityIcons = [
                                    'BAJA' => 'fa-thermometer-empty',
                                    'MEDIA' => 'fa-thermometer-quarter',
                                    'ALTA' => 'fa-thermometer-half',
                                    'CRITICA' => 'fa-thermometer-full'
                                ];
                            @endphp
                            <div class="mt-1 flex items-center">
                                <i class="fas {{ $criticalityIcons[$sla->criticality_level] ?? 'fa-thermometer' }} text-gray-400 mr-2"></i>
                                <span class="px-3 py-1 text-sm font-semibold rounded-full border {{ $criticalityColors[$sla->criticality_level] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $sla->criticality_level }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado:</label>
                            <div class="mt-1">
                                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $sla->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $sla->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Creado:</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $sla->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-500">Hace {{ $sla->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    @if($sla->description)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Descripción:</label>
                        <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded border">{{ $sla->description }}</p>
                    </div>
                    @endif

                    @if($sla->conditions)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Condiciones:</label>
                        <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded border">{{ $sla->conditions }}</p>
                    </div>
                    @endif
                </div>

                <!-- Resumen de Tiempos -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-blue-800 mb-4 text-center">Tiempos de Respuesta</h4>
                    <div class="space-y-4">
                        @php
                            function formatTimeDisplay($minutes) {
                                $hours = floor($minutes / 60);
                                $mins = $minutes % 60;
                                $parts = [];
                                if ($hours > 0) $parts[] = $hours . 'h';
                                if ($mins > 0) $parts[] = $mins . 'm';
                                return $parts ? implode(' ', $parts) : '0m';
                            }
                        @endphp

                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-700">{{ formatTimeDisplay($sla->acceptance_time_minutes) }}</div>
                            <div class="text-sm text-blue-600">Aceptación</div>
                            <div class="text-xs text-blue-500 mt-1">Máximo para aceptar solicitud</div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-700">{{ formatTimeDisplay($sla->response_time_minutes) }}</div>
                            <div class="text-sm text-blue-600">Respuesta Inicial</div>
                            <div class="text-xs text-blue-500 mt-1">Primera respuesta al usuario</div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-700">{{ formatTimeDisplay($sla->resolution_time_minutes) }}</div>
                            <div class="text-sm text-blue-600">Resolución Completa</div>
                            <div class="text-xs text-blue-500 mt-1">Solución definitiva</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Solicitudes Recientes -->
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Solicitudes Recientes ({{ $sla->serviceRequests->count() }})</h3>
                <a href="{{ route('service-requests.create') }}?sla={{ $sla->id }}"
                   class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                    <i class="fas fa-plus mr-1"></i>Nueva Solicitud
                </a>
            </div>

            @if($sla->serviceRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sla->serviceRequests->take(10) as $request)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('service-requests.show', $request) }}"
                                           class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                            {{ $request->ticket_number ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate">{{ $request->title }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $request->requester->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $request->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($sla->serviceRequests->count() > 10)
                    <div class="mt-4 text-center">
                        <a href="{{ route('service-requests.index') }}?sla={{ $sla->id }}"
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Ver todas las {{ $sla->serviceRequests->count() }} solicitudes →
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <i class="fas fa-tasks text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">No hay solicitudes asociadas a este SLA.</p>
                    <a href="{{ route('service-requests.create') }}?sla={{ $sla->id }}"
                       class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Crear Primera Solicitud
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
