@extends('layouts.app')

@section('title', 'Editar ' . $sla->name)

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
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('slas.show', $sla) }}" class="text-blue-600 hover:text-blue-700">{{ Str::limit($sla->name, 30) }}</a>
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
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Editar SLA</h2>
                        <p class="text-blue-100 opacity-90">{{ $sla->name }}</p>
                    </div>
                    <div class="text-white">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $sla->is_active ? 'bg-green-500' : 'bg-red-500' }}">
                            {{ $sla->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>

            <form action="{{ route('slas.update', $sla) }}" method="POST" id="slaForm" class="p-6">
                @csrf
                @method('PUT')

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
                                <option value="{{ $family->id }}" {{ old('service_family_id', $sla->service_family_id) == $family->id ? 'selected' : '' }}>
                                    {{ $family->name }} ({{ $family->code }})
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
                                required>
                            <option value="">Cargando servicios...</option>
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
                                required>
                            <option value="">Cargando subservicios...</option>
                        </select>
                        @error('sub_service_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Service Subservice (oculto) -->
                    <input type="hidden" name="service_subservice_id" id="service_subservice_id" value="{{ $sla->service_subservice_id }}">

                    <!-- Nombre del SLA -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del SLA *
                        </label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name', $sla->name) }}"
                               class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                               placeholder="Ej: SLA Básico Soporte Técnico"
                               required>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nivel de Criticidad -->
                    <div>
                        <label for="criticality_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Criticidad *
                        </label>
                        <select name="criticality_level" id="criticality_level"
                                class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                required>
                            <option value="">Seleccione un nivel</option>
                            @foreach($criticalityLevels as $level)
                                <option value="{{ $level }}" {{ old('criticality_level', $sla->criticality_level) == $level ? 'selected' : '' }}>
                                    {{ $level }}
                                </option>
                            @endforeach
                        </select>
                        @error('criticality_level')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estado -->
                    <div class="flex items-center">
                        <label for="is_active" class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   {{ old('is_active', $sla->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="text-sm font-medium text-gray-700">SLA Activo</span>
                        </label>
                    </div>

                    <!-- Tiempos de Respuesta -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Tiempos de Respuesta (minutos)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Tiempo de Aceptación -->
                            <div>
                                <label for="acceptance_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Aceptación *
                                </label>
                                <input type="number" name="acceptance_time_minutes" id="acceptance_time_minutes"
                                       value="{{ old('acceptance_time_minutes', $sla->acceptance_time_minutes) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                       min="1" max="1440" required>
                                <p class="text-xs text-gray-500 mt-1">Máximo para aceptar solicitud</p>
                                @error('acceptance_time_minutes')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tiempo de Respuesta -->
                            <div>
                                <label for="response_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Respuesta Inicial *
                                </label>
                                <input type="number" name="response_time_minutes" id="response_time_minutes"
                                       value="{{ old('response_time_minutes', $sla->response_time_minutes) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                       min="1" max="1440" required>
                                <p class="text-xs text-gray-500 mt-1">Primera respuesta al usuario</p>
                                @error('response_time_minutes')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tiempo de Resolución -->
                            <div>
                                <label for="resolution_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Resolución Completa *
                                </label>
                                <input type="number" name="resolution_time_minutes" id="resolution_time_minutes"
                                       value="{{ old('resolution_time_minutes', $sla->resolution_time_minutes) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                       min="1" max="1440" required>
                                <p class="text-xs text-gray-500 mt-1">Solución definitiva</p>
                                @error('resolution_time_minutes')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Validación visual de tiempos -->
                        <div id="timeValidation" class="mt-4 p-4 rounded-md border transition-all duration-300">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle mr-3 text-lg"></i>
                                <span id="validationMessage" class="text-sm font-medium">
                                    Los tiempos deben seguir: Aceptación &lt; Respuesta &lt; Resolución
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Condiciones -->
                    <div class="md:col-span-2">
                        <label for="conditions" class="block text-sm font-medium text-gray-700 mb-2">
                            Condiciones y Observaciones
                        </label>
                        <textarea name="conditions" id="conditions" rows="4"
                                  class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                  placeholder="Describa las condiciones específicas de este SLA, restricciones, horarios de aplicación, etc.">{{ old('conditions', $sla->conditions) }}</textarea>
                        @error('conditions')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Información del Sistema</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Creado:</span>
                            <span class="text-gray-900 block">{{ $sla->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Actualizado:</span>
                            <span class="text-gray-900 block">{{ $sla->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Solicitudes:</span>
                            <span class="text-gray-900 block">{{ $sla->serviceRequests->count() }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Familia actual:</span>
                            <span class="text-gray-900 block">{{ $sla->serviceFamily->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Tiempos -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-800 mb-3">Resumen de Tiempos Establecidos</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-700" id="acceptanceSummary">
                                @php
                                    $acceptanceMinutes = old('acceptance_time_minutes', $sla->acceptance_time_minutes);
                                    $acceptanceHours = floor($acceptanceMinutes / 60);
                                    $acceptanceMins = $acceptanceMinutes % 60;
                                    echo ($acceptanceHours > 0 ? $acceptanceHours . 'h ' : '') . ($acceptanceMins > 0 ? $acceptanceMins . 'm' : '0m');
                                @endphp
                            </div>
                            <div class="text-blue-600 text-sm">Aceptación</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-700" id="responseSummary">
                                @php
                                    $responseMinutes = old('response_time_minutes', $sla->response_time_minutes);
                                    $responseHours = floor($responseMinutes / 60);
                                    $responseMins = $responseMinutes % 60;
                                    echo ($responseHours > 0 ? $responseHours . 'h ' : '') . ($responseMins > 0 ? $responseMins . 'm' : '0m');
                                @endphp
                            </div>
                            <div class="text-blue-600 text-sm">Respuesta</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-700" id="resolutionSummary">
                                @php
                                    $resolutionMinutes = old('resolution_time_minutes', $sla->resolution_time_minutes);
                                    $resolutionHours = floor($resolutionMinutes / 60);
                                    $resolutionMins = $resolutionMinutes % 60;
                                    echo ($resolutionHours > 0 ? $resolutionHours . 'h ' : '') . ($resolutionMins > 0 ? $resolutionMins . 'm' : '0m');
                                @endphp
                            </div>
                            <div class="text-blue-600 text-sm">Resolución</div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="mt-8 flex justify-between items-center pt-6 border-t border-gray-200">
                    <div>
                        @if($sla->serviceRequests->count() > 0)
                            <p class="text-sm text-orange-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas.
                            </p>
                        @endif
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('slas.show', $sla) }}"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out flex items-center">
                            <i class="fas fa-save mr-2"></i>Actualizar SLA
                        </button>
                    </div>
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

    const acceptanceInput = document.getElementById('acceptance_time_minutes');
    const responseInput = document.getElementById('response_time_minutes');
    const resolutionInput = document.getElementById('resolution_time_minutes');
    const validationDiv = document.getElementById('timeValidation');
    const validationMessage = document.getElementById('validationMessage');
    const acceptanceSummary = document.getElementById('acceptanceSummary');
    const responseSummary = document.getElementById('responseSummary');
    const resolutionSummary = document.getElementById('resolutionSummary');

    // Datos iniciales del SLA
    const currentFamilyId = '{{ $sla->service_family_id }}';
    const currentServiceSubserviceId = '{{ $sla->service_subservice_id }}';
    const currentServiceId = '{{ $sla->service_subservice->service_id ?? "" }}';
    const currentSubServiceId = '{{ $sla->service_subservice->sub_service_id ?? "" }}';

    console.log('Datos iniciales del SLA:', {
        familyId: currentFamilyId,
        serviceSubserviceId: currentServiceSubserviceId,
        serviceId: currentServiceId,
        subServiceId: currentSubServiceId
    });

    // Estrategia de carga
    function loadInitialData() {
        const hasServiceSubserviceData = currentServiceId && currentSubServiceId;

        if (hasServiceSubserviceData) {
            console.log('✅ Usando datos precargados del servidor');
            loadServiceOptions(currentFamilyId, currentServiceId, currentSubServiceId);
        } else if (currentServiceSubserviceId) {
            console.log('🔄 Intentando cargar desde API con ID:', currentServiceSubserviceId);
            attemptLoadFromAPI(currentServiceSubserviceId);
        } else {
            console.log('⚠️ Cargando solo servicios (sin datos específicos)');
            loadServiceOptions(currentFamilyId);
        }
    }

    // Intentar cargar desde API
    function attemptLoadFromAPI(serviceSubserviceId) {
        showLoadingState();

        fetch(`/api/service-subservices/${serviceSubserviceId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Datos cargados desde API:', data);

                if (data.error) {
                    throw new Error(data.error);
                }

                const serviceId = data.service_id;
                const subServiceId = data.sub_service_id;

                if (serviceId && subServiceId) {
                    loadServiceOptions(currentFamilyId, serviceId, subServiceId);
                } else {
                    throw new Error('Datos incompletos desde API');
                }
            })
            .catch(error => {
                console.error('❌ Error cargando desde API:', error);
                handleAPIFailure();
            });
    }

    // Manejar fallo de API
    function handleAPIFailure() {
        console.log('🔄 Fallback: cargando servicios disponibles');
        showInfoMessage('No se pudieron cargar los datos específicos del servicio. Por favor, seleccione manualmente la combinación correcta.');
        loadServiceOptions(currentFamilyId);
    }

    // Mostrar estado de carga
    function showLoadingState() {
        serviceSelect.innerHTML = '<option value="">Buscando datos del servicio...</option>';
        subServiceSelect.innerHTML = '<option value="">Cargando...</option>';
    }

    // Mostrar mensaje informativo
    function showInfoMessage(message) {
        const existingMessage = document.querySelector('.form-info-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const infoDiv = document.createElement('div');
        infoDiv.className = 'form-info-message bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4';
        infoDiv.innerHTML = '<i class="fas fa-info-circle mr-2"></i><strong>Información:</strong> ' + message;

        const form = document.getElementById('slaForm');
        form.parentNode.insertBefore(infoDiv, form);
    }

    // Mostrar error
    function showError(message) {
        const existingError = document.querySelector('.form-error-message');
        if (existingError) {
            existingError.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        errorDiv.innerHTML = '<strong>Error:</strong> ' + message;

        const form = document.getElementById('slaForm');
        form.parentNode.insertBefore(errorDiv, form);

        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Cargar opciones de servicios
    function loadServiceOptions(familyId, targetServiceId = null, targetSubServiceId = null) {
        serviceSelect.innerHTML = '<option value="">Cargando servicios...</option>';
        serviceSelect.disabled = true;

        fetch(`/api/service-families/${familyId}/services`)
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar servicios');
                return response.json();
            })
            .then(services => {
                serviceSelect.innerHTML = '<option value="">Seleccione un servicio</option>';

                services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = service.name + (service.code ? ' (' + service.code + ')' : '');

                    if (service.id == targetServiceId) {
                        option.selected = true;
                        console.log('🎯 Servicio seleccionado:', service.name);
                    }

                    serviceSelect.appendChild(option);
                });

                serviceSelect.disabled = false;

                if (targetServiceId) {
                    console.log('🔄 Cargando subservicios para servicio:', targetServiceId);
                    loadSubServiceOptions(targetServiceId, targetSubServiceId);
                } else {
                    resetSubServiceSelect();
                }
            })
            .catch(error => {
                console.error('Error cargando servicios:', error);
                serviceSelect.innerHTML = '<option value="">Error al cargar servicios</option>';
                serviceSelect.disabled = false;
                showError('No se pudieron cargar los servicios. Por favor, recargue la página.');
            });
    }

    // Cargar opciones de subservicios
    function loadSubServiceOptions(serviceId, targetSubServiceId = null) {
        subServiceSelect.innerHTML = '<option value="">Cargando subservicios...</option>';
        subServiceSelect.disabled = true;

        fetch(`/api/services/${serviceId}/sub-services`)
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar subservicios');
                return response.json();
            })
            .then(subServices => {
                subServiceSelect.innerHTML = '<option value="">Seleccione un subservicio</option>';

                subServices.forEach(subService => {
                    const option = document.createElement('option');
                    option.value = subService.id;
                    option.textContent = subService.name + (subService.code ? ' (' + subService.code + ')' : '');
                    option.dataset.familyId = serviceFamilySelect.value;
                    option.dataset.serviceId = serviceId;
                    option.dataset.subServiceId = subService.id;

                    if (subService.id == targetSubServiceId) {
                        option.selected = true;
                        console.log('🎯 Subservicio seleccionado:', subService.name);
                        updateServiceSubserviceId(serviceFamilySelect.value, serviceId, targetSubServiceId);
                    }

                    subServiceSelect.appendChild(option);
                });

                subServiceSelect.disabled = false;
                console.log('✅ Subservicios cargados:', subServices.length);
            })
            .catch(error => {
                console.error('Error cargando subservicios:', error);
                subServiceSelect.innerHTML = '<option value="">Error al cargar subservicios</option>';
                subServiceSelect.disabled = false;
            });
    }

    // Reset selector de subservicios
    function resetSubServiceSelect() {
        subServiceSelect.innerHTML = '<option value="">Primero seleccione un servicio</option>';
        subServiceSelect.disabled = true;
        serviceSubserviceHidden.value = '';
    }

    // Event listeners para los selectores
    serviceFamilySelect.addEventListener('change', function() {
        const familyId = this.value;
        if (familyId) {
            loadServiceOptions(familyId);
            resetSubServiceSelect();
        } else {
            serviceSelect.innerHTML = '<option value="">Primero seleccione una familia</option>';
            serviceSelect.disabled = true;
            resetSubServiceSelect();
        }
    });

    serviceSelect.addEventListener('change', function() {
        const serviceId = this.value;
        if (serviceId) {
            loadSubServiceOptions(serviceId);
        } else {
            resetSubServiceSelect();
        }
    });

    subServiceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const familyId = selectedOption.dataset.familyId;
            const serviceId = selectedOption.dataset.serviceId;
            const subServiceId = selectedOption.dataset.subServiceId;
            updateServiceSubserviceId(familyId, serviceId, subServiceId);
        } else {
            serviceSubserviceHidden.value = '';
        }
    });

    // Actualizar service_subservice_id
    function updateServiceSubserviceId(familyId, serviceId, subServiceId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/api/service-subservices/find-or-create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                service_family_id: familyId,
                service_id: serviceId,
                sub_service_id: subServiceId
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Error al buscar/crear service subservice');
            return response.json();
        })
        .then(result => {
            if (result.error) throw new Error(result.error);
            serviceSubserviceHidden.value = result.id;
            console.log('Service Subservice ID actualizado:', result.id);
        })
        .catch(error => {
            console.error('Error:', error);
            serviceSubserviceHidden.value = '';
            showError('Error al actualizar el servicio. Por favor, intente nuevamente.');
        });
    }

    // =========================================================================
    // FUNCIONES DE VALIDACIÓN DE TIEMPOS (FALTANTES)
    // =========================================================================

    function formatTimeDisplay(minutes) {
        if (!minutes) return '--';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const parts = [];
        if (hours > 0) parts.push(hours + 'h');
        if (mins > 0) parts.push(mins + 'm');
        return parts.length > 0 ? parts.join(' ') : '0m';
    }

    function updateTimeSummaries() {
        acceptanceSummary.textContent = formatTimeDisplay(acceptanceInput.value);
        responseSummary.textContent = formatTimeDisplay(responseInput.value);
        resolutionSummary.textContent = formatTimeDisplay(resolutionInput.value);
    }

    function validateTimes() {
        const acceptance = parseInt(acceptanceInput.value) || 0;
        const response = parseInt(responseInput.value) || 0;
        const resolution = parseInt(resolutionInput.value) || 0;

        updateTimeSummaries();

        if (acceptance > 0 && response > 0 && resolution > 0) {
            let isValid = true;
            let message = '';
            let icon = 'fa-info-circle';
            let bgColor = 'bg-gray-50 border-gray-200';
            let textColor = 'text-gray-700';

            if (acceptance >= response) {
                isValid = false;
                message = 'El tiempo de aceptación debe ser MENOR que el tiempo de respuesta';
                icon = 'fa-exclamation-triangle';
                bgColor = 'bg-red-50 border-red-200';
                textColor = 'text-red-700';
            } else if (response >= resolution) {
                isValid = false;
                message = 'El tiempo de respuesta debe ser MENOR que el tiempo de resolución';
                icon = 'fa-exclamation-triangle';
                bgColor = 'bg-red-50 border-red-200';
                textColor = 'text-red-700';
            } else {
                message = 'Los tiempos están correctamente configurados';
                icon = 'fa-check-circle';
                bgColor = 'bg-green-50 border-green-200';
                textColor = 'text-green-700';
            }

            validationDiv.className = 'mt-4 p-4 rounded-md border transition-all duration-300 ' + bgColor;
            validationMessage.className = 'text-sm font-medium ' + textColor;
            validationMessage.innerHTML = '<i class="fas ' + icon + ' mr-2"></i>' + message;

            return isValid;
        }

        validationDiv.className = 'mt-4 p-4 bg-gray-50 border border-gray-200 rounded-md transition-all duration-300';
        validationMessage.className = 'text-sm font-medium text-gray-700';
        validationMessage.innerHTML = '<i class="fas fa-info-circle mr-2"></i>Los tiempos deben seguir: Aceptación &lt; Respuesta &lt; Resolución';

        return true;
    }

    // Event listeners para validación de tiempos
    [acceptanceInput, responseInput, resolutionInput].forEach(input => {
        input.addEventListener('input', validateTimes);
    });

    // Validación antes del envío
    document.getElementById('slaForm').addEventListener('submit', function(e) {
        if (!serviceSubserviceHidden.value) {
            e.preventDefault();
            showError('Debe seleccionar un subservicio válido antes de actualizar el SLA.');
            return;
        }

        if (!validateTimes()) {
            e.preventDefault();
            validationDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            validationDiv.classList.add('animate-pulse');
            setTimeout(function() {
                validationDiv.classList.remove('animate-pulse');
            }, 1000);
        }
    });

    // Advertencias para cambios críticos
    @if($sla->serviceRequests->count() > 0)
    const criticalitySelect = document.getElementById('criticality_level');

    criticalitySelect.addEventListener('change', function() {
        if (this.value !== '{{ $sla->criticality_level }}') {
            if (!confirm('⚠️ ¿Está seguro de cambiar el nivel de criticidad? Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas.')) {
                this.value = '{{ $sla->criticality_level }}';
            }
        }
    });

    serviceFamilySelect.addEventListener('change', function() {
        if (this.value !== '{{ $sla->service_family_id }}') {
            if (!confirm('⚠️ ¿Está seguro de cambiar la familia de servicio? Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas.')) {
                this.value = '{{ $sla->service_family_id }}';
            }
        }
    });
    @endif

    // Inicializar
    loadInitialData();
    validateTimes(); // ✅ Ahora esta función existe
});
</script>

<style>
.animate-pulse {
    animation: pulse 0.5s ease-in-out;
}

.form-error-message, .form-info-message {
    animation: fadeIn 0.3s ease-in;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
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
