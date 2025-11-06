{{-- resources/views/service-request-evidences/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Evidencia: ' . $evidence->title)

@section('content')
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="p-6">
        <!-- Header de la Evidencia -->
        <div class="flex justify-between items-start mb-6 pb-4 border-b border-gray-200">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $evidence->title }}</h2>
                <p class="text-gray-600 text-sm mt-1">
                    Solicitud #{{ $serviceRequest->ticket_number }} -
                    Creado el {{ $evidence->created_at->format('d/m/Y \\a \\l\\a\\s H:i') }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if($evidence->evidence_type == 'PASO_A_PASO') bg-blue-100 text-blue-800
                    @elseif($evidence->evidence_type == 'ARCHIVO') bg-green-100 text-green-800
                    @elseif($evidence->evidence_type == 'COMENTARIO') bg-purple-100 text-purple-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $evidence->evidence_type }}
                </span>
                @if($evidence->step_number)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Paso {{ $evidence->step_number }}
                </span>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <!-- Información General y Archivo -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Información General -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de evidencia</label>
                            <p class="text-gray-900">{{ $evidence->evidence_type }}</p>
                        </div>
                        @if($evidence->step_number)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de paso</label>
                            <p class="text-gray-900">{{ $evidence->step_number }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de creación</label>
                            <p class="text-gray-900">{{ $evidence->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Creado por</label>
                            <p class="text-gray-900">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Archivo Adjunto -->
                @if($evidence->hasFile())
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Archivo Adjunto</h3>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="flex-shrink-0">
                                <i class="fas {{ $evidence->file_icon }} text-blue-600 text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $evidence->file_original_name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $evidence->file_mime_type }} • {{ $evidence->getFormattedFileSize() }}
                                </p>
                            </div>
                        </div>

                        <!-- Visualización de Imágenes -->
                        @if($evidence->isImage())
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Vista Previa:</h4>
                            <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                                <img
                                    src="{{ Storage::url($evidence->file_path) }}"
                                    alt="{{ $evidence->file_original_name }}"
                                    class="w-full h-auto max-h-96 object-contain cursor-pointer preview-image"
                                    data-src="{{ Storage::url($evidence->file_path) }}"
                                >
                            </div>
                            <p class="text-xs text-gray-500 mt-1 text-center">
                                Haz clic en la imagen para ampliar
                            </p>
                        </div>
                        @endif

                        <!-- Visualización de PDF -->
                        @if($evidence->isPdf())
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Vista Previa PDF:</h4>
                            <div class="border border-gray-300 rounded-lg bg-white p-4">
                                <div class="text-center">
                                    <i class="fas fa-file-pdf text-red-500 text-4xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Documento PDF</p>
                                    <p class="text-xs text-gray-500">{{ $evidence->file_original_name }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="flex space-x-2">
                            <a href="{{ route('service-requests.evidences.download', [$serviceRequest, $evidence]) }}"
                                class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-download mr-2"></i>
                                Descargar
                            </a>

                            @if($evidence->isImage() || $evidence->isPdf())
                            <button type="button"
                                class="view-file-btn inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                data-src="{{ Storage::url($evidence->file_path) }}">
                                <i class="fas fa-expand mr-2"></i>
                                Ver
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Descripción -->
            @if($evidence->description)
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Descripción</h3>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-line">{{ $evidence->description }}</p>
                </div>
            </div>
            @endif

            <!-- Datos Adicionales -->
            @if(!empty($evidence->evidence_data) && count(array_filter($evidence->evidence_data)))
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Datos Adicionales</h3>
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="divide-y divide-gray-200">
                                @foreach($evidence->evidence_data as $key => $value)
                                @if(!empty($value))
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 capitalize whitespace-nowrap">
                                        {{ str_replace('_', ' ', $key) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $value }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Acciones -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('service-requests.show', $serviceRequest) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a la solicitud
                </a>

                @if($evidence->canBeDeletedSimple())
                <form action="{{ route('service-requests.evidences.destroy', [$serviceRequest, $evidence]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('¿Estás seguro de eliminar esta evidencia?')">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar Evidencia
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Scripts incluidos directamente en la página -->
<script>
// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    let currentScale = 1;
    let modal = null;
    let modalImage = null;
    let lastFocusedElement = null;

    // Función para crear el modal dinámicamente
    function createModal() {
        if (document.getElementById('imageModal')) {
            return; // El modal ya existe
        }

        const modalHTML = `
            <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="relative max-w-4xl max-h-full mx-4">
                    <!-- Botón cerrar -->
                    <button type="button"
                        id="closeModalBtn"
                        class="absolute -top-12 right-0 text-white hover:text-gray-300 focus:outline-none z-10"
                        aria-label="Cerrar modal">
                        <i class="fas fa-times text-2xl"></i>
                    </button>

                    <!-- Contenido del modal -->
                    <div class="bg-white rounded-lg overflow-hidden max-h-screen">
                        <img id="modalImage" src="" alt="" class="w-full h-auto max-h-screen object-contain">
                    </div>

                    <!-- Botones de navegación -->
                    <div class="flex justify-center items-center mt-4 space-x-4">
                        <button type="button"
                            id="zoomOutBtn"
                            class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Alejar">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button type="button"
                            id="resetZoomBtn"
                            class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Restablecer zoom">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button"
                            id="zoomInBtn"
                            class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Acercar">
                            <i class="fas fa-search-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Referencias a los elementos del modal
        modal = document.getElementById('imageModal');
        modalImage = document.getElementById('modalImage');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const resetZoomBtn = document.getElementById('resetZoomBtn');

        // Event Listeners del modal
        closeModalBtn.addEventListener('click', closeModal);
        zoomInBtn.addEventListener('click', zoomIn);
        zoomOutBtn.addEventListener('click', zoomOut);
        resetZoomBtn.addEventListener('click', resetZoom);

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Cerrar modal haciendo clic fuera de la imagen
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Prevenir que el clic en la imagen cierre el modal
        modalImage.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }

    // Función para abrir el modal
    function openModal(imageSrc) {
        // Crear el modal si no existe
        createModal();

        // Guardar el último elemento enfocado
        lastFocusedElement = document.activeElement;

        // Configurar la imagen
        modalImage.src = imageSrc;
        modalImage.alt = 'Vista ampliada de ' + imageSrc.split('/').pop();

        // Mostrar el modal
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        // Resetear zoom
        currentScale = 1;
        modalImage.style.transform = `scale(${currentScale})`;

        // Bloquear scroll del body
        document.body.style.overflow = 'hidden';

        // Enfocar el botón de cerrar para accesibilidad
        setTimeout(() => {
            document.getElementById('closeModalBtn').focus();
        }, 100);
    }

    // Función para cerrar el modal
    function closeModal() {
        if (!modal) return;

        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        // Restaurar scroll del body
        document.body.style.overflow = 'auto';

        // Restaurar el foco al último elemento
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }

        // Limpiar la imagen para liberar memoria
        modalImage.src = '';
        modalImage.alt = '';
    }

    // Función para hacer zoom in
    function zoomIn() {
        currentScale += 0.25;
        modalImage.style.transform = `scale(${currentScale})`;
    }

    // Función para hacer zoom out
    function zoomOut() {
        if (currentScale > 0.5) {
            currentScale -= 0.25;
            modalImage.style.transform = `scale(${currentScale})`;
        }
    }

    // Función para resetear el zoom
    function resetZoom() {
        currentScale = 1;
        modalImage.style.transform = `scale(${currentScale})`;
    }

    // Agregar event listeners a las imágenes y botones
    document.querySelectorAll('.preview-image, .view-file-btn').forEach(element => {
        element.addEventListener('click', function() {
            const imageSrc = this.getAttribute('data-src') || this.getAttribute('src');
            openModal(imageSrc);
        });

        // Soporte para tecla Enter en botones
        element.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                const imageSrc = this.getAttribute('data-src') || this.getAttribute('src');
                openModal(imageSrc);
            }
        });
    });
});
</script>

<style>
#imageModal {
    backdrop-filter: blur(5px);
}

#modalImage {
    transition: transform 0.3s ease;
    transform-origin: center center;
}

.hidden {
    display: none !important;
}

/* Mejorar accesibilidad del foco */
button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Prevenir scroll cuando el modal está abierto */
body.modal-open {
    overflow: hidden;
}
</style>
@endsection
