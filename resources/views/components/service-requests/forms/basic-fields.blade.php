{{-- resources/views/components/service-requests/forms/basic-fields.blade.php --}}
@props([
'serviceRequest' => null,
'services' => [], // Lista de servicios para el select
'subServices' => [], // Lista de subservicios
'errors' => null,
'mode' => 'create' // 'create' or 'edit'
])

<div class="space-y-6">
    <!-- Campo Título -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Título de la Solicitud <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="title"
            id="title"
            value="{{ old('title', $serviceRequest->title ?? '') }}"
            placeholder="Ingrese un título descriptivo para la solicitud"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('title') border-red-500 @enderror"
            required
            maxlength="255">
        @error('title')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500">Máximo 255 caracteres</p>
    </div>

    <!-- Campo Descripción -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Descripción Detallada <span class="text-red-500">*</span>
        </label>
        <textarea
            name="description"
            id="description"
            rows="6"
            placeholder="Describa en detalle el problema o requerimiento..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('description') border-red-500 @enderror"
            required>{{ old('description', $serviceRequest->description ?? '') }}</textarea>
        @error('description')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500">Proporcione todos los detalles necesarios para atender la solicitud</p>
    </div>

    <!-- Selección de Servicio y Subservicio -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Servicio -->
        <div>
            <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                Servicio <span class="text-red-500">*</span>
            </label>
            <select
                name="service_id"
                id="service_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('service_id') border-red-500 @enderror"
                required
                onchange="updateSubServices(this.value)">
                <option value="">Seleccione un servicio</option>
                @foreach($services as $service)
                <option value="{{ $service->id }}"
                    {{ old('service_id', $serviceRequest->subService->service_id ?? '') == $service->id ? 'selected' : '' }}>
                    {{ $service->name }}
                </option>
                @endforeach
            </select>
            @error('service_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Subservicio -->
        <div>
            <label for="sub_service_id" class="block text-sm font-medium text-gray-700 mb-2">
                Subservicio <span class="text-red-500">*</span>
            </label>
            <select
                name="sub_service_id"
                id="sub_service_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('sub_service_id') border-red-500 @enderror"
                required
                {{ empty($subServices) ? 'disabled' : '' }}>
                <option value="">Seleccione un subservicio</option>
                @foreach($subServices as $subService)
                <option value="{{ $subService->id }}"
                    {{ old('sub_service_id', $serviceRequest->sub_service_id ?? '') == $subService->id ? 'selected' : '' }}>
                    {{ $subService->name }}
                </option>
                @endforeach
            </select>
            @error('sub_service_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Nivel de Criticidad -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Nivel de Criticidad <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach(['BAJA', 'MEDIA', 'ALTA', 'URGENTE'] as $level)
            <label class="relative flex cursor-pointer">
                <input
                    type="radio"
                    name="criticality_level"
                    value="{{ $level }}"
                    {{ old('criticality_level', $serviceRequest->criticality_level ?? 'MEDIA') == $level ? 'checked' : '' }}
                    class="sr-only peer"
                    required>
                <div class="w-full p-4 border-2 border-gray-200 rounded-lg text-center transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-md">
                    <div class="flex flex-col items-center space-y-2">
                        @switch($level)
                        @case('BAJA')
                        <i class="fas fa-arrow-down text-green-500 text-lg"></i>
                        <span class="font-medium text-gray-700">Baja</span>
                        <span class="text-xs text-gray-500">Impacto mínimo</span>
                        @break
                        @case('MEDIA')
                        <i class="fas fa-minus text-yellow-500 text-lg"></i>
                        <span class="font-medium text-gray-700">Media</span>
                        <span class="text-xs text-gray-500">Impacto moderado</span>
                        @break
                        @case('ALTA')
                        <i class="fas fa-arrow-up text-orange-500 text-lg"></i>
                        <span class="font-medium text-gray-700">Alta</span>
                        <span class="text-xs text-gray-500">Impacto significativo</span>
                        @break
                        @case('URGENTE')
                        <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                        <span class="font-medium text-gray-700">Urgente</span>
                        <span class="text-xs text-gray-500">Impacto crítico</span>
                        @break
                        @endswitch
                    </div>
                </div>
            </label>
            @endforeach
        </div>
        @error('criticality_level')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Rutas Web (Opcional) -->
    <div>
        <label for="web_routes" class="block text-sm font-medium text-gray-700 mb-2">
            Rutas Web Relacionadas (Opcional)
        </label>
        <div id="web-routes-container">
            @php
            $existingRoutes = old('web_routes', $serviceRequest->web_routes ?? []);
            if (is_string($existingRoutes)) {
            $existingRoutes = json_decode($existingRoutes, true) ?? [];
            }
            @endphp

            @if(!empty($existingRoutes))
            @foreach($existingRoutes as $index => $route)
            <div class="flex space-x-2 mb-2 route-input-group">
                <input
                    type="url"
                    name="web_routes[]"
                    value="{{ $route }}"
                    placeholder="https://ejemplo.com/ruta"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                @if($index > 0)
                <button type="button" onclick="removeRoute(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200">
                    <i class="fas fa-times"></i>
                </button>
                @endif
            </div>
            @endforeach
            @else
            <div class="flex space-x-2 mb-2 route-input-group">
                <input
                    type="url"
                    name="web_routes[]"
                    placeholder="https://ejemplo.com/ruta"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
            </div>
            @endif
        </div>
        <button type="button" onclick="addRoute()" class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 text-sm">
            <i class="fas fa-plus mr-2"></i>Agregar otra ruta
        </button>
        <p class="mt-1 text-sm text-gray-500">Agregue URLs relacionadas con la solicitud (máximo 5)</p>
    </div>
</div>

@push('scripts')
<script>
    // Actualizar subservicios cuando cambia el servicio
    function updateSubServices(serviceId) {
        const subServiceSelect = document.getElementById('sub_service_id');

        if (!serviceId) {
            subServiceSelect.innerHTML = '<option value="">Seleccione un subservicio</option>';
            subServiceSelect.disabled = true;
            return;
        }

        // Habilitar loading
        subServiceSelect.disabled = true;
        subServiceSelect.innerHTML = '<option value="">Cargando subservicios...</option>';

        // Hacer petición AJAX para obtener subservicios
        fetch(`/api/services/${serviceId}/subservices`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Seleccione un subservicio</option>';
                data.forEach(subService => {
                    options += `<option value="${subService.id}">${subService.name}</option>`;
                });
                subServiceSelect.innerHTML = options;
                subServiceSelect.disabled = false;

                // Restaurar valor anterior si existe
                const oldValue = "{{ old('sub_service_id', $serviceRequest->sub_service_id ?? '') }}";
                if (oldValue) {
                    subServiceSelect.value = oldValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                subServiceSelect.innerHTML = '<option value="">Error al cargar subservicios</option>';
            });
    }

    // Manejo de rutas web dinámicas
    function addRoute() {
        const container = document.getElementById('web-routes-container');
        const inputGroups = container.getElementsByClassName('route-input-group');

        if (inputGroups.length >= 5) {
            alert('Máximo 5 rutas permitidas');
            return;
        }

        const newInput = document.createElement('div');
        newInput.className = 'flex space-x-2 mb-2 route-input-group';
        newInput.innerHTML = `
        <input
            type="url"
            name="web_routes[]"
            placeholder="https://ejemplo.com/ruta"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
        >
        <button type="button" onclick="removeRoute(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200">
            <i class="fas fa-times"></i>
        </button>
    `;
        container.appendChild(newInput);
    }

    function removeRoute(button) {
        const inputGroup = button.closest('.route-input-group');
        inputGroup.remove();
    }

    // Inicializar subservicios si ya hay un servicio seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const serviceId = document.getElementById('service_id').value;
        if (serviceId) {
            updateSubServices(serviceId);
        }
    });
</script>
@endpush
