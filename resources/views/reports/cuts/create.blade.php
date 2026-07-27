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
            <li class="text-gray-900 font-medium">Nuevo corte</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Crear corte</h2>
            <p class="text-sm text-gray-600">Define el rango; el sistema asociará solicitudes cerradas/resueltas dentro de esas fechas.</p>
        </div>

        @if(!$hasActiveContract)
            <div class="p-6">
                <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800">Sin contrato activo</h3>
                            <p class="mt-1 text-sm text-yellow-700">No es posible crear un corte sin un contrato activo en el espacio de trabajo actual. Configura un contrato activo antes de continuar.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Volver a cortes</a>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('reports.cuts.store') }}" class="p-6 space-y-6" id="cut-create-form">
                @csrf

                {{-- Inline error (shown when server returns error via session) --}}
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4" role="alert">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contrato activo</label>
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
                        {{ $activeContract->number }}{{ $activeContract->name ? ' - ' . $activeContract->name : '' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror" required>
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora inicio <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="start_date" id="start_date" value="{{ old('start_date', $dateSuggestion ? $dateSuggestion->startDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('start_date') border-red-500 @enderror" required>
                        @if($dateSuggestion)
                            <p class="mt-1 text-xs text-gray-500">Sugerido: <span class="font-mono">{{ $dateSuggestion->formattedStartDate() }}</span></p>
                        @endif
                        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora fin <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="end_date" id="end_date" value="{{ old('end_date', $dateSuggestion ? $dateSuggestion->endDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('end_date') border-red-500 @enderror" required>
                        <p class="mt-1 text-xs text-gray-500">
                            <span id="end-date-hint">Se calcula automáticamente: 30 días desde el inicio.</span>
                            <button type="button" id="recalc-end-date" class="ml-1 text-blue-600 hover:text-blue-800 font-medium">Recalcular</button>
                        </p>
                        @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Preview panel --}}
                <div id="cut-preview" class="hidden rounded-xl border p-4 space-y-3 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-900">Vista previa del corte</h4>
                        <span id="preview-loading" class="hidden text-xs text-gray-500">
                            <svg class="animate-spin inline h-3 w-3 mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Calculando...
                        </span>
                    </div>
                    <div id="preview-content" class="space-y-2">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold" id="preview-count">--</span>
                                <span class="text-sm text-gray-600">solicitudes se asociarán</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span id="preview-duration">--</span> días de duración
                            </div>
                        </div>
                        <div id="preview-warnings" class="space-y-1"></div>
                    </div>
                </div>

                @if(!empty($suggestedFolderPath))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de carpeta <span class="text-xs text-gray-400">(opcional)</span></label>
                    <input type="text" name="folder_name" value="{{ old('folder_name', basename($suggestedFolderPath)) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm @error('folder_name') border-red-500 @enderror" placeholder="Se generará automáticamente si se deja vacío">
                    <p class="mt-1 text-xs text-gray-500">Carpeta donde se organizarán las evidencias del corte.</p>
                    @error('folder_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit" id="submit-btn" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Crear corte
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var startInput = document.getElementById('start_date');
    var endInput = document.getElementById('end_date');
    var recalcBtn = document.getElementById('recalc-end-date');
    var previewPanel = document.getElementById('cut-preview');
    var previewLoading = document.getElementById('preview-loading');
    var previewCount = document.getElementById('preview-count');
    var previewDuration = document.getElementById('preview-duration');
    var previewWarnings = document.getElementById('preview-warnings');
    var submitBtn = document.getElementById('submit-btn');
    var userManuallyEdited = false;
    var debounceTimer = null;

    if (!startInput || !endInput) return;

    function calcEndDate(startValue) {
        if (!startValue) return null;
        var start = new Date(startValue);
        if (isNaN(start.getTime())) return null;
        var end = new Date(start.getTime() + (30 * 24 * 60 * 60 * 1000) - 1000);
        var y = end.getFullYear();
        var m = String(end.getMonth() + 1).padStart(2, '0');
        var d = String(end.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d + 'T23:59';
    }

    function autoFillEnd() {
        if (userManuallyEdited) return;
        var val = calcEndDate(startInput.value);
        if (val) endInput.value = val;
    }

    function validateClientSide() {
        if (!startInput.value || !endInput.value) {
            previewPanel.classList.add('hidden');
            return false;
        }

        var start = new Date(startInput.value);
        var end = new Date(endInput.value);

        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            previewPanel.classList.add('hidden');
            return false;
        }

        if (end <= start) {
            showLocalWarning('La fecha de fin debe ser posterior a la fecha de inicio.', true);
            return false;
        }

        var diffHours = (end - start) / (1000 * 60 * 60);
        if (diffHours < 12) {
            showLocalWarning('El rango es menor a 12 horas. El sistema no permitirá crear este corte.', true);
            return false;
        }

        return true;
    }

    function showLocalWarning(message, isError) {
        previewPanel.classList.remove('hidden');
        previewPanel.className = previewPanel.className.replace(/border-\w+-\d+/g, '').replace(/bg-\w+-\d+/g, '');
        previewPanel.classList.add('rounded-xl', 'border', 'p-4', 'space-y-3', 'transition-all', 'duration-200');

        if (isError) {
            previewPanel.classList.add('border-red-300', 'bg-red-50');
            submitBtn.disabled = true;
        } else {
            previewPanel.classList.add('border-amber-300', 'bg-amber-50');
        }

        previewCount.textContent = '--';
        previewDuration.textContent = '--';
        previewWarnings.innerHTML = '<p class="text-sm ' + (isError ? 'text-red-700' : 'text-amber-700') + ' flex items-start gap-2"><svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>' + message + '</span></p>';
    }

    function fetchPreview() {
        if (!validateClientSide()) return;

        previewPanel.classList.remove('hidden');
        previewLoading.classList.remove('hidden');
        submitBtn.disabled = true;

        var formData = new FormData();
        formData.append('start_date', startInput.value);
        formData.append('end_date', endInput.value);
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        fetch('{{ route("reports.cuts.preview") }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            previewLoading.classList.add('hidden');
            previewCount.textContent = data.request_count;
            previewDuration.textContent = data.duration_days;

            // Style based on results
            previewPanel.className = 'rounded-xl border p-4 space-y-3 transition-all duration-200';

            if (!data.valid) {
                previewPanel.classList.add('border-red-300', 'bg-red-50');
                submitBtn.disabled = true;
            } else if (data.warnings.length > 0) {
                previewPanel.classList.add('border-amber-300', 'bg-amber-50');
                submitBtn.disabled = false;
            } else {
                previewPanel.classList.add('border-green-300', 'bg-green-50');
                submitBtn.disabled = false;
            }

            // Render warnings
            previewWarnings.innerHTML = '';
            data.warnings.forEach(function(w) {
                var isError = !data.valid;
                var colorClass = isError ? 'text-red-700' : 'text-amber-700';
                previewWarnings.innerHTML += '<p class="text-sm ' + colorClass + ' flex items-start gap-2"><svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>' + w + '</span></p>';
            });

            if (data.warnings.length === 0 && data.request_count > 0) {
                previewWarnings.innerHTML = '<p class="text-sm text-green-700 flex items-center gap-2"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Rango válido. Las solicitudes se asociarán al crear el corte.</span></p>';
            }
        })
        .catch(function() {
            previewLoading.classList.add('hidden');
            submitBtn.disabled = false;
        });
    }

    function debouncedPreview() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchPreview, 600);
    }

    startInput.addEventListener('change', function() {
        autoFillEnd();
        debouncedPreview();
    });
    startInput.addEventListener('input', function() {
        autoFillEnd();
        debouncedPreview();
    });

    endInput.addEventListener('input', function() {
        userManuallyEdited = true;
        debouncedPreview();
    });
    endInput.addEventListener('change', function() {
        userManuallyEdited = true;
        debouncedPreview();
    });

    recalcBtn.addEventListener('click', function() {
        userManuallyEdited = false;
        var val = calcEndDate(startInput.value);
        if (val) {
            endInput.value = val;
            debouncedPreview();
        }
    });

    // Initial preview if both dates are pre-filled
    if (startInput.value && endInput.value) {
        fetchPreview();
    }
})();
</script>
@endsection
