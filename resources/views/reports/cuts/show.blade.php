@extends('layouts.app')

@section('content')
<div class="py-6">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">{{ $cut->name }}</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
                <h2 class="text-xl font-bold text-gray-900">{{ $cut->name }}</h2>
                <p class="text-sm text-gray-600">{{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }}</p>
                @if($cut->contract)
                    <p class="text-xs text-gray-500 mt-1">Contrato: {{ $cut->contract->number }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">
                    Criterio de asociación: solicitudes cuya fecha de asignación aceptada del técnico está dentro del rango del corte.
                </p>
                @if($cut->notes)
                    <p class="text-sm text-gray-700 mt-2">{{ $cut->notes }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if(!empty($cut->folder_path))
                    <a href="openfolder://{{ str_replace('\\', '/', $cut->folder_path) }}" class="px-3 py-2 rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50" title="Abrir carpeta en explorador de archivos">
                        <i class="fa-solid fa-folder-open"></i>
                        Abrir carpeta
                    </a>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $cut->folder_path }}').then(() => { this.innerHTML='<i class=\'fa-solid fa-check\'></i> Copiado'; setTimeout(() => { this.innerHTML='<i class=\'fa-solid fa-copy\'></i> Copiar ruta'; }, 2000); })" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" title="Copiar ruta de carpeta al portapapeles">
                        <i class="fa-solid fa-copy"></i>
                        Copiar ruta
                    </button>
                @endif
                <a href="{{ route('reports.cuts.analytics', $cut) }}" class="px-3 py-2 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                    <i class="fa-solid fa-chart-column"></i>
                    Informe analitico
                </a>
                <a href="{{ route('reports.cuts.edit', $cut) }}" class="px-3 py-2 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar corte
                </a>
                <a href="{{ route('reports.cuts.associated-requests', $cut) }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-list-check"></i>
                    Ver solicitudes
                </a>
                <form method="POST" action="{{ route('reports.cuts.sync', $cut) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-rotate"></i>
                        Recalcular
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 text-green-700 border-b border-green-100">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">{{ session('error') }}</div>
        @endif

        <div class="p-6">
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Familias de servicios para el reporte</h3>
                    <button
                        type="button"
                        id="selectAllFamilies"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded px-2 py-1"
                        aria-label="Seleccionar todas las familias"
                    >
                        <i class="fa-solid fa-check-double mr-1" aria-hidden="true"></i>
                        Seleccionar Todas
                    </button>
                </div>

                @if($families->count() > 0)
                    <form id="familyFilterForm" method="GET" action="{{ route('reports.cuts.show', $cut) }}">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($families as $family)
                                @php
                                    $obligationNumber = (int) ($family->sort_order ?? 0);
                                    $familyLabel = ($obligationNumber > 0 ? $obligationNumber . '. ' : '')
                                        . ($family->contract?->number ? ($family->contract->number . ' - ') : '')
                                        . $family->name;
                                @endphp
                                <label class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all group">
                                    <input
                                        type="checkbox"
                                        name="families[]"
                                        value="{{ $family->id }}"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array($family->id, $selectedFamilyIds ?? []) ? 'checked' : '' }}
                                    >
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 group-hover:text-blue-700">
                                            {{ $familyLabel }}
                                        </span>
                                        @if($family->description)
                                            <span class="block text-xs text-gray-500 mt-1">
                                                {{ \Illuminate\Support\Str::limit($family->description, 60) }}
                                            </span>
                                        @endif
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                                            {{ $family->services_count ?? 0 }} servicio{{ ($family->services_count ?? 0) !== 1 ? 's' : '' }}
                                        </span>
                                        <a
                                            href="{{ route('reports.cuts.associated-requests', ['cut' => $cut, 'family_id' => $family->id]) }}"
                                            class="inline-block mt-1 ml-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                            title="Ver solicitudes asociadas de esta familia en este corte"
                                            onclick="event.stopPropagation();"
                                        >
                                            {{ (int) ($familyRequestCounts[$family->id] ?? 0) }} solicitud{{ ((int) ($familyRequestCounts[$family->id] ?? 0)) !== 1 ? 'es' : '' }} en este corte
                                        </a>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-col sm:flex-row gap-3">
                            <button
                                type="submit"
                                formaction="{{ route('reports.cuts.export', $cut) }}"
                                id="downloadReportBtn"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700"
                            >
                                <i class="fa-solid fa-download mr-2" aria-hidden="true"></i>
                                Descargar reporte
                            </button>
                            <button
                                type="button"
                                id="downloadIndividualBtn"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"
                            >
                                <i class="fa-solid fa-file-zipper mr-2" aria-hidden="true"></i>
                                Descargar individual
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-500">
                            Puedes seleccionar una o varias familias. El mismo filtro aplica a la tabla, al PDF y al ZIP.
                        </p>
                        @error('families')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                @else
                    <p class="text-sm text-gray-500">No hay familias disponibles para este contrato.</p>
                @endif
            </div>

            <div id="serviceRequestsContainer" aria-live="polite">
                @include('reports.cuts.partials.service-requests-table', [
                    'cut' => $cut,
                    'serviceRequests' => $serviceRequests,
                    'selectedFamilyIds' => $selectedFamilyIds,
                    'selectedFamilyLabels' => $selectedFamilyLabels,
                ])
            </div>

            {{-- Evidence Organization Section --}}
            @if(!empty($cut->folder_path))
                <div class="mt-8 p-4 bg-gray-50 border border-gray-200 rounded-lg" id="evidenceOrganizationSection">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">
                                <i class="fa-solid fa-folder-open mr-1" aria-hidden="true"></i>
                                Organizar evidencias
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Selecciona archivos de evidencia para moverlos a la carpeta del corte.
                                <span id="evidenceCountDisplay" class="font-medium text-gray-700">{{ $evidenceCount }} evidencia{{ $evidenceCount !== 1 ? 's' : '' }} disponible{{ $evidenceCount !== 1 ? 's' : '' }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Organization Result Summary --}}
                    @if(session('organization_result'))
                        @php $orgResult = session('organization_result'); @endphp
                        <div class="mb-4 p-3 rounded-lg {{ $orgResult['failure_count'] > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200' }}">
                            <div class="flex items-start gap-2">
                                @if($orgResult['failure_count'] === 0)
                                    <i class="fa-solid fa-circle-check text-green-600 mt-0.5" aria-hidden="true"></i>
                                @else
                                    <i class="fa-solid fa-triangle-exclamation text-yellow-600 mt-0.5" aria-hidden="true"></i>
                                @endif
                                <div class="flex-1">
                                    @if($orgResult['success_count'] > 0)
                                        <p class="text-sm font-medium text-green-700">
                                            {{ $orgResult['success_count'] }} archivo{{ $orgResult['success_count'] !== 1 ? 's' : '' }} organizado{{ $orgResult['success_count'] !== 1 ? 's' : '' }} correctamente
                                        </p>
                                    @endif
                                    @if($orgResult['failure_count'] > 0)
                                        <p class="text-sm font-medium text-red-700 {{ $orgResult['success_count'] > 0 ? 'mt-1' : '' }}">
                                            {{ $orgResult['failure_count'] }} archivo{{ $orgResult['failure_count'] !== 1 ? 's' : '' }} fallaron
                                        </p>
                                        @if(!empty($orgResult['failed']))
                                            <ul class="mt-2 text-xs text-red-600 list-disc list-inside">
                                                @foreach($orgResult['failed'] as $failure)
                                                    <li>ID {{ $failure['evidence_id'] }}: {{ $failure['reason'] }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($evidences->count() > 0)
                        <form method="POST" action="{{ route('reports.cuts.organize-evidences', $cut) }}" id="organizeEvidencesForm">
                            @csrf

                            {{-- Controls --}}
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        id="selectAllEvidences"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        aria-label="Seleccionar todas las evidencias"
                                    >
                                    <span>Seleccionar todo</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <span id="selectedEvidenceCount" class="text-xs text-gray-500">0 seleccionados</span>
                                    <span id="maxSelectionWarning" class="text-xs text-red-600 hidden">Máximo 50 archivos</span>
                                    <button
                                        type="submit"
                                        id="organizeBtn"
                                        disabled
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        <i class="fa-solid fa-folder-tree mr-1.5" aria-hidden="true"></i>
                                        Organizar
                                    </button>
                                </div>
                            </div>

                            {{-- Evidence List --}}
                            <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg bg-white">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="w-10 px-3 py-2 text-left"></th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Archivo</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Solicitud</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Tipo</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Tamaño</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($evidences as $evidence)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="checkbox"
                                                        name="evidence_ids[]"
                                                        value="{{ $evidence->id }}"
                                                        class="evidence-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        aria-label="Seleccionar evidencia {{ $evidence->file_original_name ?? $evidence->title ?? 'ID ' . $evidence->id }}"
                                                    >
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid {{ $evidence->file_icon }} text-gray-400" aria-hidden="true"></i>
                                                        <span class="text-gray-900 truncate max-w-xs" title="{{ $evidence->file_original_name ?? $evidence->title ?? 'Sin nombre' }}">
                                                            {{ $evidence->file_original_name ?? $evidence->title ?? 'Sin nombre' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-gray-600">
                                                    {{ $evidence->serviceRequest?->ticket_number ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-gray-600">
                                                    @if($evidence->evidence_type === 'ENLACE')
                                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">Enlace</span>
                                                    @else
                                                        <span class="text-xs">{{ $evidence->file_type }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-gray-500 text-xs">
                                                    {{ $evidence->formatted_file_size }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @error('evidence_ids')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </form>
                    @else
                        <p class="text-sm text-gray-500">No hay evidencias disponibles para organizar en este corte.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllFamilies');
    const familyCheckboxes = document.querySelectorAll('#familyFilterForm input[name="families[]"]');
    const downloadBtn = document.getElementById('downloadReportBtn');
    const downloadIndividualBtn = document.getElementById('downloadIndividualBtn');
    const familyFilterForm = document.getElementById('familyFilterForm');
    const serviceRequestsContainer = document.getElementById('serviceRequestsContainer');
    const exportUrl = @json(route('reports.cuts.export', $cut));

    if (!selectAllBtn || familyCheckboxes.length === 0 || !familyFilterForm || !serviceRequestsContainer) {
        return;
    }

    function updateSelectAllButton() {
        const allChecked = Array.from(familyCheckboxes).every(cb => cb.checked);
        if (allChecked) {
            selectAllBtn.innerHTML = '<i class="fa-solid fa-times mr-1" aria-hidden="true"></i>Deseleccionar Todas';
            selectAllBtn.setAttribute('aria-label', 'Deseleccionar todas las familias');
        } else {
            selectAllBtn.innerHTML = '<i class="fa-solid fa-check-double mr-1" aria-hidden="true"></i>Seleccionar Todas';
            selectAllBtn.setAttribute('aria-label', 'Seleccionar todas las familias');
        }
    }

    selectAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const allChecked = Array.from(familyCheckboxes).every(cb => cb.checked);
        familyCheckboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        updateSelectAllButton();
    });

    familyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAllButton);
    });

    if (downloadBtn) {
        downloadBtn.innerHTML = '<i class="fa-solid fa-download mr-2" aria-hidden="true"></i>Descargar carpeta PDF por familia';
    }

    function getSelectedFamilyIds() {
        return Array.from(familyCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
    }

    if (downloadIndividualBtn) {
        downloadIndividualBtn.addEventListener('click', async function() {
            const selectedIds = getSelectedFamilyIds();
            if (selectedIds.length === 0) {
                alert('Selecciona al menos una familia para descargar.');
                return;
            }

            downloadIndividualBtn.disabled = true;

            let startedDownloads = 0;
            for (const familyId of selectedIds) {
                const checkParams = new URLSearchParams();
                checkParams.append('families[]', familyId);
                checkParams.append('format', 'pdf');
                checkParams.append('check_only', '1');

                try {
                    const checkResponse = await fetch(`${exportUrl}?${checkParams.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                    if (!checkResponse.ok) {
                        continue;
                    }

                    const checkData = await checkResponse.json();
                    if (!checkData?.has_requests) {
                        continue;
                    }

                    const downloadParams = new URLSearchParams();
                    downloadParams.append('families[]', familyId);
                    downloadParams.append('format', 'pdf');

                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = `${exportUrl}?${downloadParams.toString()}`;
                    document.body.appendChild(iframe);
                    setTimeout(() => iframe.remove(), 12000);
                    startedDownloads++;

                    await new Promise(resolve => setTimeout(resolve, 300));
                } catch (error) {
                    // Ignore family-level errors and continue with others.
                }
            }

            if (startedDownloads === 0) {
                alert('Ninguna de las familias seleccionadas tiene solicitudes para descargar.');
            }

            downloadIndividualBtn.disabled = false;
        });
    }

    let autoFilterTimeout = null;
    let currentRequest = null;

    const fetchFilteredResults = async (url = null) => {
        const params = new URLSearchParams(new FormData(familyFilterForm));
        const fetchUrl = url || `${familyFilterForm.action}?${params.toString()}`;

        if (currentRequest) {
            currentRequest.abort();
        }
        currentRequest = new AbortController();

        serviceRequestsContainer.classList.add('opacity-60', 'pointer-events-none', 'transition-opacity');

        try {
            const response = await fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: currentRequest.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data?.html) {
                serviceRequestsContainer.innerHTML = data.html;
            }

            const nextUrl = data?.url || fetchUrl;
            window.history.replaceState({}, '', nextUrl);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('No se pudo actualizar la tabla sin recargar.', error);
            }
        } finally {
            serviceRequestsContainer.classList.remove('opacity-60', 'pointer-events-none');
        }
    };

    const submitFilterForm = () => {
        if (autoFilterTimeout) {
            clearTimeout(autoFilterTimeout);
        }
        autoFilterTimeout = setTimeout(() => {
            fetchFilteredResults();
        }, 250);
    };

    familyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', submitFilterForm);
    });

    // Select all should also trigger auto-filter
    selectAllBtn.addEventListener('click', function() {
        submitFilterForm();
    });

    familyFilterForm.addEventListener('submit', function(e) {
        const submitter = e.submitter;
        const isDownload = submitter && submitter.id === 'downloadReportBtn';
        if (isDownload) {
            return;
        }

        e.preventDefault();
        fetchFilteredResults();
    });

    document.addEventListener('click', function(e) {
        const paginationLink = e.target.closest('#serviceRequestsContainer a[href*="page="]');
        if (!paginationLink) {
            return;
        }

        e.preventDefault();
        fetchFilteredResults(paginationLink.href);
    });

    updateSelectAllButton();

    // =====================================================================
    // Evidence Organization - Checkbox Selection Logic
    // =====================================================================
    const MAX_EVIDENCE_SELECTION = 50;
    const selectAllEvidences = document.getElementById('selectAllEvidences');
    const organizeBtn = document.getElementById('organizeBtn');
    const selectedCountEl = document.getElementById('selectedEvidenceCount');
    const maxWarningEl = document.getElementById('maxSelectionWarning');
    const evidenceCheckboxes = document.querySelectorAll('.evidence-checkbox');

    if (selectAllEvidences && organizeBtn && evidenceCheckboxes.length > 0) {
        function updateEvidenceSelectionState() {
            const checked = document.querySelectorAll('.evidence-checkbox:checked');
            const count = checked.length;
            const total = evidenceCheckboxes.length;

            // Update count display
            if (selectedCountEl) {
                selectedCountEl.textContent = count + ' seleccionado' + (count !== 1 ? 's' : '');
            }

            // Enable/disable organize button
            organizeBtn.disabled = count === 0 || count > MAX_EVIDENCE_SELECTION;

            // Show/hide max selection warning
            if (maxWarningEl) {
                if (count > MAX_EVIDENCE_SELECTION) {
                    maxWarningEl.classList.remove('hidden');
                } else {
                    maxWarningEl.classList.add('hidden');
                }
            }

            // Update select all checkbox state
            if (count === 0) {
                selectAllEvidences.checked = false;
                selectAllEvidences.indeterminate = false;
            } else if (count === total) {
                selectAllEvidences.checked = true;
                selectAllEvidences.indeterminate = false;
            } else {
                selectAllEvidences.checked = false;
                selectAllEvidences.indeterminate = true;
            }
        }

        selectAllEvidences.addEventListener('change', function() {
            const shouldCheck = this.checked;
            const limit = Math.min(evidenceCheckboxes.length, MAX_EVIDENCE_SELECTION);

            evidenceCheckboxes.forEach(function(cb, index) {
                if (shouldCheck) {
                    cb.checked = index < limit;
                } else {
                    cb.checked = false;
                }
            });

            updateEvidenceSelectionState();
        });

        evidenceCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', updateEvidenceSelectionState);
        });

        // Form submission confirmation
        const organizeForm = document.getElementById('organizeEvidencesForm');
        if (organizeForm) {
            organizeForm.addEventListener('submit', function(e) {
                const checked = document.querySelectorAll('.evidence-checkbox:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    return;
                }
                if (checked.length > MAX_EVIDENCE_SELECTION) {
                    e.preventDefault();
                    alert('No puedes organizar más de ' + MAX_EVIDENCE_SELECTION + ' archivos a la vez.');
                    return;
                }
                if (!confirm('¿Organizar ' + checked.length + ' archivo' + (checked.length !== 1 ? 's' : '') + ' en la carpeta del corte?')) {
                    e.preventDefault();
                }
            });
        }

        // Initialize state
        updateEvidenceSelectionState();
    }
});
</script>
@endsection
