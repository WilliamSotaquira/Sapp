@extends('layouts.app')

@section('title', $subService->name)

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('sub-services.index') }}" class="text-blue-600 hover:text-blue-700">Sub-Servicios</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">{{ $subService->name }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-6">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <div class="h-12 w-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-list-alt text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ $subService->name }}</h1>
                            <div class="flex items-center space-x-4 mt-1">
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-hashtag mr-1"></i>{{ $subService->code }}
                                </span>
                                @if($subService->cost)
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-dollar-sign mr-1"></i>{{ number_format($subService->cost, 2) }}
                                </span>
                                @endif
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-sort-numeric-up mr-1"></i>Orden: {{ $subService->order }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2 mt-4 md:mt-0">
                    <a href="{{ route('sub-services.edit', $subService) }}"
                       class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded transition duration-150 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </a>
                    <a href="{{ route('service-requests.create') }}?sub_service={{ $subService->id }}"
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition duration-150 flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nueva Solicitud
                    </a>
                </div>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Servicio Padre -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-cog text-blue-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Servicio Padre</h3>
                    </div>
                    <p class="text-lg font-medium text-gray-900">{{ $subService->service->name }}</p>
                    <p class="text-sm text-gray-500">Código: {{ $subService->service->code }}</p>
                    <a href="{{ route('services.show', $subService->service) }}"
                       class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-flex items-center">
                        <i class="fas fa-external-link-alt mr-1"></i>Ver servicio
                    </a>
                </div>

                <!-- Familia -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-layer-group text-green-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Familia</h3>
                    </div>
                    <p class="text-lg font-medium text-gray-900">{{ $subService->service->family->name }}</p>
                    <p class="text-sm text-gray-500">Código: {{ $subService->service->family->code }}</p>
                    <a href="{{ route('service-families.show', $subService->service->family) }}"
                       class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-flex items-center">
                        <i class="fas fa-external-link-alt mr-1"></i>Ver familia
                    </a>
                </div>

                <!-- Estado -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-check-circle {{ $subService->is_active ? 'text-green-600' : 'text-red-600' }} mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Estado</h3>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $subService->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $subService->is_active ? 'Disponible para nuevas solicitudes' : 'No disponible para nuevas solicitudes' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Información Detallada -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Costo -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-dollar-sign text-yellow-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Costo del Servicio</h3>
                    </div>
                    @if($subService->cost)
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($subService->cost, 2) }}</p>
                        <p class="text-sm text-gray-500">Precio base del sub-servicio</p>
                    @else
                        <p class="text-lg font-medium text-gray-600">Sin costo asignado</p>
                        <p class="text-sm text-gray-500">Este servicio no tiene costo asociado</p>
                    @endif
                </div>

                <!-- Estadísticas -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Estadísticas</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $subService->serviceRequests->count() }}</p>
                            <p class="text-xs text-gray-500">Total Solicitudes</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">
                                {{ $subService->serviceRequests->where('status', 'CERRADA')->count() }}
                            </p>
                            <p class="text-xs text-gray-500">Completadas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descripción -->
        @if($subService->description)
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-align-left text-gray-500 mr-2"></i>Descripción
            </h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line">{{ $subService->description }}</p>
            </div>
        </div>
        @endif

        <!-- SLAs Aplicables -->
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-handshake text-blue-600 mr-2"></i>
                SLAs Aplicables ({{ $subService->applicableSlas->count() }})
            </h3>

            @if($subService->applicableSlas->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($subService->applicableSlas as $sla)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-150">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-semibold text-gray-900">{{ $sla->name }}</h4>
                                @php
                                    $criticalityColors = [
                                        'BAJA' => 'bg-green-100 text-green-800',
                                        'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                        'ALTA' => 'bg-orange-100 text-orange-800',
                                        'CRITICA' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $criticalityColors[$sla->criticality_level] }}">
                                    {{ $sla->criticality_level }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Aceptación:</span>
                                    <span class="font-medium">{{ $sla->acceptance_time_minutes }} min</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Respuesta:</span>
                                    <span class="font-medium">{{ $sla->response_time_minutes }} min</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Resolución:</span>
                                    <span class="font-medium">{{ $sla->resolution_time_minutes }} min</span>
                                </div>
                            </div>

                            @if($sla->conditions)
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-xs text-gray-600 line-clamp-2">{{ $sla->conditions }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 bg-gray-50 rounded-lg">
                    <i class="fas fa-handshake text-3xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">No hay SLAs configurados</p>
                    <p class="text-sm text-gray-400 mt-1">Los SLAs se configuran a nivel de familia de servicio</p>
                    <a href="{{ route('slas.create') }}?family={{ $subService->service->family->id }}"
                       class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-flex items-center">
                        <i class="fas fa-plus mr-1"></i>Configurar SLA para esta familia
                    </a>
                </div>
            @endif
        </div>

        <!-- Solicitudes Recientes -->
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-tasks text-orange-600 mr-2"></i>
                    Solicitudes Recientes ({{ $subService->serviceRequests->count() }})
                </h3>
                <a href="{{ route('service-requests.create') }}?sub_service={{ $subService->id }}"
                   class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 flex items-center text-sm">
                    <i class="fas fa-plus mr-2"></i>Nueva Solicitud
                </a>
            </div>

            @if($subService->serviceRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Criticidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($subService->serviceRequests->take(5) as $request)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                        <a href="{{ route('service-requests.show', $request) }}">{{ $request->ticket_number }}</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ Str::limit($request->title, 40) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $criticalityColors = [
                                                'BAJA' => 'bg-green-100 text-green-800',
                                                'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                                'ALTA' => 'bg-orange-100 text-orange-800',
                                                'CRITICA' => 'bg-red-100 text-red-800'
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$request->criticality_level] }}">
                                            {{ $request->criticality_level }}
                                        </span>
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
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$request->status] }}">
                                            {{ $request->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('service-requests.show', $request) }}"
                                           class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($subService->serviceRequests->count() > 5)
                    <div class="mt-4 text-center">
                        <a href="{{ route('service-requests.index') }}?sub_service={{ $subService->id }}"
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Ver todas las {{ $subService->serviceRequests->count() }} solicitudes →
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fas fa-tasks text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium mb-2">No hay solicitudes registradas</p>
                    <p class="text-sm text-gray-400 mb-4">Este sub-servicio aún no tiene solicitudes asociadas</p>
                    <a href="{{ route('service-requests.create') }}?sub_service={{ $subService->id }}"
                       class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Crear Primera Solicitud
                    </a>
                </div>
            @endif
        </div>

        <!-- Información de Auditoría -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col md:flex-row md:justify-between text-sm text-gray-500">
                <div>
                    <span class="font-medium">Creado:</span>
                    {{ $subService->created_at->format('d/m/Y H:i') }}
                    @if($subService->created_at != $subService->updated_at)
                        <span class="mx-2">•</span>
                        <span class="font-medium">Actualizado:</span>
                        {{ $subService->updated_at->format('d/m/Y H:i') }}
                    @endif
                </div>
                <div class="mt-2 md:mt-0">
                    @if($subService->trashed())
                        <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 rounded text-xs">
                            <i class="fas fa-trash mr-1"></i>Eliminado
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Adicionales -->
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones Rápidas</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('sub-services.edit', $subService) }}"
               class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition duration-150 text-center">
                <i class="fas fa-edit text-2xl mb-2"></i>
                <p class="font-medium">Editar</p>
            </a>
            <a href="{{ route('service-requests.create') }}?sub_service={{ $subService->id }}"
               class="bg-green-600 text-white p-4 rounded-lg hover:bg-green-700 transition duration-150 text-center">
                <i class="fas fa-plus text-2xl mb-2"></i>
                <p class="font-medium">Nueva Solicitud</p>
            </a>
            <a href="{{ route('services.show', $subService->service) }}"
               class="bg-purple-600 text-white p-4 rounded-lg hover:bg-purple-700 transition duration-150 text-center">
                <i class="fas fa-cog text-2xl mb-2"></i>
                <p class="font-medium">Ver Servicio</p>
            </a>
            <a href="{{ route('sub-services.index') }}"
               class="bg-gray-600 text-white p-4 rounded-lg hover:bg-gray-700 transition duration-150 text-center">
                <i class="fas fa-list text-2xl mb-2"></i>
                <p class="font-medium">Volver a Lista</p>
            </a>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection
