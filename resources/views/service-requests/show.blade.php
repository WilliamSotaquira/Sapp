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

    @php
        if (!isset($previousRequestNav)) {
            $previousRequestNav = \App\Models\ServiceRequest::where('id', '<', $serviceRequest->id)
                ->orderBy('id', 'desc')
                ->first();
        }
        if (!isset($nextRequestNav)) {
            $nextRequestNav = \App\Models\ServiceRequest::where('id', '>', $serviceRequest->id)->orderBy('id')->first();
        }
    @endphp

    <div class="space-y-4 sm:space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs sm:text-sm text-slate-600 rounded-2xl px-4 py-3 bg-gradient-to-r from-sky-50 via-white to-indigo-50 border border-blue-100/60 shadow"
            id="requestNavigation"
            data-prev-url="{{ $previousRequestNav ? route('service-requests.show', $previousRequestNav) : '' }}"
            data-next-url="{{ $nextRequestNav ? route('service-requests.show', $nextRequestNav) : '' }}" role="navigation"
            aria-label="Navegación entre solicitudes">
            <div class="flex items-center flex-1">
                @if ($previousRequestNav)
                    <a href="{{ route('service-requests.show', $previousRequestNav) }}"
                        class="nav-direction nav-direction--prev focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-200"
                        title="Ir a la solicitud #{{ $previousRequestNav->ticket_number }}"
                        aria-label="Ver solicitud anterior #{{ $previousRequestNav->ticket_number }}">
                        <span class="nav-direction__icon" aria-hidden="true">
                            <i class="fas fa-arrow-left text-[10px]"></i>
                        </span>
                        <span class="nav-direction__content">
                            <span class="nav-direction__eyebrow">Anterior</span>
                            <span class="nav-direction__ticket">#{{ $previousRequestNav->ticket_number }}</span>
                        </span>
                    </a>
                @else
                    <span class="nav-direction nav-direction--disabled nav-direction--prev" aria-disabled="true">
                        <span class="nav-direction__icon" aria-hidden="true">
                            <i class="fas fa-arrow-left text-[10px]"></i>
                        </span>
                        <span class="nav-direction__content">
                            <span class="nav-direction__eyebrow">Anterior</span>
                            <span class="nav-direction__ticket">No disponible</span>
                        </span>
                    </span>
                @endif
            </div>
            <div class="hidden sm:flex justify-center w-full sm:w-auto">
                <div class="nav-pill-current" aria-live="polite">
                    <span class="nav-pill-current__eyebrow">Solicitud actual</span>
                    <span class="nav-pill-current__ticket">#{{ $serviceRequest->ticket_number }}</span>
                </div>
            </div>
            <div class="flex items-center flex-1 justify-end">
                @if ($nextRequestNav)
                    <a href="{{ route('service-requests.show', $nextRequestNav) }}"
                        class="nav-direction nav-direction--reverse focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-200"
                        title="Ir a la solicitud #{{ $nextRequestNav->ticket_number }}"
                        aria-label="Ver siguiente solicitud #{{ $nextRequestNav->ticket_number }}">
                        <span class="nav-direction__icon" aria-hidden="true">
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </span>
                        <span class="nav-direction__content">
                            <span class="nav-direction__eyebrow">Siguiente</span>
                            <span class="nav-direction__ticket">#{{ $nextRequestNav->ticket_number }}</span>
                        </span>
                    </a>
                @else
                    <span class="nav-direction nav-direction--disabled nav-direction--reverse" aria-disabled="true">
                        <span class="nav-direction__icon" aria-hidden="true">
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </span>
                        <span class="nav-direction__content">
                            <span class="nav-direction__eyebrow">Siguiente</span>
                            <span class="nav-direction__ticket">No disponible</span>
                        </span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Header Principal con botón de edición -->
        <x-service-requests.show.header.main-header :serviceRequest="$serviceRequest" />

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
        .nav-direction {
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            border-radius: 9999px;
            padding: 0.65rem 1rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 10px 25px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .nav-direction:hover,
        .nav-direction:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 15px 30px rgba(59, 130, 246, 0.2);
        }

        .nav-direction__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.25));
            color: #1d4ed8;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.65);
        }

        .nav-direction__content {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.2;
        }

        .nav-direction__eyebrow {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .nav-direction__ticket {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
        }

        .nav-direction--reverse {
            flex-direction: row-reverse;
        }

        .nav-direction--reverse .nav-direction__content {
            text-align: right;
        }

        .nav-direction--disabled {
            color: #94a3b8;
            border-color: rgba(148, 163, 184, 0.35);
            background: rgba(248, 250, 252, 0.9);
            box-shadow: inset 0 0 0 rgba(255, 255, 255, 0);
        }

        .nav-pill-current {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            min-width: 180px;
            padding: 0.6rem 1rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 6px 12px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .nav-pill-current__eyebrow {
            font-size: 0.6rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .nav-pill-current__ticket {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        @media (max-width: 640px) {
            #requestNavigation {
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .nav-direction {
                padding: 0.5rem 0.75rem;
                border-radius: 1.25rem;
                gap: 0.5rem;
                min-height: 3.25rem;
            }

            .nav-direction__icon {
                width: 2rem;
                height: 2rem;
            }

            .nav-direction__eyebrow {
                font-size: 0.6rem;
            }

            .nav-direction__ticket {
                font-size: 0.85rem;
            }

            .nav-pill-current {
                width: 100%;
                min-width: 0;
                padding: 0.55rem 0.85rem;
            }

            .nav-pill-current__ticket {
                font-size: 0.9rem;
            }
        }

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

            setupNavigationInteractions();
        });

        function setupNavigationInteractions() {
            const navContainer = document.getElementById('requestNavigation');
            if (!navContainer) {
                return;
            }

            const prevUrl = navContainer.dataset.prevUrl;
            const nextUrl = navContainer.dataset.nextUrl;

            function goTo(url) {
                if (url) {
                    window.location.href = url;
                }
            }

            document.addEventListener('keydown', function(e) {
                if (e.target && ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
                    return;
                }
                if (e.key === 'ArrowLeft') {
                    goTo(prevUrl);
                }
                if (e.key === 'ArrowRight') {
                    goTo(nextUrl);
                }
            });

            let touchStartX = null;
            let touchStartY = null;

            document.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                }
            }, {
                passive: true
            });

            document.addEventListener('touchend', function(e) {
                if (touchStartX === null || touchStartY === null) {
                    return;
                }

                const touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const diffX = touchEndX - touchStartX;
                const diffY = Math.abs(touchEndY - touchStartY);

                if (Math.abs(diffX) > 60 && diffY < 40) {
                    if (diffX > 0) {
                        goTo(prevUrl);
                    } else {
                        goTo(nextUrl);
                    }
                }

                touchStartX = null;
                touchStartY = null;
            }, {
                passive: true
            });
        }

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

        function showCopyNotification(ticketNumber, success, options = {}) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 ${
                success ? 'bg-green-500' : 'bg-red-500'
            } text-white`;

            const defaultSuccessTitle = '¡Link copiado!';
            const defaultErrorTitle = 'Error al copiar';
            const defaultSuccessMessage = 'El link público del ticket ' + ticketNumber + ' está en tu portapapeles';
            const defaultErrorMessage = 'Por favor, copia el link manualmente';

            const successTitle = options.successTitle || defaultSuccessTitle;
            const errorTitle = options.errorTitle || defaultErrorTitle;
            const successMessage = options.successMessage || defaultSuccessMessage;
            const errorMessage = options.errorMessage || defaultErrorMessage;

            const titleText = success ? successTitle : errorTitle;
            const bodyText = success ? successMessage : errorMessage;

            notification.innerHTML = `
                <i class="fas ${success ? 'fa-check-circle' : 'fa-exclamation-circle'} text-xl"></i>
                <div>
                    <div class="font-semibold">${titleText}</div>
                    <div class="text-sm opacity-90">${bodyText}</div>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        function copyTicketNumber(ticketNumber, button) {
            if (!ticketNumber) {
                return;
            }

            const iconElement = button ? button.querySelector('i') : null;
            const defaultIconClass = button ? (button.getAttribute('data-default-icon') || 'fa-copy') : 'fa-copy';
            const successIconClass = button ? (button.getAttribute('data-success-icon') || 'fa-check') : 'fa-check';

            const showButtonFeedback = () => {
                if (!button) {
                    return;
                }

                button.classList.add('bg-white/40');
                button.setAttribute('aria-label', 'Número copiado');
                if (iconElement) {
                    iconElement.classList.remove(defaultIconClass);
                    iconElement.classList.add(successIconClass);
                }

                setTimeout(() => {
                    button.classList.remove('bg-white/40');
                    button.setAttribute('aria-label', 'Copiar número de ticket');
                    if (iconElement) {
                        iconElement.classList.remove(successIconClass);
                        iconElement.classList.add(defaultIconClass);
                    }
                }, 1500);
            };

            const onCopySuccess = () => {
                showButtonFeedback();
                showCopyNotification(ticketNumber, true, {
                    successTitle: 'Número copiado',
                    successMessage: 'Número de ticket ' + ticketNumber + ' copiado al portapapeles',
                });
            };

            const onCopyFailure = () => {
                showCopyNotification(ticketNumber, false, {
                    errorTitle: 'No se pudo copiar',
                    errorMessage: 'No se pudo copiar el número de ticket. Por favor, cópialo manualmente.',
                });
            };

            const fallbackCopy = () => {
                const textArea = document.createElement('textarea');
                textArea.value = ticketNumber;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        onCopySuccess();
                    } else {
                        onCopyFailure();
                    }
                } catch (err) {
                    onCopyFailure();
                }

                document.body.removeChild(textArea);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(ticketNumber)
                    .then(onCopySuccess)
                    .catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
        }
    </script>
@endpush
