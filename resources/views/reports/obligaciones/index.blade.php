@extends('layouts.app')

@section('content')
@php
    $reportPrimaryColor = (isset($currentWorkspace?->primary_color) && preg_match('/^#([A-Fa-f0-9]{6})$/', $currentWorkspace->primary_color))
        ? strtoupper($currentWorkspace->primary_color)
        : '#2563EB';
    $reportContrastColor = (isset($currentWorkspace?->contrast_color) && preg_match('/^#([A-Fa-f0-9]{6})$/', $currentWorkspace->contrast_color))
        ? strtoupper($currentWorkspace->contrast_color)
        : '#FFFFFF';
    $extractResolutionDescription = function ($notes) {
        $notes = trim((string) $notes);
        if ($notes === '') {
            return '';
        }

        $notes = preg_replace('/\s*===\s*CIERRE(?:\s+POR\s+VENCIMIENTO|\s+NORMAL)\s*===.*$/is', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Fecha\/Hora:.*$/im', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Usuario:\s*ID\s*\d+.*$/im', '', $notes) ?? $notes;
        $notes = trim((string) $notes);

        if (preg_match('/Acciones realizadas:\s*(.*?)(?:\n\s*Notas adicionales:\s*|$)/is', $notes, $matches)) {
            $notes = trim((string) ($matches[1] ?? ''));
        }

        $notes = preg_replace('/\n{3,}/', "\n\n", $notes) ?? $notes;

        return trim((string) $notes);
    };
