<!-- Modal Wizard: Resolver y Completar -->
<div id="resolve-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="resolve-modal-title-{{ $serviceRequest->id }}"
     tabindex="-1">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full flex flex-col max-h-[90vh]">

        {{-- Header with step indicator --}}
        <div class="px-6 pt-5 pb-4 border-b border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <h3 id="resolve-modal-title-{{ $serviceRequest->id }}" class="text-lg font-semibold text-gray-900">
                        Completar Solicitud
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $serviceRequest->ticket_number }}</p>
                </div>
                <button type="button"
                        onclick="closeModal('resolve-modal-{{ $serviceRequest->id }}')"
                        class="text-gray-400 hover:text-gray-600 text-lg"
                        aria-label="Cerrar">✕</button>
            </div>

            {{-- Step indicators --}}
            <div class="flex items-center gap-2 mt-4" id="resolve-steps-{{ $serviceRequest->id }}">
                <div class="resolve-step resolve-step--active" data-step="1">
                    <span class="resolve-step__dot">1</span>
                    <span class="resolve-step__label">Resolución</span>
                </div>
                <div class="resolve-step__connector"></div>
                <div class="resolve-step" data-step="2">
                    <span class="resolve-step__dot">2</span>
                    <span class="resolve-step__label">Cierre</span>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('service-requests.resolve', $serviceRequest) }}" method="POST"
              id="resolve-wizard-form-{{ $serviceRequest->id }}" class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('PATCH')
            <input type="hidden" name="also_close" value="1">

            <div class="overflow-y-auto flex-1 min-h-0">

                {{-- STEP 1: Resolution --}}
                <div class="p-6 space-y-4" id="resolve-step1-{{ $serviceRequest->id }}">
                    @if($errors->any())
                        <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                            <ul class="text-sm text-red-600">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">
                            {{ $serviceRequest->evidences->count() }} evidencia(s)
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-50 text-green-700 font-medium">
                            {{ $serviceRequest->tasks->where('status', 'completed')->count() }} tarea(s) completada(s)
                        </span>
                    </div>

                    {{-- Resolution description --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="resolution_description_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700">
                                ¿Qué se hizo? *
                            </label>
                            <button type="button"
                                    id="btn-generate-resolution-{{ $serviceRequest->id }}"
                                    class="hidden inline-flex items-center px-2 py-1 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100 transition"
                                    onclick="generateResolutionWithAI('{{ $serviceRequest->id }}')"
                                    title="Regenerar">
                                <i class="fas fa-redo mr-1"></i>Regenerar
                            </button>
                        </div>
                        <textarea
                            name="resolution_description"
                            id="resolution_description_{{ $serviceRequest->id }}"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-green-500 focus:border-green-500"
                            placeholder="Generando descripción automáticamente..."
                            required
                            minlength="10">{{ old('resolution_description') }}</textarea>
                        <p class="mt-1 text-xs text-gray-400" id="resolution-hint-{{ $serviceRequest->id }}">Mínimo 10 caracteres.</p>
                    </div>

                    {{-- Email reply (opcional, bajo demanda) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="toggle-email-reply-{{ $serviceRequest->id }}"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-200"
                                       onchange="toggleEmailReplySection('{{ $serviceRequest->id }}', this.checked)">
                                <span class="text-sm font-medium text-gray-700">Generar respuesta para correo</span>
                            </label>
                            <button type="button"
                                    id="btn-generate-email-reply-{{ $serviceRequest->id }}"
                                    class="hidden inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition"
                                    onclick="generateEmailReply('{{ $serviceRequest->id }}')"
                                    title="Regenerar">
                                <i class="fas fa-redo mr-1"></i>Regenerar
                            </button>
                        </div>
                        <div id="email-reply-section-{{ $serviceRequest->id }}" class="hidden">
                            <div id="email-reply-box-{{ $serviceRequest->id }}" class="hidden">
                                <div class="relative">
                                    <div id="email-reply-text-{{ $serviceRequest->id }}"
                                         class="w-full px-3 py-2 border border-blue-200 rounded-lg text-gray-800 bg-blue-50 text-sm leading-relaxed whitespace-pre-wrap min-h-[50px] max-h-[120px] overflow-y-auto"></div>
                                    <button type="button"
                                            id="btn-copy-email-reply-{{ $serviceRequest->id }}"
                                            onclick="copyEmailReply('{{ $serviceRequest->id }}')"
                                            class="absolute top-2 right-2 inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 transition"
                                            title="Copiar">
                                        <i class="fas fa-copy mr-1"></i>Copiar
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-400" id="email-reply-hint-{{ $serviceRequest->id }}">
                                    Listo para pegar en tu correo.
                                </p>
                            </div>
                            <p class="text-xs text-gray-400 hidden" id="email-reply-loading-{{ $serviceRequest->id }}">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Generando respuesta...
                            </p>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Close --}}
                <div class="p-6 space-y-4 hidden" id="resolve-step2-{{ $serviceRequest->id }}">
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <p class="text-sm font-medium text-green-800">Resolución lista</p>
                        </div>
                        <p class="text-xs text-green-700 mt-1" id="resolve-summary-{{ $serviceRequest->id }}">
                            La descripción de resolución está completa.
                        </p>
                    </div>

                    {{-- Response channel --}}
                    <div>
                        <label for="response_channel_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                            ¿Cómo se informó al solicitante?
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label class="resolve-channel-option">
                                <input type="radio" name="response_channel" value="CORREO" checked class="sr-only peer">
                                <div class="resolve-channel-card peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                    <i class="fas fa-envelope text-base"></i>
                                    <span>Correo</span>
                                </div>
                            </label>
                            <label class="resolve-channel-option">
                                <input type="radio" name="response_channel" value="WHATSAPP" class="sr-only peer">
                                <div class="resolve-channel-card peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                    <i class="fab fa-whatsapp text-base"></i>
                                    <span>WhatsApp</span>
                                </div>
                            </label>
                            <label class="resolve-channel-option">
                                <input type="radio" name="response_channel" value="LLAMADA" class="sr-only peer">
                                <div class="resolve-channel-card peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                    <i class="fas fa-phone text-base"></i>
                                    <span>Llamada</span>
                                </div>
                            </label>
                            <label class="resolve-channel-option">
                                <input type="radio" name="response_channel" value="OTRA" class="sr-only peer">
                                <div class="resolve-channel-card peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                    <i class="fas fa-ellipsis-h text-base"></i>
                                    <span>Otra</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Closure notes --}}
                    <div>
                        <label for="closure_notes_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                            Observaciones de cierre <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <input type="text" name="closure_notes" id="closure_notes_{{ $serviceRequest->id }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-purple-500 focus:border-purple-500"
                               placeholder="Ej: Solicitante conforme con la solución">
                    </div>

                    {{-- Skip close option --}}
                    <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer mt-2">
                        <input type="checkbox" id="skip-close-{{ $serviceRequest->id }}" class="w-3.5 h-3.5 text-gray-400 rounded border-gray-300"
                               onchange="document.querySelector('[name=also_close]').value = this.checked ? '0' : '1'">
                        <span>Solo resolver, cerrar después manualmente</span>
                    </label>
                </div>
            </div>

            {{-- Footer with navigation --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                <button type="button" id="resolve-back-{{ $serviceRequest->id }}"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hidden"
                        aria-label="Volver al paso anterior">
                    <i class="fas fa-arrow-left mr-1"></i> Atrás
                </button>
                <div class="flex-1"></div>
                <button type="button"
                        onclick="closeModal('resolve-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition">
                    Cancelar
                </button>
                <button type="button" id="resolve-next-{{ $serviceRequest->id }}"
                        class="px-5 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                    Siguiente <i class="fas fa-arrow-right ml-1"></i>
                </button>
                <button type="submit" id="resolve-submit-{{ $serviceRequest->id }}"
                        class="px-5 py-2 text-sm font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition hidden">
                    <i class="fas fa-check-double mr-1"></i> Completar
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
/* Wizard steps */
.resolve-step {
    display: flex;
    align-items: center;
    gap: 6px;
}

.resolve-step__dot {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 700;
    background: #e2e8f0;
    color: #64748b;
    transition: all 0.2s ease;
}

.resolve-step--active .resolve-step__dot {
    background: #10b981;
    color: white;
}

.resolve-step--done .resolve-step__dot {
    background: #10b981;
    color: white;
}

.resolve-step__label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
}

.resolve-step--active .resolve-step__label {
    color: #0f172a;
    font-weight: 600;
}

.resolve-step--done .resolve-step__label {
    color: #059669;
}

.resolve-step__connector {
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    border-radius: 1px;
    transition: background 0.2s ease;
}

.resolve-step__connector--filled {
    background: #10b981;
}

/* Channel cards */
.resolve-channel-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
}

.resolve-channel-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
</style>
@endpush

<script>
/**
 * Wizard navigation logic for resolve modal
 */
(function() {
    const id = @json($serviceRequest->id);
    const step1 = document.getElementById(`resolve-step1-${id}`);
    const step2 = document.getElementById(`resolve-step2-${id}`);
    const nextBtn = document.getElementById(`resolve-next-${id}`);
    const backBtn = document.getElementById(`resolve-back-${id}`);
    const submitBtn = document.getElementById(`resolve-submit-${id}`);
    const stepsEl = document.getElementById(`resolve-steps-${id}`);
    const textarea = document.getElementById(`resolution_description_${id}`);
    const summary = document.getElementById(`resolve-summary-${id}`);

    if (!step1 || !step2 || !nextBtn || !backBtn || !submitBtn) return;

    function goToStep(step) {
        if (step === 2) {
            // Validate step 1
            if (!textarea || textarea.value.trim().length < 10) {
                textarea.focus();
                textarea.classList.add('border-red-300', 'ring-1', 'ring-red-300');
                setTimeout(() => textarea.classList.remove('border-red-300', 'ring-1', 'ring-red-300'), 2000);
                return;
            }

            // Update summary
            const desc = textarea.value.trim();
            if (summary) {
                summary.textContent = '"' + (desc.length > 80 ? desc.substring(0, 80) + '...' : desc) + '"';
            }

            step1.classList.add('hidden');
            step2.classList.remove('hidden');
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
            backBtn.classList.remove('hidden');

            // Update step indicators
            const steps = stepsEl.querySelectorAll('.resolve-step');
            steps[0].classList.remove('resolve-step--active');
            steps[0].classList.add('resolve-step--done');
            steps[1].classList.add('resolve-step--active');
            stepsEl.querySelector('.resolve-step__connector').classList.add('resolve-step__connector--filled');

            // Replace dot 1 with checkmark
            steps[0].querySelector('.resolve-step__dot').innerHTML = '<i class="fas fa-check text-[9px]"></i>';
        } else {
            step2.classList.add('hidden');
            step1.classList.remove('hidden');
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
            backBtn.classList.add('hidden');

            // Reset step indicators
            const steps = stepsEl.querySelectorAll('.resolve-step');
            steps[0].classList.add('resolve-step--active');
            steps[0].classList.remove('resolve-step--done');
            steps[1].classList.remove('resolve-step--active');
            stepsEl.querySelector('.resolve-step__connector').classList.remove('resolve-step__connector--filled');
            steps[0].querySelector('.resolve-step__dot').textContent = '1';
        }
    }

    nextBtn.addEventListener('click', () => goToStep(2));
    backBtn.addEventListener('click', () => goToStep(1));
})();

/**
 * Auto-genera la descripción de resolución al abrir el modal.
 * La respuesta de correo solo se genera bajo demanda (toggle).
 */
function initResolveModal(serviceRequestId, onComplete) {
    const textarea = document.getElementById(`resolution_description_${serviceRequestId}`);

    if (textarea && !textarea.value.trim()) {
        generateResolutionWithAI(serviceRequestId, true, onComplete);
    } else {
        const btnRes = document.getElementById(`btn-generate-resolution-${serviceRequestId}`);
        if (btnRes) btnRes.classList.remove('hidden');
        if (onComplete) onComplete();
    }
}

/**
 * Toggle para mostrar/ocultar y generar la respuesta de correo.
 */
function toggleEmailReplySection(serviceRequestId, enabled) {
    const section = document.getElementById(`email-reply-section-${serviceRequestId}`);
    const btnEmail = document.getElementById(`btn-generate-email-reply-${serviceRequestId}`);

    if (enabled) {
        if (section) section.classList.remove('hidden');
        // Generar solo si no se ha generado antes
        const box = document.getElementById(`email-reply-box-${serviceRequestId}`);
        if (box && box.classList.contains('hidden')) {
            generateEmailReply(serviceRequestId, true);
        }
        if (btnEmail) btnEmail.classList.remove('hidden');
    } else {
        if (section) section.classList.add('hidden');
    }
}

function generateResolutionWithAI(serviceRequestId, isAutoGeneration = false, onDone = null) {
    const btn = document.getElementById(`btn-generate-resolution-${serviceRequestId}`);
    const textarea = document.getElementById(`resolution_description_${serviceRequestId}`);
    const hint = document.getElementById(`resolution-hint-${serviceRequestId}`);

    if (!textarea) { if (onDone) onDone(); return; }

    if (btn && !isAutoGeneration) {
        btn.disabled = true;
        btn.classList.add('opacity-60');
    }
    hint.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Analizando tareas...';
    hint.className = 'mt-1 text-xs text-purple-600';

    fetch(`/service-requests/${serviceRequestId}/generate-resolution`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.resolution_text) {
            textarea.value = data.resolution_text;
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
            hint.textContent = '✅ Generado. Revisa y ajusta si es necesario.';
            hint.className = 'mt-1 text-xs text-green-600';
        } else {
            hint.textContent = '⚠️ ' + (data.message || 'Escribe manualmente.');
            hint.className = 'mt-1 text-xs text-amber-600';
        }
    })
    .catch(() => {
        hint.textContent = '⚠️ Error de conexión. Escribe manualmente.';
        hint.className = 'mt-1 text-xs text-red-600';
    })
    .finally(() => {
        if (btn) { btn.classList.remove('hidden', 'opacity-60'); btn.disabled = false; }
        if (onDone) onDone();
    });
}

