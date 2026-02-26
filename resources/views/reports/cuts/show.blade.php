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
                    Criterio de asociación: solicitudes con actividad en el rango (creación o actualización de la solicitud/tareas, y creación de evidencias/historiales).
                </p>
                @if($cut->notes)
                    <p class="text-sm text-gray-700 mt-2">{{ $cut->notes }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.cuts.edit', $cut) }}" class="px-3 py-2 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar corte
                </a>
                <a href="{{ route('reports.cuts.requests', $cut) }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-list-check"></i>
                    Gestionar solicitudes
                </a>
                <form method="POST" action="{{ route('reports.cuts.sync', $cut) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-rotate"></i>
                        Actualizar
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
                                    $familyLabel = $family->contract?->number
                                        ? ($family->contract->number . ' - ' . $family->name)
                                        : $family->name;
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
                                        <span class="inline-block mt-1 ml-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full">
                                            {{ (int) ($familyRequestCounts[$family->id] ?? 0) }} solicitud{{ ((int) ($familyRequestCounts[$family->id] ?? 0)) !== 1 ? 'es' : '' }} en este corte
                                        </span>
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
});
</script>
@endsection
