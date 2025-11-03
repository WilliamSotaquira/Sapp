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

    // Filtrar sub-servicios por familia
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

        // Resetear selecciones dependientes
        subServiceSelect.value = '';
        slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
        slaInfo.classList.add('hidden');
    });

    // Cargar SLAs cuando se selecciona un sub-servicio
    subServiceSelect.addEventListener('change', function() {
        const subServiceId = this.value;

        if (!subServiceId) {
            slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
            slaInfo.classList.add('hidden');
            return;
        }

        // Mostrar loading
        slaSelect.innerHTML = '<option value="">Cargando SLAs...</option>';
        slaInfo.classList.add('hidden');

        // Hacer petición AJAX para obtener SLAs
        fetch(`/sub-services/${subServiceId}/slas`)
            .then(response => response.json())
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
                console.error('Error:', error);
                slaSelect.innerHTML = '<option value="">Error al cargar SLAs</option>';
            });
    });

    // Mostrar información del SLA seleccionado
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

    function formatTime(minutes) {
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

    // Validación antes de enviar el formulario
    document.getElementById('serviceRequestForm').addEventListener('submit', function(e) {
        const subServiceId = document.getElementById('sub_service_id').value;
        const slaId = document.getElementById('sla_id').value;

        if (!subServiceId || !slaId) {
            e.preventDefault();
            alert('Por favor seleccione un sub-servicio y un SLA antes de continuar.');
        }
    });
});
</script>
@endsection