@endphp
<div class="py-8 px-4 sm:px-6 lg:px-8">
    @php
        $selectedStatuses = collect($filters['statuses'] ?? []);
        $activeFilterCount = collect([
            ($filters['cut_id'] ?? null) && ($filters['cut_id'] ?? null) !== 'all' ? 'cut' : null,
            ($selectedStatuses->count() > 0 && $selectedStatuses->count() < count($statuses)) ? 'status' : null,
            filled($filters['q'] ?? '') ? 'q' : null,
        ])->filter()->count();
        $exportParams = array_filter([
            'cut_id' => $filters['cut_id'] ?? null,
            'statuses' => $filters['statuses'] ?? [],
            'q' => $filters['q'] ?? null,
        ], function ($value) {
            if (is_array($value)) {
                return count($value) > 0;
            }

            return $value !== null && $value !== '' && $value !== 'all';
        });
        $selectedStatusLabels = $selectedStatuses
            ->map(fn ($statusKey) => $statuses[$statusKey] ?? $statusKey)
            ->values();
        $familyExportRequirements = collect($familyExportRequirements ?? []);
    @endphp
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Reporte de Obligaciones</h1>
            <p class="text-gray-600 mt-2">Gestión de obligaciones, actividades y productos ejecutados</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 font-semibold text-blue-800">
                <i class="fas fa-filter text-xs"></i>Filtros activos: {{ $activeFilterCount }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 font-semibold text-slate-700">
                <i class="fas fa-layer-group text-xs"></i>{{ $stats['familias'] ?? 0 }} familias
            </span>
        </div>
    </div>

    <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 rounded-full bg-amber-100 p-2 text-amber-700">
                <i class="fas fa-link text-sm"></i>
            </div>
            <div class="space-y-2 text-sm text-amber-900">
                <h2 class="text-base font-bold text-amber-950">Regla para generar PDF y Excel</h2>
                <p>Antes de descargar el informe, debes registrar un enlace del directorio en la nube por cada familia incluida en el reporte.</p>
                <p>Ese enlace es obligatorio para generar el PDF o el Excel. Si falta un enlace o si no es una ruta absoluta, la exportación se bloquea.</p>
                <p>Dentro del programa solo se muestran los nombres de los archivos y su extensión; no se publican esos enlaces en la tabla de productos.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow p-5 mb-8 border border-gray-100">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            id="openFiltersSidebar"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        <i class="fas fa-sliders-h text-xs"></i>
                        Filtros
                    </button>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                        <i class="fas fa-layer-group text-[11px]"></i>
                        {{ $selectedStatuses->count() }} estado{{ $selectedStatuses->count() === 1 ? '' : 's' }} seleccionado{{ $selectedStatuses->count() === 1 ? '' : 's' }}
                    </span>
                    @if(($filters['cut_id'] ?? null) && ($filters['cut_id'] ?? null) !== 'all')
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                            <i class="fas fa-calendar-alt text-[11px]"></i>
                            {{ $cuts->firstWhere('id', (int) $filters['cut_id'])?->name ?? 'Corte seleccionado' }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($selectedStatusLabels as $statusLabel)
                        <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700">
                            {{ $statusLabel }}
                        </span>
                    @endforeach
                    @if(filled($filters['q'] ?? ''))
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                            "{{ $filters['q'] }}"
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        class="js-export-obligaciones bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg"
                        data-format="pdf"
                        data-export-url="{{ route('reports.obligaciones.export') }}">
                    Descargar PDF
                </button>
                <button type="button"
                        class="js-export-obligaciones bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-5 rounded-lg"
                        data-format="xlsx"
                        data-export-url="{{ route('reports.obligaciones.export') }}">
                    Descargar Excel
                </button>
                <a href="{{ route('reports.obligaciones.download-evidences', $exportParams) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-5 rounded-lg">
                    Descargar Evidencias
                </a>
            </div>
        </div>
    </div>

    <div id="cloudLinksOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/40 backdrop-blur-[1px]"></div>

    <aside id="cloudLinksPanel" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl translate-x-full flex-col overflow-y-auto bg-white shadow-2xl transition-transform duration-300 ease-in-out">
        <div class="border-b border-emerald-700 bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-semibold text-white">
                        <i class="fas fa-cloud"></i>
                        Directorios en la nube
                    </h3>
                    <p class="mt-1 text-xs text-emerald-100">Se requiere un enlace absoluto por cada familia antes de exportar.</p>
                </div>
                <button type="button" id="closeCloudLinksPanel" class="text-white transition-colors hover:text-emerald-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form id="cloudLinksForm" class="flex flex-1 flex-col">
            <div class="flex-1 space-y-5 px-6 py-5">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    El sistema pedirá un enlace `https://...` por cada familia visible en el reporte. No se aceptan rutas relativas.
                </div>

                @forelse($familyExportRequirements as $familyRequirement)
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <label for="family-link-{{ $familyRequirement['id'] }}" class="block text-sm font-semibold text-gray-900">
                            {{ $familyRequirement['name'] }}
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Directorio en la nube donde reposará la información de esta familia.</p>
                        <input type="url"
                               id="family-link-{{ $familyRequirement['id'] }}"
                               name="family_links[{{ $familyRequirement['id'] }}]"
                               class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="https://..."
                               inputmode="url"
                               required>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        No hay familias disponibles para exportar con los filtros actuales.
                    </div>
                @endforelse
            </div>

            <div class="flex gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" id="cancelCloudLinksPanel" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700">
                    Continuar con la descarga
                </button>
            </div>
        </form>
    </aside>

    <div id="filtersSidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/40 backdrop-blur-[1px]"></div>

    <aside id="filtersSidebar" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md translate-x-full flex-col overflow-y-auto bg-white shadow-2xl transition-transform duration-300 ease-in-out">
        <div class="border-b border-blue-700 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-white">
                    <i class="fas fa-sliders-h"></i>
                    Filtros del Reporte
                </h3>
                <button type="button" id="closeFiltersSidebar" class="text-white transition-colors hover:text-blue-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-blue-100">Ajusta corte, estados y búsqueda del reporte.</p>
        </div>

        <form method="GET" action="{{ route('reports.obligaciones.index') }}" class="flex flex-1 flex-col" id="cutFilterForm">
            <div class="flex-1 space-y-6 px-6 py-5">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Corte</label>
                    <select name="cut_id" id="cutFilterSelect" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Todos los cortes</option>
                        @foreach($cuts as $cut)
                            <option value="{{ $cut->id }}" {{ (string)($filters['cut_id'] ?? '') === (string)$cut->id ? 'selected' : '' }}>
                                {{ $cut->name }} ({{ $cut->start_date?->format('d/m/Y') }} - {{ $cut->end_date?->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">Estados</label>
                        <button type="button"
                                id="toggleAllStatuses"
                                class="text-xs font-semibold text-blue-700 hover:text-blue-800 hover:underline">
                            Seleccionar todos
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 sm:grid-cols-2">
                        @foreach($statuses as $statusKey => $statusLabel)
                            @php
                                $isChecked = in_array($statusKey, $filters['statuses'] ?? [], true);
                            @endphp
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700">
                                <input type="checkbox"
                                       name="statuses[]"
                                       value="{{ $statusKey }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       {{ $isChecked ? 'checked' : '' }}>
                                <span>{{ $statusLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Por defecto se muestra <strong>Cerrada</strong>. Marca otros estados para sumarlos al reporte.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text"
                           name="q"
                           value="{{ $filters['q'] ?? '' }}"
                           placeholder="Ticket, obligación, solicitante o familia"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <a href="{{ route('reports.obligaciones.index') }}" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">
                    <i class="fas fa-redo mr-2"></i>Limpiar
                </a>
                <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </form>
    </aside>

    @if(($familySummaries ?? collect())->count() > 0)
        <div class="bg-white rounded-2xl shadow p-5 mb-8 border border-gray-100">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Navegación por Familias</h2>
                    <p class="text-sm text-gray-500">Salta rápido a cada bloque del reporte.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($familySummaries as $familySummary)
                    <a href="#{{ $familySummary['anchor'] }}"
                       class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                        <span>{{ $familySummary['name'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] text-gray-600">{{ $familySummary['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Lista de Obligaciones Agrupadas por Familia -->
    @if($serviceRequests->count() > 0)
        <div class="space-y-6">
            @foreach($serviceRequests as $serviceName => $obligaciones)
                @php
                    $familyAnchor = 'family-' . (\Illuminate\Support\Str::slug($serviceName) !== '' ? \Illuminate\Support\Str::slug($serviceName) : 'sin-familia');
                @endphp
                <div id="{{ $familyAnchor }}" class="bg-white rounded-2xl shadow overflow-hidden border border-gray-100 scroll-mt-24">
                    <!-- Encabezado de la Familia -->
                    <div class="px-6 py-3" style="background-color: {{ $reportPrimaryColor }};">
                        @php
                            $familyDescription = $obligaciones->first()?->subService?->service?->family?->description;
                            $familySortOrder = $obligaciones->first()?->subService?->service?->family?->sort_order;
                            $familyHeading = $familySortOrder !== null ? ($familySortOrder . '. ' . $serviceName) : $serviceName;
                            $familyTotal = $obligaciones->count();
                            $familyTaskCount = $obligaciones->sum(fn ($sr) => (int) $sr->tasks->count());
                            $familyEvidenceCount = $obligaciones->sum(fn ($sr) => (int) $sr->evidences->count());
                        @endphp
                        <h2 class="text-lg font-bold" style="color: {{ $reportContrastColor }};">{{ $familyHeading }}</h2>
                        @if($familyDescription)
                            <p class="text-sm mt-1" style="color: {{ $reportContrastColor }}; opacity: .9;">{{ $familyDescription }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full px-3 py-1 font-semibold" style="background-color: rgba(255,255,255,.16); color: {{ $reportContrastColor }};">
                                {{ $familyTotal }} obligaciones
                            </span>
                            <span class="rounded-full px-3 py-1 font-semibold" style="background-color: rgba(255,255,255,.16); color: {{ $reportContrastColor }};">
                                {{ $familyTaskCount }} actividades
                            </span>
                            <span class="rounded-full px-3 py-1 font-semibold" style="background-color: rgba(255,255,255,.16); color: {{ $reportContrastColor }};">
                                {{ $familyEvidenceCount }} productos
                            </span>
                        </div>
                    </div>

                    <!-- Tabla de Obligaciones de la Familia -->
                    <table class="min-w-full table-fixed divide-y divide-gray-200">
                        <colgroup>
                            <col class="w-1/3">
                            <col class="w-1/3">
                            <col class="w-1/3">
                        </colgroup>
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Obligaciones</th>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Actividades Ejecutadas</th>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Productos Presentados</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($obligaciones as $sr)
                                <tr class="hover:bg-gray-50">
                                    <!-- OBLIGACIONES -->
                                    <td class="px-6 py-3 text-sm">
                                        @php
                                            $requesterName = $sr->requester?->name ?? $sr->requestedBy?->name ?? 'Sin solicitante';
                                            $activitiesCount = $sr->tasks->count();
                                            $productsCount = $sr->evidences->count();
                                        @endphp
                                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                            <a href="{{ route('service-requests.show', $sr) }}"
                                               class="text-[11px] font-semibold text-blue-700 bg-blue-50 rounded-full px-2.5 py-1 hover:bg-blue-100 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               aria-label="Abrir solicitud {{ $sr->ticket_number }}">
                                                {{ $sr->ticket_number }}
                                            </a>
                                            <span class="text-[11px] font-semibold text-gray-700 bg-gray-100 rounded-full px-2.5 py-1">{{ $statuses[$sr->status] ?? $sr->status }}</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">{{ $sr->title }}</p>
                                        @if($sr->description)
                                            <p class="text-xs text-gray-600 mt-1">{{ Str::limit($sr->description, 120) }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-500">
                                            <span><i class="fas fa-user mr-1 text-gray-400"></i>{{ $requesterName }}</span>
                                            <span><i class="fas fa-list-check mr-1 text-gray-400"></i>{{ $activitiesCount }} actividades</span>
                                            <span><i class="fas fa-paperclip mr-1 text-gray-400"></i>{{ $productsCount }} productos</span>
                                        </div>
                                    </td>

                                    <!-- ACTIVIDADES EJECUTADAS -->
                                    <td class="px-6 py-3 text-sm">
                                        @php
                                            $activitySummary = $extractResolutionDescription($sr->resolution_notes ?? '');
                                        @endphp
                                        @if($activitySummary !== '')
                                            <div class="whitespace-pre-line text-xs leading-5 text-gray-800">{{ $activitySummary }}</div>
                                        @else
                                            <p class="text-xs text-gray-500">—</p>
                                        @endif
                                    </td>

                                    <!-- PRODUCTOS PRESENTADOS -->
                                    <td class="px-6 py-3 text-sm break-words">
                                        @php
                                            $fileEvidences = $sr->evidences->where('file_path');
                                            $hasProducts = $fileEvidences->count() > 0;
                                        @endphp
                                        @if($hasProducts)
                                            <ul class="space-y-1">
                                                @foreach($fileEvidences as $evidence)
                                                    @php
                                                        $realFileName = trim((string) ($evidence->file_original_name ?? $evidence->file_name ?? ''));
                                                    @endphp
                                                    @if($realFileName !== '')
                                                        <li class="text-xs text-gray-700 break-all">
                                                            {{ $realFileName }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-xs text-gray-500">—</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No se encontraron obligaciones con los filtros especificados</p>
        </div>
    @endif
</div>

<style>
    .bg-gradient-to-r {
        background-image: linear-gradient(to right, var(--tw-gradient-stops));
    }
</style>
<script>
    (function () {
        const body = document.body;
        const form = document.getElementById('cutFilterForm');
        const sidebar = document.getElementById('filtersSidebar');
        const overlay = document.getElementById('filtersSidebarOverlay');
        const openButton = document.getElementById('openFiltersSidebar');
        const closeButton = document.getElementById('closeFiltersSidebar');
        const toggleAllStatusesButton = document.getElementById('toggleAllStatuses');
        const statusCheckboxes = form ? Array.from(form.querySelectorAll('input[name="statuses[]"]')) : [];

        if (!form || !sidebar || !overlay || !openButton || !closeButton) {
            return;
        }

        const openSidebar = function () {
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        };

        const closeSidebar = function () {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        };

        openButton.addEventListener('click', openSidebar);
        closeButton.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        const refreshToggleAllStatusesLabel = function () {
            if (!toggleAllStatusesButton || statusCheckboxes.length === 0) {
                return;
            }

            const allChecked = statusCheckboxes.every(function (checkbox) {
                return checkbox.checked;
            });

            toggleAllStatusesButton.textContent = allChecked ? 'Deseleccionar todos' : 'Seleccionar todos';
        };

        if (toggleAllStatusesButton && statusCheckboxes.length > 0) {
            toggleAllStatusesButton.addEventListener('click', function () {
                const allChecked = statusCheckboxes.every(function (checkbox) {
                    return checkbox.checked;
                });

                statusCheckboxes.forEach(function (checkbox) {
                    checkbox.checked = !allChecked;
                });

                refreshToggleAllStatusesLabel();
            });

            statusCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshToggleAllStatusesLabel);
            });

            refreshToggleAllStatusesLabel();
        }
    })();

    (function () {
        const body = document.body;
        const panel = document.getElementById('cloudLinksPanel');
        const overlay = document.getElementById('cloudLinksOverlay');
        const closeButton = document.getElementById('closeCloudLinksPanel');
        const cancelButton = document.getElementById('cancelCloudLinksPanel');
        const form = document.getElementById('cloudLinksForm');
        const exportButtons = document.querySelectorAll('.js-export-obligaciones');
        const exportParams = @json($exportParams);
        const familyRequirements = @json($familyExportRequirements->values());
        const familyLinksCache = {};
        let activeFormat = null;
        let activeExportUrl = null;

        if (!panel || !overlay || !closeButton || !cancelButton || !form || exportButtons.length === 0) {
            return;
        }

        const openPanel = function () {
            panel.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        };

        const closePanel = function () {
            panel.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        };

        const collectCurrentLinks = function () {
            familyRequirements.forEach(function (family) {
                const input = form.querySelector(`[name="family_links[${family.id}]"]`);
                if (input) {
                    familyLinksCache[family.id] = input.value.trim();
                }
            });
        };

        const restoreCachedLinks = function () {
            familyRequirements.forEach(function (family) {
                const input = form.querySelector(`[name="family_links[${family.id}]"]`);
                if (input && familyLinksCache[family.id]) {
                    input.value = familyLinksCache[family.id];
                }
            });
        };

        exportButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFormat = button.dataset.format || 'pdf';
                activeExportUrl = button.dataset.exportUrl || '';
                restoreCachedLinks();
                openPanel();
            });
        });

        closeButton.addEventListener('click', function () {
            collectCurrentLinks();
            closePanel();
        });

        cancelButton.addEventListener('click', function () {
            collectCurrentLinks();
            closePanel();
        });

        overlay.addEventListener('click', function () {
            collectCurrentLinks();
            closePanel();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                collectCurrentLinks();
                closePanel();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!activeExportUrl) {
                closePanel();
                return;
            }

            const params = new URLSearchParams();
            Object.entries(exportParams || {}).forEach(function ([key, value]) {
                if (Array.isArray(value)) {
                    value.forEach(function (item) {
                        params.append(`${key}[]`, item);
                    });
                    return;
                }

                if (value !== null && value !== '') {
                    params.set(key, value);
                }
            });

            params.set('format', activeFormat || 'pdf');

            if (familyRequirements.length === 0) {
                closePanel();
                window.location.href = `${activeExportUrl}?${params.toString()}`;
                return;
            }

            let hasInvalidUrl = false;

            familyRequirements.forEach(function (family) {
                const input = form.querySelector(`[name="family_links[${family.id}]"]`);
                const value = input ? input.value.trim() : '';

                if (input) {
                    input.setCustomValidity('');
                }

                if (!value) {
                    hasInvalidUrl = true;
                    if (input) {
                        input.setCustomValidity('Este enlace es obligatorio para exportar el informe.');
                        input.reportValidity();
                    }
                    return;
                }

                try {
                    const parsed = new URL(value);
                    if (!['http:', 'https:'].includes(parsed.protocol)) {
                        throw new Error('invalid-protocol');
                    }
                } catch (error) {
                    hasInvalidUrl = true;
                    if (input) {
                        input.setCustomValidity('Ingresa una ruta absoluta válida, por ejemplo https://...');
                        input.reportValidity();
                    }
                    return;
                }

                familyLinksCache[family.id] = value;
                params.set(`family_links[${family.id}]`, value);
            });

            if (hasInvalidUrl) {
                return;
            }

            closePanel();
            window.location.href = `${activeExportUrl}?${params.toString()}`;
        });
    })();
</script>
@endsection
