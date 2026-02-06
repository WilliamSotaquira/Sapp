@extends('layouts.app')

@section('content')
<div class="py-6">
    <!-- Breadcrumb -->
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Informe por Rango de Tiempo</li>
        </ol>
    </nav>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Informe por rango de tiempo</p>
                <h2 class="text-xl font-bold text-gray-900">3 pasos rápidos</h2>
                <p class="text-sm text-gray-600">Elige fechas, familias y formato. Nada más.</p>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                <i class="fa-solid fa-bolt"></i> Simple y completo
            </span>
        </div>

        <form action="{{ route('reports.time-range.generate') }}" method="POST" id="reportForm" class="p-6 space-y-8">
            @csrf

            <!-- Paso 1: Fechas -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">1</span>
                    <h4 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-blue-600" aria-hidden="true"></i>
                        Rango de fechas
                    </h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('start_date') border-red-500 @enderror"
                            id="start_date"
                            name="start_date"
                            value="{{ old('start_date', request('start_date')) }}"
                            max="{{ date('Y-m-d') }}"
                            required
                            aria-required="true"
                            aria-describedby="start_date_help"
                        >
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p id="start_date_help" class="mt-1 text-xs text-gray-500">Seleccione la fecha inicial del período</p>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha de Fin <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('end_date') border-red-500 @enderror"
                            id="end_date"
                            name="end_date"
                            value="{{ old('end_date', request('end_date')) }}"
                            max="{{ date('Y-m-d') }}"
                            required
                            aria-required="true"
                            aria-describedby="end_date_help"
                        >
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                        <p id="end_date_help" class="mt-1 text-xs text-gray-500">Seleccione la fecha final del período</p>
                    </div>
                </div>

                <!-- Date validation alert -->
                <div id="dateAlert" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg hidden" role="alert">
                    <div class="flex items-start">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <p class="text-sm text-red-700" id="dateAlertMessage"></p>
                    </div>
                </div>
            </div>

            <!-- Paso 2: Familias -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">2</span>
                        <h4 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-layer-group text-blue-600" aria-hidden="true"></i>
                            Familias de servicios
                        </h4>
                    </div>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($families as $family)
                            <div class="relative">
                                <label class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all group">
                                    <input
                                        type="checkbox"
                                        name="families[]"
                                        value="{{ $family->id }}"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array($family->id, old('families', [])) ? 'checked' : '' }}
                                    >
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 group-hover:text-blue-700">
                                            @php
                                                $familyLabel = $family->contract?->number
                                                    ? ($family->contract->number . ' - ' . $family->name)
                                                    : $family->name;
                                            @endphp
                                            {{ $familyLabel }}
                                        </span>
                                        @if($family->description)
                                            <span class="block text-xs text-gray-500 mt-1">
                                                {{ Str::limit($family->description, 60) }}
                                            </span>
                                        @endif
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                                            {{ $family->services_count ?? 0 }} servicio{{ ($family->services_count ?? 0) !== 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @error('families')
                        <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror

                    <p class="mt-3 text-xs text-gray-500 flex items-center gap-2">
                        <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
                        Selecciona una o varias familias. Si no marcas nada, te avisamos antes de generar.
                    </p>
                @else
                    <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-exclamation-triangle text-yellow-600 text-xl mr-3" aria-hidden="true"></i>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">No hay familias de servicios disponibles</p>
                                <p class="text-xs text-yellow-700 mt-1">Por favor, cree al menos una familia de servicios antes de generar reportes.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Paso 3: Formato -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">3</span>
                    <h4 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-file-export text-blue-600" aria-hidden="true"></i>
                        Formato de exportación
                    </h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all group has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                        <input
                            type="radio"
                            name="format"
                            value="pdf"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            {{ old('format', 'pdf') === 'pdf' ? 'checked' : '' }}
                            required
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf text-red-600 text-lg" aria-hidden="true"></i>
                                <span class="text-sm font-semibold text-gray-900">PDF</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Reporte visual listo para imprimir.</p>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-green-50 hover:border-green-300 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                        <input
                            type="radio"
                            name="format"
                            value="excel"
                            class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                            {{ old('format') === 'excel' ? 'checked' : '' }}
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-file-excel text-green-600 text-lg" aria-hidden="true"></i>
                                <span class="text-sm font-semibold text-gray-900">Excel</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Datos editables en hojas separadas.</p>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 hover:border-purple-300 transition-all group has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 {{ !extension_loaded('zip') ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input
                            type="radio"
                            name="format"
                            value="zip"
                            class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                            {{ old('format') === 'zip' ? 'checked' : '' }}
                            {{ !extension_loaded('zip') ? 'disabled' : '' }}
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-file-zipper text-purple-600 text-lg" aria-hidden="true"></i>
                                <span class="text-sm font-semibold text-gray-900">ZIP</span>
                                @if(!extension_loaded('zip'))
                                    <span class="ml-1 px-2 py-0.5 text-2xs bg-red-100 text-red-700 rounded-full font-medium">No disponible</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-600 mt-1">Excel + evidencias en un solo archivo.</p>
                        </div>
                    </label>
                </div>

                @error('format')
                    <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                <button
                    type="submit"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    id="generateBtn"
                    {{ $families->count() === 0 ? 'disabled' : '' }}
                >
                    <i class="fa-solid fa-rocket mr-2" aria-hidden="true"></i>
                    Generar Informe
                </button>

                <a
                    href="{{ route('reports.index') }}"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all"
                >
                    <i class="fa-solid fa-arrow-left mr-2" aria-hidden="true"></i>
                    Volver
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date validation elements
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const dateAlert = document.getElementById('dateAlert');
    const dateAlertMessage = document.getElementById('dateAlertMessage');
    const generateBtn = document.getElementById('generateBtn');

    // Validate date range
    function validateDateRange() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let isValid = true;
        let message = '';

        if (startDateInput.value && endDateInput.value) {
            if (startDate > endDate) {
                isValid = false;
                message = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
            } else if (endDate > today) {
                isValid = false;
                message = 'La fecha de fin no puede ser posterior a la fecha actual.';
            } else if (startDate > today) {
                isValid = false;
                message = 'La fecha de inicio no puede ser posterior a la fecha actual.';
            }
        }

        // Update UI
        if (!isValid) {
            dateAlert.classList.remove('hidden');
            dateAlertMessage.textContent = message;
            generateBtn.disabled = true;
            startDateInput.classList.add('border-red-500');
            endDateInput.classList.add('border-red-500');
        } else {
            dateAlert.classList.add('hidden');
            generateBtn.disabled = {{ $families->count() === 0 ? 'true' : 'false' }};
            startDateInput.classList.remove('border-red-500');
            endDateInput.classList.remove('border-red-500');
        }

        return isValid;
    }

    // Attach event listeners
    startDateInput.addEventListener('change', validateDateRange);
    endDateInput.addEventListener('change', validateDateRange);

    // Select All Families functionality
    const selectAllBtn = document.getElementById('selectAllFamilies');
    const familyCheckboxes = document.querySelectorAll('input[name="families[]"]');

    if (selectAllBtn && familyCheckboxes.length > 0) {
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const allChecked = Array.from(familyCheckboxes).every(cb => cb.checked);

            familyCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });

            // Update button text and icon
            if (!allChecked) {
                this.innerHTML = '<i class="fa-solid fa-times mr-1" aria-hidden="true"></i>Deseleccionar Todas';
                this.setAttribute('aria-label', 'Deseleccionar todas las familias');
            } else {
                this.innerHTML = '<i class="fa-solid fa-check-double mr-1" aria-hidden="true"></i>Seleccionar Todas';
                this.setAttribute('aria-label', 'Seleccionar todas las familias');
            }
        });
    }    // Form submission validation
    const reportForm = document.getElementById('reportForm');
    let downloadCheckInterval;

    reportForm.addEventListener('submit', function(e) {
        if (!validateDateRange()) {
            e.preventDefault();
            dateAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        // Check if at least one family is selected
        const checkedFamilies = document.querySelectorAll('input[name="families[]"]:checked');
        if (checkedFamilies.length === 0) {
            e.preventDefault();
            alert('Por favor, seleccione al menos una familia de servicios.');
            return false;
        }

        // Show loading state
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Generando reporte...';

        // Reset button after 10 seconds (en caso de descarga exitosa o error)
        setTimeout(function() {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fa-solid fa-rocket mr-2" aria-hidden="true"></i>Generar Informe';
        }, 10000);
    });

    // Initial validation on page load
    if (startDateInput.value && endDateInput.value) {
        validateDateRange();
    }

    // Accessibility: Enter key support for custom buttons
    selectAllBtn?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
});
</script>
@endsection
