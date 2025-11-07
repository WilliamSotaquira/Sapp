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
