{{-- resources/views/components/service-requests/forms/basic-fields.blade.php --}}
@props([
    'serviceRequest' => null,
    'subServices' => [], // Lista de subservicios (opcional; puede ser vacía si usamos Select2 AJAX)
    'selectedSubService' => null, // Subservicio precargado para mostrar selección inicial
    'selectedCutId' => null, // Corte precargado para edición
    'requesters' => [], // Lista de solicitantes para seleccionar solicitante
    'companies' => [], // Lista de empresas para seleccionar
    'cuts' => [], // Lista de cortes disponibles
    'errors' => null,
    'mode' => 'create', // 'create' or 'edit'
])

<div class="space-y-6">

    @php
        $titleBorderClass = $errors->has('title') ? 'border-red-500' : 'border-gray-300';
        $descriptionBorderClass = $errors->has('description') ? 'border-red-500' : 'border-gray-300';
        $requesterBorderClass = $errors->has('requester_id') ? 'border-red-500' : 'border-gray-300';
        $entryChannelBorderClass = $errors->has('entry_channel') ? 'border-red-500' : 'border-gray-300';
        $subServiceBorderClass = $errors->has('sub_service_id') ? 'border-red-500' : 'border-gray-300';
    @endphp

    <!-- CAMPOS OCULTOS REQUERIDOS - CON VALORES POR DEFECTO -->
    <input type="hidden" name="sla_id" id="sla_id" value="{{ old('sla_id', $serviceRequest->sla_id ?? '') }}">
    <input type="hidden" name="web_routes" id="web_routes_json" value="{{ old('web_routes', '[]') }}">
    <input type="hidden" name="requested_by" id="requested_by"
        value="{{ old('requested_by', $serviceRequest->requested_by ?? auth()->id()) }}">

    <!-- Campo Título -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Título de la Solicitud <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title" id="title" value="{{ old('title', $serviceRequest->title ?? '') }}"
            placeholder="Ingrese un título descriptivo para la solicitud"
            class="w-full px-4 py-3 border {{ $titleBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            @if(($mode ?? 'create') === 'create' && !$errors->any()) autofocus @endif
            @error('title') aria-invalid="true" @enderror
            required maxlength="255">
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-500">Máximo 255 caracteres.</p>
    </div>


    <!-- Campo Descripción -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Descripción <span class="text-red-500">*</span>
        </label>
        <textarea name="description" id="description" rows="4"
            placeholder="Describa en detalle el problema o requerimiento..."
            class="w-full px-4 py-3 border {{ $descriptionBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            required>{{ old('description', $serviceRequest->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-500">Describe lo necesario para atenderla.</p>
    </div>

    <!-- Espacio de trabajo -->
    @php
        $currentCompanyId = old('company_id', $serviceRequest->company_id ?? (session('current_company_id') ?? null));
    @endphp
    <input type="hidden" name="company_id" id="company_id" value="{{ $currentCompanyId }}">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Espacio de trabajo</label>
        <div class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
            {{ $currentWorkspace->name ?? 'Sin espacio seleccionado' }}
        </div>
        @error('company_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Contrato activo (solo lectura) -->
    @php
        $activeContract = $currentCompany?->activeContract;
    @endphp
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Contrato activo</label>
        <div class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
            {{ $activeContract ? ($activeContract->number . ($activeContract->name ? ' - ' . $activeContract->name : '')) : 'Sin contrato activo' }}
        </div>
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
            class="w-full px-4 py-3 border {{ $requesterBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            required>
            <option value="">Seleccione un solicitante</option>
            @foreach ($requesters as $requester)
                <option value="{{ $requester->id }}"
                    data-company-id="{{ $requester->company_id ?? '' }}"
                    {{ $currentRequesterId == $requester->id ? 'selected' : '' }}>
                    {{ $requester->name }} - {{ $requester->email }}
                    @if ($requester->department)
                        ({{ $requester->department }})
                    @endif
                </option>
            @endforeach
        </select>

        <p class="mt-1 text-xs text-gray-500 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span>Selecciona quién realiza la solicitud.</span>
        </p>

        <div class="mt-2 flex justify-end">
            <button type="button" id="openRequesterQuickCreate" tabindex="-1"
                class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                <i class="fas fa-user-plus"></i>
                <span>Crear solicitante</span>
            </button>
        </div>

        @error('requester_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <!-- Modal: Crear solicitante rápido (sin refrescar) -->
        <div id="requesterQuickCreateModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50" data-overlay></div>

            <div class="relative w-full min-h-screen flex items-center justify-center p-4">
                <div class="w-[96%] max-w-2xl">
                <div class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Crear solicitante</h3>
                        <button type="button" id="closeRequesterQuickCreate"
                            class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="px-6 py-5 max-h-[75vh] overflow-y-auto">
                        <div id="requesterQuickCreateErrors" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
                            <p class="text-sm font-medium text-red-800 mb-1">Revisa los campos:</p>
                            <ul class="text-sm text-red-700 list-disc list-inside" data-errors-list></ul>
                        </div>

                        <div id="requesterQuickCreateForm" data-url="{{ route('api.requesters.quick-create') }}" class="space-y-4">
                            <div>
                                <label for="quickRequesterName" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" id="quickRequesterName" data-quick-requester-field disabled maxlength="255"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="quickRequesterEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="quickRequesterEmail" data-quick-requester-field disabled maxlength="255"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label for="quickRequesterPhone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                    <input type="text" id="quickRequesterPhone" data-quick-requester-field disabled maxlength="20"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="quickRequesterDepartment" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                    @php
                                        $departmentOptions = \App\Models\Requester::getDepartmentOptions();
                                    @endphp
                                    <select id="quickRequesterDepartment" data-quick-requester-field disabled
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Seleccione un departamento</option>
                                        @foreach ($departmentOptions as $department)
                                            <option value="{{ $department }}">{{ $department }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="quickRequesterPosition" class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                                    <input type="text" id="quickRequesterPosition" data-quick-requester-field disabled maxlength="255"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>

                            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4">
                                <button type="button" id="cancelRequesterQuickCreate"
                                    class="w-full sm:w-auto px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="button" id="submitRequesterQuickCreate"
                                    class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                    Crear y seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const companySelect = document.getElementById('company_id');
                    const requesterSelect = document.getElementById('requester_id');
                    if (!companySelect || !requesterSelect) return;

                    const requesterOptions = Array.from(requesterSelect.options);

                    function applyRequesterFilter() {
                        const companyId = companySelect.value;
                        let hasSelection = false;

                        requesterOptions.forEach((option) => {
                            if (!option.value) return;
                            const optionCompanyId = option.getAttribute('data-company-id') || '';
                            const shouldShow = !companyId || optionCompanyId === companyId;
                            option.hidden = !shouldShow;
                            option.disabled = !shouldShow;
                            if (shouldShow && option.selected) {
                                hasSelection = true;
                            }
                        });

                        if (companyId && !hasSelection && requesterSelect.value) {
                            requesterSelect.value = '';
                            requesterSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    companySelect.addEventListener('change', applyRequesterFilter);
                    applyRequesterFilter();
                });
            </script>
        @endpush
    @endonce

    <!-- Canal de ingreso -->
    @php
        $entryChannelOptions = \App\Models\ServiceRequest::getEntryChannelOptions();
        $selectedEntryChannel = old(
            'entry_channel',
            $serviceRequest->entry_channel
                ?? (($mode ?? 'create') === 'create' ? \App\Models\ServiceRequest::ENTRY_CHANNEL_CORPORATE_EMAIL : null)
        );
        $isReportable = old('is_reportable', $serviceRequest->is_reportable ?? true);
    @endphp
    <div>
        <label for="entry_channel" class="block text-sm font-medium text-gray-700 mb-2">
            Canal de ingreso <span class="text-red-500">*</span>
        </label>
        <select name="entry_channel" id="entry_channel"
            class="w-full px-4 py-3 border {{ $entryChannelBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            @if(($mode ?? 'create') === 'create' && !$errors->has('entry_channel')) tabindex="-1" @endif
            required>
            <option value="">Selecciona un canal</option>
            @foreach ($entryChannelOptions as $value => $option)
                <option value="{{ $value }}" {{ $selectedEntryChannel === $value ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            Indica el origen de la solicitud.
        </p>
        @error('entry_channel')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Selector de Corte (opcional) -->
    @php
        $cutBorderClass = $errors->has('cut_id') ? 'border-red-500' : 'border-gray-300';
        $defaultCutId = null;
        if (($mode ?? 'create') === 'create' && !empty($cuts)) {
            $defaultCutId = optional($cuts->sortByDesc('end_date')->first())->id;
        }
        $selectedCutValue = old('cut_id', $selectedCutId ?? $serviceRequest->cut_id ?? $defaultCutId);
    @endphp
    <div>
        <label for="cut_id" class="block text-sm font-medium text-gray-700 mb-2">
            Corte <span class="text-gray-500 text-xs">(Opcional)</span>
        </label>
    @php
        $activeContractId = $currentCompany?->active_contract_id;
    @endphp
    <select name="cut_id" id="cut_id" data-active-contract-id="{{ $activeContractId }}"
            data-preserve-selected="{{ ($mode ?? 'create') === 'edit' ? '1' : '0' }}"
            class="w-full px-4 py-3 border {{ $cutBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            @if(($mode ?? 'create') === 'create') tabindex="-1" @endif>
            <option value="">Sin corte asignado</option>
        @foreach ($cuts as $cut)
            <option value="{{ $cut->id }}"
                    data-contract-id="{{ $cut->contract_id ?? '' }}"
                    {{ (string)$selectedCutValue === (string)$cut->id ? 'selected' : '' }}>
                {{ $cut->name }} ({{ $cut->start_date->format('d/m/Y') }} - {{ $cut->end_date->format('d/m/Y') }})
            </option>
        @endforeach
    </select>
        <p class="mt-1 text-xs text-gray-500">
            Vincula esta solicitud a un período (opcional).
        </p>
        @error('cut_id')
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
            class="w-full px-4 py-3 border {{ $subServiceBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            required>
            <option value="">Seleccione un subservicio</option>
            @php
                $selectedId = old('sub_service_id', $serviceRequest->sub_service_id ?? null);
            @endphp

            @if (collect($subServices)->isNotEmpty())
                @php
                    // Agrupar los subservicios
                    $groupedSubServices = [];
                    foreach ($subServices as $subService) {
                        $family = $subService->service?->family;
                        $familyName = $family?->name ?? 'Sin Familia';
                        $contractNumber = $family?->contract?->number;
                        $familyName = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
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
                                @php
                                    $family = $subService->service?->family;
                                    $familyName = $family?->name ?? 'Sin familia';
                                    $contractNumber = $family?->contract?->number;
                                    $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
                                @endphp
                                data-family-name="{{ $familyLabel }}"
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
                        {{ $currentCriticality == $level ? 'checked' : '' }} class="sr-only peer" required tabindex="-1">
                    <div
                        class="w-full p-4 border-2 border-gray-200 rounded-lg text-center transition-all duration-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:shadow-md">
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
        @php
            $existingRoutesRaw = old('web_routes', $serviceRequest->web_routes ?? []);

            if (is_string($existingRoutesRaw)) {
                $decoded = json_decode($existingRoutesRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $existingRoutes = $decoded;
                } else {
                    $existingRoutes = array_filter(array_map('trim', explode(',', $existingRoutesRaw)));
                }
            } elseif (is_array($existingRoutesRaw)) {
                $existingRoutes = $existingRoutesRaw;
            } else {
                $existingRoutes = [];
            }

            $existingRoutesText = implode(', ', array_values(array_filter(array_map(function ($route) {
                return trim((string) $route);
            }, $existingRoutes))));
        @endphp
        <input type="text" name="web_routes_temp[]" value="{{ $existingRoutesText }}"
            placeholder="https://ejemplo.com/ruta, /ruta-interna, https://otro-enlace.com"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 web-route-input">
        <p class="mt-1 text-sm text-gray-500">
            Ingresa URLs completas o rutas internas separadas por comas. Máximo 8 rutas.
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
                    tabindex="-1"
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

            /* =========================================================
               Select2 dentro de modales (crear solicitante)
               ========================================================= */
            .select2-container--open {
                z-index: 99999;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single {
                height: 40px;
                border-radius: 0.5rem;
                border-color: #d1d5db;
                padding: 0.35rem 0.75rem;
                display: flex;
                align-items: center;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single .select2-selection__rendered {
                line-height: 24px;
                padding-left: 0;
                padding-right: 2.25rem;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single .select2-selection__arrow {
                height: 38px;
                right: 0.75rem;
            }

            .select2-dropdown.s2-modal-dropdown {
                border-radius: 0.75rem;
                overflow: hidden;
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

                    function attachPasteSupport($select) {
                        if (!$select || !$select.length) return;

                        function getClipboardText(e) {
                            if (e && e.clipboardData && typeof e.clipboardData.getData === 'function') {
                                return e.clipboardData.getData('text');
                            }
                            if (window.clipboardData && typeof window.clipboardData.getData === 'function') {
                                return window.clipboardData.getData('Text');
                            }
                            return '';
                        }

                        function focusSearchWithText(text) {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (!search) return;
                            search.focus();
                            search.value = text;
                            const evt = document.createEvent('HTMLEvents');
                            evt.initEvent('input', true, true);
                            search.dispatchEvent(evt);
                        }

                        $select.on('select2:open', function () {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (!search || search.dataset.pasteBound) return;
                            search.dataset.pasteBound = '1';
                            search.addEventListener('paste', function (e) {
                                const text = getClipboardText(e);
                                if (!text) return;
                                e.preventDefault();
                                focusSearchWithText(text.trim());
                            });
                        });

                        const container = $select.next('.select2-container');
                        if (container.length) {
                            const selection = container[0].querySelector('.select2-selection');
                            if (selection && !selection.dataset.pasteBound) {
                                selection.dataset.pasteBound = '1';
                                selection.addEventListener('paste', function (e) {
                                    const text = getClipboardText(e);
                                    if (!text) return;
                                    e.preventDefault();
                                    $select.select2('open');
                                    setTimeout(function () {
                                        focusSearchWithText(text.trim());
                                    }, 0);
                                });
                            }
                        }

                        const nativeSelect = $select[0];
                        if (nativeSelect && !nativeSelect.dataset.pasteBound) {
                            nativeSelect.dataset.pasteBound = '1';
                            nativeSelect.addEventListener('paste', function (e) {
                                const text = getClipboardText(e);
                                if (!text) return;
                                e.preventDefault();
                                $select.select2('open');
                                setTimeout(function () {
                                    focusSearchWithText(text.trim());
                                }, 0);
                            });
                        }
                    }

                    const requesterSelect = window.jQuery('#requester_id');
                    if (requesterSelect.length && !requesterSelect.data('select2')) {
                        requesterSelect.select2({
                            width: '100%',
                            placeholder: 'Seleccione un solicitante',
                            allowClear: true
                        });

                        // Al tabular y abrir el Select2, enfocar la búsqueda automáticamente
                        requesterSelect.on('select2:open', function () {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (search instanceof HTMLInputElement) {
                                search.focus();
                                search.select();
                            } else if (search) {
                                search.focus();
                            }
                        });
                        attachPasteSupport(requesterSelect);
                    }

                    // Crear solicitante sin refrescar (modal + AJAX)
                    try {
                        const modal = document.getElementById('requesterQuickCreateModal');
                        const openBtn = document.getElementById('openRequesterQuickCreate');
                        const closeBtn = document.getElementById('closeRequesterQuickCreate');
                        const cancelBtn = document.getElementById('cancelRequesterQuickCreate');
                        const form = document.getElementById('requesterQuickCreateForm');
                        const errorsBox = document.getElementById('requesterQuickCreateErrors');

                        if (modal && openBtn && closeBtn && cancelBtn && form) {
                            if (!modal.dataset.bound) {
                                modal.dataset.bound = '1';

                                const overlay = modal.querySelector('[data-overlay]');
                                const errorsList = errorsBox?.querySelector('[data-errors-list]');
                                const nameInput = document.getElementById('quickRequesterName');
                                const submitBtn = document.getElementById('submitRequesterQuickCreate');

                                let lastFocusEl = null;

                                function showModal() {
                                    lastFocusEl = document.activeElement;
                                    modal.classList.remove('hidden');
                                    modal.setAttribute('aria-hidden', 'false');
                                    if (errorsBox) errorsBox.classList.add('hidden');
                                    if (errorsList) errorsList.innerHTML = '';

                                    // IMPORTANTE: evitar que estos campos (dentro del form principal) bloqueen el submit
                                    // cuando el modal está oculto. Al abrir, los habilitamos; al cerrar, los deshabilitamos.
                                    modal.querySelectorAll('[data-quick-requester-field]').forEach((el) => {
                                        el.disabled = false;
                                    });

                                    if (nameInput) {
                                        nameInput.value = '';
                                        setTimeout(() => nameInput.focus(), 0);
                                    }
                                    const emailInput = document.getElementById('quickRequesterEmail');
                                    const phoneInput = document.getElementById('quickRequesterPhone');
                                    const deptInput = document.getElementById('quickRequesterDepartment');
                                    const posInput = document.getElementById('quickRequesterPosition');
                                    if (emailInput) emailInput.value = '';
                                    if (phoneInput) phoneInput.value = '';
                                    if (deptInput) {
                                        deptInput.value = '';
                                        if (window.jQuery && window.jQuery.fn?.select2 && window.jQuery(deptInput).data('select2')) {
                                            window.jQuery(deptInput).val(null).trigger('change');
                                        }
                                    }
                                    if (posInput) posInput.value = '';

                                    // Select2: Departamento dentro del modal
                                    if (window.jQuery && window.jQuery.fn?.select2 && deptInput) {
                                        const $dept = window.jQuery(deptInput);
                                        if (!$dept.data('select2')) {
                                            $dept.select2({
                                                width: '100%',
                                                placeholder: 'Seleccione un departamento',
                                                allowClear: true,
                                                dropdownParent: window.jQuery(modal),
                                                selectionCssClass: 's2-modal-selection',
                                                dropdownCssClass: 's2-modal-dropdown'
                                            });

                                            $dept.on('select2:open', function () {
                                                const search = document.querySelector('.select2-container--open .select2-search__field');
                                                if (search instanceof HTMLInputElement) {
                                                    search.focus();
                                                    search.select();
                                                } else if (search) {
                                                    search.focus();
                                                }
                                            });
                                        } else {
                                            // asegurar dropdownParent correcto en caso de re-render
                                            $dept.data('select2').$dropdown?.appendTo(window.jQuery(modal));
                                        }
                                    }
                                }

                                function hideModal() {
                                    modal.classList.add('hidden');
                                    modal.setAttribute('aria-hidden', 'true');

                                    modal.querySelectorAll('[data-quick-requester-field]').forEach((el) => {
                                        el.disabled = true;
                                    });

                                    // devolver foco a quien abrió el modal (o al botón)
                                    const target = lastFocusEl || openBtn;
                                    if (target && typeof target.focus === 'function') {
                                        setTimeout(() => target.focus(), 0);
                                    }
                                }

                                function getFocusableElements(container) {
                                    if (!container) return [];
                                    const selectors = [
                                        'a[href]',
                                        'area[href]',
                                        'input:not([disabled]):not([type="hidden"])',
                                        'select:not([disabled])',
                                        'textarea:not([disabled])',
                                        'button:not([disabled])',
                                        'iframe',
                                        'object',
                                        'embed',
                                        '[contenteditable="true"]',
                                        '[tabindex]:not([tabindex="-1"])'
                                    ].join(',');

                                    return Array.from(container.querySelectorAll(selectors)).filter((el) => {
                                        if (!(el instanceof HTMLElement)) return false;
                                        if (el.hasAttribute('disabled')) return false;
                                        if (el.getAttribute('aria-hidden') === 'true') return false;
                                        // visible (Select2 y modales)
                                        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
                                    });
                                }

                                openBtn.addEventListener('click', showModal);
                                closeBtn.addEventListener('click', hideModal);
                                cancelBtn.addEventListener('click', hideModal);
                                overlay?.addEventListener('click', hideModal);

                                modal.addEventListener('keydown', function (e) {
                                    if (e.key === 'Escape') {
                                        e.preventDefault();
                                        hideModal();
                                    }

                                    if (e.key === 'Tab' && !modal.classList.contains('hidden')) {
                                        const focusables = getFocusableElements(modal);
                                        if (!focusables.length) {
                                            e.preventDefault();
                                            return;
                                        }

                                        const first = focusables[0];
                                        const last = focusables[focusables.length - 1];
                                        const active = document.activeElement;

                                        if (!(active instanceof HTMLElement) || !modal.contains(active)) {
                                            e.preventDefault();
                                            first.focus();
                                            return;
                                        }

                                        if (e.shiftKey && active === first) {
                                            e.preventDefault();
                                            last.focus();
                                        } else if (!e.shiftKey && active === last) {
                                            e.preventDefault();
                                            first.focus();
                                        }
                                    }
                                });

                                async function submitQuickRequester() {

                                    if (errorsBox) errorsBox.classList.add('hidden');
                                    if (errorsList) errorsList.innerHTML = '';

                                    const url = form.dataset.url;
                                    if (!url) {
                                        if (errorsBox) {
                                            errorsBox.classList.remove('hidden');
                                            if (errorsList) errorsList.innerHTML = '<li>No se configuró la URL del endpoint.</li>';
                                        }
                                        return;
                                    }

                                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                                    const payload = {
                                        name: (document.getElementById('quickRequesterName')?.value || '').trim(),
                                        email: (document.getElementById('quickRequesterEmail')?.value || '').trim() || null,
                                        phone: (document.getElementById('quickRequesterPhone')?.value || '').trim() || null,
                                        department: (document.getElementById('quickRequesterDepartment')?.value || '').trim() || null,
                                        position: (document.getElementById('quickRequesterPosition')?.value || '').trim() || null,
                                        company_id: (document.getElementById('company_id')?.value || '').trim() || null,
                                    };

                                    if (!payload.name) {
                                        if (errorsBox) errorsBox.classList.remove('hidden');
                                        if (errorsList) errorsList.innerHTML = '<li>El nombre es obligatorio.</li>';
                                        setTimeout(() => nameInput?.focus(), 0);
                                        return;
                                    }

                                    if (submitBtn) {
                                        submitBtn.disabled = true;
                                        submitBtn.classList.add('opacity-75');
                                    }

                                    try {
                                        const res = await fetch(url, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                                            },
                                            body: JSON.stringify(payload),
                                        });

                                        const data = await res.json().catch(() => null);

                                        if (!res.ok) {
                                            const messages = [];
                                            if (data?.errors && typeof data.errors === 'object') {
                                                for (const key of Object.keys(data.errors)) {
                                                    const arr = data.errors[key];
                                                    if (Array.isArray(arr)) {
                                                        for (const msg of arr) messages.push(String(msg));
                                                    }
                                                }
                                            }
                                            if (!messages.length) {
                                                messages.push(data?.message ? String(data.message) : 'No se pudo crear el solicitante.');
                                            }
                                            if (errorsBox) {
                                                errorsBox.classList.remove('hidden');
                                                if (errorsList) {
                                                    errorsList.innerHTML = messages.map(m => `<li>${String(m).replace(/</g,'&lt;').replace(/>/g,'&gt;')}</li>`).join('');
                                                }
                                            }
                                            return;
                                        }

                                        const requesterId = data?.id;
                                        const display = data?.display || (data?.name || 'Solicitante');
                                        if (!requesterId) {
                                            if (errorsBox) {
                                                errorsBox.classList.remove('hidden');
                                                if (errorsList) errorsList.innerHTML = '<li>Respuesta inválida del servidor.</li>';
                                            }
                                            return;
                                        }

                                        const select = document.getElementById('requester_id');
                                        if (select) {
                                            const newOption = new Option(display, String(requesterId), true, true);
                                            const companyId = (document.getElementById('company_id')?.value || '').trim();
                                            if (companyId) {
                                                newOption.setAttribute('data-company-id', companyId);
                                            }
                                            if (window.jQuery && window.jQuery.fn?.select2 && window.jQuery(select).data('select2')) {
                                                window.jQuery(select).append(newOption).trigger('change');
                                            } else {
                                                select.appendChild(newOption);
                                                select.value = String(requesterId);
                                                select.dispatchEvent(new Event('change', { bubbles: true }));
                                            }
                                        }

                                        hideModal();
                                    } finally {
                                        if (submitBtn) {
                                            submitBtn.disabled = false;
                                            submitBtn.classList.remove('opacity-75');
                                        }
                                    }
                                }

                                submitBtn?.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    submitQuickRequester();
                                });

                                modal.addEventListener('keydown', function (e) {
                                    // Evitar que Enter dispare el submit del formulario principal (HTML anidado)
                                    if (e.key === 'Enter') {
                                        const target = e.target;
                                        const isInput = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA');
                                        if (isInput) {
                                            e.preventDefault();
                                            submitQuickRequester();
                                        }
                                    }
                                });
                            }
                        }
                    } catch (e) {
                        // Evitar que un error aquí rompa el resto del formulario
                    }

                    const entryChannelSelect = window.jQuery('#entry_channel');
                    if (entryChannelSelect.length && !entryChannelSelect.data('select2')) {
                        entryChannelSelect.select2({
                            width: '100%',
                            placeholder: 'Seleccione un canal',
                            minimumResultsForSearch: Infinity
                        });
                    }

                    const cutSelect = window.jQuery('#cut_id');
                    if (cutSelect.length && !cutSelect.data('select2')) {
                        cutSelect.select2({
                            width: '100%',
                            placeholder: 'Sin corte asignado'
                        });

                        cutSelect.on('select2:open', function () {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (search instanceof HTMLInputElement) {
                                search.focus();
                                search.select();
                            } else if (search) {
                                search.focus();
                            }
                        });

                        attachPasteSupport(cutSelect);
                    }

                    const subServiceSelect = window.jQuery('#sub_service_id');
                    if (subServiceSelect.length && !subServiceSelect.data('select2')) {
                        function getFocusableElementsInDocument() {
                            const selectors = [
                                'a[href]',
                                'area[href]',
                                'input:not([disabled]):not([type="hidden"])',
                                'select:not([disabled])',
                                'textarea:not([disabled])',
                                'button:not([disabled])',
                                '[contenteditable="true"]',
                                '[tabindex]:not([tabindex="-1"])'
                            ].join(',');

                            return Array.from(document.querySelectorAll(selectors)).filter((el) => {
                                if (!(el instanceof HTMLElement)) return false;
                                if (el.hasAttribute('disabled')) return false;
                                if (el.getAttribute('aria-hidden') === 'true') return false;
                                return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
                            });
                        }

                        function focusRelativeTo(el, direction) {
                            const focusables = getFocusableElementsInDocument();
                            const idx = focusables.indexOf(el);
                            if (idx === -1) return;
                            const nextIdx = direction === 'prev' ? idx - 1 : idx + 1;
                            const target = focusables[nextIdx];
                            if (target && typeof target.focus === 'function') {
                                target.focus();
                            }
                        }

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

                        // UX: al abrir, enfocar el buscador; y con Tab/Shift+Tab cerrar y mover foco
                        subServiceSelect.on('select2:open', function () {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (search instanceof HTMLElement) {
                                search.focus();
                                if (search instanceof HTMLInputElement) {
                                    search.select();
                                }

                                // Evitar múltiples bindings
                                if (!search.dataset.tabBound) {
                                    search.dataset.tabBound = '1';
                                    search.addEventListener('keydown', function (e) {
                                        if (e.key !== 'Tab') return;
                                        e.preventDefault();

                                        // Cerrar el dropdown primero
                                        subServiceSelect.select2('close');

                                        // Mover el foco relativo al contenedor de Select2
                                        const s2 = subServiceSelect.data('select2');
                                        const selectionEl = s2?.$selection?.get?.(0) || s2?.$container?.get?.(0) || null;
                                        const base = (selectionEl instanceof HTMLElement) ? selectionEl : search;

                                        setTimeout(() => {
                                            focusRelativeTo(base, e.shiftKey ? 'prev' : 'next');
                                        }, 0);
                                    });
                                }
                            }
                        });
                        attachPasteSupport(subServiceSelect);

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
            if (slaIdInput) slaIdInput.value = '';
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
        slaId = slaId || '';

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

    // Preparar rutas web como JSON antes de enviar
    function prepareWebRoutes() {
        console.log('🌐 Preparando web_routes...');
        const input = document.querySelector('input[name="web_routes_temp[]"]');
        const raw = String(input?.value ?? '');
        const routes = raw
            .split(',')
            .map((item) => item.trim())
            .filter((item) => item.length > 0);

        const maxRoutes = 8;
        const normalizedRoutes = routes.slice(0, maxRoutes);

        if (routes.length > maxRoutes) {
            console.warn(`⚠️ Se recibieron más de ${maxRoutes} rutas. Se tomarán las primeras ${maxRoutes}.`);
        }

        const webRoutesInput = document.getElementById('web_routes_json');
        if (webRoutesInput) {
            webRoutesInput.value = JSON.stringify(normalizedRoutes);
            console.log('✅ web_routes establecido:', webRoutesInput.value);
        }

        return normalizedRoutes;
    }

    // INICIALIZACIÓN
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOM Cargado - Inicializando formulario...');

        // Mantener sincronizados familia/servicio/SLA/criticidad al cambiar subservicio
        const subServiceSelect = document.getElementById('sub_service_id');
        const cutSelect = document.getElementById('cut_id');
        if (subServiceSelect) {
            subServiceSelect.addEventListener('change', updateFormFields);
        }

        function filterCutsByContract() {
            if (!cutSelect) return;
            const contractId = cutSelect.dataset.activeContractId || '';
            const preserveSelected = cutSelect.dataset.preserveSelected === '1';
            let hasSelection = false;
            Array.from(cutSelect.options).forEach(option => {
                if (!option.value) return;
                const optionContractId = option.dataset.contractId || '';
                const shouldShow = !contractId || optionContractId === contractId || (preserveSelected && option.selected);
                option.hidden = !shouldShow;
                option.disabled = !shouldShow;
                if (shouldShow && option.selected) {
                    hasSelection = true;
                }
            });

            if (contractId && !hasSelection && cutSelect.value) {
                cutSelect.value = '';
            }

            if (window.jQuery && window.jQuery.fn?.select2) {
                const $cutSelect = window.jQuery(cutSelect);
                if ($cutSelect.data('select2')) {
                    $cutSelect.trigger('change.select2');
                }
            }
        }

        filterCutsByContract();

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
                    // No bloqueamos el envío: el backend validará y mostrará errores.
                    console.warn('⚠️ service_id/family_id vacíos; se enviará para que valide el backend.');
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
