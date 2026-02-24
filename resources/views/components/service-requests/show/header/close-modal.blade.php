<!-- Modal de Cerrar Solicitud -->
<div id="close-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="close-modal-title-{{ $serviceRequest->id }}"
     tabindex="-1">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
        <!-- Header fijo -->
        <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex justify-between items-start gap-4">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-9 h-9 bg-purple-100 rounded-full mt-0.5">
                    <i class="fas fa-lock text-purple-600 text-sm"></i>
                </div>
                <div>
                    <h3 id="close-modal-title-{{ $serviceRequest->id }}" class="text-lg font-semibold text-gray-900 leading-tight">
                        Cerrar Solicitud
                    </h3>
                    <p class="text-sm text-gray-600 mt-0.5">
                        Ticket: <span class="font-mono font-semibold text-gray-900">#{{ $serviceRequest->ticket_number }}</span>
                    </p>
                </div>
            </div>
            <button type="button"
                    onclick="closeModal('close-modal-{{ $serviceRequest->id }}')"
                    class="text-gray-400 hover:text-gray-600 text-xl transition-colors duration-200"
                    aria-label="Cerrar diálogo">
                ✕
            </button>
        </div>

        @php
            $evidencesCount = $serviceRequest->evidences->count();
        @endphp

        <!-- Formulario: body con scroll + footer fijo -->
        <form action="{{ route('service-requests.close', $serviceRequest) }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('POST')

            <div class="p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
                <!-- Alerta de confirmación -->
                <div class="p-4 bg-purple-50 border border-purple-200 rounded-md">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-purple-500 mt-0.5 mr-2 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-purple-800">Acción Final</p>
                            <p class="text-xs text-purple-700 mt-1">
                                Al cerrar, la solicitud cambiará a estado <strong>CERRADA</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-700 mb-1">Revisa los campos:</p>
                        <ul class="text-sm text-red-600 list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Resumen del cierre (alineado) -->
                <div class="bg-gray-50 rounded-md p-4 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-clipboard-check mr-2 text-purple-600"></i>
                        Resumen del cierre
                    </h4>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-gray-600 shrink-0 w-28">Ticket</dt>
                            <dd class="font-mono font-semibold text-gray-900 text-right">{{ $serviceRequest->ticket_number }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-gray-600 shrink-0 w-28">Evidencias</dt>
                            <dd class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $evidencesCount > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $evidencesCount }} adjunta(s)
                                </span>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-gray-600 shrink-0 w-28">Estado actual</dt>
                            <dd class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ $serviceRequest->status }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-gray-600 shrink-0 w-28">Nuevo estado</dt>
                            <dd class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    CERRADA
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        @if($serviceRequest->status === 'PAUSADA')
                            <div>
                                <label for="closure_reason_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    Motivo de cierre por vencimiento *
                                </label>
                                <textarea
                                    name="closure_reason"
                                    id="closure_reason_{{ $serviceRequest->id }}"
                                    rows="3"
                                    required
                                    minlength="10"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Describe el motivo del cierre por vencimiento...">{{ old('closure_reason') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres.</p>
                            </div>
                        @else
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                <h4 class="text-sm font-semibold text-gray-800 mb-2 flex items-center">
                                    <i class="fas fa-paper-plane mr-2 text-purple-600"></i>
                                    Respuesta
                                </h4>
                                <select
                                    name="response_channel"
                                    id="response_channel_{{ $serviceRequest->id }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Selecciona una opción</option>
                                    <option value="CORREO" {{ old('response_channel', 'CORREO') == 'CORREO' ? 'selected' : '' }}>Correo</option>
                                    <option value="WHATSAPP" {{ old('response_channel') == 'WHATSAPP' ? 'selected' : '' }}>WhatsApp</option>
                                    <option value="LLAMADA" {{ old('response_channel') == 'LLAMADA' ? 'selected' : '' }}>Llamada</option>
                                    <option value="OTRA" {{ old('response_channel') == 'OTRA' ? 'selected' : '' }}>Otra</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-paperclip mr-2 text-purple-600"></i>
                                Evidencias
                            </h4>
                            <span class="text-xs text-gray-500">{{ $evidencesCount }} adjunta(s)</span>
                        </div>

                        <div class="space-y-3">
                            <div id="close-evidence-list-{{ $serviceRequest->id }}" class="space-y-3"></div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                        id="add-close-link-evidence-{{ $serviceRequest->id }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <i class="fas fa-plus mr-2"></i>
                                    Agregar enlace
                                </button>
                                <button type="button"
                                        id="add-close-file-evidence-{{ $serviceRequest->id }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <i class="fas fa-plus mr-2"></i>
                                    Agregar adjunto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-end gap-3">
                <button type="button"
                        onclick="closeModal('close-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                    <i class="fas fa-lock mr-2"></i>
                    Confirmar Cierre
                </button>
            </div>
        </form>

        <script>
            (function () {
                const id = @json($serviceRequest->id);
                const list = document.getElementById(`close-evidence-list-${id}`);
                const addLinkBtn = document.getElementById(`add-close-link-evidence-${id}`);
                const addFileBtn = document.getElementById(`add-close-file-evidence-${id}`);

                if (!list || !addLinkBtn || !addFileBtn) return;

                function bindEvidenceBlock(block) {
                    const removeBtn = block.querySelector('[data-remove-evidence]');

                    if (removeBtn) {
                        removeBtn.addEventListener('click', () => {
                            block.remove();
                            renumberEvidenceBlocks();
                        });
                    }
                }

                function renumberEvidenceBlocks() {
                    const blocks = list.querySelectorAll('[data-evidence-block]');
                    blocks.forEach((block, idx) => {
                        block.dataset.index = String(idx);
                        const title = block.querySelector('p.text-sm.font-medium');
                        if (title) title.textContent = `Evidencia #${idx + 1}`;
                    });
                }

                function createEvidenceBlock(type) {
                    const index = list.querySelectorAll('[data-evidence-block]').length;
                    const block = document.createElement('div');
                    block.className = 'space-y-3 border border-gray-200 rounded-md p-3 bg-white';
                    block.setAttribute('data-evidence-block', '');
                    block.setAttribute('data-index', String(index));
                    const isFile = type === 'ARCHIVO';

                    block.innerHTML = `
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-700">Evidencia #${index + 1}</p>
                            <button type="button"
                                    class="text-xs text-red-600 hover:text-red-700 font-semibold"
                                    data-remove-evidence>
                                Quitar
                            </button>
                        </div>
                        <input type="hidden" name="evidence_type[]" value="${type}" />
                        <div>
                            <label for="evidence_file_${id}_${index}" class="block text-sm font-medium text-gray-700 mb-1 ${isFile ? '' : 'hidden'}" data-file-label>
                                Archivo
                            </label>
                            <input
                                type="file"
                                name="files[]"
                                id="evidence_file_${id}_${index}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                                ${isFile ? '' : 'style="display:none;"'}
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" />
                            <p class="mt-1 text-xs text-gray-500 ${isFile ? '' : 'hidden'}" data-file-hint>Formatos permitidos: JPG, PNG, GIF, PDF, DOC, XLS, TXT, ZIP</p>
                        </div>
                        <div>
                            <label for="evidence_link_${id}_${index}" class="block text-sm font-medium text-gray-700 mb-1 ${isFile ? 'hidden' : ''}" data-link-label>
                                Enlace
                            </label>
                            <input
                                type="url"
                                name="link_url[]"
                                id="evidence_link_${id}_${index}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                                ${isFile ? 'style="display:none;"' : ''}
                                placeholder="https://..." />
                        </div>
                    `;
                    list.appendChild(block);
                    bindEvidenceBlock(block);
                    renumberEvidenceBlocks();
                }

                addLinkBtn.addEventListener('click', () => createEvidenceBlock('ENLACE'));
                addFileBtn.addEventListener('click', () => createEvidenceBlock('ARCHIVO'));

            })();
        </script>
    </div>
</div>
