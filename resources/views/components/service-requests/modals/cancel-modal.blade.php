@props(['request'])

<!-- Modal de Cancelación -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-times text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Cancelar Solicitud</h3>
            <p class="text-sm text-gray-500 mt-2">¿Está seguro de cancelar esta solicitud?</p>

            <form action="{{ route('service-requests.cancel', $request) }}" method="POST" class="mt-4">
                @csrf
                <div class="mb-4">
                    <label for="cancel_reason" class="block text-left text-sm font-medium text-gray-700 mb-1">Motivo:</label>
                    <textarea id="cancel_reason" name="cancel_reason" rows="3" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Describa el motivo de la cancelación..."></textarea>
                </div>

                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" onclick="serviceRequestModals.close('cancel')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                        Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
