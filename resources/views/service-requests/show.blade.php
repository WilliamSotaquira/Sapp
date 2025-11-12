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

    <div class="space-y-6">
        <!-- Header Principal con botón de edición -->
        <div class="flex justify-between items-center flex-wrap gap-4">
            <x-service-requests.show.header.main-header :serviceRequest="$serviceRequest" :technicians="$technicians" />
        </div>

        <!-- Tarjetas de Información -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-service-requests.show.info-cards.service-info :serviceRequest="$serviceRequest" />
            <x-service-requests.show.info-cards.assignment-info :serviceRequest="$serviceRequest" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-service-requests.show.info-cards.timelines-info :serviceRequest="$serviceRequest" />
            <x-service-requests.show.info-cards.sla-info :serviceRequest="$serviceRequest" />
        </div>

        <!-- Paneles de Contenido -->
        <x-service-requests.show.content.description-panel :serviceRequest="$serviceRequest" />

        <!-- Panel de Rutas Web (solo si existen) -->
        @if ($serviceRequest->hasWebRoutes())
            <x-service-requests.show.content.web-routes-panel :serviceRequest="$serviceRequest" />
        @endif

        <x-service-requests.show.content.actions-panel :serviceRequest="$serviceRequest" />

        <!-- Sistema de Evidencias -->
        <x-service-requests.show.evidences.evidence-gallery :serviceRequest="$serviceRequest" />

        <!-- Historial y Timeline -->
        {{-- <x-service-requests.show.history.history-timeline :serviceRequest="$serviceRequest" /> --}}
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
    </script>
@endpush
