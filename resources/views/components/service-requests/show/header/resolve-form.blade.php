<!-- resources/views/components/service-requests/show/header/resolve-form.blade.php -->
@props(['serviceRequest'])

@php
$user = auth()->user();
$canResolve = $serviceRequest->status === 'EN_PROCESO' &&
              $user && $user->can('resolve', $serviceRequest);
@endphp

@if ($canResolve)
    <div class="lg:ml-4">
        <button type="button" id="resolveButton-{{ $serviceRequest->id }}"
            class="bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 ease-out transform hover:scale-105 focus:outline-none focus:ring-3 focus:ring-teal-400 focus:ring-opacity-50 shadow-lg flex items-center border-0 group"
            aria-haspopup="dialog"
            aria-controls="resolveModal-{{ $serviceRequest->id }}">
            <i class="fas fa-check-double mr-2 group-hover:scale-110 transition-transform duration-300" aria-hidden="true"></i>
            Resolver Solicitud
        </button>
    </div>

    <!-- Modal de resolución -->
    <div id="resolveModal-{{ $serviceRequest->id }}"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modal-title-{{ $serviceRequest->id }}"
         aria-describedby="modal-description-{{ $serviceRequest->id }}">

        <!-- Fondo -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300"
             id="modalBackdrop-{{ $serviceRequest->id }}"></div>

        <!-- Contenido del modal -->
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto my-8 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-hidden flex flex-col"
             id="modalContent-{{ $serviceRequest->id }}">
            <div class="p-6 flex-1 overflow-y-auto">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-gradient-to-br from-teal-100 to-emerald-100 p-3 rounded-xl mr-3">
                            <i class="fas fa-check-double text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 id="modal-title-{{ $serviceRequest->id }}" class="text-lg font-bold text-gray-800">
                                Resolver Solicitud
                            </h3>
                            <p id="modal-description-{{ $serviceRequest->id }}" class="text-xs text-gray-600 mt-1">
                                #{{ $serviceRequest->ticket_number }}
                            </p>
                        </div>
                    </div>
                    <button type="button" id="closeModal-{{ $serviceRequest->id }}"
                            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 text-lg p-2 rounded-lg transition-all duration-200"
                            aria-label="Cerrar modal" data-close>
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Mensajes de error -->
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg" role="alert">
                        <div class="flex items-center text-red-800">
                            <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i>
                            <span class="font-semibold text-sm">Errores encontrados:</span>
                        </div>
                        <ul class="mt-2 text-xs text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-center">
                                    <i class="fas fa-chevron-right text-xs mr-1 opacity-70"></i>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- FORMULARIO CORREGIDO: Asegurar que solo envíe a la ruta resolve -->
                <form id="resolveForm-{{ $serviceRequest->id }}"
                      action="{{ route('service-requests.resolve', $serviceRequest) }}"
                      method="POST"
                      data-route="{{ route('service-requests.resolve', $serviceRequest) }}">
                    @csrf
                    @method('PATCH') <!-- ✅ Solo PATCH para resolve -->

                    <div class="space-y-4">
                        <!-- Campo de tiempo -->
                        <div>
                            <label for="actual_resolution_time-{{ $serviceRequest->id }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-2 text-teal-600"></i>
                                Tiempo de Resolución (minutos) *
                            </label>
                            <input type="number" id="actual_resolution_time-{{ $serviceRequest->id }}" name="actual_resolution_time"
                                min="1" max="525600" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 text-gray-700 placeholder-gray-400"
                                placeholder="Ej: 120 (2 horas)"
                                value="{{ old('actual_resolution_time') }}"
                                title="Ingresa el tiempo en minutos. Mínimo: 1 minuto, Máximo: 525600 minutos (1 año)">
                            @error('actual_resolution_time')
                                <p class="text-red-600 text-xs mt-1 flex items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Campo de notas -->
                        <div>
                            <label for="resolution_notes-{{ $serviceRequest->id }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-teal-600"></i>
                                Notas de Resolución *
                            </label>
                            <textarea id="resolution_notes-{{ $serviceRequest->id }}" name="resolution_notes" rows="4" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 text-gray-700 placeholder-gray-400 resize-none"
                                placeholder="Describe detalladamente cómo se resolvió la solicitud (mínimo 10 caracteres)..."
                                title="Las notas deben tener al menos 10 caracteres describiendo la solución">{{ old('resolution_notes') }}</textarea>
                            @error('resolution_notes')
                                <p class="text-red-600 text-xs mt-1 flex items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Mínimo 10 caracteres
                            </p>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-200">
                        <button type="button" id="cancelButton-{{ $serviceRequest->id }}"
                            class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200 font-medium flex items-center text-sm" data-close>
                            <i class="fas fa-times mr-1" aria-hidden="true"></i>
                            Cancelar
                        </button>
                        <button type="submit" id="submitButton-{{ $serviceRequest->id }}"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition duration-200 font-medium flex items-center text-sm shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-check-double mr-1" aria-hidden="true"></i>
                            <span id="submitText-{{ $serviceRequest->id }}">Confirmar</span>
                            <div id="submitSpinner-{{ $serviceRequest->id }}" class="hidden ml-1">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        #modalContent-{{ $serviceRequest->id }} {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #modalBackdrop-{{ $serviceRequest->id }} {
            transition: opacity 0.3s ease-out;
        }
    </style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestId = {{ $serviceRequest->id }};
    const resolveButton = document.getElementById('resolveButton-' + requestId);
    const modal = document.getElementById('resolveModal-' + requestId);
    const closeButtons = modal ? modal.querySelectorAll('[data-close]') : [];
    const form = document.getElementById('resolveForm-' + requestId);

    // Abrir modal
    if (resolveButton) {
        resolveButton.addEventListener('click', function() {
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        });
    }

    // Cerrar modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    });

    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });

    // Validación en tiempo real
    if (form) {
        const timeInput = form.querySelector('input[name="actual_resolution_time"]');
        const notesInput = form.querySelector('textarea[name="resolution_notes"]');
        const submitButton = form.querySelector('button[type="submit"]');

        function validateForm() {
            const timeValid = timeInput && timeInput.value >= 1;
            const notesValid = notesInput && notesInput.value.trim().length >= 10;

            if (submitButton) {
                submitButton.disabled = !(timeValid && notesValid);
            }
        }

        if (timeInput) timeInput.addEventListener('input', validateForm);
        if (notesInput) notesInput.addEventListener('input', validateForm);

        // Validación inicial
        validateForm();
    }
});
</script>
@endif
