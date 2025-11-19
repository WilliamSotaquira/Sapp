@extends('layouts.app')

@section('title', "Solicitud {$serviceRequest->ticket_number}")

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li class="inline-flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-700">Solicitudes de
                    Servicio</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitud #{{ $serviceRequest->ticket_number }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')

    <div class="space-y-4 sm:space-y-6">
        <!-- Header Principal con botón de edición -->
        <div class="flex justify-between items-center flex-wrap gap-3 sm:gap-4">
            <x-service-requests.show.header.main-header :serviceRequest="$serviceRequest" :technicians="$technicians" />
        </div>

        <!-- Descripción del Problema (Lo más importante primero) -->
        <x-service-requests.show.content.description-panel :serviceRequest="$serviceRequest" />

        <!-- Información Clave en 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <x-service-requests.show.info-cards.service-info :serviceRequest="$serviceRequest" />
            <x-service-requests.show.info-cards.assignment-info :serviceRequest="$serviceRequest" />
        </div>

        <!-- Tiempos y SLA -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <x-service-requests.show.info-cards.timelines-info :serviceRequest="$serviceRequest" />
            <x-service-requests.show.info-cards.sla-info :serviceRequest="$serviceRequest" />
        </div>

        <!-- Sistema de Evidencias -->
        <x-service-requests.show.evidences.evidence-gallery :serviceRequest="$serviceRequest" />

        <!-- Tareas Asociadas -->
        <x-service-requests.show.content.tasks-panel :serviceRequest="$serviceRequest" />

        <!-- Panel de Rutas Web (solo si existen) -->
        @if ($serviceRequest->hasWebRoutes())
            <x-service-requests.show.content.web-routes-panel :serviceRequest="$serviceRequest" />
        @endif

        <!-- Notas y Comentarios del Sistema (Información complementaria) -->
        <x-service-requests.show.evidences.system-notes :serviceRequest="$serviceRequest" />

        <!-- Historial y Timeline (Al final, información histórica) -->
        {{-- <x-service-requests.show.history.history-timeline :serviceRequest="$serviceRequest" /> --}}


        <!-- Acciones Disponibles (Segundo en importancia) -->
        <x-service-requests.show.content.actions-panel :serviceRequest="$serviceRequest" />
    </div>

    <!-- Modal de vista previa para evidencias -->
    <x-service-requests.show.evidences.evidence-preview />
@endsection

@section('styles')
    <style>
        /* Timeline Styles */
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 1.5rem;
            width: 1rem;
            height: 2px;
            background: #e5e7eb;
        }

        .group:hover .timeline-dot {
            transform: scale(1.1);
            transition: transform 0.2s ease-in-out;
        }

        /* Smooth transitions for timeline */
        .timeline-enter {
            opacity: 0;
            transform: translateX(-20px);
        }

        .timeline-enter-active {
            opacity: 1;
            transform: translateX(0);
            transition: opacity 0.3s, transform 0.3s;
        }

        /* Estilos para evidencias */
        .evidence-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
        }

        .evidence-preview:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }

        @media (max-width: 768px) {
            .timeline-item::before {
                left: -1.5rem;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Service Request Show page loaded - Evidences system ready');

            // ✅ MEJOR OPCIÓN: Separar completamente el JS del Blade
            const evidenceCount = {
                {
                    $serviceRequest - > evidences ? $serviceRequest - > evidences - > count() : 0
                }
            };
            if (evidenceCount > 0) {
                console.log('Evidencias cargadas:', evidenceCount);
            } else {
                console.log('No hay evidencias para esta solicitud');
            }
            f

            // Script para manejar errores de carga de imágenes
            document.addEventListener('error', function(e) {
                if (e.target.tagName === 'IMG' && e.target.classList.contains('evidence-image')) {
                    console.warn('Error cargando imagen de evidencia:', e.target.src);
                    e.target.style.display = 'none';
                    // Mostrar placeholder de error
                    const parent = e.target.parentElement;
                    if (parent) {
                        parent.innerHTML = `
                        <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 text-center">Error cargando imagen</p>
                    `;
                    }
                }
            }, true);
        });

        // ✅ FUNCIONES GLOBALES para el modal de vista previa
        function openPreview(fileUrl, fileName) {
            const modal = document.getElementById('previewModal');
            const image = document.getElementById('previewImage');
            const title = document.getElementById('previewTitle');
            const info = document.getElementById('previewInfo');
            const downloadLink = document.getElementById('previewDownload');

            // Mostrar loader mientras carga
            image.style.display = 'none';
            modal.classList.remove('hidden');

            const tempImage = new Image();
            tempImage.onload = function() {
                image.src = fileUrl;
                image.style.display = 'block';
                title.textContent = fileName;
                info.textContent = `Vista previa de ${fileName}`;
                downloadLink.href = fileUrl;
                downloadLink.download = fileName;
            };

            tempImage.onerror = function() {
                image.style.display = 'none';
                title.textContent = 'Error';
                info.textContent = 'No se pudo cargar la imagen';
                downloadLink.style.display = 'none';
            };

            tempImage.src = fileUrl;
            document.body.style.overflow = 'hidden';
        }

        function closePreview() {
            const modal = document.getElementById('previewModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });

        // Cerrar modal haciendo click fuera
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('previewModal');
            if (e.target === modal) {
                closePreview();
            }
        });

        // Función para copiar link público al portapapeles
        function copyPublicLink(url, ticketNumber) {
            // Intentar usar la API moderna del portapapeles
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function() {
                    showCopyNotification(ticketNumber, true);
                }).catch(function(err) {
                    // Fallback si falla
                    copyToClipboardFallback(url, ticketNumber);
                });
            } else {
                // Fallback para navegadores antiguos
                copyToClipboardFallback(url, ticketNumber);
            }
        }

        // Método alternativo para copiar
        function copyToClipboardFallback(text, ticketNumber) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                showCopyNotification(ticketNumber, successful);
            } catch (err) {
                showCopyNotification(ticketNumber, false);
            }

            document.body.removeChild(textArea);
        }

        // Mostrar notificación de copia
        function showCopyNotification(ticketNumber, success) {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 ${
                success ? 'bg-green-500' : 'bg-red-500'
            } text-white`;

            notification.innerHTML = `
                <i class="fas ${success ? 'fa-check-circle' : 'fa-exclamation-circle'} text-xl"></i>
                <div>
                    <div class="font-semibold">${success ? '¡Link copiado!' : 'Error al copiar'}</div>
                    <div class="text-sm opacity-90">${
                        success
                            ? 'El link público del ticket ' + ticketNumber + ' está en tu portapapeles'
                            : 'Por favor, copia el link manualmente'
                    }</div>
                </div>
            `;

            document.body.appendChild(notification);

            // Animar entrada
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Remover después de 3 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
@endpush
