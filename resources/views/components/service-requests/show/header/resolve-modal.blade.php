<!-- Modal de Resolución -->
<div id="resolve-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="resolve-modal-title-{{ $serviceRequest->id }}"
     tabindex="-1">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full mr-3">
                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                </div>
                <h3 id="resolve-modal-title-{{ $serviceRequest->id }}" class="text-lg font-medium text-gray-900">
                    Resolver Solicitud
                </h3>
            </div>
            <button type="button"
                    onclick="closeModal('resolve-modal-{{ $serviceRequest->id }}')"
                    class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200"
                    aria-label="Cerrar diálogo">
                ✕
            </button>
        </div>

        <!-- Información de evidencias -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-check-circle mr-2 text-blue-500"></i>
                <span>Evidencias adjuntas: <strong>{{ $serviceRequest->evidences->count() }}</strong></span>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('service-requests.resolve', $serviceRequest) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <label for="resolution_description_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción de acciones realizadas *
                    </label>
                    <textarea
                        name="resolution_description"
                        id="resolution_description_{{ $serviceRequest->id }}"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500"
                        placeholder="Describe detalladamente las acciones realizadas para resolver la solicitud..."
                        required
                        minlength="10">{{ old('resolution_description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres.</p>
                </div>

                <div>
                    <label for="resolution_notes_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                        Notas adicionales (opcional)
                    </label>
                    <textarea
                        name="resolution_notes"
                        id="resolution_notes_{{ $serviceRequest->id }}"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500"
                        placeholder="Agrega cualquier información adicional relevante...">{{ old('resolution_notes') }}</textarea>
                </div>

            </div>

            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-green-500 mt-0.5 mr-2 flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-medium text-green-800">Proceso de Resolución</p>
                        <p class="text-xs text-green-700 mt-1">
                            Al resolver, la solicitud cambiará a estado <strong>RESUELTA</strong> y podrás proceder al cierre final.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        class="copy-completed-tasks px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-md hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                        data-service-request-id="{{ $serviceRequest->id }}"
                        title="Copiar tareas y subtareas completas">
                    <i class="fas fa-copy mr-2"></i>
                    Copiar Completas
                </button>
                <button type="button"
                        onclick="closeModal('resolve-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmar Resolución
                </button>
            </div>
        </form>
    </div>
</div>
