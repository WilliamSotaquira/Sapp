@props(['serviceRequest'])

@php
    // Permitir evidencias mientras la solicitud está en gestión (ACEPTADA, EN_PROCESO, PAUSADA) o finalizada (RESUELTA, CERRADA, NO_VIABLE)
    $canUploadEvidence = in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA', 'NO_VIABLE'], true);
@endphp

@if(!$canUploadEvidence)
<!-- Mensaje cuando la solicitud no permite agregar evidencias -->
<div class="border-2 border-gray-300 rounded-2xl p-6 text-center bg-gray-50">
    <div class="max-w-md mx-auto">
        <i class="fas fa-lock text-3xl text-gray-400 mb-4"></i>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">Evidencias Bloqueadas</h4>
        <p class="text-gray-500 text-sm mb-4">
            Solo puedes agregar evidencias cuando la solicitud está en gestión (<strong>ACEPTADA</strong>, <strong>EN PROCESO</strong> o <strong>PAUSADA</strong>) o finalizada (<strong>RESUELTA</strong> o <strong>CERRADA</strong>).
        </p>
        <div class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-200 text-gray-600 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <span>Estado actual: <strong>{{ $serviceRequest->status }}</strong></span>
        </div>
    </div>
</div>
@else
<!-- Formulario unificado de carga de evidencias -->
<div id="evidenceUploadArea" class="border border-dashed border-gray-300 rounded-xl p-4 hover:border-blue-300 hover:bg-blue-50/30 transition duration-150">
    <form action="{{ route('service-requests.evidences.store', $serviceRequest) }}"
          method="POST"
          enctype="multipart/form-data"
          id="evidenceUploadForm"
          class="space-y-3">
        @csrf
        <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">

        @if(session('evidence_success'))
            <div class="bg-green-100 border border-green-300 text-green-700 px-3 py-2 rounded-lg text-xs">
                <i class="fas fa-check-circle mr-1"></i>{{ session('evidence_success') }}
            </div>
        @endif
        @if(session('evidence_error'))
            <div class="bg-red-100 border border-red-300 text-red-700 px-3 py-2 rounded-lg text-xs">
                <i class="fas fa-exclamation-triangle mr-1"></i>{{ session('evidence_error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-4 items-start">
            {{-- File upload --}}
            <div class="space-y-2">
                <label for="evidenceFiles" class="flex items-center gap-3 cursor-pointer group">
                    <span class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2 text-xs"></i>Archivos
                    </span>
                    <span class="text-xs text-gray-400 group-hover:text-gray-600 transition">
                        Arrastra, selecciona o pega (Ctrl+V)
                    </span>
                    <input type="file"
                           name="files[]"
                           id="evidenceFiles"
                           multiple
                           class="hidden"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.csv,.svg">
                </label>
                <div id="fileList" class="space-y-1 hidden"></div>
                <p class="text-[10px] text-gray-300">JPG, PNG, GIF, PDF, DOC, XLS, TXT, ZIP, CSV, SVG · 10MB máx.</p>
            </div>

            {{-- Separator --}}
            <div class="hidden md:flex flex-col items-center self-stretch py-2">
                <div class="w-px flex-1 bg-gray-200"></div>
                <span class="text-[10px] text-gray-300 py-1">+</span>
                <div class="w-px flex-1 bg-gray-200"></div>
            </div>

            {{-- Link --}}
            <div class="space-y-2">
                <label for="link_url" class="text-xs font-medium text-gray-500">Enlace (opcional)</label>
                <input type="url"
                       name="link_url"
                       id="link_url"
                       placeholder="https://..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3 pt-1">
            <button type="submit"
                    id="uploadButton"
                    class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-upload mr-2 text-xs"></i>Guardar evidencia(s)
            </button>
            <span class="text-xs text-gray-400" id="uploadHint">Selecciona archivos y/o agrega un enlace</span>
        </div>
    </form>
</div>

<script>
(function () {
    const evidenceInput = document.getElementById('evidenceFiles');
    const fileList = document.getElementById('fileList');
    const uploadButton = document.getElementById('uploadButton');
    const uploadArea = document.getElementById('evidenceUploadArea');
    const evidenceForm = document.getElementById('evidenceUploadForm');
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'csv', 'svg'];

    if (!evidenceInput || !fileList || !uploadButton || !uploadArea) {
        return;
    }

    function renderSelectedFiles(files) {
        if (files.length > 0) {
            fileList.innerHTML = '';
            fileList.classList.remove('hidden');
            uploadButton.classList.remove('hidden');

            Array.from(files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                fileItem.innerHTML = `
                    <div class="flex items-center space-x-2 min-w-0">
                        <i class="fas fa-file text-gray-400"></i>
                        <span class="text-sm text-gray-700 truncate">${file.name}</span>
                    </div>
                    <span class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</span>
                `;
                fileList.appendChild(fileItem);
            });

            return;
        }

        fileList.innerHTML = '';
        fileList.classList.add('hidden');
        uploadButton.classList.add('hidden');
    }

    function getCurrentFiles() {
        return Array.from(evidenceInput.files || []);
    }

    function setFiles(files) {
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        evidenceInput.files = dataTransfer.files;
        renderSelectedFiles(evidenceInput.files);
    }

    function mergeFiles(newFiles) {
        const mergedFiles = [...getCurrentFiles()];

        Array.from(newFiles).forEach(file => {
            const fileKey = [file.name, file.size, file.type, file.lastModified].join('::');
            const alreadyExists = mergedFiles.some(existingFile => {
                const existingKey = [existingFile.name, existingFile.size, existingFile.type, existingFile.lastModified].join('::');
                return existingKey === fileKey;
            });

            if (!alreadyExists) {
                mergedFiles.push(file);
            }
        });

        setFiles(mergedFiles);
    }

    function isAllowedFile(file) {
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        return allowedExtensions.includes(extension);
    }

    function createClipboardFile(blob, index) {
        const extension = (blob.type || 'image/png').split('/')[1] || 'png';
        const timestamp = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
        const safeExtension = extension === 'svg+xml' ? 'svg' : extension;

        return new File([blob], `portapapeles-${timestamp}-${index}.${safeExtension}`, {
            type: blob.type || 'image/png',
            lastModified: Date.now(),
        });
    }

    evidenceInput.addEventListener('change', function () {
        renderSelectedFiles(this.files);
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function () {
            uploadArea.classList.add('border-blue-400', 'bg-blue-50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function () {
            uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
        }, false);
    });

    uploadArea.addEventListener('drop', function (e) {
        const files = Array.from(e.dataTransfer.files || []).filter(isAllowedFile);
        if (files.length > 0) {
            mergeFiles(files);
        }
    }, false);

    document.addEventListener('paste', function (e) {
        const activeElement = document.activeElement;
        const isTypingInField = activeElement && ['INPUT', 'TEXTAREA'].includes(activeElement.tagName);

        if (isTypingInField && activeElement.id === 'link_url') {
            return;
        }

        const clipboardFiles = Array.from(e.clipboardData?.items || [])
            .filter(item => item.kind === 'file')
            .map((item, index) => {
                const blob = item.getAsFile();
                return blob ? createClipboardFile(blob, index + 1) : null;
            })
            .filter(file => file && isAllowedFile(file));

        if (clipboardFiles.length === 0) {
            return;
        }

        e.preventDefault();
        mergeFiles(clipboardFiles);
    });

    if (evidenceForm) {
        evidenceForm.addEventListener('submit', function() {
            setTimeout(() => {
                this.reset();
                renderSelectedFiles([]);
            }, 1000);
        });
    }

    // Auto-replace URLs de prueba: reemplazar 'ovprdnwportwebapp01' por 'www'
    const linkUrlInput = document.getElementById('link_url');
    if (linkUrlInput) {
        linkUrlInput.addEventListener('input', function () {
            if (this.value.includes('ovprdnwportwebapp01')) {
                this.value = this.value.replace(/ovprdnwportwebapp01/g, 'www');
            }
        });
    }
})();
</script>
@endif
