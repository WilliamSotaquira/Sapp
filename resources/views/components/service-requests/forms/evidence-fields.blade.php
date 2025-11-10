{{-- resources/views/components/service-requests/forms/evidence-fields.blade.php --}}

@props([
'evidence' => null, // Para edición, null para creación
'serviceRequest' => null, // Opcional: para contexto de solicitud
'hideTitle' => false, // Opcional: ocultar título de sección
])

<div class="space-y-6 evidence-fields">
    @unless($hideTitle)
    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-paperclip text-blue-500 mr-2"></i>
            Información de Evidencia
        </h3>
        <p class="text-sm text-gray-500 mt-1">Complete los detalles de la evidencia</p>
    </div>
    @endunless

    <!-- Campo: Título -->
    <div>
        <label for="evidence_title" class="block text-sm font-medium text-gray-700 mb-2">
            Título de la Evidencia <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            id="evidence_title"
            name="title"
            value="{{ old('title', $evidence->title ?? '') }}"
            required
            maxlength="255"
            placeholder="Ej: Captura de pantalla del error, Documento de configuración..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
        @error('title')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Campo: Tipo de Evidencia -->
    <div>
        <label for="evidence_type" class="block text-sm font-medium text-gray-700 mb-2">
            Tipo de Evidencia <span class="text-red-500">*</span>
        </label>
        <select
            id="evidence_type"
            name="evidence_type"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
            <option value="">Seleccione un tipo</option>
            <option value="ARCHIVO" {{ old('evidence_type', $evidence->evidence_type ?? '') == 'ARCHIVO' ? 'selected' : '' }}>
                📎 Archivo Adjunto
            </option>
            <option value="PASO_A_PASO" {{ old('evidence_type', $evidence->evidence_type ?? '') == 'PASO_A_PASO' ? 'selected' : '' }}>
                📝 Descripción Paso a Paso
            </option>
            <option value="ENLACE" {{ old('evidence_type', $evidence->evidence_type ?? '') == 'ENLACE' ? 'selected' : '' }}>
                🔗 Enlace Externo
            </option>
        </select>
        @error('evidence_type')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Campo: Descripción -->
    <div>
        <label for="evidence_description" class="block text-sm font-medium text-gray-700 mb-2">
            Descripción
        </label>
        <textarea
            id="evidence_description"
            name="description"
            rows="4"
            placeholder="Describa la evidencia, pasos realizados, observaciones importantes..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 resize-vertical">{{ old('description', $evidence->description ?? '') }}</textarea>
        @error('description')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500">Máximo 1000 caracteres</p>
    </div>

    <!-- Campo: Archivo (solo para tipo ARCHIVO) -->
    <div id="file_upload_section" style="display: none;">
        <label for="evidence_file" class="block text-sm font-medium text-gray-700 mb-2">
            Archivo <span id="file_required" style="display: none;" class="text-red-500">*</span>
        </label>

        @if($evidence && $evidence->hasFile())
        <!-- Mostrar archivo actual si está editando -->
        <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file text-blue-500"></i>
                    <div>
                        <p class="font-medium text-gray-800">{{ $evidence->file_original_name }}</p>
                        <p class="text-sm text-gray-600">{{ $evidence->formatted_file_size }}</p>
                    </div>
                </div>
                <a href="{{ $evidence->getFileUrl() }}"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Ver actual
                </a>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-2">Seleccione un nuevo archivo solo si desea reemplazar el actual</p>
        @endif

        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition duration-200 bg-gray-50">
            <input
                type="file"
                id="evidence_file"
                name="file"
                class="hidden"
                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
            <div id="file_upload_content">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                <p class="text-gray-600 font-medium">Haga clic para seleccionar un archivo</p>
                <p class="text-gray-500 text-sm mt-1">Formatos: imágenes, PDF, documentos, archivos comprimidos</p>
                <p class="text-gray-400 text-xs mt-2">Tamaño máximo: 10MB</p>
            </div>
            <div id="file_selected" class="hidden text-left">
                <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                    <i class="fas fa-file text-green-500"></i>
                    <div class="flex-1">
                        <p id="file_name" class="font-medium text-gray-800"></p>
                        <p id="file_size" class="text-sm text-gray-600"></p>
                    </div>
                    <button type="button" onclick="clearFileSelection()" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @error('file')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Campo: Enlace (solo para tipo ENLACE) -->
    <div id="link_section" style="display: none;">
        <label for="evidence_link" class="block text-sm font-medium text-gray-700 mb-2">
            Enlace URL
        </label>
        <input
            type="url"
            id="evidence_link"
            name="link_url"
            value="{{ old('link_url', $evidence->link_url ?? '') }}"
            placeholder="https://ejemplo.com/documento"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
        @error('link_url')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Campo: Visibilidad -->
    <div>
        <label class="flex items-center space-x-3">
            <input
                type="checkbox"
                name="is_public"
                value="1"
                {{ old('is_public', $evidence->is_public ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Hacer esta evidencia visible para el solicitante</span>
        </label>
        <p class="text-sm text-gray-500 mt-1">Si está desmarcado, solo será visible para el personal técnico</p>
    </div>

    <!-- Información contextual si hay serviceRequest -->
    @if($serviceRequest)
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex items-center space-x-2 text-sm text-gray-600">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span>Evidencia para solicitud: <strong>{{ $serviceRequest->ticket_number }}</strong> - {{ $serviceRequest->title }}</span>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const evidenceTypeSelect = document.getElementById('evidence_type');
        const fileUploadSection = document.getElementById('file_upload_section');
        const linkSection = document.getElementById('link_section');
        const fileRequired = document.getElementById('file_required');
        const fileInput = document.getElementById('evidence_file');
        const fileUploadContent = document.getElementById('file_upload_content');
        const fileSelected = document.getElementById('file_selected');
        const fileName = document.getElementById('file_name');
        const fileSize = document.getElementById('file_size');

        // Mostrar/ocultar secciones según tipo de evidencia
        function toggleSections() {
            const selectedType = evidenceTypeSelect.value;

            // Ocultar todas las secciones primero
            fileUploadSection.style.display = 'none';
            linkSection.style.display = 'none';
            fileRequired.style.display = 'none';

            // Mostrar secciones según el tipo seleccionado
            if (selectedType === 'ARCHIVO') {
                fileUploadSection.style.display = 'block';
                fileRequired.style.display = 'inline';
            } else if (selectedType === 'ENLACE') {
                linkSection.style.display = 'block';
            }

            // Si está editando y tiene archivo, no hacer required el campo file
            @if($evidence && $evidence - > hasFile())
            fileRequired.style.display = 'none';
            @endif
        }

        // Manejar selección de archivo
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileUploadContent.classList.add('hidden');
                fileSelected.classList.remove('hidden');
            }
        });

        // Limpiar selección de archivo
        window.clearFileSelection = function() {
            fileInput.value = '';
            fileUploadContent.classList.remove('hidden');
            fileSelected.classList.add('hidden');
        }

        // Formatear tamaño de archivo
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Event listeners
        evidenceTypeSelect.addEventListener('change', toggleSections);

        // Click en área de upload
        fileUploadSection.querySelector('.border-dashed').addEventListener('click', function() {
            fileInput.click();
        });

        // Inicializar estado
        toggleSections();

        // Si hay error de validación, asegurar que se muestren las secciones correctas
        @if($errors - > has('file') || $errors - > has('link_url'))
        toggleSections();
        @endif
    });
</script>

<style>
    .evidence-fields .border-dashed {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }

    .evidence-fields .border-dashed:hover {
        border-color: #3b82f6;
        background-color: #f8fafc;
    }

    .resize-vertical {
        resize: vertical;
        min-height: 100px;
    }
</style>
