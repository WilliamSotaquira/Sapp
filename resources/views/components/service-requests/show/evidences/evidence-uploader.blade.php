@props(['serviceRequest'])

<div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-gray-400 transition duration-150">
    <div class="max-w-md mx-auto">
        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-4"></i>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">Agregar Evidencias</h4>
        <p class="text-gray-500 text-sm mb-4">
            Arrastra y suelta archivos aquí o haz clic para seleccionarlos
        </p>

        <!-- ✅ CORREGIDO: Usar la ruta correcta service-requests.evidences.store -->
        <form action="{{ route('service-requests.evidences.store', $serviceRequest) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4"
              id="evidenceUploadForm">
            @csrf
            <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">

            <div class="flex items-center justify-center">
                <label for="evidenceFiles" class="cursor-pointer">
                    <span class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-150 inline-flex items-center font-semibold">
                        <i class="fas fa-plus mr-2"></i>Seleccionar Archivos
                    </span>
                    <input type="file"
                           name="files[]"
                           id="evidenceFiles"
                           multiple
                           class="hidden"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
                           onchange="handleFileSelection(this)">
                </label>
            </div>

            <div id="fileList" class="text-left space-y-2 hidden"></div>

            <div class="text-xs text-gray-400">
                Formatos permitidos: JPG, PNG, GIF, PDF, DOC, XLS, TXT, ZIP<br>
                Tamaño máximo por archivo: 10MB
            </div>

            <button type="submit"
                    id="uploadButton"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-150 font-semibold hidden">
                <i class="fas fa-upload mr-2"></i>Subir Archivos
            </button>
        </form>
    </div>
</div>

<script>
function handleFileSelection(input) {
    const fileList = document.getElementById('fileList');
    const uploadButton = document.getElementById('uploadButton');

    if(input.files.length > 0) {
        fileList.innerHTML = '';
        fileList.classList.remove('hidden');
        uploadButton.classList.remove('hidden');

        Array.from(input.files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
            fileItem.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="fas fa-file text-gray-400"></i>
                    <span class="text-sm text-gray-700">${file.name}</span>
                </div>
                <span class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</span>
            `;
            fileList.appendChild(fileItem);
        });
    } else {
        fileList.classList.add('hidden');
        uploadButton.classList.add('hidden');
    }
}

// Drag and drop functionality
const uploadArea = document.querySelector('.border-dashed');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, unhighlight, false);
});

function highlight() {
    uploadArea.classList.add('border-blue-400', 'bg-blue-50');
}

function unhighlight() {
    uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
}

uploadArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    document.getElementById('evidenceFiles').files = files;
    handleFileSelection(document.getElementById('evidenceFiles'));
}
</script>
