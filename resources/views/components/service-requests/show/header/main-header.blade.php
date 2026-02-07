@props(['serviceRequest'])

{{-- SOLUCIÓN DEFINITIVA - Obtener técnicos directamente --}}
@php
    use App\Models\User;
    $technicians = User::orderBy('name')->get();

    $headerGradient = match($serviceRequest->status) {
        'CERRADA', 'CANCELADA', 'RECHAZADA' => 'from-gray-50 to-gray-100',
        default => 'from-white to-slate-50',
    };
@endphp

<!-- Header Principal -->
<div class="bg-gradient-to-r {{ $headerGradient }} border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-4 sm:mb-6 w-full">
    <div class="px-4 sm:px-5 py-3 text-slate-900">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="flex items-start gap-3 flex-1">
                <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500">
                    <i class="fas fa-ticket-alt text-sm"></i>
                </div>
                <div class="flex flex-col max-w-full sm:max-w-lg">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base sm:text-lg font-semibold text-slate-900">Solicitud {{ $serviceRequest->ticket_number }}</h1>
                        <button type="button"
                            class="copy-ticket-btn inline-flex items-center justify-center w-6 h-6 rounded-md bg-white hover:bg-slate-100 transition text-slate-600 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300"
                            data-default-icon="fa-copy"
                            data-success-icon="fa-check"
                            aria-label="Copiar número de ticket"
                            onclick="copyTicketNumber('{{ $serviceRequest->ticket_number }}', this)">
                            <i class="fas fa-copy text-[11px]"></i>
                        </button>
                    </div>
                    <p class="text-slate-600 mt-1 text-[11px] sm:text-xs line-clamp-2">{{ $serviceRequest->title }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto lg:max-w-md">
                <!-- Componente unificado de acciones -->
                <x-service-requests.show.header.workflow-actions :serviceRequest="$serviceRequest" :technicians="$technicians" :showLabels="true"
                    :compact="false" />

                <!-- Estado mostrado en tarjeta de información -->
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            if (typeof window.copyTicketNumber !== 'function') {
                window.copyTicketNumber = function(ticketNumber, button) {
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

                    const notify = (success, options = {}) => {
                        if (typeof window.showCopyNotification === 'function') {
                            window.showCopyNotification(ticketNumber, success, options);
                        } else if (!success) {
                            alert(options.errorMessage || 'No se pudo copiar el número. Por favor copia manualmente.');
                        } else {
                            console.info(options.successMessage || `Ticket ${ticketNumber} copiado`);
                        }
                    };

                    const onCopySuccess = () => {
                        showButtonFeedback();
                        notify(true, {
                            successTitle: 'Número copiado',
                            successMessage: 'Número de ticket ' + ticketNumber + ' copiado al portapapeles',
                        });
                    };

                    const onCopyFailure = () => {
                        notify(false, {
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
                };
            }
        </script>
    @endpush
@endonce
