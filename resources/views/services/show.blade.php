{{-- resources/views/services/show.blade.php --}}
@extends('layouts.app')

@section('title', $service->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $service->name }}</h1>
            <div class="flex items-center mt-2 space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    <i class="fas fa-circle mr-1" style="font-size: 6px;"></i>
                    {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-hashtag mr-1"></i>
                    {{ $service->code }}
                </span>
                @if($service->family)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                    <i class="fas fa-layer-group mr-1"></i>
                    {{ $service->family->name }}
                </span>
                @endif
            </div>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('services.edit', $service) }}"
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-edit mr-2"></i>Editar
            </a>
            <a href="{{ route('services.index') }}"
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
        <!-- Información del Servicio -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white px-6 py-4">
                    <h2 class="text-xl font-bold">Información del Servicio</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <p class="text-gray-900">{{ $service->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                            <p class="text-gray-900 font-mono">{{ $service->code }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Familia</label>
                            <p class="text-gray-900">
                                @if($service->family)
                                    {{ $service->family->name }} ({{ $service->family->code }})
                                @else
                                    <span class="text-gray-400 italic">Sin familia asignada</span>
                                @endif
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <p class="text-gray-900 whitespace-pre-line">
                                {{ $service->description ?? 'Sin descripción' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <p class="text-gray-900">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <p class="text-gray-900">{{ $service->order }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub-Servicios -->
        <div>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-green-600 text-white px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold">Sub-Servicios</h2>
                        <span class="bg-green-700 text-white px-2 py-1 rounded-full text-sm">
                            {{ $service->subServices->count() }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    @if($service->subServices->count() > 0)
                        <div class="space-y-3">
                            @foreach($service->subServices as $subService)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition duration-150">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $subService->name }}</h3>
                                        @if($subService->description)
                                        <p class="text-sm text-gray-500 mt-1">{{ Str::limit($subService->description, 80) }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $subService->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-list-alt text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">No hay sub-servicios registrados</p>
                            <p class="text-sm text-gray-400 mt-1">Los sub-servicios se mostrarán aquí</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                <div class="bg-gray-600 text-white px-6 py-4">
                    <h2 class="text-xl font-bold">Información Adicional</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Creado:</span>
                            <span class="text-gray-900">{{ $service->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Actualizado:</span>
                            <span class="text-gray-900">{{ $service->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID:</span>
                            <span class="text-gray-900 font-mono">{{ $service->id }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