function generateEmailReply(serviceRequestId, isAutoGeneration = false, onDone = null) {
    const btn = document.getElementById(`btn-generate-email-reply-${serviceRequestId}`);
    const box = document.getElementById(`email-reply-box-${serviceRequestId}`);
    const textEl = document.getElementById(`email-reply-text-${serviceRequestId}`);
    const loading = document.getElementById(`email-reply-loading-${serviceRequestId}`);

    if (!textEl) { if (onDone) onDone(); return; }

    if (btn && !isAutoGeneration) { btn.disabled = true; btn.classList.add('opacity-60'); }
    box.classList.add('hidden');
    loading.classList.remove('hidden');

    fetch(`/service-requests/${serviceRequestId}/generate-email-reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        loading.classList.add('hidden');
        if (data.success && data.resolution_text) {
            textEl.textContent = data.resolution_text;
            box.classList.remove('hidden');
        }
    })
    .catch(() => { loading.classList.add('hidden'); })
    .finally(() => {
        if (btn) { btn.classList.remove('hidden', 'opacity-60'); btn.disabled = false; }
        if (onDone) onDone();
    });
}

function copyEmailReply(serviceRequestId) {
    const textEl = document.getElementById(`email-reply-text-${serviceRequestId}`);
    const copyBtn = document.getElementById(`btn-copy-email-reply-${serviceRequestId}`);
    if (!textEl || !copyBtn) return;

    const text = textEl.innerText || textEl.textContent;
    function onSuccess() {
        const orig = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i>OK';
        setTimeout(() => { copyBtn.innerHTML = orig; }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(() => {});
    } else {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); onSuccess(); } catch(e) {}
        document.body.removeChild(ta);
    }
}
</script>
