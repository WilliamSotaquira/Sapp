@props(['serviceRequest'])

<!-- Modal de Aceptación -->
<div id="acceptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <i class="fas fa-check text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Aceptar Solicitud</h3>
            <p class="text-sm text-gray-500 mt-2">¿Está seguro de aceptar esta solicitud?</p>

            <form action="{{ route('service-requests.accept', $serviceRequest) }}" method="POST" class="mt-4">
                @csrf
                <div class="mb-4">
                    <label for="accept_notes" class="block text-left text-sm font-medium text-gray-700 mb-1">Notas (opcional):</label>
                    <textarea id="accept_notes" name="accept_notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Notas adicionales sobre la aceptación..."></textarea>
                </div>

                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" onclick="serviceRequestModals.close('accept')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                        Confirmar Aceptación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
