<!-- Sección de Rutas Web (Opcional) -->
<div class="md:col-span-2">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Rutas Web (Opcional)</h3>
        <button type="button" id="toggleWebRoutes"
            class="bg-blue-100 text-blue-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-200 transition-colors">
            <i class="fas fa-plus mr-2"></i>Agregar Rutas Web
        </button>
    </div>

    <!-- Contenedor de rutas web (inicialmente oculto) -->
    <div id="webRoutesSection" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <!-- Rutas Web Múltiples -->
        <div>
            <label for="web_routes" class="block text-sm font-medium text-gray-700">Rutas Web (URLs)</label>
            <textarea name="web_routes" id="web_routes" rows="3"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ingrese una o varias URLs separadas por comas">{{ old('web_routes') }}</textarea>
            <small class="text-gray-500 text-xs mt-1">
                Separe múltiples URLs con comas. La primera URL será considerada como la principal.
            </small>
            @error('web_routes')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ruta Web Principal -->
        <div>
            <label for="main_web_route" class="block text-sm font-medium text-gray-700">Ruta Web Principal</label>
            <input type="url" name="main_web_route" id="main_web_route" value="{{ old('main_web_route') }}"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="https://ejemplo.com">
            <small class="text-gray-500 text-xs mt-1">
                URL principal relacionada con esta solicitud.
            </small>
            @error('main_web_route')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botón para ocultar la sección -->
        <div class="flex justify-end">
            <button type="button" id="hideWebRoutes"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i>Ocultar Rutas Web
            </button>
        </div>
    </div>
</div>
