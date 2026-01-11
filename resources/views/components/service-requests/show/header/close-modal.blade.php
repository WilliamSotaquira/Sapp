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
            $tasksTotal = $serviceRequest->tasks()->count();
            $hasTasks = $tasksTotal > 0;

            $tasks = $hasTasks
                ? $serviceRequest->tasks()
                    ->with(['technician.user'])
                    ->withCount([
                        'subtasks',
                        'subtasks as completed_subtasks_count' => fn ($q) => $q->where('is_completed', true),
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                : collect();

            $taskStatusConfig = [
                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Pendiente'],
                'confirmed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Confirmada'],
                'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'En Proceso'],
                'completed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Completada'],
                'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Cancelada'],
            ];
        @endphp

        <!-- Formulario: body con scroll + footer fijo -->
        <form action="{{ route('service-requests.close', $serviceRequest) }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            @method('POST')

            <div class="p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
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
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Resumen del cierre</h4>
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

                <!-- Alerta de confirmación -->
                <div class="p-4 bg-purple-50 border border-purple-200 rounded-md">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-purple-500 mt-0.5 mr-2 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-purple-800">Acción Final</p>
                            <p class="text-xs text-purple-700 mt-1">
                                Al cerrar, la solicitud cambiará a estado <strong>CERRADA</strong> y no podrá ser modificada.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 {{ $hasTasks ? 'lg:grid-cols-2' : '' }} gap-4">
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
                            <div class="mb-4">
                                <label for="resolution_description_close_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descripción de Acciones Realizadas (Opcional)
                                </label>
                                <textarea
                                    name="resolution_description"
                                    id="resolution_description_close_{{ $serviceRequest->id }}"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Describe las acciones realizadas para resolver esta solicitud (opcional)...">{{ old('resolution_description') }}</textarea>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1">
                                    <label for="closure_email_draft_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700">
                                        Respuesta por correo (borrador)
                                    </label>
                                    <button
                                        type="button"
                                        id="close-email-copy-{{ $serviceRequest->id }}"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        <i class="fas fa-copy mr-2"></i>
                                        Copiar
                                    </button>
                                </div>
                                <textarea
                                    id="closure_email_draft_{{ $serviceRequest->id }}"
                                    rows="6"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Escribe aquí el texto del correo final para copiar/pegar..." spellcheck="true"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Este borrador es para copiar/pegar. No se almacena al cerrar.</p>
                            </div>
                        @endif
                    </div>

                    @if($hasTasks)
                        <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-tasks mr-2 text-purple-600"></i>
                                    Tareas asociadas
                                </h4>
                                <a href="{{ route('tasks.index', ['service_request_id' => $serviceRequest->id]) }}"
                                   class="text-xs text-purple-600 hover:text-purple-800 hover:underline">
                                    Ver todas
                                </a>
                            </div>

                            <p class="text-xs text-gray-500 mb-3">
                                {{ $tasksTotal }} tarea(s). Mostrando {{ min(10, $tasksTotal) }}.
                            </p>

                            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                @foreach($tasks as $task)
                                    @php
                                        $statusKey = strtolower($task->status ?? 'pending');
                                        $status = $taskStatusConfig[$statusKey] ?? $taskStatusConfig['pending'];
                                        $techName = $task->technician && $task->technician->user ? $task->technician->user->name : null;
                                    @endphp
                                    <div class="bg-white border border-gray-200 rounded-md p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('tasks.show', $task) }}"
                                                       class="font-mono text-xs font-semibold text-purple-600 hover:text-purple-800 hover:underline">
                                                        {{ $task->task_code }}
                                                    </a>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                                        {{ $status['label'] }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-900 truncate mt-0.5">
                                                    {{ $task->title }}
                                                </div>
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-500 mt-1">
                                                    @if($techName)
                                                        <span><i class="fas fa-user mr-1 text-gray-400"></i>{{ $techName }}</span>
                                                    @endif
                                                    @if(($task->subtasks_count ?? 0) > 0)
                                                        <span class="text-purple-700">
                                                            <i class="fas fa-list-check mr-1 text-purple-500"></i>
                                                            {{ (int) ($task->completed_subtasks_count ?? 0) }}/{{ (int) $task->subtasks_count }} subtareas
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('tasks.show', $task) }}"
                                               class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-full transition-colors"
                                               title="Ver tarea">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                const btn = document.getElementById(`close-email-copy-${id}`);
                const textarea = document.getElementById(`closure_email_draft_${id}`);

                if (!btn || !textarea) return;

                btn.addEventListener('click', async () => {
                    const text = (textarea.value || '').trim();
                    if (!text) return;

                    try {
                        await navigator.clipboard.writeText(text);
                    } catch (e) {
                        // Fallback: el usuario puede copiar manualmente
                    }
                });
            })();
        </script>
    </div>
</div>
