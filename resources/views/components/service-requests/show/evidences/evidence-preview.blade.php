@props(['evidence'])

<!-- Modal de vista previa -->
<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900" id="previewTitle"></h3>
            <button type="button"
                    onclick="closePreview()"
                    class="text-gray-400 hover:text-gray-600 transition duration-150">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 max-h-[70vh] overflow-auto">
            <img id="previewImage" src="" alt="" class="mx-auto max-w-full max-h-full">
        </div>
        <div class="flex items-center justify-between p-4 border-t bg-gray-50">
            <div class="text-sm text-gray-600" id="previewInfo"></div>
            <a href="#" id="previewDownload"
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                <i class="fas fa-download mr-2"></i>Descargar
            </a>
        </div>
    </div>
</div>

<script>
function openPreview(fileUrl, fileName) {
    const modal = document.getElementById('previewModal');
    const image = document.getElementById('previewImage');
    const title = document.getElementById('previewTitle');
    const info = document.getElementById('previewInfo');
    const downloadLink = document.getElementById('previewDownload');

    // ✅ USAR fileUrl directamente del modelo
    image.src = fileUrl;
    title.textContent = fileName;
    info.textContent = `Vista previa de ${fileName}`;
    downloadLink.href = fileUrl;
    downloadLink.download = fileName;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        closePreview();
    }
});

// Cerrar modal haciendo click fuera
document.getElementById('previewModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closePreview();
    }
});
</script>
