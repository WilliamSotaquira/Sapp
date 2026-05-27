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

                <!-- Respuesta para correo (generada por IA) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Respuesta para correo
                            <span class="ml-1 text-xs font-normal text-gray-400">(opcional)</span>
                        </label>
                        <button type="button"
                                id="btn-generate-email-reply-{{ $serviceRequest->id }}"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
                                onclick="generateEmailReply('{{ $serviceRequest->id }}')"
                                title="Generar respuesta breve no técnica para enviar por correo">
                            <i class="fas fa-envelope-open-text mr-1"></i>
                            Generar respuesta
                        </button>
                    </div>

                    <!-- Área de resultado: oculta hasta que se genere -->
                    <div id="email-reply-box-{{ $serviceRequest->id }}" class="hidden">
                        <div class="relative">
                            <div id="email-reply-text-{{ $serviceRequest->id }}"
                                 class="w-full px-3 py-2 border border-blue-200 rounded-md text-gray-800 bg-blue-50 text-sm leading-relaxed whitespace-pre-wrap min-h-[60px]"></div>
                            <button type="button"
                                    id="btn-copy-email-reply-{{ $serviceRequest->id }}"
                                    onclick="copyEmailReply('{{ $serviceRequest->id }}')"
                                    class="absolute top-2 right-2 inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors duration-200"
                                    title="Copiar texto al portapapeles">
                                <i class="fas fa-copy mr-1"></i>
                                Copiar
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-400" id="email-reply-hint-{{ $serviceRequest->id }}">
                            Texto listo para pegar en tu correo. Puedes editarlo antes de enviar.
                        </p>
                    </div>

                    <!-- Mensaje de estado mientras carga -->
                    <p class="text-xs text-gray-400 hidden" id="email-reply-loading-{{ $serviceRequest->id }}">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Generando respuesta...
                    </p>
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
function generateEmailReply(serviceRequestId) {
    const btn      = document.getElementById(`btn-generate-email-reply-${serviceRequestId}`);
    const box      = document.getElementById(`email-reply-box-${serviceRequestId}`);
    const textEl   = document.getElementById(`email-reply-text-${serviceRequestId}`);
    const hint     = document.getElementById(`email-reply-hint-${serviceRequestId}`);
    const loading  = document.getElementById(`email-reply-loading-${serviceRequestId}`);

    if (!btn || !textEl) return;

    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generando...';
    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');
    box.classList.add('hidden');
    loading.classList.remove('hidden');

    fetch(`/service-requests/${serviceRequestId}/generate-email-reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        loading.classList.add('hidden');
        if (data.success && data.resolution_text) {
            textEl.textContent = data.resolution_text;
            box.classList.remove('hidden');
            hint.textContent = 'Texto listo para pegar en tu correo. Puedes editarlo antes de enviar.';
            hint.className = 'mt-1 text-xs text-gray-400';
        } else {
            loading.classList.add('hidden');
            const msg = document.createElement('p');
            msg.className = 'mt-1 text-xs text-amber-600';
            msg.textContent = '⚠️ ' + (data.message || 'No se pudo generar. Intenta de nuevo.');
            btn.parentElement.parentElement.appendChild(msg);
            setTimeout(() => msg.remove(), 5000);
        }
    })
    .catch(() => {
        loading.classList.add('hidden');
        const msg = document.createElement('p');
        msg.className = 'mt-1 text-xs text-red-600';
        msg.textContent = '⚠️ Error de conexión. Intenta de nuevo.';
        btn.parentElement.parentElement.appendChild(msg);
        setTimeout(() => msg.remove(), 5000);
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-envelope-open-text mr-1"></i> Regenerar';
        btn.disabled = false;
        btn.classList.remove('opacity-60', 'cursor-not-allowed');
    });
}

function copyEmailReply(serviceRequestId) {
    const textEl  = document.getElementById(`email-reply-text-${serviceRequestId}`);
    const copyBtn = document.getElementById(`btn-copy-email-reply-${serviceRequestId}`);
    if (!textEl) return;

    navigator.clipboard.writeText(textEl.textContent).then(() => {
        const original = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Copiado';
        copyBtn.classList.add('text-green-600', 'border-green-300');
        setTimeout(() => {
            copyBtn.innerHTML = original;
            copyBtn.classList.remove('text-green-600', 'border-green-300');
        }, 2000);
    }).catch(() => {
        // fallback para navegadores sin clipboard API
        const range = document.createRange();
        range.selectNodeContents(textEl);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();

        const original = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Copiado';
        setTimeout(() => { copyBtn.innerHTML = original; }, 2000);
    });
}

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
