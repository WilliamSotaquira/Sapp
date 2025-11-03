@extends('layouts.app')

@section('title', $service->name)

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('services.index') }}" class="text-blue-600 hover:text-blue-700">Servicios</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">{{ $service->name }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-6">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <div class="h-12 w-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-cog text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ $service->name }}</h1>
                            <div class="flex items-center space-x-4 mt-1">
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-hashtag mr-1"></i>{{ $service->code }}
                                </span>
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-sort-numeric-up mr-1"></i>Orden: {{ $service->order }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2 mt-4 md:mt-0">
                    <a href="{{ route('services.edit', $service) }}"
                       class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded transition duration-150 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </a>
                    <a href="{{ route('sub-services.create') }}?service={{ $service->id }}"
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition duration-150 flex items-center">
                        <i class="fas fa-plus mr-2"></i>Agregar Sub-Servicio
                    </a>
                </div>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Familia -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-layer-group text-blue-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Familia de Servicio</h3>
                    </div>
                    <p class="text-lg font-medium text-gray-900">{{ $service->family->name }}</p>
                    <p class="text-sm text-gray-500">Código: {{ $service->family->code }}</p>
                </div>

                <!-- Estado -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-check-circle {{ $service->is_active ? 'text-green-600' : 'text-red-600' }} mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Estado</h3>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $service->is_active ? 'Disponible para nuevos sub-servicios' : 'No disponible para nuevos sub-servicios' }}
                    </p>
                </div>

                <!-- Estadísticas -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                        <h3 class="font-semibold text-gray-700">Estadísticas</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $service->subServices->count() }}</p>
                            <p class="text-xs text-gray-500">Total Sub-Servicios</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ $service->activeSubServices->count() }}</p>
                            <p class="text-xs text-gray-500">Activos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descripción -->
        @if($service->description)
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-align-left text-gray-500 mr-2"></i>Descripción
            </h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line">{{ $service->description }}</p>
            </div>
        </div>
        @endif

        <!-- Sub-Servicios -->
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-list-alt text-purple-600 mr-2"></i>
                    Sub-Servicios ({{ $service->subServices->count() }})
                </h3>
                <a href="{{ route('sub-services.create') }}?service={{ $service->id }}"
                   class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 flex items-center text-sm">
                    <i class="fas fa-plus mr-2"></i>Nuevo Sub-Servicio
                </a>
            </div>

            @if($service->subServices->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($service->subServices as $subService)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-150">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-semibold text-gray-900">{{ $subService->name }}</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $subService->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium mb-2">
                                    <i class="fas fa-hashtag mr-1"></i>{{ $subService->code }}
                                </span>
                                @if($subService->cost)
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium ml-2">
                                        <i class="fas fa-dollar-sign mr-1"></i>{{ number_format($subService->cost, 2) }}
                                    </span>
                                @endif
                            </div>

                            @if($subService->description)
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $subService->description }}</p>
                            @endif

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Orden: {{ $subService->order }}</span>
                                <div class="flex space-x-2">
                                    <a href="{{ route('sub-services.show', $subService) }}"
                                       class="text-blue-600 hover:text-blue-800 transition duration-150"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sub-services.edit', $subService) }}"
                                       class="text-green-600 hover:text-green-800 transition duration-150"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fas fa-list-alt text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium mb-2">No hay sub-servicios registrados</p>
                    <p class="text-sm text-gray-400 mb-4">Comienza agregando el primer sub-servicio a este servicio</p>
                    <a href="{{ route('sub-services.create') }}?service={{ $service->id }}"
                       class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Agregar Primer Sub-Servicio
                    </a>
                </div>
            @endif
        </div>

        <!-- Información de Auditoría -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col md:flex-row md:justify-between text-sm text-gray-500">
                <div>
                    <span class="font-medium">Creado:</span>
                    {{ $service->created_at->format('d/m/Y H:i') }}
                    @if($service->created_at != $service->updated_at)
                        <span class="mx-2">•</span>
                        <span class="font-medium">Actualizado:</span>
                        {{ $service->updated_at->format('d/m/Y H:i') }}
                    @endif
                </div>
                <div class="mt-2 md:mt-0">
                    @if($service->trashed())
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('services.edit', $service) }}"
               class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition duration-150 text-center">
                <i class="fas fa-edit text-2xl mb-2"></i>
                <p class="font-medium">Editar Servicio</p>
            </a>
            <a href="{{ route('sub-services.create') }}?service={{ $service->id }}"
               class="bg-green-600 text-white p-4 rounded-lg hover:bg-green-700 transition duration-150 text-center">
                <i class="fas fa-plus-circle text-2xl mb-2"></i>
                <p class="font-medium">Nuevo Sub-Servicio</p>
            </a>
            <a href="{{ route('services.index') }}"
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
