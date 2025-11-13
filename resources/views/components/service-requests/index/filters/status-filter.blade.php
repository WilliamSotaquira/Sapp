<div>
    <label for="status" class="block text-xs font-medium text-gray-700 mb-2">
        <i class="fas fa-tag mr-2"></i>Estado
    </label>
    <select id="status"
            name="status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Todos los estados</option>
        <option value="PENDIENTE" {{ request('status') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
        <option value="ACEPTADA" {{ request('status') == 'ACEPTADA' ? 'selected' : '' }}>Aceptada</option>
        <option value="EN_PROCESO" {{ request('status') == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
        <option value="PAUSADA" {{ request('status') == 'PAUSADA' ? 'selected' : '' }}>Pausada</option>
        <option value="RESUELTA" {{ request('status') == 'RESUELTA' ? 'selected' : '' }}>Resuelta</option>
        <option value="CERRADA" {{ request('status') == 'CERRADA' ? 'selected' : '' }}>Cerrada</option>
        <option value="CANCELADA" {{ request('status') == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
    </select>
</div>
