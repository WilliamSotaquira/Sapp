@php
    /** @var \App\Models\Company|null $company */
    $company = $company ?? null;
@endphp

<div class="space-y-6">
    <div>
        <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">
            Logo
        </label>
        <input
            type="file"
            id="logo"
            name="logo"
            accept=".jpg,.jpeg,.png,.webp,.svg"
            class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('logo') border-red-500 @enderror"
        >
        <p class="text-xs text-gray-500 mt-1">Formatos permitidos: JPG, PNG, WEBP, SVG. Tamaño máximo: 2MB.</p>
        @error('logo')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror

        @if(!empty($company?->logo_path))
            <div class="mt-3 flex items-center gap-3">
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo de {{ $company->name }}" class="h-14 w-14 object-contain border border-gray-200 rounded bg-white">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="remove_logo" value="1" class="mr-2">
                    Quitar logo actual
                </label>
            </div>
        @endif
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Nombre de la entidad <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $company->name ?? '') }}"
            required
            maxlength="255"
            class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
            placeholder="Ej: Secretaría de Movilidad"
        >
        @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="nit" class="block text-sm font-medium text-gray-700 mb-1">
            NIT
        </label>
        <input
            type="text"
            id="nit"
            name="nit"
            value="{{ old('nit', $company->nit ?? '') }}"
            maxlength="50"
            class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('nit') border-red-500 @enderror"
            placeholder="Ej: 900123456-7"
        >
        @error('nit')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
            Dirección de contacto
        </label>
        <input
            type="text"
            id="address"
            name="address"
            value="{{ old('address', $company->address ?? '') }}"
            maxlength="255"
            class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror"
            placeholder="Ej: Calle 13 # 37-35"
        >
        @error('address')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">
                Color principal
            </label>
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    id="primary_color"
                    value="{{ old('primary_color', $company->primary_color ?? '#2563EB') }}"
                    class="w-12 h-12 border border-gray-300 rounded cursor-pointer"
                    oninput="document.getElementById('primary_color_hex').value = this.value"
                >
                <input
                    type="text"
                    id="primary_color_hex"
                    name="primary_color"
                    value="{{ old('primary_color', $company->primary_color ?? '') }}"
                    maxlength="7"
                    class="flex-1 border border-gray-300 rounded-md shadow-sm p-3 font-mono focus:ring-blue-500 focus:border-blue-500 @error('primary_color') border-red-500 @enderror"
                    placeholder="#2563EB"
                >
            </div>
            @error('primary_color')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="alternate_color" class="block text-sm font-medium text-gray-700 mb-1">
                Color alterno
            </label>
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    id="alternate_color"
                    value="{{ old('alternate_color', $company->alternate_color ?? '#0F172A') }}"
                    class="w-12 h-12 border border-gray-300 rounded cursor-pointer"
                    oninput="document.getElementById('alternate_color_hex').value = this.value"
                >
                <input
                    type="text"
                    id="alternate_color_hex"
                    name="alternate_color"
                    value="{{ old('alternate_color', $company->alternate_color ?? '') }}"
                    maxlength="7"
                    class="flex-1 border border-gray-300 rounded-md shadow-sm p-3 font-mono focus:ring-blue-500 focus:border-blue-500 @error('alternate_color') border-red-500 @enderror"
                    placeholder="#0F172A"
                >
            </div>
            @error('alternate_color')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="contrast_color" class="block text-sm font-medium text-gray-700 mb-1">
                Color de contraste
            </label>
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    id="contrast_color"
                    value="{{ old('contrast_color', $company->contrast_color ?? '#FFFFFF') }}"
                    class="w-12 h-12 border border-gray-300 rounded cursor-pointer"
                    oninput="document.getElementById('contrast_color_hex').value = this.value"
                >
                <input
                    type="text"
                    id="contrast_color_hex"
                    name="contrast_color"
                    value="{{ old('contrast_color', $company->contrast_color ?? '') }}"
                    maxlength="7"
                    class="flex-1 border border-gray-300 rounded-md shadow-sm p-3 font-mono focus:ring-blue-500 focus:border-blue-500 @error('contrast_color') border-red-500 @enderror"
                    placeholder="#FFFFFF"
                >
            </div>
            @error('contrast_color')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
