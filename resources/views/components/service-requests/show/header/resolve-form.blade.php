<!-- resources/views/components/service-requests/show/header/resolve-form.blade.php -->
@props(['serviceRequest'])

@if ($serviceRequest->status === 'EN_PROCESO')
    <div class="lg:ml-4">
        <button type="button" id="resolveButton"
            class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-full text-sm font-semibold transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-teal-300 shadow-sm flex items-center border-2 border-teal-400">
            <i class="fas fa-check-double mr-2"></i>
            Resolver Solicitud
        </button>
    </div>

    <!-- Modal de resolución -->
    <div id="resolveModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-teal-100 p-3 rounded-xl mr-4">
                            <i class="fas fa-check-double text-teal-600 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Resolver Solicitud</h3>
                            <p class="text-sm text-gray-500 mt-1">#{{ $serviceRequest->ticket_number }}</p>
                        </div>
                    </div>
                    <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center text-red-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="font-semibold">Errores encontrados:</span>
                        </div>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="resolveForm" action="{{ route('service-requests.resolve', $serviceRequest) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-6">
                        <div>
                            <label for="actual_resolution_time" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-2 text-teal-600"></i>
                                Tiempo de Resolución (minutos) *
                            </label>
                            <input type="number" id="actual_resolution_time" name="actual_resolution_time"
                                min="1" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 text-gray-700 placeholder-gray-400"
                                placeholder="Ingresa el tiempo en minutos" value="{{ old('actual_resolution_time') }}">
                            @error('actual_resolution_time')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="resolution_notes" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-teal-600"></i>
                                Notas de Resolución *
                            </label>
                            <textarea id="resolution_notes" name="resolution_notes" rows="5" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 text-gray-700 placeholder-gray-400 resize-none"
                                placeholder="Describe cómo se resolvió la solicitud...">{{ old('resolution_notes') }}</textarea>
                            @error('resolution_notes')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                        <button type="button" id="cancelButton"
                            class="px-6 py-3 text-gray-600 border-2 border-gray-300 rounded-xl hover:bg-gray-50 transition duration-200 font-medium">
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition duration-200 font-medium flex items-center shadow-sm">
                            <i class="fas fa-check-double mr-2"></i>
                            Confirmar Resolución
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JavaScript simple y directo
        document.addEventListener('DOMContentLoaded', function() {
            const resolveButton = document.getElementById('resolveButton');
            const closeModal = document.getElementById('closeModal');
            const cancelButton = document.getElementById('cancelButton');
            const modal = document.getElementById('resolveModal');

            if (resolveButton && modal) {
                resolveButton.addEventListener('click', function() {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                });
            }

            function closeModalFunc() {
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = 'auto';
                }
            }

            if (closeModal) closeModal.addEventListener('click', closeModalFunc);
            if (cancelButton) cancelButton.addEventListener('click', closeModalFunc);

            // Cerrar modal con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeModalFunc();
            });

            // Cerrar modal haciendo click fuera
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModalFunc();
                });
            }
        });
    </script>
@endif
