@props(['serviceRequest'])

{{-- SOLUCIÓN DEFINITIVA - Obtener técnicos directamente --}}
@php
    use App\Models\User;
    $technicians = User::orderBy('name')->get();
@endphp

<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden mb-4 sm:mb-6 md:mb-8 w-full">
    <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 flex-1">
                <div class="bg-white/20 p-2 sm:p-3 rounded-xl sm:rounded-2xl backdrop-blur-sm flex items-center flex-col self-start sm:self-auto">
                    <i class="fas fa-ticket-alt text-3xl sm:text-4xl md:text-5xl"></i>
                    <span class="mt-1.5 sm:mt-2 inline-flex">
                        <x-service-requests.show.header.criticality-indicator :criticality="$serviceRequest->criticality_level" />
                    </span>
                </div>
                <div class="flex flex-col max-w-full sm:max-w-md lg:max-w-lg">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg sm:text-xl md:text-2xl font-bold">Solicitud #{{ $serviceRequest->ticket_number }}</h1>
                        <button type="button"
                            class="copy-ticket-btn inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 transition text-white border border-white/40 focus:outline-none focus:ring-2 focus:ring-white/60"
                            data-copy-ticket="{{ $serviceRequest->ticket_number }}"
                            data-original-label="Copiar número de ticket"
                            data-success-label="Número copiado"
                            data-default-icon="fa-copy"
                            data-success-icon="fa-check"
                            aria-label="Copiar número de ticket">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <span class="inline-flex">
                        <p class="text-blue-100 opacity-90 mt-0.5 sm:mt-1 text-xs sm:text-sm line-clamp-2">{{ $serviceRequest->title }}</p>
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto lg:max-w-md">
                <!-- Componente unificado de acciones -->
                <x-service-requests.show.header.workflow-actions :serviceRequest="$serviceRequest" :technicians="$technicians" :showLabels="true"
                    :compact="false" />

                <!-- SOLUCIÓN: Usar solo el componente status-indicator -->
                <x-service-requests.show.header.status-indicator :serviceRequest="$serviceRequest" />
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function(event) {
                const button = event.target.closest('[data-copy-ticket]');
                if (!button) {
                    return;
                }

                const ticket = button.getAttribute('data-copy-ticket');
                if (!ticket) {
                    return;
                }

                const originalLabel = button.getAttribute('data-original-label') || 'Copiar número de ticket';
                const successLabel = button.getAttribute('data-success-label') || 'Número copiado';

                const defaultIconClass = button.getAttribute('data-default-icon') || 'fa-copy';
                const successIconClass = button.getAttribute('data-success-icon') || 'fa-check';
                const iconElement = button.querySelector('i');

                const showFeedback = () => {
                    button.classList.add('bg-white/40');
                    button.setAttribute('aria-label', successLabel);
                    if (iconElement) {
                        iconElement.classList.remove(defaultIconClass);
                        iconElement.classList.add(successIconClass);
                    }

                    setTimeout(() => {
                        button.classList.remove('bg-white/40');
                        button.setAttribute('aria-label', originalLabel);
                        if (iconElement) {
                            iconElement.classList.remove(successIconClass);
                            iconElement.classList.add(defaultIconClass);
                        }
                    }, 1800);
                };

                const fallbackCopy = () => {
                    const textarea = document.createElement('textarea');
                    textarea.value = ticket;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();

                    try {
                        const successful = document.execCommand('copy');
                        if (successful) {
                            showFeedback();
                        } else {
                            alert('No se pudo copiar el número. Por favor copia manualmente.');
                        }
                    } catch (err) {
                        alert('No se pudo copiar el número. Por favor copia manualmente.');
                    }

                    document.body.removeChild(textarea);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(ticket)
                        .then(showFeedback)
                        .catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            });
        </script>
    @endpush
@endonce
