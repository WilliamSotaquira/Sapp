@props(['serviceRequest'])

<div class="pause-modal-container">
    <!-- Botón mejorado para abrir modal -->
    <button type="button" onclick="openPauseModal()"
            class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ $slot ?? 'Pausar Trabajo' }}
    </button>

    <!-- Modal mejorado con Tailwind CSS -->
    <div id="pauseRequestModal" class="fixed inset-0 z-50 hidden overflow-y-auto transition-opacity duration-300">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Fondo oscuro con animación -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-70 backdrop-blur-sm" onclick="closePauseModal()"></div>

            <!-- Modal centrado con animación -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="relative inline-block w-full max-w-md p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-xl sm:my-16 scale-95 opacity-0"
                 id="modalContent">
                <!-- Header con gradiente -->
                <div class="flex items-center justify-between p-5 bg-gradient-to-r from-amber-500 to-amber-600 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-white bg-opacity-20 rounded-full">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-white">Pausar Solicitud</h3>
                    </div>
                    <button type="button" class="text-white hover:text-amber-100 focus:outline-none transition-colors duration-200"
                            onclick="closePauseModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form action="{{ route('service-requests.pause', $serviceRequest) }}" method="POST"
                      id="pauseRequestForm">
                    @csrf
                    <div class="p-6">
                        <!-- Alerta mejorada -->
                        <div class="flex items-start p-4 mb-5 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-amber-800">Importante</h4>
                                <p class="mt-1 text-sm text-amber-700">
                                    Debes proporcionar una razón detallada para pausar la solicitud.
                                </p>
                            </div>
                        </div>

                        <!-- Textarea mejorada -->
                        <div class="mb-1">
                            <label for="pause_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Razón de la pausa <span class="text-red-500">*</span>
                            </label>
                            <textarea id="pause_reason" name="pause_reason" rows="4"
                                      placeholder="Describe detalladamente la razón por la cual se pausa esta solicitud..." required minlength="10"
                                      maxlength="500"
                                      class="w-full px-4 py-3 text-gray-700 placeholder-gray-400 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 resize-none">{{ old('pause_reason') }}</textarea>
                            <div class="flex justify-between mt-1">
                                <p class="text-xs text-gray-500">
                                    Mínimo 10 caracteres. Este registro quedará en el historial de la solicitud.
                                </p>
                                <span id="charCount" class="text-xs text-gray-500">0/500</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer mejorado -->
                    <div class="flex justify-end gap-3 p-5 bg-gray-50 rounded-b-xl">
                        <button type="button"
                                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-200"
                                onclick="closePauseModal()">
                            Cancelar
                        </button>
                        <button type="submit" id="submitButton"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-amber-500 border border-transparent rounded-lg shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Confirmar Pausa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openPauseModal() {
        console.log('Abriendo modal de pausa...');
        const modal = document.getElementById('pauseRequestModal');
        const modalContent = document.getElementById('modalContent');

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Animación de entrada
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);

        // Limpiar textarea
        const textarea = document.getElementById('pause_reason');
        if (textarea) {
            textarea.value = '';
            textarea.classList.remove('border-red-500', 'border-green-500');
            updateCharCount();
        }

        // Deshabilitar botón de envío inicialmente
        const submitButton = document.getElementById('submitButton');
        if (submitButton) {
            submitButton.disabled = true;
        }
    }

    function closePauseModal() {
        const modal = document.getElementById('pauseRequestModal');
        const modalContent = document.getElementById('modalContent');

        // Animación de salida
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 200);
    }

    // Actualizar contador de caracteres
    function updateCharCount() {
        const textarea = document.getElementById('pause_reason');
        const charCount = document.getElementById('charCount');
        if (textarea && charCount) {
            const length = textarea.value.length;
            charCount.textContent = `${length}/500`;

            if (length > 450) {
                charCount.classList.add('text-red-500');
                charCount.classList.remove('text-gray-500', 'text-amber-500');
            } else if (length >= 10) {
                charCount.classList.add('text-amber-500');
                charCount.classList.remove('text-gray-500', 'text-red-500');
            } else {
                charCount.classList.remove('text-amber-500', 'text-red-500');
                charCount.classList.add('text-gray-500');
            }
        }
    }

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePauseModal();
        }
    });

    // Validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('pauseRequestForm');
        const textarea = document.getElementById('pause_reason');
        const submitButton = document.getElementById('submitButton');

        // Inicializar contador de caracteres
        updateCharCount();

        if (form && textarea && submitButton) {
            // Validación en tiempo real
            textarea.addEventListener('input', function() {
                updateCharCount();
                const reason = this.value.trim();

                // Habilitar/deshabilitar botón según validación
                if (reason.length >= 10) {
                    submitButton.disabled = false;
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                } else if (reason.length > 0) {
                    submitButton.disabled = true;
                    this.classList.remove('border-green-500');
                    this.classList.add('border-red-500');
                } else {
                    submitButton.disabled = true;
                    this.classList.remove('border-red-500', 'border-green-500');
                }
            });

            form.addEventListener('submit', function(e) {
                const reason = textarea.value.trim();

                if (!reason || reason.length < 10) {
                    e.preventDefault();

                    // Mostrar error visual
                    textarea.classList.remove('border-green-500');
                    textarea.classList.add('border-red-500');
                    textarea.focus();

                    // Agregar animación de sacudida
                    textarea.classList.add('animate-shake');
                    setTimeout(() => {
                        textarea.classList.remove('animate-shake');
                    }, 500);

                    // Mostrar mensaje
                    alert('❌ Por favor, ingresa una razón detallada de al menos 10 caracteres.');
                } else {
                    textarea.classList.remove('border-red-500');
                    textarea.classList.add('border-green-500');

                    // Mostrar indicador de envío
                    submitButton.disabled = true;
                    submitButton.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Procesando...
                    `;
                }
            });
        }
    });

    // Cerrar modal al hacer clic fuera
    document.getElementById('pauseRequestModal')?.addEventListener('click', function(event) {
        if (event.target === this) {
            closePauseModal();
        }
    });

    console.log('Modal de pausa mejorado con Tailwind CSS cargado correctamente');
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .animate-shake {
        animation: shake 0.5s ease-in-out;
    }

    #modalContent {
        transition: all 0.2s ease-out;
    }
</style>
