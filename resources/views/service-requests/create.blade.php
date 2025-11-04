@extends('layouts.app')

@section('title', 'Crear Solicitud de Servicio')

@section('breadcrumb')
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-700">Solicitudes</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Nueva Solicitud</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="bg-white shadow-md rounded-lg p-6">
    <form action="{{ route('service-requests.store') }}" method="POST" id="serviceRequestForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Familia de Servicio -->
            <div class="md:col-span-2">
                <label for="service_family_filter" class="block text-sm font-medium text-gray-700">Filtrar por Familia de Servicio</label>
                <select id="service_family_filter" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas las familias</option>
                    @foreach($subServices->keys() as $familyName)
                    <option value="{{ $familyName }}">{{ $familyName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sub-Servicio -->
            <div class="md:col-span-2">
                <label for="sub_service_id" class="block text-sm font-medium text-gray-700">Sub-Servicio *</label>
                <select name="sub_service_id" id="sub_service_id" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccione un sub-servicio</option>
                    @foreach($subServices as $familyName => $familySubServices)
                    <optgroup label="{{ $familyName }}" data-family="{{ $familyName }}">
                        @foreach($familySubServices as $subService)
                        <option value="{{ $subService->id }}"
                            data-family="{{ $familyName }}"
                            data-service="{{ $subService->service->name }}">
                            {{ $subService->name }} - {{ $subService->service->name }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                @error('sub_service_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- SLA -->
            <div class="md:col-span-2">
                <label for="sla_id" class="block text-sm font-medium text-gray-700">Acuerdo de Nivel de Servicio (SLA) *</label>
                <select name="sla_id" id="sla_id" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccione un sub-servicio primero</option>
                </select>
                @error('sla_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <div id="sla_info" class="mt-2 hidden">
                    <div class="bg-gray-50 p-3 rounded text-sm">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                            <div><strong>Aceptación:</strong> <span id="acceptance_time"></span></div>
                            <div><strong>Respuesta:</strong> <span id="response_time"></span></div>
                            <div><strong>Resolución:</strong> <span id="resolution_time"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Título -->
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Título *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Describa brevemente la solicitud"
                    required>
                @error('title')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Descripción -->
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Descripción Detallada *</label>
                <textarea name="description" id="description" rows="4"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Describa en detalle el problema o requerimiento"
                    required>{{ old('description') }}</textarea>
                @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sección de Rutas Web (Opcional) -->
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Rutas Web (Opcional)</h3>
                    <button type="button" id="toggleWebRoutes"
                        class="bg-blue-100 text-blue-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-200 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Agregar Rutas Web
                    </button>
                </div>

                <!-- Contenedor de rutas web (inicialmente oculto) -->
                <div id="webRoutesSection" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">

                    <!-- Rutas Web Múltiples -->
                    <div>
                        <label for="web_routes" class="block text-sm font-medium text-gray-700">Rutas Web (URLs)</label>
                        <textarea name="web_routes" id="web_routes" rows="3"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ingrese una o varias URLs separadas por comas&#10;Ejemplo: https://ejemplo.com, https://app.ejemplo.com/dashboard">{{ old('web_routes') }}</textarea>
                        <small class="text-gray-500 text-xs mt-1">
                            Separe múltiples URLs con comas. La primera URL será considerada como la principal.
                        </small>
                        @error('web_routes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ruta Web Principal -->
                    <div>
                        <label for="main_web_route" class="block text-sm font-medium text-gray-700">Ruta Web Principal</label>
                        <input type="url" name="main_web_route" id="main_web_route" value="{{ old('main_web_route') }}"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="https://ejemplo.com">
                        <small class="text-gray-500 text-xs mt-1">
                            URL principal relacionada con esta solicitud (se llenará automáticamente con la primera URL ingresada).
                        </small>
                        @error('main_web_route')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botón para ocultar la sección -->
                    <div class="flex justify-end">
                        <button type="button" id="hideWebRoutes"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400 transition-colors">
                            <i class="fas fa-times mr-2"></i>Ocultar Rutas Web
                        </button>
                    </div>
                </div>
            </div>

            <!-- Nivel de Criticidad -->
            <div>
                <label for="criticality_level" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
                <select name="criticality_level" id="criticality_level" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccione criticidad</option>
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

            <!-- Asignado a -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-gray-700">Asignar a</label>
                <select name="assigned_to" id="assigned_to"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Sin asignar</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
                @error('assigned_to')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Botones -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('service-requests.index') }}"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Cancelar
            </a>
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Crear Solicitud
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const familyFilter = document.getElementById('service_family_filter');
    const subServiceSelect = document.getElementById('sub_service_id');
    const slaSelect = document.getElementById('sla_id');
    const slaInfo = document.getElementById('sla_info');
    const toggleWebRoutesBtn = document.getElementById('toggleWebRoutes');
    const hideWebRoutesBtn = document.getElementById('hideWebRoutes');
    const webRoutesSection = document.getElementById('webRoutesSection');
    const webRoutesTextarea = document.getElementById('web_routes');
    const mainWebRouteInput = document.getElementById('main_web_route');

    // =============================================
    // FUNCIONALIDAD RUTAS WEB
    // =============================================
    if (toggleWebRoutesBtn && hideWebRoutesBtn && webRoutesSection) {
        toggleWebRoutesBtn.addEventListener('click', function() {
            webRoutesSection.classList.remove('hidden');
            this.classList.add('hidden');
        });

        hideWebRoutesBtn.addEventListener('click', function() {
            webRoutesSection.classList.add('hidden');
            toggleWebRoutesBtn.classList.remove('hidden');
            webRoutesTextarea.value = '';
            mainWebRouteInput.value = '';
        });
    }

    // =============================================
    // FILTRADO POR FAMILIA
    // =============================================
    if (familyFilter && subServiceSelect) {
        familyFilter.addEventListener('change', function() {
            const selectedFamily = this.value;
            const options = subServiceSelect.querySelectorAll('option, optgroup');

            options.forEach(option => {
                if (option.tagName === 'OPTGROUP') {
                    option.style.display = !selectedFamily || option.getAttribute('data-family') === selectedFamily ? 'block' : 'none';
                } else if (option.tagName === 'OPTION' && option.value) {
                    const optionFamily = option.getAttribute('data-family');
                    option.style.display = !selectedFamily || optionFamily === selectedFamily ? 'block' : 'none';
                }
            });

            subServiceSelect.value = '';
            if (slaSelect) {
                slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
            }
            if (slaInfo) {
                slaInfo.classList.add('hidden');
            }
        });
    }

    // =============================================
    // CARGA DINÁMICA DE SLAs
    // =============================================
    if (subServiceSelect && slaSelect) {
        subServiceSelect.addEventListener('change', function() {
            cargarSLAs(this.value);
        });

        const options = subServiceSelect.querySelectorAll('option');
        options.forEach((option) => {
            option.addEventListener('click', function() {
                setTimeout(() => {
                    if (subServiceSelect.value === this.value) {
                        subServiceSelect.dispatchEvent(new Event('change'));
                    }
                }, 50);
            });
        });

        subServiceSelect.addEventListener('blur', function() {
            if (this.value) {
                cargarSLAs(this.value);
            }
        });

        function cargarSLAs(subServiceId) {
            if (!subServiceId) {
                slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
                if (slaInfo) slaInfo.classList.add('hidden');
                return;
            }

            slaSelect.innerHTML = '<option value="">Cargando SLAs...</option>';
            if (slaInfo) slaInfo.classList.add('hidden');

            fetch(`/sub-services/${subServiceId}/slas`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error HTTP: ' + response.status);
                    }
                    return response.json();
                })
                .then(slas => {
                    if (slas.length === 0) {
                        slaSelect.innerHTML = '<option value="">No hay SLAs disponibles para este sub-servicio</option>';
                        return;
                    }

                    slaSelect.innerHTML = '<option value="">Seleccione un SLA</option>';
                    slas.forEach(sla => {
                        const option = document.createElement('option');
                        option.value = sla.id;
                        option.textContent = `${sla.name} (${sla.criticality_level})`;
                        option.setAttribute('data-acceptance', sla.acceptance_time_minutes);
                        option.setAttribute('data-response', sla.response_time_minutes);
                        option.setAttribute('data-resolution', sla.resolution_time_minutes);
                        slaSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    slaSelect.innerHTML = '<option value="">Error al cargar SLAs</option>';
                });
        }
    }

    // =============================================
    // INFORMACIÓN DEL SLA SELECCIONADO
    // =============================================
    if (slaSelect && slaInfo) {
        slaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value && selectedOption.hasAttribute('data-acceptance')) {
                const acceptance = selectedOption.getAttribute('data-acceptance');
                const response = selectedOption.getAttribute('data-response');
                const resolution = selectedOption.getAttribute('data-resolution');

                document.getElementById('acceptance_time').textContent = formatTime(acceptance);
                document.getElementById('response_time').textContent = formatTime(response);
                document.getElementById('resolution_time').textContent = formatTime(resolution);

                slaInfo.classList.remove('hidden');
            } else {
                slaInfo.classList.add('hidden');
            }
        });
    }

    // =============================================
    // AUTO-COMPLETADO RUTAS WEB
    // =============================================
    if (webRoutesTextarea && mainWebRouteInput) {
        webRoutesTextarea.addEventListener('input', function() {
            const routesText = this.value.trim();

            if (routesText) {
                const firstUrl = routesText.split(',')[0].trim();
                if (isValidUrl(firstUrl)) {
                    mainWebRouteInput.value = firstUrl;
                }
            } else {
                mainWebRouteInput.value = '';
            }
        });
    }

    // =============================================
    // VALIDACIÓN DEL FORMULARIO
    // =============================================
    const serviceRequestForm = document.getElementById('serviceRequestForm');
    if (serviceRequestForm) {
        serviceRequestForm.addEventListener('submit', function(e) {
            const subServiceId = document.getElementById('sub_service_id').value;
            const slaId = document.getElementById('sla_id').value;

            if (!subServiceId || !slaId) {
                e.preventDefault();
                alert('Por favor seleccione un sub-servicio y un SLA antes de continuar.');
            }
        });
    }

    // =============================================
    // FUNCIONES UTILITARIAS
    // =============================================
    function formatTime(minutes) {
        minutes = parseInt(minutes);
        if (minutes < 60) {
            return `${minutes} min`;
        } else if (minutes < 1440) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return mins > 0 ? `${hours}h ${mins}min` : `${hours} horas`;
        } else {
            const days = Math.floor(minutes / 1440);
            const hours = Math.floor((minutes % 1440) / 60);
            return hours > 0 ? `${days}d ${hours}h` : `${days} días`;
        }
    }

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});
</script>
@endsection
