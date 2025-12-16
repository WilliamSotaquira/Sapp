@props(['evidence'])

<!-- Modal de vista previa -->
<div id="previewModal"
     class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="previewTitle"
     tabindex="-1">
    <div class="bg-white rounded-2xl max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900" id="previewTitle"></h3>
            <button type="button"
                    onclick="closePreview()"
                    class="text-gray-400 hover:text-gray-600 transition duration-150"
                    aria-label="Cerrar vista previa">
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
function openPreview(fileUrl, fileName, triggerEl) {
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

    if (window.openModal) {
        window.openModal('previewModal', triggerEl);
    } else {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden','false');
        document.body.style.overflow = 'hidden';
    }
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    if (window.closeModal) {
        window.closeModal('previewModal');
    } else {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden','true');
        document.body.style.overflow = 'auto';
    }
}

// El comportamiento de ESC/tab/backdrop lo gestiona el modal manager global en show.blade.php
</script>
