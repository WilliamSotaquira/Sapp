{{-- resources/views/components/service-requests/forms/basic-fields.blade.php --}}
@props([
    'serviceRequest' => null,
    'subServices' => [], // Lista de subservicios (opcional; puede ser vacía si usamos Select2 AJAX)
    'selectedSubService' => null, // Subservicio precargado para mostrar selección inicial
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
    <input type="hidden" name="requested_by"
        value="{{ old('requested_by', $serviceRequest->requested_by ?? auth()->id()) }}">

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
                    @if ($requester->department)
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


    <!-- Resto del formulario permanece igual -->



    <!-- SELECT2: Selector de Subservicios (con búsqueda integrada) -->
    <div>
        <label for="sub_service_id" class="block text-sm font-medium text-gray-700 mb-2">
            Subservicio <span class="text-red-500">*</span>
        </label>

        <select name="sub_service_id" id="sub_service_id"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 @error('sub_service_id') border-red-500 @enderror"
            required>
            <option value="">Seleccione un subservicio</option>
            @php
                $selectedId = old('sub_service_id', $serviceRequest->sub_service_id ?? null);
            @endphp

            @if (!empty($subServices))
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
                                $slaId = null;

                                if ($subService->relationLoaded('slas') && $subService->slas->isNotEmpty()) {
                                    $sla = $subService->slas->first();
                                    $criticalityLevel = $sla->criticality_level ?? 'MEDIA';
                                    $slaId = $sla->id;
                                }
                            @endphp
                            <option value="{{ $subService->id }}" data-service-id="{{ $subService->service_id }}"
                                data-service-name="{{ $subService->service->name }}"
                                data-family-name="{{ $subService->service->family->name ?? 'Sin familia' }}"
                                data-family-id="{{ $subService->service->family->id ?? '' }}"
                                data-criticality-level="{{ $criticalityLevel }}" data-sla-id="{{ $slaId }}"
                                {{ (string)$selectedId === (string)$subService->id ? 'selected' : '' }}>
                                {{ $subService->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            @elseif ($selectedSubService)
                @php
                    $criticalityLevel = 'MEDIA';
                    $slaId = null;

                    if ($selectedSubService->relationLoaded('slas') && $selectedSubService->slas->isNotEmpty()) {
                        $sla = $selectedSubService->slas->first();
                        $criticalityLevel = $sla->criticality_level ?? 'MEDIA';
                        $slaId = $sla->id;
                    }
                @endphp
                <option value="{{ $selectedSubService->id }}" selected
                    data-service-id="{{ $selectedSubService->service_id }}"
                    data-service-name="{{ $selectedSubService->service->name ?? '' }}"
                    data-family-name="{{ $selectedSubService->service->family->name ?? '' }}"
                    data-family-id="{{ $selectedSubService->service->family->id ?? '' }}"
                    data-criticality-level="{{ $criticalityLevel }}"
                    data-sla-id="{{ $slaId }}">
                    {{ $selectedSubService->name }}
                </option>
            @endif
        </select>

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
                        {{ $currentCriticality == $level ? 'checked' : '' }} class="sr-only peer" required>
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

            /* Foco visible (teclado) */
            .select2-container--default.select2-container--focus .select2-selection--single,
            .select2-container--default .select2-selection--single:focus {
                border-color: #2563eb; /* blue-600 */
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
                outline: none;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
                color: #1f2937;
            }

            /* Render del valor seleccionado: compacto y truncable */
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                min-width: 0;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered .ss-name {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 46px;
                right: 0.75rem;
            }

            .select2-dropdown {
                border-radius: 0.75rem;
                border-color: #d1d5db;
            }

            /* Dropdown: opción resaltada (hover / teclado) con contraste accesible */
            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: #e0f2fe; /* sky-100 */
                color: #0c4a6e;           /* sky-900 */
            }

            /* Dropdown: opción seleccionada */
            .select2-container--default .select2-results__option[aria-selected='true'] {
                background-color: #f1f5f9; /* slate-100 */
                color: #0f172a;            /* slate-900 */
            }

            /* Mejorar visibilidad del botón limpiar */
            .select2-container--default .select2-selection--single .select2-selection__clear {
                color: #6b7280; /* gray-500 */
                font-size: 18px;
                line-height: 1;
                margin-right: 0.25rem;
            }

            .select2-container--default .select2-selection--single .select2-selection__clear:hover {
                color: #111827; /* gray-900 */
            }

            /* Separación del texto seleccionado para que no choque con la X */
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                padding-right: 2.25rem;
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

                    const subServiceSelect = window.jQuery('#sub_service_id');
                    if (subServiceSelect.length && !subServiceSelect.data('select2')) {
                        function escapeHtml(value) {
                            return String(value ?? '')
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        function normalizeText(value) {
                            const raw = String(value ?? '').toLowerCase().trim();
                            // Quitar tildes/diacríticos para búsquedas tipo "publicacion" == "publicación"
                            try {
                                return raw.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                            } catch (e) {
                                return raw;
                            }
                        }

                        function getMetaFromSelect2Item(data) {
                            const el = data?.element;

                            const familyName = el?.dataset?.familyName ?? data?.familyName ?? '';
                            const serviceName = el?.dataset?.serviceName ?? data?.serviceName ?? '';
                            const criticalityLevel = (el?.dataset?.criticalityLevel ?? data?.criticalityLevel ?? '').toUpperCase();
                            const slaId = el?.dataset?.slaId ?? data?.slaId ?? '';

                            const familyId = el?.dataset?.familyId ?? data?.familyId ?? '';
                            const serviceId = el?.dataset?.serviceId ?? data?.serviceId ?? '';

                            return {
                                familyName,
                                serviceName,
                                criticalityLevel,
                                slaId,
                                familyId,
                                serviceId,
                            };
                        }

                        function subServiceMatcher(params, data) {
                            const term = normalizeText(params.term);

                            if (!term) {
                                return data;
                            }

                            if (data.children && data.children.length) {
                                const filteredChildren = [];
                                for (const child of data.children) {
                                    const match = subServiceMatcher(params, child);
                                    if (match) filteredChildren.push(match);
                                }

                                if (filteredChildren.length) {
                                    const modified = window.jQuery.extend(true, {}, data);
                                    modified.children = filteredChildren;
                                    return modified;
                                }

                                return null;
                            }

                            if (!data || !data.id) {
                                return null;
                            }

                            const meta = getMetaFromSelect2Item(data);
                            const name = normalizeText(data.text || '');
                            const family = normalizeText(meta.familyName);
                            const service = normalizeText(meta.serviceName);
                            const slaId = normalizeText(meta.slaId);
                            const criticality = normalizeText(meta.criticalityLevel);

                            const haystack = `${name} ${family} ${service} ${slaId} ${criticality}`;
                            return haystack.includes(term) ? data : null;
                        }

                        function upsertSelectedOptionMeta(selectEl, item) {
                            if (!selectEl || !item || !item.id) return;

                            let option = selectEl.querySelector(`option[value="${String(item.id)}"]`);
                            if (!option) {
                                option = new Option(item.text || String(item.id), item.id, true, true);
                                selectEl.appendChild(option);
                            }

                            const meta = getMetaFromSelect2Item(item);
                            option.dataset.familyName = meta.familyName || option.dataset.familyName || '';
                            option.dataset.serviceName = meta.serviceName || option.dataset.serviceName || '';
                            option.dataset.familyId = meta.familyId || option.dataset.familyId || '';
                            option.dataset.serviceId = meta.serviceId || option.dataset.serviceId || '';
                            option.dataset.criticalityLevel = meta.criticalityLevel || option.dataset.criticalityLevel || '';
                            option.dataset.slaId = meta.slaId || option.dataset.slaId || '';
                        }

                        subServiceSelect.select2({
                            width: '100%',
                            placeholder: 'Seleccione un subservicio',
                            allowClear: true,
                            minimumInputLength: 0,
                            language: {
                                searching: function() {
                                    return 'Buscando...';
                                },
                                noResults: function() {
                                    return 'No se encontraron resultados';
                                }
                            },
                            ajax: {
                                url: '{{ url('api/sub-services/search') }}',
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return {
                                        term: params.term || '',
                                        page: params.page || 1,
                                        per_page: 20,
                                    };
                                },
                                processResults: function(payload, params) {
                                    const results = Array.isArray(payload?.results) ? payload.results : [];
                                    const more = Boolean(payload?.pagination?.more);

                                    return {
                                        results,
                                        pagination: {
                                            more
                                        }
                                    };
                                },
                                cache: true,
                            },
                            // Fallback si se carga lista local en algún contexto
                            matcher: subServiceMatcher,
                            templateResult: function(data) {
                                if (!data || !data.id) {
                                    return data.text;
                                }

                                const meta = getMetaFromSelect2Item(data);
                                const family = meta.familyName;
                                const service = meta.serviceName;
                                const criticality = meta.criticalityLevel;
                                const slaId = meta.slaId;

                                const badgeClassByCriticality = {
                                    'BAJA': 'bg-green-100 text-green-800',
                                    'MEDIA': 'bg-yellow-100 text-yellow-800',
                                    'ALTA': 'bg-orange-100 text-orange-800',
                                    'URGENTE': 'bg-red-100 text-red-800',
                                    'CRITICA': 'bg-red-200 text-red-900'
                                };

                                const badgeClass = badgeClassByCriticality[criticality] || 'bg-gray-100 text-gray-700';
                                const familyHtml = family ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded bg-blue-50 text-blue-800 border border-blue-100">${escapeHtml(family)}</span>` : '';
                                const serviceHtml = service ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded bg-indigo-50 text-indigo-800 border border-indigo-100">${escapeHtml(service)}</span>` : '';
                                const slaHtml = slaId ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded bg-gray-50 text-gray-700 border border-gray-200">SLA #${escapeHtml(slaId)}</span>` : '';
                                const badgeHtml = criticality
                                    ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded ${badgeClass}">${criticality}</span>`
                                    : '';

                                return window.jQuery(
                                    `<div class="py-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="font-medium text-gray-900">${escapeHtml(data.text)}</div>
                                            ${badgeHtml}
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            ${familyHtml}
                                            ${serviceHtml}
                                            ${slaHtml}
                                        </div>
                                    </div>`
                                );
                            },
                            templateSelection: function(data) {
                                if (!data || !data.id) {
                                    return data.text;
                                }

                                const meta = getMetaFromSelect2Item(data);
                                const criticality = meta.criticalityLevel;
                                const slaId = meta.slaId;

                                const badgeClassByCriticality = {
                                    'BAJA': 'bg-green-100 text-green-800',
                                    'MEDIA': 'bg-yellow-100 text-yellow-800',
                                    'ALTA': 'bg-orange-100 text-orange-800',
                                    'URGENTE': 'bg-red-100 text-red-800',
                                    'CRITICA': 'bg-red-200 text-red-900'
                                };
                                const badgeClass = badgeClassByCriticality[criticality] || 'bg-gray-100 text-gray-700';

                                const name = escapeHtml(data.text);
                                const slaHtml = slaId
                                    ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded bg-gray-50 text-gray-700 border border-gray-200">SLA #${escapeHtml(slaId)}</span>`
                                    : '';
                                const critHtml = criticality
                                    ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded ${badgeClass}">${criticality}</span>`
                                    : '';

                                return window.jQuery(
                                    `<span class="flex items-center gap-2 min-w-0">
                                        <span class="ss-name">${name}</span>
                                        ${slaHtml}
                                        ${critHtml}
                                    </span>`
                                );
                            },
                            escapeMarkup: function(markup) {
                                return markup;
                            }
                        });

                        subServiceSelect.on('select2:select', function(e) {
                            const rawSelect = subServiceSelect.get(0);
                            const selectedItem = e?.params?.data;
                            upsertSelectedOptionMeta(rawSelect, selectedItem);

                            if (typeof window.updateFormFields === 'function') {
                                window.updateFormFields();
                            } else if (typeof updateFormFields === 'function') {
                                updateFormFields();
                            }
                        });

                        subServiceSelect.on('select2:clear', function() {
                            if (typeof window.updateFormFields === 'function') {
                                window.updateFormFields();
                            } else if (typeof updateFormFields === 'function') {
                                updateFormFields();
                            }
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initSelect2Fields, {
                        once: true
                    });
                } else {
                    initSelect2Fields();
                }
            })
            ();
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
        let serviceId = selectedOption.getAttribute('data-service-id') || '';
        let familyId = selectedOption.getAttribute('data-family-id') || '';
        let serviceName = selectedOption.getAttribute('data-service-name') || '';
        let familyName = selectedOption.getAttribute('data-family-name') || '';
        let criticalityLevel = selectedOption.getAttribute('data-criticality-level') || '';
        let slaId = selectedOption.getAttribute('data-sla-id') || '';

        // Fallback: si el select está en modo Select2 AJAX y no tenemos data-* aún,
        // tomamos la metadata desde el item seleccionado en Select2.
        if ((!serviceId || !familyId) && window.jQuery && window.jQuery.fn?.select2) {
            try {
                const $s2 = window.jQuery('#sub_service_id');
                if ($s2.length && $s2.data('select2')) {
                    const data = $s2.select2('data');
                    const selected = Array.isArray(data) ? data[0] : null;
                    if (selected) {
                        serviceId = serviceId || String(selected.serviceId ?? '');
                        familyId = familyId || String(selected.familyId ?? '');
                        serviceName = serviceName || String(selected.serviceName ?? '');
                        familyName = familyName || String(selected.familyName ?? '');
                        criticalityLevel = criticalityLevel || String(selected.criticalityLevel ?? '');
                        slaId = slaId || String(selected.slaId ?? '');
                    }
                }
            } catch (e) {
                // No hacemos nada, usamos los valores existentes
            }
        }

        serviceName = serviceName || 'Servicio';
        familyName = familyName || 'Familia';
        criticalityLevel = criticalityLevel || 'MEDIA';
        slaId = slaId || '1';

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

        // Mantener sincronizados familia/servicio/SLA/criticidad al cambiar subservicio
        const subServiceSelect = document.getElementById('sub_service_id');
        if (subServiceSelect) {
            subServiceSelect.addEventListener('change', updateFormFields);
        }

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
