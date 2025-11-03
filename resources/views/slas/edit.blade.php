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
                    <a href="{{ route('slas.show', $sla) }}" class="text-blue-600 hover:text-blue-700">{{ $sla->name }}</a>
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
        <form action="{{ route('slas.update', $sla) }}" method="POST" id="slaForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Familia de Servicio -->
                <div class="md:col-span-2">
                    <label for="service_family_id" class="block text-sm font-medium text-gray-700">Familia de Servicio *</label>
                    <select name="service_family_id" id="service_family_id"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
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

                <!-- Nombre del SLA -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre del SLA *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $sla->name) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: SLA Básico Soporte Técnico"
                           required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nivel de Criticidad -->
                <div>
                    <label for="criticality_level" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
                    <select name="criticality_level" id="criticality_level"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
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
                <div class="flex items-end">
                    <label for="is_active" class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', $sla->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">SLA Activo</span>
                    </label>
                </div>

                <!-- Tiempos de Respuesta -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tiempos de Respuesta (en minutos)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Tiempo de Aceptación -->
                        <div>
                            <label for="acceptance_time_minutes" class="block text-sm font-medium text-gray-700">
                                Tiempo de Aceptación *
                            </label>
                            <input type="number" name="acceptance_time_minutes" id="acceptance_time_minutes"
                                   value="{{ old('acceptance_time_minutes', $sla->acceptance_time_minutes) }}"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                   min="1" max="1440" required>
                            <p class="text-xs text-gray-500 mt-1">Tiempo máximo para aceptar la solicitud</p>
                            @error('acceptance_time_minutes')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tiempo de Respuesta -->
                        <div>
                            <label for="response_time_minutes" class="block text-sm font-medium text-gray-700">
                                Tiempo de Respuesta *
                            </label>
                            <input type="number" name="response_time_minutes" id="response_time_minutes"
                                   value="{{ old('response_time_minutes', $sla->response_time_minutes) }}"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                   min="1" max="1440" required>
                            <p class="text-xs text-gray-500 mt-1">Tiempo máximo para dar primera respuesta</p>
                            @error('response_time_minutes')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tiempo de Resolución -->
                        <div>
                            <label for="resolution_time_minutes" class="block text-sm font-medium text-gray-700">
                                Tiempo de Resolución *
                            </label>
                            <input type="number" name="resolution_time_minutes" id="resolution_time_minutes"
                                   value="{{ old('resolution_time_minutes', $sla->resolution_time_minutes) }}"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                   min="1" max="1440" required>
                            <p class="text-xs text-gray-500 mt-1">Tiempo máximo para resolver completamente</p>
                            @error('resolution_time_minutes')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Validación visual de tiempos -->
                    <div id="timeValidation" class="mt-4 p-3 rounded-md {{ $errors->has('acceptance_time_minutes') || $errors->has('response_time_minutes') || $errors->has('resolution_time_minutes') ? 'bg-red-50 border border-red-200' : 'bg-gray-50 border border-gray-200' }}">
                        <div class="flex items-center text-sm">
                            <i class="fas fa-info-circle mr-2 {{ $errors->has('acceptance_time_minutes') || $errors->has('response_time_minutes') || $errors->has('resolution_time_minutes') ? 'text-red-500' : 'text-blue-500' }}"></i>
                            <span id="validationMessage">
                                @if($errors->has('acceptance_time_minutes') || $errors->has('response_time_minutes') || $errors->has('resolution_time_minutes'))
                                    ❌ Los tiempos de respuesta no son coherentes
                                @else
                                    Los tiempos deben seguir: Aceptación < Respuesta < Resolución
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Condiciones -->
                <div class="md:col-span-2">
                    <label for="conditions" class="block text-sm font-medium text-gray-700">Condiciones y Observaciones</label>
                    <textarea name="conditions" id="conditions" rows="4"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describa las condiciones específicas de este SLA, restricciones, horarios de aplicación, etc.">{{ old('conditions', $sla->conditions) }}</textarea>
                    @error('conditions')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Información del Sistema</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Creado:</span>
                        <span class="text-gray-900">{{ $sla->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Última actualización:</span>
                        <span class="text-gray-900">{{ $sla->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Solicitudes asociadas:</span>
                        <span class="text-gray-900">{{ $sla->serviceRequests->count() }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Familia actual:</span>
                        <span class="text-gray-900">{{ $sla->serviceFamily->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Resumen de Tiempos -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Resumen de Tiempos Establecidos:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="font-semibold text-blue-700" id="acceptanceSummary">
                            @php
                                function formatTimeDisplay($minutes) {
                                    $hours = floor($minutes / 60);
                                    $mins = $minutes % 60;
                                    $parts = [];
                                    if ($hours > 0) $parts[] = $hours . 'h';
                                    if ($mins > 0) $parts[] = $mins . 'm';
                                    return $parts ? implode(' ', $parts) : '0m';
                                }
                            @endphp
                            {{ formatTimeDisplay(old('acceptance_time_minutes', $sla->acceptance_time_minutes)) }}
                        </div>
                        <div class="text-blue-600">Aceptación</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-blue-700" id="responseSummary">
                            {{ formatTimeDisplay(old('response_time_minutes', $sla->response_time_minutes)) }}
                        </div>
                        <div class="text-blue-600">Respuesta Inicial</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-blue-700" id="resolutionSummary">
                            {{ formatTimeDisplay(old('resolution_time_minutes', $sla->resolution_time_minutes)) }}
                        </div>
                        <div class="text-blue-600">Resolución Completa</div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-between items-center">
                <div>
                    @if($sla->serviceRequests->count() > 0)
                        <p class="text-sm text-orange-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas.
                            Los cambios afectarán a todas las solicitudes futuras.
                        </p>
                    @endif
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('slas.show', $sla) }}"
                       class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition flex items-center">
                        <i class="fas fa-save mr-2"></i>Actualizar SLA
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const acceptanceInput = document.getElementById('acceptance_time_minutes');
        const responseInput = document.getElementById('response_time_minutes');
        const resolutionInput = document.getElementById('resolution_time_minutes');
        const validationDiv = document.getElementById('timeValidation');
        const validationMessage = document.getElementById('validationMessage');
        const acceptanceSummary = document.getElementById('acceptanceSummary');
        const responseSummary = document.getElementById('responseSummary');
        const resolutionSummary = document.getElementById('resolutionSummary');

        function formatTime(minutes) {
            if (!minutes) return '--';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            if (hours > 0) {
                return `${hours}h ${mins > 0 ? mins + 'm' : ''}`;
            }
            return `${mins}m`;
        }

        function updateTimeSummaries() {
            acceptanceSummary.textContent = formatTime(acceptanceInput.value);
            responseSummary.textContent = formatTime(responseInput.value);
            resolutionSummary.textContent = formatTime(resolutionInput.value);
        }

        function validateTimes() {
            const acceptance = parseInt(acceptanceInput.value) || 0;
            const response = parseInt(responseInput.value) || 0;
            const resolution = parseInt(resolutionInput.value) || 0;

            updateTimeSummaries();

            if (acceptance > 0 && response > 0 && resolution > 0) {
                let isValid = true;
                let message = '';

                if (acceptance >= response) {
                    isValid = false;
                    message = '❌ El tiempo de aceptación debe ser MENOR que el tiempo de respuesta.';
                } else if (response >= resolution) {
                    isValid = false;
                    message = '❌ El tiempo de respuesta debe ser MENOR que el tiempo de resolución.';
                } else {
                    message = '✅ Los tiempos están correctamente configurados.';
                }

                validationDiv.classList.remove('hidden');
                validationMessage.textContent = message;

                if (isValid) {
                    validationDiv.className = 'mt-4 p-3 bg-green-50 border border-green-200 rounded-md';
                    validationMessage.className = 'text-green-700';
                } else {
                    validationDiv.className = 'mt-4 p-3 bg-red-50 border border-red-200 rounded-md';
                    validationMessage.className = 'text-red-700';
                }

                return isValid;
            }

            validationDiv.classList.remove('hidden');
            validationDiv.className = 'mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md';
            validationMessage.textContent = 'Los tiempos deben seguir: Aceptación < Respuesta < Resolución';
            validationMessage.className = 'text-gray-700';

            return true;
        }

        // Event listeners para validación en tiempo real
        acceptanceInput.addEventListener('input', validateTimes);
        responseInput.addEventListener('input', validateTimes);
        resolutionInput.addEventListener('input', validateTimes);

        // Validación inicial
        validateTimes();

        // Validación antes del envío del formulario
        document.getElementById('slaForm').addEventListener('submit', function(e) {
            if (!validateTimes()) {
                e.preventDefault();
                alert('Por favor, corrija los tiempos de respuesta antes de enviar el formulario.');
            }
        });

        // Mostrar advertencia si hay solicitudes asociadas
        @if($sla->serviceRequests->count() > 0)
        const criticalitySelect = document.getElementById('criticality_level');
        const familySelect = document.getElementById('service_family_id');

        criticalitySelect.addEventListener('change', function() {
            if (this.value !== '{{ $sla->criticality_level }}') {
                if (!confirm('⚠️ Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas. ¿Está seguro de cambiar el nivel de criticidad? Esto puede afectar los tiempos de las solicitudes existentes.')) {
                    this.value = '{{ $sla->criticality_level }}';
                }
            }
        });

        familySelect.addEventListener('change', function() {
            if (this.value !== '{{ $sla->service_family_id }}') {
                if (!confirm('⚠️ Este SLA tiene {{ $sla->serviceRequests->count() }} solicitudes asociadas. ¿Está seguro de cambiar la familia de servicio? Esto puede hacer que el SLA no sea aplicable a las solicitudes existentes.')) {
                    this.value = '{{ $sla->service_family_id }}';
                }
            }
        });
        @endif
    });
</script>
@endsection
