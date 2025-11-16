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

    <!-- Info Banner -->
    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
            <i class="fa-solid fa-circle-info text-blue-600 text-xl mr-3 mt-0.5" aria-hidden="true"></i>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Generar Informe por Rango de Tiempo</h2>
                <p class="text-sm text-gray-700">Configure los parámetros del informe y seleccione el formato de exportación deseado.</p>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 border-b border-blue-700">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <i class="fa-solid fa-chart-line mr-2" aria-hidden="true"></i>
                Configuración del Informe
            </h3>
        </div>

        <form action="{{ route('reports.time-range.generate') }}" method="POST" id="reportForm" class="p-6">
            @csrf

            <!-- Date Range Section -->
            <div class="mb-8">
                <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fa-solid fa-calendar-days text-blue-600 mr-2" aria-hidden="true"></i>
                    Rango de Fechas
                </h4>

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

            <!-- Service Families Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-base font-semibold text-gray-900 flex items-center">
                        <i class="fa-solid fa-layer-group text-blue-600 mr-2" aria-hidden="true"></i>
                        Familias de Servicios
                    </h4>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($families as $family)
                            <div class="relative">
                                <label class="flex items-start p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition-all group">
                                    <input
                                        type="checkbox"
                                        name="families[]"
                                        value="{{ $family->id }}"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array($family->id, old('families', [])) ? 'checked' : '' }}
                                    >
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 group-hover:text-blue-700">
                                            {{ $family->name }}
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

                    <p class="mt-4 text-xs text-gray-500">
                        <i class="fa-solid fa-info-circle mr-1" aria-hidden="true"></i>
                        Seleccione al menos una familia de servicios para generar el informe
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

            <!-- Export Format Section -->
            <div class="mb-8">
                <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fa-solid fa-file-export text-blue-600 mr-2" aria-hidden="true"></i>
                    Formato de Exportación
                </h4>

                <div class="space-y-3">
                    <!-- PDF Option -->
                    <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition-all group has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                        <input
                            type="radio"
                            name="format"
                            value="pdf"
                            class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            {{ old('format', 'pdf') === 'pdf' ? 'checked' : '' }}
                            required
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-file-pdf text-red-600 text-xl mr-2" aria-hidden="true"></i>
                                <span class="text-sm font-medium text-gray-900 group-hover:text-blue-700">PDF Document</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Descarga un archivo PDF con el informe completo y estadísticas</p>
                        </div>
                    </label>

                    <!-- Excel Option -->
                    <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-green-50 hover:border-green-400 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                        <input
                            type="radio"
                            name="format"
                            value="excel"
                            class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                            {{ old('format') === 'excel' ? 'checked' : '' }}
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-file-excel text-green-600 text-xl mr-2" aria-hidden="true"></i>
                                <span class="text-sm font-medium text-gray-900 group-hover:text-green-700">Excel Spreadsheet</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Descarga un archivo Excel con múltiples hojas de datos estructurados</p>
                        </div>
                    </label>

                    <!-- ZIP Option -->
                    <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50 hover:border-purple-400 transition-all group has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50 {{ !extension_loaded('zip') ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input
                            type="radio"
                            name="format"
                            value="zip"
                            class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                            {{ old('format') === 'zip' ? 'checked' : '' }}
                            {{ !extension_loaded('zip') ? 'disabled' : '' }}
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <i class="fa-solid fa-file-zipper text-purple-600 text-xl mr-2" aria-hidden="true"></i>
                                <span class="text-sm font-medium text-gray-900 group-hover:text-purple-700">
                                    ZIP Package con Evidencias
                                </span>
                                @if(!extension_loaded('zip'))
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full font-medium">
                                        No disponible
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Descarga un archivo ZIP con el informe Excel y todas las evidencias adjuntas
                            </p>
                            @if(!extension_loaded('zip'))
                                <p class="text-xs text-red-600 mt-2">
                                    <i class="fa-solid fa-exclamation-circle mr-1" aria-hidden="true"></i>
                                    La extensión ZIP de PHP no está habilitada. Active php_zip en php.ini
                                </p>
                            @endif
                        </div>
                    </label>
                </div>

                @error('format')
                    <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
                <button
                    type="submit"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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

    <!-- Information Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Features Card -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fa-solid fa-sparkles text-white text-lg" aria-hidden="true"></i>
                    </div>
                    <h5 class="text-base font-semibold text-gray-900">Características</h5>
                </div>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <i class="fa-solid fa-check text-green-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Filtrado por rango de fechas personalizado</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check text-green-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Agrupación por familias de servicios</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check text-green-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Estadísticas detalladas y métricas</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check text-green-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Múltiples formatos de exportación</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fa-solid fa-chart-pie text-white text-lg" aria-hidden="true"></i>
                    </div>
                    <h5 class="text-base font-semibold text-gray-900">Estadísticas Incluidas</h5>
                </div>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <i class="fa-solid fa-chart-line text-blue-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Total de solicitudes por estado</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-chart-line text-blue-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Distribución por familia de servicios</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-chart-line text-blue-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Tiempos promedio de resolución</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-chart-line text-blue-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span>Evidencias adjuntas por solicitud</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Export Formats Card -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fa-solid fa-download text-white text-lg" aria-hidden="true"></i>
                    </div>
                    <h5 class="text-base font-semibold text-gray-900">Formatos Disponibles</h5>
                </div>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <i class="fa-solid fa-file-pdf text-red-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span><strong>PDF:</strong> Informe visual imprimible</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-file-excel text-green-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span><strong>Excel:</strong> Datos editables en hojas</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-file-zipper text-purple-600 mr-2 mt-0.5" aria-hidden="true"></i>
                        <span><strong>ZIP:</strong> Excel + todas las evidencias</span>
                    </li>
                </ul>
            </div>
        </div>
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
