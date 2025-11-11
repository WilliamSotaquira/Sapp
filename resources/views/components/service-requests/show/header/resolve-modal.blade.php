{{-- resources/views/components/service-requests/show/header/resolve-modal.blade.php --}}
<div class="inline">
    <!-- Botón para abrir modal -->
    <button type="button"
            onclick="openResolveModal({{ $serviceRequest->id }})"
            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <i class="fas fa-check-double mr-2" aria-hidden="true"></i>
        Resolver Solicitud
    </button>

    <!-- Modal -->
    <div id="resolve-modal-{{ $serviceRequest->id }}"
         class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Resolver Solicitud #{{ $serviceRequest->ticket_number }}
                </h3>
                <button type="button"
                        onclick="closeResolveModal({{ $serviceRequest->id }})"
                        class="text-gray-400 hover:text-gray-500 text-xl">
                    ✕
                </button>
            </div>

            <!-- Mostrar mensajes de sesión -->
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span class="text-red-700 text-sm font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Mostrar errores de validación -->
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <h4 class="text-sm font-medium text-red-800 mb-2">Errores en el formulario:</h4>
                    <ul class="text-sm text-red-600 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('service-requests.resolve', $serviceRequest) }}" method="POST" id="resolve-form-{{ $serviceRequest->id }}">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <div>
                        <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Notas de Resolución *
                        </label>
                        <textarea name="resolution_notes"
                                id="resolution_notes"
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500 @error('resolution_notes') border-red-500 @enderror"
                                placeholder="Describe detalladamente cómo se resolvió la solicitud..."
                                required>{{ old('resolution_notes', '') }}</textarea>
                        @error('resolution_notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres</p>
                    </div>

                    <div>
                        <label for="actual_resolution_time" class="block text-sm font-medium text-gray-700 mb-1">
                            Tiempo de Resolución (minutos) *
                        </label>
                        <input type="number"
                            name="actual_resolution_time"
                            id="actual_resolution_time"
                            min="1"
                            max="480"
                            value="{{ old('actual_resolution_time', 60) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500 @error('actual_resolution_time') border-red-500 @enderror"
                            required>
                        @error('actual_resolution_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Tiempo en minutos (1-480)</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                            onclick="closeResolveModal({{ $serviceRequest->id }})"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Confirmar Resolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResolveModal(requestId) {
    document.getElementById('resolve-modal-' + requestId).classList.remove('hidden');
}

function closeResolveModal(requestId) {
    document.getElementById('resolve-modal-' + requestId).classList.add('hidden');
}

// Cerrar modal al hacer click fuera
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
    }
});

// Validación del formulario
document.getElementById('resolve-form-{{ $serviceRequest->id }}').addEventListener('submit', function(e) {
    const notes = document.getElementById('resolution_notes').value;
    const time = document.getElementById('actual_resolution_time').value;

    console.log('Enviando formulario con:', { notes, time });
});
</script>
