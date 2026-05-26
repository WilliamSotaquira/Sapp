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

        <p class="mb-4 text-sm text-gray-700">
            Evidencias adjuntas:
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">
                {{ $serviceRequest->evidences->count() }}
            </span>
        </p>

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
                    <div class="flex items-center justify-between mb-1">
                        <label for="resolution_description_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700">
                            Descripción de acciones realizadas *
                        </label>
                        <button type="button"
                                id="btn-generate-resolution-{{ $serviceRequest->id }}"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200"
                                onclick="generateResolutionWithAI('{{ $serviceRequest->id }}')"
                                title="Analizar tareas completadas y generar descripción automáticamente">
                            <i class="fas fa-magic mr-1"></i>
                            Generar con IA
                        </button>
                    </div>
                    <textarea
                        name="resolution_description"
                        id="resolution_description_{{ $serviceRequest->id }}"
                        rows="5"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500"
                        placeholder="Describe las acciones realizadas o usa 'Generar con IA' para crear automáticamente..."
                        required
                        minlength="10">{{ old('resolution_description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500" id="resolution-hint-{{ $serviceRequest->id }}">Mínimo 10 caracteres. Puedes generar automáticamente con IA.</p>
                </div>

                <div>
                    <label for="resolution_notes_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                        Notas adicionales (opcional)
                    </label>
                    <textarea
                        name="resolution_notes"
                        id="resolution_notes_{{ $serviceRequest->id }}"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-green-500 focus:border-green-500"
                        placeholder="Observaciones o información adicional...">{{ old('resolution_notes') }}</textarea>
                </div>
            </div>

            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-green-500 mt-0.5 mr-2 flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-medium text-green-800">Proceso de Resolución</p>
                        <p class="text-xs text-green-700 mt-1">
                            Al resolver, la solicitud cambiará a estado <strong>RESUELTA</strong>. El tiempo de resolución se calcula automáticamente.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
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

<script>
function generateResolutionWithAI(serviceRequestId) {
    const btn = document.getElementById(`btn-generate-resolution-${serviceRequestId}`);
    const textarea = document.getElementById(`resolution_description_${serviceRequestId}`);
    const hint = document.getElementById(`resolution-hint-${serviceRequestId}`);

    if (!btn || !textarea) return;

    // Estado de carga
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Analizando...';
    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');
    hint.textContent = 'Analizando tareas y subtareas completadas...';
    hint.classList.add('text-purple-600');

    fetch(`/service-requests/${serviceRequestId}/generate-resolution`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.resolution_text) {
            textarea.value = data.resolution_text;
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';

            const stats = [];
            if (data.tasks_analyzed) stats.push(`${data.tasks_analyzed} tareas analizadas`);
            if (data.completed_count) stats.push(`${data.completed_count} completadas`);

            hint.textContent = `✅ Generado automáticamente. ${stats.join(', ')}. Revisa y ajusta si es necesario.`;
            hint.classList.remove('text-purple-600');
            hint.classList.add('text-green-600');
        } else {
            hint.textContent = `⚠️ ${data.message || 'No se pudo generar. Escribe manualmente.'}`;
            hint.classList.remove('text-purple-600');
            hint.classList.add('text-amber-600');
        }
    })
    .catch(error => {
        console.error('Error generating resolution:', error);
        hint.textContent = '⚠️ Error de conexión. Escribe la descripción manualmente.';
        hint.classList.remove('text-purple-600');
        hint.classList.add('text-red-600');
    })
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
        btn.classList.remove('opacity-60', 'cursor-not-allowed');
    });
}
</script>
