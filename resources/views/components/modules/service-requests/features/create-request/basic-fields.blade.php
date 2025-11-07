<!-- Título -->
<div class="md:col-span-2">
    <label for="title" class="block text-sm font-medium text-gray-700">Título *</label>
    <input type="text" name="title" id="title" value="{{ old('title') }}"
        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
        placeholder="Describa brevemente la solicitud"
        required>
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
        required>{{ old('description') }}</textarea>
    @error('description')
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
