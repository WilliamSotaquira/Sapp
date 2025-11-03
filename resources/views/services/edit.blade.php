@extends('layouts.app')

@section('title', 'Editar ' . $service->name)

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
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('services.show', $service) }}" class="text-blue-600 hover:text-blue-700">{{ $service->name }}</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Editar</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Editar Servicio</h2>
            <p class="text-gray-600">Actualice la información del servicio <strong>"{{ $service->name }}"</strong>.</p>
        </div>

        <form action="{{ route('services.update', $service) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Familia de Servicio -->
                <div class="md:col-span-2">
                    <label for="service_family_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Familia de Servicio *
                    </label>
                    <select name="service_family_id" id="service_family_id"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Seleccione una familia de servicio</option>
                        @foreach($serviceFamilies as $family)
                            <option value="{{ $family->id }}"
                                {{ old('service_family_id', $service->service_family_id) == $family->id ? 'selected' : '' }}>
                                {{ $family->name }} ({{ $family->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('service_family_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del Servicio *
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $service->name) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: Soporte de Software"
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
                    <input type="text" name="code" id="code" value="{{ old('code', $service->code) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                           placeholder="Ej: SSW"
                           maxlength="10"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Máximo 10 caracteres. Debe ser único por familia.</p>
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Orden -->
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Orden de Visualización
                    </label>
                    <input type="number" name="order" id="order" value="{{ old('order', $service->order) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           min="0"
                           max="999">
                    <p class="text-xs text-gray-500 mt-1">Define el orden en que aparecerá el servicio en las listas.</p>
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
                                   {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="is_active" value="0"
                                   {{ !old('is_active', $service->is_active) ? 'checked' : '' }}
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
                              placeholder="Describa el servicio, sus características y alcance...">{{ old('description', $service->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Información Actual -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Información Actual del Servicio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-blue-700">Familia:</span>
                        <span class="ml-2 text-blue-900">{{ $service->family->name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Estado:</span>
                        <span class="ml-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Sub-Servicios:</span>
                        <span class="ml-2 text-blue-900">{{ $service->subServices->count() }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-blue-700">Creado:</span>
                        <span class="ml-2 text-blue-900">{{ $service->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Advertencia sobre sub-servicios -->
            @if($service->subServices->count() > 0 && !$service->is_active)
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Advertencia</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Este servicio tiene {{ $service->subServices->count() }} sub-servicios.
                                Si lo marca como inactivo, los sub-servicios podrían verse afectados.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Botones -->
            <div class="mt-6 flex justify-between items-center pt-6 border-t border-gray-200">
                <div>
                    <a href="{{ route('services.show', $service) }}"
                       class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition duration-150 flex items-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
                <div class="flex space-x-3">
                    <button type="button"
                            onclick="if(confirm('¿Está seguro de que desea eliminar este servicio?')) { document.getElementById('delete-form').submit(); }"
                            class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition duration-150 flex items-center {{ $service->subServices->count() > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $service->subServices->count() > 0 ? 'disabled' : '' }}
                            title="{{ $service->subServices->count() > 0 ? 'No se puede eliminar porque tiene sub-servicios' : 'Eliminar servicio' }}">
                        <i class="fas fa-trash mr-2"></i>Eliminar
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition duration-150 flex items-center">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </form>

        <!-- Formulario de eliminación oculto -->
        <form id="delete-form" action="{{ route('services.destroy', $service) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');
        const familySelect = document.getElementById('service_family_id');

        // Validación en tiempo real para el código
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        // Verificar duplicación de código al cambiar familia o código
        function checkCodeUniqueness() {
            const familyId = familySelect.value;
            const code = codeInput.value;

            if (familyId && code) {
                // Aquí podrías agregar una verificación AJAX en tiempo real
                // para verificar si el código ya existe en la familia seleccionada
                console.log('Verificando código:', code, 'para familia:', familyId);
            }
        }

        familySelect.addEventListener('change', checkCodeUniqueness);
        codeInput.addEventListener('blur', checkCodeUniqueness);

        // Mostrar advertencia si se cambia a inactivo y hay sub-servicios
        const inactiveRadio = document.querySelector('input[value="0"]');
        if (inactiveRadio) {
            inactiveRadio.addEventListener('change', function() {
                if (this.checked && {{ $service->subServices->count() }} > 0) {
                    if (!document.querySelector('.bg-yellow-50')) {
                        // Crear advertencia dinámica si no existe
                        const warningDiv = document.createElement('div');
                        warningDiv.className = 'mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4';
                        warningDiv.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Advertencia</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Este servicio tiene {{ $service->subServices->count() }} sub-servicios activos.
                                        Al marcar el servicio como inactivo, los sub-servicios podrían verse afectados.
                                    </p>
                                </div>
                            </div>
                        `;
                        document.querySelector('form').insertBefore(warningDiv, document.querySelector('.mt-6'));
                    }
                }
            });
        }

        // Enfocar el primer campo al cargar la página
        nameInput.focus();
        nameInput.select();
    });
</script>
@endsection
