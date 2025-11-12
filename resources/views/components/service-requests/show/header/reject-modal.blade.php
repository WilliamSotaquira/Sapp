    <!-- Modal -->
    <div id="reject-modal-{{ $serviceRequest->id }}"
        class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Rechazar Solicitud #{{ $serviceRequest->ticket_number }}
                </h3>
                <button type="button"
                    onclick="document.getElementById('reject-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-500 text-xl">
                    ✕
                </button>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <ul class="text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('service-requests.reject', $serviceRequest) }}" method="POST">
                @csrf
                @method('POST')

                <div class="space-y-4">
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">
                            Motivo del Rechazo *
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-red-500 focus:border-red-500"
                            placeholder="Explica detalladamente por qué se rechaza esta solicitud..." required minlength="10">{{ old('rejection_reason') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres. Sé específico sobre los motivos del
                            rechazo.</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                        onclick="document.getElementById('reject-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Confirmar Rechazo
                    </button>
                </div>
            </form>
        </div>
    </div>
