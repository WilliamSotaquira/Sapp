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

<div class="border border-gray-200 rounded-lg hover:shadow-md transition-shadow duration-200" data-task-card="{{ $task->id }}" data-task-completed="{{ strtolower($task->status ?? '') === 'completed' ? '1' : '0' }}">
    <div class="p-4 {{ $subtasks->count() > 0 ? 'border-b border-gray-100' : '' }}">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-1">
                <input type="checkbox"
                    id="task-{{ $task->id }}"
                    class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                    {{ strtolower($task->status ?? '') === 'completed' ? 'checked' : '' }}
                    onchange="toggleTaskStatus({{ $task->id }}, this.checked)"
                    {{ ($task->status === 'cancelled' || !$canConfirmProgress) ? 'disabled' : '' }}
                    title="{{ !$canConfirmProgress ? 'Solo se puede confirmar avance cuando la solicitud está ACEPTADA o EN PROCESO.' : '' }}">
            </div>

            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <a href="{{ route('tasks.show', $task) }}"
                        class="font-mono text-sm font-semibold text-purple-600 hover:text-purple-800 hover:underline">
                        {{ $task->task_code }}
                    </a>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $task->type === 'impact' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                        <i class="fas {{ $task->type === 'impact' ? 'fa-exclamation-triangle' : 'fa-clipboard-list' }} mr-1"></i>
                        {{ $task->type === 'impact' ? 'IMPACTO' : 'REGULAR' }}
                    </span>

                    <span id="task-status-badge-{{ $task->id }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                        <i class="fas {{ $status['icon'] }} mr-1"></i>
                        {{ $status['label'] }}
                    </span>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $priority['bg'] }} {{ $priority['text'] }}">
                        {{ $priority['label'] }}
                    </span>
                </div>

                <h4 class="text-sm font-medium text-gray-900 mb-2 {{ $task->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                    {{ $task->title }}
                </h4>

                @if($task->description)
                    <p class="text-xs text-gray-600 line-clamp-2 mb-2 {{ $task->status === 'completed' ? 'text-gray-400' : '' }}">
                        {{ Str::limit($task->description, 150) }}
                    </p>
                @endif

                <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                    @if($task->technician && $task->technician->user)
                        <div class="flex items-center">
                            <i class="fas fa-user text-gray-400 mr-1"></i>
                            <span>{{ $task->technician->user->name }}</span>
                        </div>
                    @endif

                    @if($task->scheduled_date)
                        <div class="flex items-center">
                            <i class="fas fa-calendar text-gray-400 mr-1"></i>
                            <span>{{ \Carbon\Carbon::parse($task->scheduled_date)->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($task->estimated_hours)
                        <div class="flex items-center">
                            <i class="fas fa-clock text-gray-400 mr-1"></i>
                            <span>{{ $task->estimated_hours }}h estimadas</span>
                        </div>
                    @endif

                    <div class="flex items-center">
                        <i class="fas fa-calendar-plus text-gray-400 mr-1"></i>
                        <span>Creada {{ $task->created_at->diffForHumans() }}</span>
                    </div>

                    @if($subtasks->count() > 0)
                        @php
                            $completedSubtasks = $subtasks->where('is_completed', true)->count();
                        @endphp
                        <div class="flex items-center text-purple-600 font-medium">
                            <i class="fas fa-list-check text-purple-500 mr-1"></i>
                            <span>{{ $completedSubtasks }}/{{ $subtasks->count() }} subtareas completadas</span>
                        </div>
                    @endif
                </div>
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
        <div class="bg-gray-50 p-4">
            <h5 class="text-xs font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-list-ul mr-2"></i>
                Subtareas ({{ $subtasks->count() }})
            </h5>
            <div class="space-y-2">
                @foreach($subtasks as $subtask)
                    @php
                        $subPriorityKey = strtolower($subtask->priority ?? 'medium');
                        $subPriority = $priorityConfig[$subPriorityKey] ?? $priorityConfig['medium'];
                    @endphp
                    <div class="flex items-start gap-3 p-2 bg-white rounded border border-gray-200 hover:border-purple-200 transition-colors" data-subtask-completed="{{ $subtask->is_completed ? '1' : '0' }}">
                        <div class="flex-shrink-0 mt-0.5">
                            <input type="checkbox"
                                id="subtask-{{ $subtask->id }}"
                                class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                                {{ $subtask->is_completed ? 'checked' : '' }}
                                onchange="toggleSubtaskStatus({{ $task->id }}, {{ $subtask->id }}, this.checked)"
                                {{ ($task->status === 'cancelled' || !$canConfirmProgress) ? 'disabled' : '' }}
                                title="{{ !$canConfirmProgress ? 'Solo se puede confirmar avance cuando la solicitud está ACEPTADA o EN PROCESO.' : '' }}">
                        </div>

                        <div class="flex-1">
                            <label for="subtask-{{ $subtask->id }}" class="text-sm {{ $subtask->is_completed ? 'line-through text-gray-500' : 'text-gray-700 cursor-pointer' }}">
                                {{ $subtask->title }}
                            </label>
                            @if($subtask->description)
                                <p class="text-xs text-gray-500 mt-1 {{ $subtask->is_completed ? 'text-gray-400' : '' }}">
                                    {{ Str::limit($subtask->description, 100) }}
                                </p>
                            @endif
                        </div>

                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $subPriority['bg'] }} {{ $subPriority['text'] }} flex-shrink-0">
                            {{ $subPriority['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
