<div>
    <label for="criticality" class="block text-xs font-medium text-gray-700 mb-2">
        <i class="fas fa-flag mr-2"></i>Prioridad
    </label>
    <select id="criticality"
            name="criticality"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Todas las prioridades</option>
        <option value="BAJA" {{ request('criticality') == 'BAJA' ? 'selected' : '' }}>Baja</option>
        <option value="MEDIA" {{ request('criticality') == 'MEDIA' ? 'selected' : '' }}>Media</option>
        <option value="ALTA" {{ request('criticality') == 'ALTA' ? 'selected' : '' }}>Alta</option>
        <option value="CRITICA" {{ request('criticality') == 'CRITICA' ? 'selected' : '' }}>Crítica</option>
    </select>
</div>
