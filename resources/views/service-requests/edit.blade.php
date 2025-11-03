@extends('layouts.app')

@section('title', 'Editar Solicitud ' . $serviceRequest->ticket_number)

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
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-blue-600 hover:text-blue-700">{{ $serviceRequest->ticket_number }}</a>
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
        @if($serviceRequest->status !== 'PENDIENTE')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Solo se pueden editar solicitudes en estado PENDIENTE. Esta solicitud está en estado <strong>{{ $serviceRequest->status }}</strong>.
            </div>
        @endif

        <form action="{{ route('service-requests.update', $serviceRequest) }}" method="POST" id="serviceRequestForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información de la Solicitud -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Información de la Solicitud</h3>
                </div>

                <!-- Ticket Number (solo lectura) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Ticket</label>
                    <div class="mt-1 p-2 bg-gray-100 rounded border text-gray-600">
                        {{ $serviceRequest->ticket_number }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">El número de ticket no se puede modificar</p>
                </div>

                <!-- Estado Actual (solo lectura) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado Actual</label>
                    <div class="mt-1">
                        @php
                            $statusColors = [
                                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                'RESUELTA' => 'bg-green-100 text-green-800',
                                'CERRADA' => 'bg-gray-100 text-gray-800',
                                'CANCELADA' => 'bg-red-100 text-red-800'
                            ];
                        @endphp
                        <span class="px-3 py-2 text-sm font-semibold rounded-full {{ $statusColors[$serviceRequest->status] }}">
                            {{ $serviceRequest->status }}
                        </span>
                    </div>
                </div>

                <!-- Familia de Servicio (solo lectura) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Familia de Servicio</label>
                    <div class="mt-1 p-2 bg-gray-100 rounded border text-gray-600">
                        {{ $serviceRequest->subService->service->family->name }}
                    </div>
                </div>

                <!-- Servicio (solo lectura) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Servicio</label>
                    <div class="mt-1 p-2 bg-gray-100 rounded border text-gray-600">
                        {{ $serviceRequest->subService->service->name }}
                    </div>
                </div>

                <!-- Sub-Servicio -->
                <div class="md:col-span-2">
                    <label for="sub_service_id" class="block text-sm font-medium text-gray-700">Sub-Servicio *</label>
                    <select name="sub_service_id" id="sub_service_id" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>
                        <option value="">Seleccione un sub-servicio</option>
                        @foreach($subServices as $familyName => $familySubServices)
                            <optgroup label="{{ $familyName }}">
                                @foreach($familySubServices as $subService)
                                    <option value="{{ $subService->id }}"
                                            {{ old('sub_service_id', $serviceRequest->sub_service_id) == $subService->id ? 'selected' : '' }}>
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
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>
                        <option value="">Seleccione un SLA</option>
                        @foreach(\App\Models\ServiceLevelAgreement::where('is_active', true)->get() as $sla)
                            <option value="{{ $sla->id }}"
                                    {{ old('sla_id', $serviceRequest->sla_id) == $sla->id ? 'selected' : '' }}
                                    data-acceptance="{{ $sla->acceptance_time_minutes }}"
                                    data-response="{{ $sla->response_time_minutes }}"
                                    data-resolution="{{ $sla->resolution_time_minutes }}">
                                {{ $sla->name }} ({{ $sla->criticality_level }}) - {{ $sla->serviceFamily->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sla_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Información del SLA seleccionado -->
                    <div id="sla_info" class="mt-2 bg-gray-50 p-3 rounded text-sm hidden">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                            <div><strong>Aceptación:</strong> <span id="acceptance_time"></span></div>
                            <div><strong>Respuesta:</strong> <span id="response_time"></span></div>
                            <div><strong>Resolución:</strong> <span id="resolution_time"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Título -->
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700">Título *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $serviceRequest->title) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Describa brevemente la solicitud"
                           required
                           {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>
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
                              required
                              {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>{{ old('description', $serviceRequest->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nivel de Criticidad -->
                <div>
                    <label for="criticality_level" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
                    <select name="criticality_level" id="criticality_level" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>
                        <option value="">Seleccione criticidad</option>
                        @foreach($criticalityLevels as $level)
                            <option value="{{ $level }}" {{ old('criticality_level', $serviceRequest->criticality_level) == $level ? 'selected' : '' }}>
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
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            {{ $serviceRequest->status !== 'PENDIENTE' ? 'disabled' : '' }}>
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to', $serviceRequest->assigned_to) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Información de Fechas (solo lectura) -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Información del Sistema</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="font-medium text-gray-700">Creada:</label>
                            <p class="text-gray-600">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="font-medium text-gray-700">Actualizada:</label>
                            <p class="text-gray-600">{{ $serviceRequest->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($serviceRequest->accepted_at)
                        <div>
                            <label class="font-medium text-gray-700">Aceptada:</label>
                            <p class="text-gray-600">{{ $serviceRequest->accepted_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif
                        @if($serviceRequest->responded_at)
                        <div>
                            <label class="font-medium text-gray-700">Respondida:</label>
                            <p class="text-gray-600">{{ $serviceRequest->responded_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('service-requests.show', $serviceRequest) }}"
                   class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>

                @if($serviceRequest->status === 'PENDIENTE')
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Actualizar Solicitud
                    </button>
                @else
                    <button type="button"
                            class="bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed"
                            disabled>
                        <i class="fas fa-ban mr-2"></i>No Editable
                    </button>
                @endif
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slaSelect = document.getElementById('sla_id');
    const slaInfo = document.getElementById('sla_info');

    // Mostrar información del SLA seleccionado
    function updateSlaInfo() {
        const selectedOption = slaSelect.options[slaSelect.selectedIndex];

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
    }

    // Mostrar info del SLA actual al cargar la página
    updateSlaInfo();

    // Actualizar info cuando cambia el SLA
    slaSelect.addEventListener('change', updateSlaInfo);

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

    // Si el estado no es PENDIENTE, prevenir envío del formulario
    @if($serviceRequest->status !== 'PENDIENTE')
    document.getElementById('serviceRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('No se puede editar una solicitud que no está en estado PENDIENTE.');
    });
    @endif
});
</script>
@endsection
