{{-- resources/views/sub-services/show.blade.php --}}
@extends('layouts.app')

@section('title', $subService->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $subService->name }}</h1>
            <div class="flex items-center mt-2 space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    <i class="fas fa-circle mr-1" style="font-size: 6px;"></i>
                    {{ $subService->is_active ? 'Activo' : 'Inactivo' }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-hashtag mr-1"></i>
                    {{ $subService->code }}
                </span>
                @if($subService->cost)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-dollar-sign mr-1"></i>
                    ${{ number_format($subService->cost, 2) }}
                </span>
                @endif
            </div>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('sub-services.edit', $subService) }}"
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-edit mr-2"></i>Editar
            </a>
            <a href="{{ route('sub-services.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información del Sub-Servicio -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white px-6 py-4">
                    <h2 class="text-xl font-bold">Información del Sub-Servicio</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <p class="text-gray-900">{{ $subService->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                            <p class="text-gray-900 font-mono">{{ $subService->code }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Servicio Principal</label>
                            <div class="flex items-center space-x-2">
                                @if($subService->service)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $subService->service->code }}
                                </span>
                                <span class="text-gray-900">{{ $subService->service->name }}</span>
                                @if($subService->service->family)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $subService->service->family->name }}
                                </span>
                                @endif
                                @else
                                <span class="text-gray-400 italic">Sin servicio asignado</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <p class="text-gray-900 whitespace-pre-line">
                                {{ $subService->description ?? 'Sin descripción' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <p class="text-gray-900">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $subService->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Costo</label>
                                <p class="text-gray-900">
                                    @if($subService->cost)
                                        ${{ number_format($subService->cost, 2) }}
                                    @else
                                        <span class="text-gray-400 italic">Sin costo definido</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <p class="text-gray-900">{{ $subService->order }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Solicitudes</label>
                                <p class="text-gray-900">
                                    {{ $subService->serviceRequests()->count() }} solicitudes
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-600 text-white px-6 py-4">
                    <h2 class="text-xl font-bold">Información Adicional</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Creado:</span>
                            <span class="text-gray-900">{{ $subService->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Actualizado:</span>
                            <span class="text-gray-900">{{ $subService->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID:</span>
                            <span class="text-gray-900 font-mono">{{ $subService->id }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                <div class="bg-green-600 text-white px-6 py-4">
                    <h2 class="text-xl font-bold">Acciones</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <a href="{{ route('service-requests.create') }}?sub_service_id={{ $subService->id }}"
                           class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition duration-200">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Crear Solicitud
                        </a>

                        <a href="{{ route('services.show', $subService->service_id) }}"
                           class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition duration-200">
                            <i class="fas fa-cog mr-2"></i>
                            Ver Servicio Principal
                        </a>

                        <form action="{{ route('sub-services.destroy', $subService) }}" method="POST" class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition duration-200"
                                    onclick="return confirm('¿Está seguro de eliminar el sub-servicio \"{{ $subService->name }}\"?')">
                                <i class="fas fa-trash mr-2"></i>
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
