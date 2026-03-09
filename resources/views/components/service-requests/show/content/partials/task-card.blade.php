@props(['task', 'canConfirmProgress' => true])

@php
    $statusConfig = [
        'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-clock', 'label' => 'Pendiente'],
        'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-spinner', 'label' => 'En Proceso'],
        'blocked' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'icon' => 'fa-ban', 'label' => 'Bloqueada'],
        'in_review' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'icon' => 'fa-magnifying-glass', 'label' => 'En Revisión'],
        'rescheduled' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-calendar-days', 'label' => 'Reprogramada'],
        'completed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-check-double', 'label' => 'Completada'],
        'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times-circle', 'label' => 'Cancelada'],

        // Compatibilidad con valores legacy (si existen en data histórica)
        'confirmed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle', 'label' => 'Confirmada'],
    ];
    $priorityConfig = [
        'low' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Baja'],
        'medium' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Media'],
        'high' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'Alta'],
        'critical' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Crítica'],

        // Compatibilidad con valores legacy (si existen en data histórica)
        'urgent' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Crítica'],
    ];

    $statusKey = strtolower($task->status ?? 'pending');
    $priorityKey = strtolower($task->priority ?? 'medium');

    $status = $statusConfig[$statusKey] ?? $statusConfig['pending'];
    $priority = $priorityConfig[$priorityKey] ?? $priorityConfig['medium'];

    $subtasks = $task->subtasks ?? collect();
@endphp

<div class="border border-gray-200 rounded-lg" data-task-card="{{ $task->id }}" data-task-completed="{{ strtolower($task->status ?? '') === 'completed' ? '1' : '0' }}">
    <div class="p-3 {{ $subtasks->count() > 0 ? 'border-b border-gray-100' : '' }}">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-1">
                <input type="checkbox"
                    id="task-{{ $task->id }}"
                    class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                    {{ strtolower($task->status ?? '') === 'completed' ? 'checked' : '' }}
                    onchange="toggleTaskStatus({{ $task->id }}, this.checked)"
                    {{ ($task->status === 'cancelled' || !$canConfirmProgress) ? 'disabled' : '' }}
                    title="{{ !$canConfirmProgress ? 'Solo se puede confirmar avance cuando la solicitud está PENDIENTE, ACEPTADA o EN PROCESO.' : '' }}">
            </div>

            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('tasks.show', $task) }}"
                        class="font-mono text-sm font-semibold text-purple-600 hover:text-purple-800 hover:underline">
                        {{ $task->task_code }}
                    </a>

                    <span id="task-status-badge-{{ $task->id }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                        <i class="fas {{ $status['icon'] }} mr-1"></i>
                        {{ $status['label'] }}
                    </span>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $priority['bg'] }} {{ $priority['text'] }}">
                        {{ $priority['label'] }}
                    </span>
                </div>

                <h4 class="text-sm font-medium text-gray-900 {{ $task->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                    {{ $task->title }}
                </h4>

                @if($subtasks->count() > 0)
                    @php $completedSubtasks = $subtasks->where('is_completed', true)->count(); @endphp
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $completedSubtasks }}/{{ $subtasks->count() }} subtareas completadas
                    </p>
                @endif
            </div>

            <div class="flex-shrink-0">
                <a href="{{ route('tasks.show', $task) }}"
                    class="inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-full transition-colors duration-200"
                    title="Ver detalle">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    @if($subtasks->count() > 0)
        <div class="bg-gray-50 p-3">
            <h5 class="text-xs font-semibold text-gray-700 mb-2">
                Subtareas ({{ $subtasks->count() }})
            </h5>
            <div class="space-y-1.5">
                @foreach($subtasks as $subtask)
                    <div class="flex items-start gap-2 p-2 bg-white rounded border border-gray-200" data-subtask-completed="{{ $subtask->is_completed ? '1' : '0' }}">
                        <div class="flex-shrink-0 mt-0.5">
                            <input type="checkbox"
                                id="subtask-{{ $subtask->id }}"
                                class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                                {{ $subtask->is_completed ? 'checked' : '' }}
                                onchange="toggleSubtaskStatus({{ $task->id }}, {{ $subtask->id }}, this.checked)"
                                {{ ($task->status === 'cancelled' || !$canConfirmProgress) ? 'disabled' : '' }}
                                title="{{ !$canConfirmProgress ? 'Solo se puede confirmar avance cuando la solicitud está PENDIENTE, ACEPTADA o EN PROCESO.' : '' }}">
                        </div>

                        <div class="flex-1">
                            <label for="subtask-{{ $subtask->id }}" class="text-sm {{ $subtask->is_completed ? 'line-through text-gray-500' : 'text-gray-700 cursor-pointer' }}">
                                {{ $subtask->title }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
