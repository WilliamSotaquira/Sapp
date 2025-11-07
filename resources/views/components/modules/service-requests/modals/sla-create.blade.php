<!-- Modal para crear nuevo SLA -->
<div id="createSlaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo SLA</h3>

            <form id="createSlaForm">
                @csrf
                <input type="hidden" id="modal_sub_service_id" name="sub_service_id">

                <div class="grid grid-cols-1 gap-4 mb-4">
                    <!-- Nombre del SLA -->
                    <div>
                        <label for="sla_name" class="block text-sm font-medium text-gray-700">Nombre del SLA *</label>
                        <input type="text" id="sla_name" name="name" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ej: SLA Básico - Crítico">
                    </div>

                    <!-- Nivel de Criticidad -->
                    <div>
                        <label for="sla_criticality" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
                        <select id="sla_criticality" name="criticality_level" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione criticidad</option>
                            @foreach($criticalityLevels as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tiempos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="modal_acceptance_time" class="block text-sm font-medium text-gray-700">Tiempo de Aceptación (minutos) *</label>
                            <input type="number" id="modal_acceptance_time" name="acceptance_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="30">
                        </div>
                        <div>
                            <label for="modal_response_time" class="block text-sm font-medium text-gray-700">Tiempo de Respuesta (minutos) *</label>
                            <input type="number" id="modal_response_time" name="response_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="60">
                        </div>
                        <div>
                            <label for="modal_resolution_time" class="block text-sm font-medium text-gray-700">Tiempo de Resolución (minutos) *</label>
                            <input type="number" id="modal_resolution_time" name="resolution_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="240">
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="sla_description" class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea id="sla_description" name="description" rows="3"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Descripción opcional del SLA"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" id="closeSlaModal"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Crear SLA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
