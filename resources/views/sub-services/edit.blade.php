{{-- resources/views/sub-services/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear Sub-Servicio')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-blue-600 text-white px-6 py-4">
                <h2 class="text-2xl font-bold">Crear Nuevo Sub-Servicio</h2>
            </div>

            <form action="{{ route('sub-services.store') }}" method="POST" class="p-6">
                @csrf

                <!-- Servicio Principal -->
                <div class="mb-6">
                    <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Servicio Principal *
                    </label>
                    <select name="service_id" id="service_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Seleccione un servicio</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                                @if($service->family)
                                    ({{ $service->family->name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('service_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre del Sub-Servicio -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del Sub-Servicio *
                    </label>
                    <input type="text" name="name" id="name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="{{ old('name') }}"
                           placeholder="Ingrese el nombre del sub-servicio"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Código del Sub-Servicio -->
                <div class="mb-6">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Código del Sub-Servicio *
                    </label>
                    <input type="text" name="code" id="code" maxlength="20"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 uppercase"
                           value="{{ old('code') }}"
                           placeholder="Ej: SUB-001, SOP-BASICO"
                           required>
                    <p class="mt-1 text-sm text-gray-500">
                        Código único de hasta 20 caracteres para identificar el sub-servicio
                    </p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descripción -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describa el sub-servicio, características, alcance...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Costo -->
                <div class="mb-6">
                    <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">
                        Costo (Opcional)
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="cost" id="cost" step="0.01" min="0"
                               class="block w-full pl-7 pr-12 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0.00"
                               value="{{ old('cost') }}">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">USD</span>
                        </div>
                    </div>
                    @error('cost')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Orden -->
                <div class="mb-6">
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Orden de Visualización
                    </label>
                    <input type="number" name="order" id="order"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="{{ old('order', 0) }}"
                           min="0">
                    @error('order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado -->
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Sub-servicio activo
                        </label>
                    </div>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('sub-services.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>Crear Sub-Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
