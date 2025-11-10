@props(['criticalityLevels' => []])

@php
    // Manejar diferentes tipos de datos para criticalityLevels
    if (is_string($criticalityLevels)) {
        $criticalityLevels = [];
    }
@endphp

<div class="sla-fields space-y-4">
    <!-- Información de SLA -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-800 mb-2">Información de SLA</h3>

        <!-- Nivel de Criticidad -->
        <div class="mb-4">
            <label for="criticality_level" class="block text-sm font-medium text-gray-700 mb-1">
                Nivel de Criticidad *
            </label>
            <select name="criticality_level" id="criticality_level" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Seleccionar nivel de criticidad</option>
                @foreach($criticalityLevels as $key => $level)
                    @if(is_object($level) && property_exists($level, 'id') && property_exists($level, 'name'))
                        <!-- Si es objeto con propiedades id y name -->
                        <option value="{{ $level->id }}"
                                {{ old('criticality_level') == $level->id ? 'selected' : '' }}>
                            {{ $level->name }}
                        </option>
                    @elseif(is_array($level) && isset($level['id']) && isset($level['name']))
                        <!-- Si es array asociativo -->
                        <option value="{{ $level['id'] }}"
                                {{ old('criticality_level') == $level['id'] ? 'selected' : '' }}>
                            {{ $level['name'] }}
                        </option>
                    @elseif(is_string($level))
                        <!-- Si es string simple -->
                        <option value="{{ $level }}"
                                {{ old('criticality_level') == $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @else
                        <!-- Si es cualquier otro tipo -->
                        <option value="{{ $key }}"
                                {{ old('criticality_level') == $key ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endif
                @endforeach
            </select>
            @error('criticality_level')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Campos de tiempo de SLA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="response_time" class="block text-sm font-medium text-gray-700 mb-1">Tiempo de Respuesta</label>
                <div class="flex items-center space-x-2">
                    <input type="number" id="response_time" name="response_time"
                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Horas" readonly>
                    <span class="text-sm text-gray-500 whitespace-nowrap">horas</span>
                </div>
            </div>

            <div>
                <label for="resolution_time" class="block text-sm font-medium text-gray-700 mb-1">Tiempo de Resolución</label>
                <div class="flex items-center space-x-2">
                    <input type="number" id="resolution_time" name="resolution_time"
                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Horas" readonly>
                    <span class="text-sm text-gray-500 whitespace-nowrap">horas</span>
                </div>
            </div>

            <div>
                <label for="sla_name" class="block text-sm font-medium text-gray-700 mb-1">SLA Aplicado</label>
                <input type="text" id="sla_name"
                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100 text-gray-500"
                       placeholder="Seleccione subservicio" readonly>
            </div>
        </div>
    </div>
</div>
