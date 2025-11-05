<!-- resources/views/slas/create.blade.php -->
@extends('layouts.app')

@section('title', 'Crear SLA')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Crear Nuevo SLA</h2>
                <p class="text-gray-600 mt-1">Defina los niveles de servicio para el subservicio seleccionado</p>
            </div>

            <form action="{{ route('slas.store') }}" method="POST" class="p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Familia de Servicio -->
                    <div>
                        <label for="service_family_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Familia de Servicio *
                        </label>
                        <select name="service_family_id" id="service_family_id"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            required>
                            <option value="">Seleccione una familia de servicio</option>
                            @foreach($serviceFamilies as $family)
                            <option value="{{ $family->id }}" {{ old('service_family_id') == $family->id ? 'selected' : '' }}>
                                {{ $family->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('service_family_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Servicio -->
                    <div>
                        <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Servicio *
                        </label>
                        <select name="service_id" id="service_id"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            required disabled>
                            <option value="">Primero seleccione una familia</option>
                        </select>
                        @error('service_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Subservicio -->
                    <div>
                        <label for="sub_service_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Subservicio *
                        </label>
                        <select name="sub_service_id" id="sub_service_id"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            required disabled>
                            <option value="">Primero seleccione un servicio</option>
                        </select>
                        @error('sub_service_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Service Subservice (oculto, para mantener compatibilidad) -->
                    <input type="hidden" name="service_subservice_id" id="service_subservice_id" value="">

                    <!-- Resto del formulario permanece igual -->
                    <!-- Nombre del SLA -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del SLA *
                        </label>
                        <input type="text" name="name" id="name"
                            value="{{ old('name') }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            placeholder="Ej: SLA para Publicación de Noticias"
                            required>
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            placeholder="Descripción detallada del acuerdo de nivel de servicio">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nivel de Criticidad -->
                    <div>
                        <label for="criticality_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Criticidad *
                        </label>
                        <select name="criticality_level" id="criticality_level"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="">Seleccione nivel</option>
                            @foreach($criticalityLevels as $level)
                            <option value="{{ $level }}" {{ old('criticality_level') == $level ? 'selected' : '' }}>
                                {{ $level }}
                            </option>
                            @endforeach
                        </select>
                        @error('criticality_level')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tiempo de Aceptación -->
                    <div>
                        <label for="acceptance_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                            Tiempo de Aceptación (minutos) *
                        </label>
                        <input type="number" name="acceptance_time_minutes" id="acceptance_time_minutes"
                            value="{{ old('acceptance_time_minutes', 30) }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            min="1" max="1440" step="1"
                            placeholder="Ej: 30"
                            required>
                        @error('acceptance_time_minutes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tiempo de Respuesta (horas) -->
                    <div>
                        <label for="response_time_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Tiempo de Respuesta (horas) *
                        </label>
                        <input type="number" name="response_time_hours" id="response_time_hours"
                            value="{{ old('response_time_hours') }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            min="1" max="720" step="1"
                            placeholder="Ej: 24"
                            required>
                        @error('response_time_hours')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tiempo de Resolución (horas) -->
                    <div>
                        <label for="resolution_time_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Tiempo de Resolución (horas) *
                        </label>
                        <input type="number" name="resolution_time_hours" id="resolution_time_hours"
                            value="{{ old('resolution_time_hours') }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            min="1" max="720" step="1"
                            placeholder="Ej: 48"
                            required>
                        @error('resolution_time_hours')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Porcentaje de Disponibilidad -->
                    <div>
                        <label for="availability_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                            Disponibilidad (%) *
                        </label>
                        <input type="number" name="availability_percentage" id="availability_percentage"
                            value="{{ old('availability_percentage', 99.9) }}"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            min="0" max="100" step="0.1"
                            placeholder="Ej: 99.9"
                            required>
                        @error('availability_percentage')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Condiciones -->
                    <div class="md:col-span-2">
                        <label for="conditions" class="block text-sm font-medium text-gray-700 mb-2">
                            Condiciones Especiales
                        </label>
                        <textarea name="conditions" id="conditions" rows="3"
                            class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            placeholder="Condiciones especiales del SLA">{{ old('conditions') }}</textarea>
                        @error('conditions')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estado Activo -->
                    <div class="flex items-center md:col-span-2">
                        <input type="checkbox" name="is_active" id="is_active"
                            value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            SLA Activo
                        </label>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('slas.index') }}"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out">
                        Crear SLA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const serviceFamilySelect = document.getElementById('service_family_id');
        const serviceSelect = document.getElementById('service_id');
        const subServiceSelect = document.getElementById('sub_service_id');
        const serviceSubserviceHidden = document.getElementById('service_subservice_id');
        const form = document.querySelector('form');
        let isSubmitting = false;

        // Función para obtener el token CSRF de forma segura
        function getCsrfToken() {
            // Intentar obtener del meta tag
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                return csrfMeta.getAttribute('content');
            }

            // Intentar obtener del input hidden
            const csrfInput = document.querySelector('input[name="_token"]');
            if (csrfInput) {
                return csrfInput.value;
            }

            // Si no se encuentra, mostrar error
            console.error('CSRF token no encontrado');
            return null;
        }

        // Cargar servicios cuando se selecciona una familia
        serviceFamilySelect.addEventListener('change', function() {
            const familyId = this.value;
            console.log('Familia seleccionada:', familyId);

            if (familyId) {
                // Habilitar y cargar servicios
                serviceSelect.disabled = false;
                serviceSelect.innerHTML = '<option value="">Cargando servicios...</option>';

                fetch(`/api/service-families/${familyId}/services`)
                    .then(response => {
                        if (!response.ok) throw new Error('Error al cargar servicios');
                        return response.json();
                    })
                    .then(services => {
                        console.log('Servicios cargados:', services);
                        serviceSelect.innerHTML = '<option value="">Seleccione un servicio</option>';
                        services.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.id;
                            option.textContent = service.name;
                            serviceSelect.appendChild(option);
                        });

                        // Reset subservicios
                        subServiceSelect.disabled = true;
                        subServiceSelect.innerHTML = '<option value="">Primero seleccione un servicio</option>';
                        serviceSubserviceHidden.value = '';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        serviceSelect.innerHTML = '<option value="">Error al cargar servicios</option>';
                        showError('Error al cargar servicios. Intente nuevamente.');
                    });
            } else {
                resetServiceAndSubservice();
            }
        });

        // Cargar subservicios cuando se selecciona un servicio
        serviceSelect.addEventListener('change', function() {
            const serviceId = this.value;
            const familyId = serviceFamilySelect.value;
            console.log('Servicio seleccionado:', serviceId, 'Familia:', familyId);

            if (serviceId && familyId) {
                // Habilitar y cargar subservicios
                subServiceSelect.disabled = false;
                subServiceSelect.innerHTML = '<option value="">Cargando subservicios...</option>';

                fetch(`/api/services/${serviceId}/sub-services`)
                    .then(response => {
                        if (!response.ok) throw new Error('Error al cargar subservicios');
                        return response.json();
                    })
                    .then(subServices => {
                        console.log('Subservicios cargados:', subServices);
                        subServiceSelect.innerHTML = '<option value="">Seleccione un subservicio</option>';
                        subServices.forEach(subService => {
                            const option = document.createElement('option');
                            option.value = subService.id;
                            option.textContent = subService.name;
                            option.dataset.familyId = familyId;
                            option.dataset.serviceId = serviceId;
                            option.dataset.subServiceId = subService.id;
                            subServiceSelect.appendChild(option);
                        });
                        serviceSubserviceHidden.value = '';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        subServiceSelect.innerHTML = '<option value="">Error al cargar subservicios</option>';
                        showError('Error al cargar subservicios. Intente nuevamente.');
                    });
            } else {
                resetSubservice();
            }
        });

        // Buscar o crear service_subservice_id cuando se selecciona un subservicio
        subServiceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            console.log('Subservicio seleccionado:', selectedOption.value);

            if (selectedOption.value) {
                const familyId = selectedOption.dataset.familyId;
                const serviceId = selectedOption.dataset.serviceId;
                const subServiceId = selectedOption.dataset.subServiceId;

                console.log('Datos para find-or-create:', {
                    familyId,
                    serviceId,
                    subServiceId
                });

                // Mostrar loading
                showLoading('Procesando selección...');

                // Buscar el service_subservice_id existente o crear uno nuevo
                findOrCreateServiceSubservice(familyId, serviceId, subServiceId)
                    .then(() => {
                        hideLoading();
                        console.log('Service Subservice ID asignado:', serviceSubserviceHidden.value);
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error en findOrCreate:', error);
                        showError('Error al procesar la selección: ' + error.message);
                        serviceSubserviceHidden.value = '';
                    });
            } else {
                serviceSubserviceHidden.value = '';
            }
        });

        // Interceptar el envío del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) return;

            // Validar que service_subservice_id tenga valor
            if (!serviceSubserviceHidden.value) {
                showError('Debe seleccionar un subservicio válido antes de crear el SLA.');
                return;
            }

            console.log('Enviando formulario con service_subservice_id:', serviceSubserviceHidden.value);

            // Validar tiempos
            if (!validateTimes()) {
                return;
            }

            isSubmitting = true;
            showLoading('Creando SLA...');

            try {
                // Enviar el formulario de forma tradicional
                form.submit();
            } catch (error) {
                console.error('Error:', error);
                showError('Error al crear el SLA: ' + error.message);
                isSubmitting = false;
                hideLoading();
            }
        });

        function findOrCreateServiceSubservice(familyId, serviceId, subServiceId) {
            return new Promise((resolve, reject) => {
                const csrfToken = getCsrfToken();
                if (!csrfToken) {
                    reject(new Error('Token de seguridad no encontrado'));
                    return;
                }

                const data = {
                    service_family_id: familyId,
                    service_id: serviceId,
                    sub_service_id: subServiceId
                };

                console.log('Enviando petición a /api/service-subservices/find-or-create:', data);

                fetch('/api/service-subservices/find-or-create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        console.log('Respuesta recibida, status:', response.status);
                        if (!response.ok) {
                            return response.json().then(err => {
                                console.error('Error en respuesta:', err);
                                throw new Error(err.error || `Error ${response.status} al buscar/crear service subservice`);
                            });
                        }
                        return response.json();
                    })
                    .then(result => {
                        console.log('Resultado exitoso:', result);
                        if (result.error) {
                            throw new Error(result.error);
                        }
                        serviceSubserviceHidden.value = result.id;
                        console.log('Service Subservice ID asignado:', result.id);
                        resolve(result);
                    })
                    .catch(error => {
                        console.error('Error en fetch:', error);
                        reject(error);
                    });
            });
        }

        function resetServiceAndSubservice() {
            serviceSelect.disabled = true;
            serviceSelect.innerHTML = '<option value="">Primero seleccione una familia</option>';
            resetSubservice();
        }

        function resetSubservice() {
            subServiceSelect.disabled = true;
            subServiceSelect.innerHTML = '<option value="">Primero seleccione un servicio</option>';
            serviceSubserviceHidden.value = '';
        }

        function validateTimes() {
            const acceptanceTime = parseInt(document.getElementById('acceptance_time_minutes').value) || 0;
            const responseTimeHours = parseInt(document.getElementById('response_time_hours').value) || 0;
            const resolutionTimeHours = parseInt(document.getElementById('resolution_time_hours').value) || 0;

            // Convertir horas a minutos para validación
            const responseTimeMinutes = responseTimeHours * 60;
            const resolutionTimeMinutes = resolutionTimeHours * 60;

            if (acceptanceTime >= responseTimeMinutes) {
                showError('El tiempo de aceptación debe ser menor al tiempo de respuesta.');
                return false;
            }

            if (responseTimeMinutes >= resolutionTimeMinutes) {
                showError('El tiempo de respuesta debe ser menor al tiempo de resolución.');
                return false;
            }

            return true;
        }

        function showError(message) {
            // Remover mensajes de error anteriores
            const existingError = document.querySelector('.form-error-message');
            if (existingError) {
                existingError.remove();
            }

            // Crear nuevo mensaje de error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            errorDiv.innerHTML = `
            <strong>Error:</strong> ${message}
        `;

            // Insertar antes del formulario
            form.parentNode.insertBefore(errorDiv, form);

            // Hacer scroll al mensaje de error
            errorDiv.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function showLoading(message) {
            // Remover loading anterior
            hideLoading();

            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'form-loading';
            loadingDiv.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            loadingDiv.innerHTML = `
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-700">${message}</span>
            </div>
        `;

            document.body.appendChild(loadingDiv);
        }

        function hideLoading() {
            const loadingDiv = document.getElementById('form-loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
    });
</script>

<style>
    .form-error-message {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection
