<!-- SLA -->
<div class="md:col-span-2">
    <label for="sla_id" class="block text-sm font-medium text-gray-700">Acuerdo de Nivel de Servicio (SLA) *</label>
    <select name="sla_id" id="sla_id" required
        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Seleccione un sub-servicio primero</option>
    </select>

    <!-- Botón para crear nuevo SLA -->
    <div id="createSlaButton" class="mt-2 hidden">
        <button type="button"
            class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Crear Nuevo SLA para este Sub-Servicio
        </button>
    </div>

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
