{{-- resources/views/components/service-requests/forms/basic-fields.blade.php --}}
@props([
    'serviceRequest' => null,
    'subServices' => [], // Lista de subservicios
    'requesters' => [], // Lista de solicitantes para seleccionar solicitante
    'errors' => null,
    'mode' => 'create', // 'create' or 'edit'
])

<div class="space-y-6">

    {{-- En tu formulario, muestra todos los errores --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-lg font-medium text-red-800 mb-2">Errores de validación:</h3>
            <ul class="list-disc list-inside text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- CAMPOS OCULTOS REQUERIDOS - CON VALORES POR DEFECTO -->
    <input type="hidden" name="sla_id" id="sla_id" value="{{ old('sla_id', '1') }}">
    <input type="hidden" name="web_routes" id="web_routes_json" value="{{ old('web_routes', '[]') }}">
    <input type="hidden" name="requested_by" value="{{ old('requested_by', $serviceRequest->requested_by ?? auth()->id()) }}">

    <!-- SELECTOR DE SOLICITANTE - EDITABLE EN AMBOS MODOS -->
    <div>
        <label for="requester_id" class="block text-sm font-medium text-gray-700 mb-2">
            Solicitante <span class="text-red-500">*</span>
        </label>

        @php
            $currentRequesterId = old('requester_id', $serviceRequest->requester_id ?? null);
        @endphp

        <select name="requester_id" id="requester_id"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('requester_id') border-red-500 @enderror"
            required>
            <option value="">Seleccione un solicitante</option>
            @foreach ($requesters as $requester)
                <option value="{{ $requester->id }}" {{ $currentRequesterId == $requester->id ? 'selected' : '' }}>
                    {{ $requester->name }} - {{ $requester->email }}
                    @if($requester->department)
                        ({{ $requester->department }})
                    @endif
                </option>
            @endforeach
        </select>

        <p class="mt-1 text-sm text-gray-500 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span>Seleccione la persona que realiza la solicitud</span>
        </p>

        @error('requester_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Canal de ingreso -->
    @php
        $entryChannelOptions = \App\Models\ServiceRequest::getEntryChannelOptions();
        $selectedEntryChannel = old('entry_channel', $serviceRequest->entry_channel ?? null);
        $isReportable = old('is_reportable', $serviceRequest->is_reportable ?? true);
    @endphp
    <div>
        <label for="entry_channel" class="block text-sm font-medium text-gray-700 mb-2">
            Canal de ingreso <span class="text-red-500">*</span>
        </label>
        <select name="entry_channel" id="entry_channel"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('entry_channel') border-red-500 @enderror"
            required>
            <option value="">Selecciona un canal</option>
            @foreach ($entryChannelOptions as $value => $option)
                <option value="{{ $value }}" {{ $selectedEntryChannel === $value ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">
            Usa este campo para saber desde qué canal se originó la solicitud.
        </p>
        @error('entry_channel')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Configuración de reportes -->
    <div>
        <div class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <div class="flex items-center h-6">
                <input type="hidden" name="is_reportable" value="1">
                <input id="is_reportable" name="is_reportable" type="checkbox" value="0"
                    class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    {{ $isReportable ? '' : 'checked' }}>
            </div>
            <div class="flex-1">
                <label for="is_reportable" class="block text-sm font-semibold text-gray-800">
                    Excluir esta solicitud de los reportes
                </label>
                <p class="text-sm text-gray-600">
                    Activa esta casilla si la solicitud no debe contarse ni mostrarse en los reportes y exportaciones.
                </p>
            </div>
        </div>
    </div>

    <!-- Resto del formulario permanece igual -->
    <!-- Campo Título -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Título de la Solicitud <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title" id="title" value="{{ old('title', $serviceRequest->title ?? '') }}"
            placeholder="Ingrese un título descriptivo para la solicitud"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('title') border-red-500 @enderror"
            required maxlength="255">
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500">Máximo 255 caracteres</p>
    </div>

    <!-- Campo Descripción -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Descripción Detallada <span class="text-red-500">*</span>
        </label>
        <textarea name="description" id="description" rows="6"
            placeholder="Describa en detalle el problema o requerimiento..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('description') border-red-500 @enderror"
            required>{{ old('description', $serviceRequest->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500">Proporcione todos los detalles necesarios para atender la solicitud</p>
    </div>

    <!-- COMPONENTE REDISEÑADO: Búsqueda en tiempo real para Subservicios AGRUPADOS -->
    <div>
        <label for="sub_service_search" class="block text-sm font-medium text-gray-700 mb-2">
            Subservicio <span class="text-red-500">*</span>
        </label>

        <!-- Campo de búsqueda -->
        <div class="relative mb-2">
            <input type="text" id="sub_service_search" placeholder="Buscar subservicio..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('sub_service_id') border-red-500 @enderror">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Contenedor de resultados -->
        <div id="sub_service_results"
            class="hidden max-h-80 overflow-y-auto border border-gray-300 rounded-lg bg-white shadow-lg z-10">
            <!-- Los resultados se cargarán aquí dinámicamente -->
        </div>

        <!-- Campo oculto para almacenar el valor seleccionado -->
        <select name="sub_service_id" id="sub_service_id" class="hidden" required>
            <option value="">Seleccione un subservicio</option>
            @php
                // Agrupar los subservicios
                $groupedSubServices = [];
                foreach ($subServices as $subService) {
                    $familyName = $subService->service->family->name ?? 'Sin Familia';
                    $serviceName = $subService->service->name ?? 'Sin Servicio';
                    $groupKey = $familyName . '|' . $serviceName;

                    if (!isset($groupedSubServices[$groupKey])) {
                        $groupedSubServices[$groupKey] = [
                            'family_name' => $familyName,
                            'service_name' => $serviceName,
                            'subservices' => [],
                        ];
                    }
                    $groupedSubServices[$groupKey]['subservices'][] = $subService;
                }
            @endphp

            @foreach ($groupedSubServices as $group)
                <optgroup label="{{ $group['family_name'] }} - {{ $group['service_name'] }}">
                    @foreach ($group['subservices'] as $subService)
                        @php
                            $criticalityLevel = 'MEDIA';
                            $slaId = '1';

                            if ($subService->relationLoaded('slas') && $subService->slas->isNotEmpty()) {
                                $sla = $subService->slas->first();
                                $criticalityLevel = $sla->criticality_level ?? 'MEDIA';
                                $slaId = $sla->id ?? '1';
                            }
                        @endphp
                        <option value="{{ $subService->id }}" data-service-id="{{ $subService->service_id }}"
                            data-service-name="{{ $subService->service->name }}"
                            data-family-name="{{ $subService->service->family->name ?? 'Sin familia' }}"
                            data-family-id="{{ $subService->service->family->id ?? '' }}"
                            data-criticality-level="{{ $criticalityLevel }}" data-sla-id="{{ $slaId }}"
                            {{ old('sub_service_id', $serviceRequest->sub_service_id ?? '') == $subService->id ? 'selected' : '' }}>
                            {{ $subService->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>

        <!-- Elemento para mostrar la selección actual -->
        <div id="selected_subservice" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
            <div class="flex justify-between items-center">
                <div>
                    <span class="font-medium" id="selected_name"></span>
                    <div class="text-sm text-gray-600">
                        <span id="selected_family"></span> - <span id="selected_service"></span>
                    </div>
                </div>
                <button type="button" id="clear_selection" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>

        @error('sub_service_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Información automática de Familia y Servicio -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Familia</label>
            <div class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                <span id="family-display" class="text-gray-500">Seleccione un subservicio</span>
            </div>
            <input type="hidden" name="family_id" id="family_id" value="{{ old('family_id', '') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
            <div class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                <span id="service-display" class="text-gray-500">Seleccione un subservicio</span>
            </div>
            <input type="hidden" name="service_id" id="service_id" value="{{ old('service_id', '') }}">
        </div>
    </div>

    <!-- Nivel de Criticidad - Agregar CRITICA como opción -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Nivel de Criticidad <span class="text-red-500">*</span>
        </label>

        @php
            $currentCriticality = old('criticality_level', $serviceRequest->criticality_level ?? 'MEDIA');
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4" id="criticality-level-container">
            @foreach (['BAJA', 'MEDIA', 'ALTA', 'URGENTE', 'CRITICA'] as $level)
                <label class="relative flex cursor-pointer criticality-level-option">
                    <input type="radio" name="criticality_level" value="{{ $level }}"
                        {{ $currentCriticality == $level ? 'checked' : '' }}
                        class="sr-only peer" required>
                    <div
                        class="w-full p-4 border-2 border-gray-200 rounded-lg text-center transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-md">
                        <div class="flex flex-col items-center space-y-2">
                            @switch($level)
                                @case('BAJA')
                                    <i class="fas fa-arrow-down text-green-500 text-lg"></i>
                                    <span class="font-medium text-gray-700">Baja</span>
                                    <span class="text-xs text-gray-500">Impacto mínimo</span>
                                @break

                                @case('MEDIA')
                                    <i class="fas fa-minus text-yellow-500 text-lg"></i>
                                    <span class="font-medium text-gray-700">Media</span>
                                    <span class="text-xs text-gray-500">Impacto moderado</span>
                                @break

                                @case('ALTA')
                                    <i class="fas fa-arrow-up text-orange-500 text-lg"></i>
                                    <span class="font-medium text-gray-700">Alta</span>
                                    <span class="text-xs text-gray-500">Impacto significativo</span>
                                @break

                                @case('URGENTE')
                                    <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                                    <span class="font-medium text-gray-700">Urgente</span>
                                    <span class="text-xs text-gray-500">Impacto crítico</span>
                                @break

                                @case('CRITICA')
                                    <i class="fas fa-skull-crossbones text-red-700 text-lg"></i>
                                    <span class="font-medium text-gray-700">Crítica</span>
                                    <span class="text-xs text-gray-500">Impacto extremo</span>
                                @break
                            @endswitch
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
        @error('criticality_level')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Rutas Web (Opcional) -->
    <div>
        <label for="web_routes" class="block text-sm font-medium text-gray-700 mb-2">
            Rutas Web Relacionadas (Opcional)
        </label>
        <div id="web-routes-container">
            @php
                $existingRoutes = old('web_routes', $serviceRequest->web_routes ?? []);
                if (is_string($existingRoutes)) {
                    $existingRoutes = json_decode($existingRoutes, true) ?? [];
                }
                // Asegurar que siempre haya al menos un input vacío
                if (empty($existingRoutes)) {
                    $existingRoutes = [''];
                }
            @endphp

            @foreach ($existingRoutes as $index => $route)
                <div class="flex space-x-2 mb-2 route-input-group">
                    <input type="text" name="web_routes_temp[]" value="{{ $route }}"
                        placeholder="https://ejemplo.com/ruta o /ruta-interna"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 web-route-input">
                    @if ($index > 0 || !empty($route))
                        <button type="button" onclick="removeRoute(this)"
                            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200 remove-route-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" onclick="addRoute()"
            class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 text-sm">
            <i class="fas fa-plus mr-2"></i>Agregar otra ruta
        </button>
        <p class="mt-1 text-sm text-gray-500">
            Agregue URLs completas (https://...) o rutas internas (/admin, /dashboard). Máximo 5 rutas.
        </p>
        <div id="web-routes-error" class="mt-1 hidden">
            <p class="text-sm text-red-600"></p>
        </div>
    </div>
</div>

@once
    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--default .select2-selection--single {
                height: 48px;
                border-radius: 0.5rem;
                border-color: #d1d5db;
                padding: 0.5rem 0.75rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
                color: #1f2937;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 46px;
                right: 0.75rem;
            }
            .select2-dropdown {
                border-radius: 0.75rem;
                border-color: #d1d5db;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
        <script>
            (function setupSelect2() {
                function initSelect2Fields() {
                    if (!window.jQuery || !window.jQuery.fn.select2) {
                        console.warn('Select2 no está disponible.');
                        return;
                    }

                    const requesterSelect = window.jQuery('#requester_id');
                    if (requesterSelect.length && !requesterSelect.data('select2')) {
                        requesterSelect.select2({
                            width: '100%',
                            placeholder: 'Seleccione un solicitante',
                            allowClear: true
                        });
                    }

                    const entryChannelSelect = window.jQuery('#entry_channel');
                    if (entryChannelSelect.length && !entryChannelSelect.data('select2')) {
                        entryChannelSelect.select2({
                            width: '100%',
                            placeholder: 'Seleccione un canal',
                            minimumResultsForSearch: Infinity
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initSelect2Fields, { once: true });
                } else {
                    initSelect2Fields();
                }
            })();
        </script>
    @endpush
@endonce

<style>
    /* Asegurar que los estilos de Tailwind se apliquen a los radios seleccionados */
    input[name="criticality_level"]:checked+div {
        border-color: #3b82f6;
        background-color: #eff6ff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Estilos para elementos destacados en la búsqueda */
    .highlighted {
        background-color: #dbeafe !important;
    }
</style>

<script>
    // =============================================
    // FUNCIONALIDAD DE BÚSQUEDA EN TIEMPO REAL
    // =============================================

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('sub_service_search');
        const resultsContainer = document.getElementById('sub_service_results');
        const hiddenSelect = document.getElementById('sub_service_id');
        const selectedDisplay = document.getElementById('selected_subservice');
        const selectedName = document.getElementById('selected_name');
        const selectedFamily = document.getElementById('selected_family');
        const selectedService = document.getElementById('selected_service');
        const clearButton = document.getElementById('clear_selection');

        // Cargar datos de las opciones
        const optionsData = [];
        const optgroups = hiddenSelect.querySelectorAll('optgroup');

        optgroups.forEach(optgroup => {
            const groupLabel = optgroup.getAttribute('label');
            const [familyName, serviceName] = groupLabel.split(' - ');

            optgroup.querySelectorAll('option').forEach(option => {
                if (option.value) {
                    optionsData.push({
                        id: option.value,
                        name: option.textContent,
                        familyName: familyName,
                        serviceName: serviceName,
                        serviceId: option.getAttribute('data-service-id'),
                        familyId: option.getAttribute('data-family-id'),
                        criticalityLevel: option.getAttribute('data-criticality-level'),
                        slaId: option.getAttribute('data-sla-id'),
                        element: option
                    });
                }
            });
        });

        // Mostrar selección actual si existe
        const selectedOption = hiddenSelect.querySelector('option[selected]');
        if (selectedOption && selectedOption.value) {
            showSelectedSubservice(
                selectedOption.value,
                selectedOption.textContent,
                selectedOption.getAttribute('data-family-name'),
                selectedOption.getAttribute('data-service-name')
            );
        }

        // Función para filtrar opciones
        function filterOptions(searchTerm) {
            const normalizedTerm = searchTerm.toLowerCase().trim();

            if (normalizedTerm === '') {
                return [];
            }

            // Filtrar opciones que coincidan con el término de búsqueda
            return optionsData.filter(option =>
                option.name.toLowerCase().includes(normalizedTerm) ||
                option.familyName.toLowerCase().includes(normalizedTerm) ||
                option.serviceName.toLowerCase().includes(normalizedTerm)
            );
        }

        // Función para mostrar resultados
        function displayResults(results) {
            resultsContainer.innerHTML = '';

            if (results.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'p-3 text-gray-500 text-center';
                noResults.textContent = 'No se encontraron resultados';
                resultsContainer.appendChild(noResults);
            } else {
                // Agrupar resultados por familia y servicio
                const groupedResults = {};

                results.forEach(result => {
                    const groupKey = `${result.familyName}|${result.serviceName}`;

                    if (!groupedResults[groupKey]) {
                        groupedResults[groupKey] = {
                            familyName: result.familyName,
                            serviceName: result.serviceName,
                            items: []
                        };
                    }

                    groupedResults[groupKey].items.push(result);
                });

                // Crear elementos para cada grupo
                Object.values(groupedResults).forEach(group => {
                    const groupHeader = document.createElement('div');
                    groupHeader.className = 'p-2 bg-gray-100 font-medium text-gray-700 border-b';
                    groupHeader.textContent = `${group.familyName} - ${group.serviceName}`;
                    resultsContainer.appendChild(groupHeader);

                    group.items.forEach(item => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'p-3 hover:bg-blue-50 cursor-pointer border-b';
                        resultItem.dataset.id = item.id;

                        resultItem.innerHTML = `
                            <div class="font-medium">${item.name}</div>
                            <div class="text-sm text-gray-600">${item.familyName} - ${item.serviceName}</div>
                        `;

                        resultItem.addEventListener('click', function() {
                            selectSubservice(item);
                        });

                        resultsContainer.appendChild(resultItem);
                    });
                });
            }

            resultsContainer.classList.remove('hidden');
        }

        // Función para seleccionar un subservicio
        function selectSubservice(subservice) {
            // Actualizar el select oculto
            hiddenSelect.value = subservice.id;

            // Mostrar la selección
            showSelectedSubservice(
                subservice.id,
                subservice.name,
                subservice.familyName,
                subservice.serviceName
            );

            // Actualizar todos los campos del formulario
            updateFormFields();

            // Limpiar búsqueda y ocultar resultados
            searchInput.value = '';
            resultsContainer.classList.add('hidden');
        }

        // Función para mostrar la selección actual
        function showSelectedSubservice(id, name, family, service) {
            selectedName.textContent = name;
            selectedFamily.textContent = family;
            selectedService.textContent = service;
            selectedDisplay.classList.remove('hidden');
        }

        // Función para limpiar la selección
        function clearSelection() {
            hiddenSelect.value = '';
            selectedDisplay.classList.add('hidden');
            searchInput.value = '';
            resultsContainer.classList.add('hidden');
            updateFormFields();
        }

        // Event listeners para búsqueda
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value;

            if (searchTerm.length >= 2) {
                const results = filterOptions(searchTerm);
                displayResults(results);
            } else {
                resultsContainer.classList.add('hidden');
            }
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                const results = filterOptions(this.value);
                displayResults(results);
            }
        });

        clearButton.addEventListener('click', clearSelection);

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !resultsContainer.contains(event.target)) {
                resultsContainer.classList.add('hidden');
            }
        });

        // Permitir navegación con teclado
        searchInput.addEventListener('keydown', function(event) {
            const visibleResults = resultsContainer.querySelectorAll('div[data-id]');

            if (visibleResults.length === 0) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (resultsContainer.querySelector('.highlighted')) {
                    const current = resultsContainer.querySelector('.highlighted');
                    const next = current.nextElementSibling;

                    if (next && next.dataset.id) {
                        current.classList.remove('highlighted', 'bg-blue-100');
                        next.classList.add('highlighted', 'bg-blue-100');
                        next.scrollIntoView({
                            block: 'nearest'
                        });
                    }
                } else {
                    const first = visibleResults[0];
                    first.classList.add('highlighted', 'bg-blue-100');
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (resultsContainer.querySelector('.highlighted')) {
                    const current = resultsContainer.querySelector('.highlighted');
                    const prev = current.previousElementSibling;

                    // Si el elemento anterior es un encabezado de grupo, buscar el anterior
                    if (prev && !prev.dataset.id) {
                        const prevPrev = prev.previousElementSibling;
                        if (prevPrev && prevPrev.dataset.id) {
                            current.classList.remove('highlighted', 'bg-blue-100');
                            prevPrev.classList.add('highlighted', 'bg-blue-100');
                            prevPrev.scrollIntoView({
                                block: 'nearest'
                            });
                        }
                    } else if (prev && prev.dataset.id) {
                        current.classList.remove('highlighted', 'bg-blue-100');
                        prev.classList.add('highlighted', 'bg-blue-100');
                        prev.scrollIntoView({
                            block: 'nearest'
                        });
                    }
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                const highlighted = resultsContainer.querySelector('.highlighted');
                if (highlighted) {
                    const id = highlighted.dataset.id;
                    const subservice = optionsData.find(opt => opt.id === id);
                    if (subservice) {
                        selectSubservice(subservice);
                    }
                }
            }
        });
    });

    // =============================================
    // FUNCIONALIDAD EXISTENTE DEL FORMULARIO
    // =============================================

    // Función para actualizar todos los campos automáticamente
    function updateFormFields() {
        console.log('🔄 Actualizando campos del formulario...');

        const select = document.getElementById('sub_service_id');
        if (!select) {
            console.error('❌ No se encontró el select sub_service_id');
            return;
        }

        const selectedOption = select.options[select.selectedIndex];

        // Campos críticos
        const serviceIdInput = document.getElementById('service_id');
        const familyIdInput = document.getElementById('family_id');
        const slaIdInput = document.getElementById('sla_id');
        const familyDisplay = document.getElementById('family-display');
        const serviceDisplay = document.getElementById('service-display');

        if (!selectedOption || !selectedOption.value) {
            console.log('📭 No hay selección - estableciendo valores por defecto');
            if (serviceIdInput) serviceIdInput.value = '';
            if (familyIdInput) familyIdInput.value = '';
            if (slaIdInput) slaIdInput.value = '1';
            if (familyDisplay) {
                familyDisplay.textContent = 'Seleccione un subservicio';
                familyDisplay.className = 'text-gray-500';
            }
            if (serviceDisplay) {
                serviceDisplay.textContent = 'Seleccione un subservicio';
                serviceDisplay.className = 'text-gray-500';
            }
            setCriticalityLevel('MEDIA');
            return;
        }

        // Obtener datos de los atributos data
        const serviceId = selectedOption.getAttribute('data-service-id') || '';
        const familyId = selectedOption.getAttribute('data-family-id') || '';
        const serviceName = selectedOption.getAttribute('data-service-name') || 'Servicio';
        const familyName = selectedOption.getAttribute('data-family-name') || 'Familia';
        const criticalityLevel = selectedOption.getAttribute('data-criticality-level') || 'MEDIA';
        const slaId = selectedOption.getAttribute('data-sla-id') || '1';

        console.log('📋 Datos extraídos:', {
            serviceId,
            familyId,
            serviceName,
            familyName,
            criticalityLevel,
            slaId
        });

        // ESTABLECER VALORES
        if (serviceIdInput) serviceIdInput.value = serviceId;
        if (familyIdInput) familyIdInput.value = familyId;
        if (slaIdInput) slaIdInput.value = slaId;
        if (familyDisplay) {
            familyDisplay.textContent = familyName;
            familyDisplay.className = 'text-gray-700 font-medium';
        }
        if (serviceDisplay) {
            serviceDisplay.textContent = serviceName;
            serviceDisplay.className = 'text-gray-700 font-medium';
        }

        console.log('✅ Campos establecidos:', {
            service_id: serviceIdInput?.value,
            family_id: familyIdInput?.value,
            sla_id: slaIdInput?.value
        });

        setCriticalityLevel(criticalityLevel);
    }

    function setCriticalityLevel(level) {
        console.log('🎯 Configurando criticidad:', level);
        const radio = document.querySelector(`input[name="criticality_level"][value="${level}"]`);
        if (radio) {
            radio.checked = true;
            // Forzar actualización de estilos
            document.querySelectorAll('input[name="criticality_level"]').forEach(r => {
                r.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
        } else {
            console.warn('⚠️ No se encontró el radio para:', level);
            // Fallback a MEDIA
            const mediaRadio = document.querySelector('input[name="criticality_level"][value="MEDIA"]');
            if (mediaRadio) {
                mediaRadio.checked = true;
                mediaRadio.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        }
    }

    // Funciones para rutas web
    function addRoute() {
        const container = document.getElementById('web-routes-container');
        const inputGroups = container.getElementsByClassName('route-input-group');

        if (inputGroups.length >= 5) {
            alert('Máximo 5 rutas permitidas');
            return;
        }

        const newInput = document.createElement('div');
        newInput.className = 'flex space-x-2 mb-2 route-input-group';
        newInput.innerHTML = `
            <input
                type="text"
                name="web_routes_temp[]"
                placeholder="https://ejemplo.com/ruta o /ruta-interna"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 web-route-input"
            >
            <button type="button" onclick="removeRoute(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(newInput);
    }

    function removeRoute(button) {
        const inputGroup = button.closest('.route-input-group');
        if (inputGroup) {
            inputGroup.remove();
        }
    }

    // Preparar rutas web como JSON antes de enviar
    function prepareWebRoutes() {
        console.log('🌐 Preparando web_routes...');
        const tempInputs = document.querySelectorAll('input[name="web_routes_temp[]"]');
        const routes = [];

        tempInputs.forEach(input => {
            const value = input.value.trim();
            if (value) routes.push(value);
        });

        const webRoutesInput = document.getElementById('web_routes_json');
        if (webRoutesInput) {
            webRoutesInput.value = JSON.stringify(routes);
            console.log('✅ web_routes establecido:', webRoutesInput.value);
        }

        return routes;
    }

    // INICIALIZACIÓN
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOM Cargado - Inicializando formulario...');

        // Ejecutar inmediatamente para establecer valores iniciales
        setTimeout(updateFormFields, 100);

        // Configurar envío del formulario
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('📤 Enviando formulario...');

                // Preparar rutas web
                prepareWebRoutes();

                // Verificación final
                const finalCheck = {
                    service_id: document.getElementById('service_id')?.value,
                    family_id: document.getElementById('family_id')?.value,
                    sla_id: document.getElementById('sla_id')?.value,
                    requested_by: document.getElementById('requested_by')?.value,
                    web_routes: document.getElementById('web_routes_json')?.value
                };

                console.log('🔍 Verificación final:', finalCheck);

                if (!finalCheck.service_id || !finalCheck.family_id) {
                    e.preventDefault();
                    alert(
                        '❌ Error: Faltan datos requeridos. Por favor, seleccione un subservicio válido.'
                        );
                    return false;
                }

                console.log('✅ Formulario listo para enviar');
            });
        }

        console.log('🎉 Inicialización completada');
    });

    // Exponer función globalmente para debugging
    window.debugForm = function() {
        const fields = ['service_id', 'family_id', 'sla_id', 'requested_by', 'web_routes_json'];
        const values = {};
        fields.forEach(id => {
            const el = document.getElementById(id);
            values[id] = el ? el.value : 'NO EXISTE';
        });
        console.log('🔍 Estado del formulario:', values);
        return values;
    };
</script>
