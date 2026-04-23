@props(['serviceRequest'])

@php
    // Permitir evidencias en EN_PROCESO, RESUELTA y CERRADA
    $canUploadEvidence = in_array($serviceRequest->status, ['EN_PROCESO', 'RESUELTA', 'CERRADA'], true);
@endphp

@if(!$canUploadEvidence)
<!-- Mensaje cuando la solicitud no permite agregar evidencias -->
<div class="border-2 border-gray-300 rounded-2xl p-6 text-center bg-gray-50">
    <div class="max-w-md mx-auto">
        <i class="fas fa-lock text-3xl text-gray-400 mb-4"></i>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">Evidencias Bloqueadas</h4>
        <p class="text-gray-500 text-sm mb-4">
            Solo puedes agregar evidencias cuando la solicitud está en estado <strong>EN PROCESO</strong>, <strong>RESUELTA</strong> o <strong>CERRADA</strong>.
        </p>
        <div class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-200 text-gray-600 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <span>Estado actual: <strong>{{ $serviceRequest->status }}</strong></span>
        </div>
    </div>
</div>
@else
<!-- Formulario normal de carga de evidencias -->
<div id="evidenceUploadArea" class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-gray-400 transition duration-150">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 text-left">
            <div class="space-y-4 md:pr-4 md:border-r md:border-gray-200">
                <div class="text-center md:text-left">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                    <h4 class="text-lg font-semibold text-gray-700 mb-1">Archivos</h4>
                    <p class="text-gray-500 text-sm">
                        Arrastra y suelta archivos aquí o haz clic para seleccionarlos
                    </p>
                </div>

                <!-- Mensajes de éxito/error -->
                @if(session('evidence_success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('evidence_success') }}
                    </div>
                @endif

                @if(session('evidence_error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('evidence_error') }}
                    </div>
                @endif

                <form action="{{ route('service-requests.evidences.store', $serviceRequest) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-4"
                      id="evidenceUploadForm">
                    @csrf
                    <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">

                    <div class="w-full">
                        <label for="evidenceFiles" class="cursor-pointer block w-full">
                            <span class="w-56 h-10 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150 inline-flex items-center justify-center font-semibold">
                                <i class="fas fa-plus mr-2"></i>Seleccionar Archivos
                            </span>
                            <input type="file"
                                   name="files[]"
                                   id="evidenceFiles"
                                   multiple
                                   class="hidden"
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.csv,.svg">
                        </label>
                    </div>

                    <div id="fileList" class="text-left space-y-2 hidden"></div>

                    <div class="text-xs text-gray-400">
                        Formatos permitidos: JPG, PNG, GIF, PDF, DOC, XLS, TXT, ZIP, CSV, SVG<br>
                        También puedes pegar imágenes con `Ctrl + V` o `Cmd + V`<br>
                        Tamaño máximo por archivo: 10MB
                    </div>

                    <button type="submit"
                            id="uploadButton"
                            class="w-56 h-10 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-150 font-semibold hidden">
                        <i class="fas fa-upload mr-2"></i>Subir Archivos
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                <div class="text-center md:text-left">
                    <i class="fas fa-link text-3xl text-gray-400 mb-3"></i>
                    <h4 class="text-lg font-semibold text-gray-700 mb-1">Enlace</h4>
                    <p class="text-gray-500 text-sm">
                        Guarda una URL como evidencia de la solicitud
                    </p>
                </div>

                <form action="{{ route('service-requests.evidences.store', $serviceRequest) }}"
                      method="POST"
                      class="space-y-3 text-left">
                    @csrf
                    <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">
                    <div>
                        <label for="link_url" class="block text-sm font-medium text-gray-700 mb-1">URL *</label>
                        <input type="url"
                               name="link_url"
                               id="link_url"
                               required
                               placeholder="https://..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit"
                            class="w-56 h-10 bg-slate-700 text-white px-4 py-2 rounded-lg hover:bg-slate-800 transition duration-150 font-semibold">
                        <i class="fas fa-link mr-2"></i>Guardar Enlace
                    </button>
                </form>
            </div>
        </div>
    </div>
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
})();
</script>
@endif
