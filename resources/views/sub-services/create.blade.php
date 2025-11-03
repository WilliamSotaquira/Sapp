@extends('layouts.app')

@section('title', 'Crear Sub-Servicio')

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
                    <span class="text-gray-500">Crear Sub-Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Crear Nuevo Sub-Servicio</h2>
            <p class="text-gray-600">Complete la información para registrar un nuevo sub-servicio en el sistema.</p>
        </div>

        <form action="{{ route('sub-services.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Servicio Padre -->
                <div class="md:col-span-2">
                    <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Servicio Padre *
                    </label>
                    <select name="service_id" id="service_id"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Seleccione un servicio padre</option>
                        @foreach($services as $familyName => $familyServices)
                            <optgroup label="{{ $familyName }}">
                                @foreach($familyServices as $service)
                                    <option value="{{ $service->id }}" {{ old('service_id', request('service')) == $service->id ? 'selected' : '' }}>
                                        {{ $service->name }} ({{ $service->code }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('service_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del Sub-Servicio *
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: Instalación de Office"
                           required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Código -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Código *
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                           placeholder="Ej: OFFICE"
                           maxlength="10"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Máximo 10 caracteres. Debe ser único por servicio.</p>
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Costo -->
                <div>
                    <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">
                        Costo
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="cost" id="cost" value="{{ old('cost') }}"
                               class="block w-full pl-7 pr-12 border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0.00"
                               step="0.01"
                               min="0">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">USD</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Deje en blanco si no aplica costo.</p>
                    @error('cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Orden -->
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Orden de Visualización
                    </label>
                    <input type="number" name="order" id="order" value="{{ old('order', 0) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           min="0"
                           max="999">
                    <p class="text-xs text-gray-500 mt-1">Define el orden en que aparecerá el sub-servicio en las listas.</p>
                    @error('order')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <div class="mt-2 space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="is_active" value="0"
                                   {{ old('is_active') === '0' ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Inactivo</span>
                        </label>
                    </div>
                    @error('is_active')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descripción -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describa el sub-servicio, sus características y alcance...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Información Importante
                </h3>
                <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                    <li>El código debe ser único dentro del mismo servicio padre</li>
                    <li>Los sub-servicios inactivos no estarán disponibles para nuevas solicitudes</li>
                    <li>El costo es opcional y se mostrará en las solicitudes de servicio</li>
                    <li>El orden define la posición en listas y menús (menor número = primera posición)</li>
                </ul>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('sub-services.index') }}"
                   class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition duration-150 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition duration-150 flex items-center">
                    <i class="fas fa-save mr-2"></i>Guardar Sub-Servicio
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generar código basado en el nombre si está vacío
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');

        nameInput.addEventListener('blur', function() {
            if (!codeInput.value.trim()) {
                // Generar código desde el nombre (primeras letras de cada palabra)
                const name = this.value.trim();
                if (name.length > 0) {
                    const code = name
                        .toUpperCase()
                        .replace(/[^A-Z0-9\s]/g, '')
                        .split(' ')
                        .map(word => word.substring(0, 3))
                        .join('')
                        .substring(0, 10);
                    codeInput.value = code;
                }
            }
        });

        // Validación en tiempo real para el código
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        // Formatear costo
        const costInput = document.getElementById('cost');
        costInput.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // Enfocar el primer campo al cargar la página
        nameInput.focus();
    });
</script>
@endsection
