@props(['subServices' => [], 'criticalityLevels' => []])

<div class="assignment-fields space-y-4">

    <!-- Campos de SLA -->

    <div class="md:grid-flow-row-dense md:grid-3 md:grid-cols-1 md:gap-4">

        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 pb-4">Detalles del Servicio</h3>

            <!-- Sub-Servicio -->
            <div class="md:col-span-1 mb-4">
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

            <!-- Selector de SLA -->
            <div class="mb-4">
                <label for="sla_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Seleccionar SLA
                </label>
                <div class="flex gap-2">
                    <select id="sla_id" name="sla_id"
                        class="flex-1 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un sub-servicio primero</option>
                    </select>
                    <button type="button" id="createSlaButton"
                        class="hidden bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Crear SLA
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
