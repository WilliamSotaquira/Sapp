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
