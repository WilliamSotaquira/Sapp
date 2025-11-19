@props(['serviceRequest'])

@php
    // Obtener todas las tareas con sus subtareas
    $tasks = $serviceRequest->tasks()
        ->with(['technician.user', 'subtasks'])
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg mr-3">
                    <i class="fas fa-tasks text-purple-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Tareas Asociadas</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $tasks->count() }} tarea(s) {{ $tasks->count() === 1 ? 'registrada' : 'registradas' }}
                    </p>
                </div>
            </div>
            @if($serviceRequest->status === 'EN_PROCESO')
                <a href="{{ route('tasks.create', ['service_request_id' => $serviceRequest->id]) }}"
                   class="inline-flex items-center px-3 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-plus mr-2"></i>
                    Nueva Tarea
                </a>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if($tasks->isEmpty())
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <i class="fas fa-tasks text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">No hay tareas asociadas a esta solicitud.</p>
                @if($serviceRequest->status === 'EN_PROCESO')
                    <p class="text-xs text-gray-400 mt-2">Puedes crear tareas manualmente cuando inicies el servicio.</p>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach($tasks as $task)
                    <div class="border border-gray-200 rounded-lg hover:shadow-md transition-shadow duration-200">
                        <!-- Tarea Principal -->
                        <div class="p-4 {{ $task->subtasks && $task->subtasks->count() > 0 ? 'border-b border-gray-100' : '' }}">
                            <div class="flex items-start gap-3">
                                <!-- Checkbox para marcar tarea completada -->
                                <div class="flex-shrink-0 mt-1">
                                    <input type="checkbox"
                                           id="task-{{ $task->id }}"
                                           class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                                           {{ $task->status === 'completed' ? 'checked' : '' }}
                                           onchange="toggleTaskStatus({{ $task->id }}, this.checked)"
                                           {{ $task->status === 'cancelled' ? 'disabled' : '' }}>
                                </div>

                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <!-- Código de tarea -->
                                        <a href="{{ route('tasks.show', $task) }}"
                                           class="font-mono text-sm font-semibold text-purple-600 hover:text-purple-800 hover:underline">
                                            {{ $task->task_code }}
                                        </a>

                                        <!-- Badge de tipo -->
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $task->type === 'impact' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                            <i class="fas {{ $task->type === 'impact' ? 'fa-exclamation-triangle' : 'fa-clipboard-list' }} mr-1"></i>
                                            {{ $task->type === 'impact' ? 'IMPACTO' : 'REGULAR' }}
                                        </span>

                                        <!-- Badge de status -->
                                        @php
                                            $statusConfig = [
                                                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-clock', 'label' => 'Pendiente'],
                                                'confirmed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle', 'label' => 'Confirmada'],
                                                'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-spinner', 'label' => 'En Proceso'],
                                                'completed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-check-double', 'label' => 'Completada'],
                                                'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times-circle', 'label' => 'Cancelada'],
                                            ];
                                            $status = $statusConfig[$task->status] ?? $statusConfig['pending'];
                                        @endphp
                                        <span id="task-status-badge-{{ $task->id }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                            <i class="fas {{ $status['icon'] }} mr-1"></i>
                                            {{ $status['label'] }}
                                        </span>

                                        <!-- Badge de prioridad -->
                                        @php
                                            $priorityConfig = [
                                                'LOW' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Baja'],
                                                'MEDIUM' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Media'],
                                                'HIGH' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'Alta'],
                                                'URGENT' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Urgente'],
                                            ];
                                            $priority = $priorityConfig[$task->priority] ?? $priorityConfig['MEDIUM'];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $priority['bg'] }} {{ $priority['text'] }}">
                                            {{ $priority['label'] }}
                                        </span>
                                    </div>

                                    <!-- Título de tarea -->
                                    <h4 class="text-sm font-medium text-gray-900 mb-2 {{ $task->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                                        {{ $task->title }}
                                    </h4>

                                    <!-- Descripción (truncada) -->
                                    @if($task->description)
                                        <p class="text-xs text-gray-600 line-clamp-2 mb-2 {{ $task->status === 'completed' ? 'text-gray-400' : '' }}">
                                            {{ Str::limit($task->description, 150) }}
                                        </p>
                                    @endif

                                    <!-- Información adicional -->
                                    <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                                        @if($task->technician)
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

                                        @if($task->subtasks && $task->subtasks->count() > 0)
                                            @php
                                                $completedSubtasks = $task->subtasks->where('is_completed', true)->count();
                                                $totalSubtasks = $task->subtasks->count();
                                            @endphp
                                            <div class="flex items-center text-purple-600 font-medium">
                                                <i class="fas fa-list-check text-purple-500 mr-1"></i>
                                                <span>{{ $completedSubtasks }}/{{ $totalSubtasks }} subtareas completadas</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Botón ver detalle -->
                                <div class="flex-shrink-0">
                                    <a href="{{ route('tasks.show', $task) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-full transition-colors duration-200"
                                       title="Ver detalle">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Subtareas -->
                        @if($task->subtasks && $task->subtasks->count() > 0)
                            <div class="bg-gray-50 p-4">
                                <h5 class="text-xs font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-list-ul mr-2"></i>
                                    Subtareas ({{ $task->subtasks->count() }})
                                </h5>
                                <div class="space-y-2">
                                    @foreach($task->subtasks as $subtask)
                                        <div class="flex items-start gap-3 p-2 bg-white rounded border border-gray-200 hover:border-purple-200 transition-colors">
                                            <!-- Checkbox para subtarea -->
                                            <div class="flex-shrink-0 mt-0.5">
                                                <input type="checkbox"
                                                       id="subtask-{{ $subtask->id }}"
                                                       class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 cursor-pointer"
                                                       {{ $subtask->is_completed ? 'checked' : '' }}
                                                       onchange="toggleSubtaskStatus({{ $task->id }}, {{ $subtask->id }}, this.checked)"
                                                       {{ $task->status === 'cancelled' ? 'disabled' : '' }}>
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

                                            <!-- Badge de prioridad subtarea -->
                                            @php
                                                $subPriority = $priorityConfig[$subtask->priority] ?? $priorityConfig['MEDIUM'];
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $subPriority['bg'] }} {{ $subPriority['text'] }} flex-shrink-0">
                                                {{ $subPriority['label'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function toggleTaskStatus(taskId, isChecked) {
    console.log('Toggle task:', taskId, 'checked:', isChecked);

    const checkbox = document.getElementById('task-' + taskId);
    const badge = document.getElementById('task-status-badge-' + taskId);
    const titleElement = checkbox.closest('.flex').querySelector('h4');
    const descriptionElement = checkbox.closest('.flex').querySelector('p');

    if (!checkbox || !badge) {
        console.error('Elementos no encontrados');
        return;
    }

    // Deshabilitar checkbox mientras se procesa
    checkbox.disabled = true;

    const url = `/tasks/${taskId}/toggle-status`;
    console.log('Enviando a:', url);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            completed: isChecked
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        if (data.success) {
            // Actualizar badge y estilos sin recargar
            if (isChecked) {
                badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800';
                badge.innerHTML = '<i class="fas fa-check-double mr-1"></i>Completada';

                // Agregar estilo tachado
                if (titleElement) {
                    titleElement.classList.add('line-through', 'text-gray-500');
                }
                if (descriptionElement) {
                    descriptionElement.classList.add('text-gray-400');
                }

                // Marcar todas las subtareas
                toggleAllSubtasks(taskId, true);
            } else {
                badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800';
                badge.innerHTML = '<i class="fas fa-spinner mr-1"></i>En Proceso';

                // Quitar estilo tachado
                if (titleElement) {
                    titleElement.classList.remove('line-through', 'text-gray-500');
                }
                if (descriptionElement) {
                    descriptionElement.classList.remove('text-gray-400');
                }

                // Desmarcar todas las subtareas
                toggleAllSubtasks(taskId, false);
            }

            // Mostrar mensaje de éxito
            showNotification(data.message, 'success');
            checkbox.disabled = false;
        } else {
            // Revertir checkbox
            checkbox.checked = !isChecked;
            showNotification(data.message || 'Error al actualizar la tarea', 'error');
            checkbox.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        checkbox.checked = !isChecked;
        showNotification('Error al actualizar la tarea: ' + error.message, 'error');
        checkbox.disabled = false;
    });
}

function toggleAllSubtasks(taskId, isChecked) {
    const taskElement = document.getElementById('task-' + taskId).closest('.border');
    const subtaskCheckboxes = taskElement.querySelectorAll('input[id^="subtask-"]');

    console.log(`${isChecked ? 'Marcando' : 'Desmarcando'} ${subtaskCheckboxes.length} subtareas`);

    subtaskCheckboxes.forEach(checkbox => {
        const subtaskId = checkbox.id.replace('subtask-', '');

        // Solo actualizar si el estado es diferente
        if (checkbox.checked !== isChecked) {
            checkbox.checked = isChecked;

            // Actualizar visualmente
            const label = checkbox.closest('.flex').querySelector('label');
            const description = checkbox.closest('.flex').querySelector('p');

            if (isChecked) {
                if (label) {
                    label.classList.add('line-through', 'text-gray-500');
                    label.classList.remove('cursor-pointer');
                }
                if (description) {
                    description.classList.add('text-gray-400');
                }
            } else {
                if (label) {
                    label.classList.remove('line-through', 'text-gray-500');
                    label.classList.add('cursor-pointer', 'text-gray-700');
                }
                if (description) {
                    description.classList.remove('text-gray-400');
                }
            }

            // Enviar actualización al servidor
            updateSubtaskInBackground(taskId, subtaskId, isChecked);
        }
    });

    // Actualizar contador
    updateSubtaskCounter(taskId);
}

function updateSubtaskInBackground(taskId, subtaskId, isChecked) {
    fetch(`/tasks/${taskId}/subtasks/${subtaskId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            is_completed: isChecked
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error al actualizar subtarea:', data.message);
        }
    })
    .catch(error => {
        console.error('Error al actualizar subtarea en background:', error);
    });
}

function toggleSubtaskStatus(taskId, subtaskId, isChecked) {
    console.log('Toggle subtask:', taskId, subtaskId, 'checked:', isChecked);

    const checkbox = document.getElementById('subtask-' + subtaskId);
    const label = checkbox.closest('.flex').querySelector('label');
    const description = checkbox.closest('.flex').querySelector('p');
    const taskCheckbox = document.getElementById('task-' + taskId);

    if (!checkbox) {
        console.error('Checkbox de subtarea no encontrado');
        return;
    }

    checkbox.disabled = true;

    const url = `/tasks/${taskId}/subtasks/${subtaskId}/toggle`;
    console.log('Enviando a:', url);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            is_completed: isChecked
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        if (data.success) {
            // Actualizar estilos de la subtarea
            if (isChecked) {
                if (label) {
                    label.classList.add('line-through', 'text-gray-500');
                    label.classList.remove('cursor-pointer');
                }
                if (description) {
                    description.classList.add('text-gray-400');
                }
            } else {
                if (label) {
                    label.classList.remove('line-through', 'text-gray-500');
                    label.classList.add('cursor-pointer', 'text-gray-700');
                }
                if (description) {
                    description.classList.remove('text-gray-400');
                }

                // Si desmarcamos una subtarea, desmarcar la tarea padre
                if (taskCheckbox && taskCheckbox.checked) {
                    taskCheckbox.checked = false;
                    // Actualizar visualmente la tarea padre
                    const badge = document.getElementById('task-status-badge-' + taskId);
                    const titleElement = taskCheckbox.closest('.flex').querySelector('h4');
                    const descriptionElement = taskCheckbox.closest('.flex').querySelector('p');

                    if (badge) {
                        badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800';
                        badge.innerHTML = '<i class="fas fa-spinner mr-1"></i>En Proceso';
                    }
                    if (titleElement) {
                        titleElement.classList.remove('line-through', 'text-gray-500');
                    }
                    if (descriptionElement) {
                        descriptionElement.classList.remove('text-gray-400');
                    }

                    // Actualizar estado en servidor
                    updateTaskInBackground(taskId, false);
                }
            }

            // Actualizar contador de subtareas completadas
            updateSubtaskCounter(taskId);

            // Si todas las subtareas están marcadas, marcar la tarea padre automáticamente
            checkAndMarkParentTask(taskId);

            showNotification(data.message, 'success');
            checkbox.disabled = false;
        } else {
            checkbox.checked = !isChecked;
            showNotification(data.message || 'Error al actualizar la subtarea', 'error');
            checkbox.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        checkbox.checked = !isChecked;
        showNotification('Error al actualizar la subtarea: ' + error.message, 'error');
        checkbox.disabled = false;
    });
}

function checkAndMarkParentTask(taskId) {
    const taskElement = document.getElementById('task-' + taskId).closest('.border');
    const subtaskCheckboxes = taskElement.querySelectorAll('input[id^="subtask-"]');
    const taskCheckbox = document.getElementById('task-' + taskId);

    let allChecked = true;
    subtaskCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            allChecked = false;
        }
    });

    // Si todas las subtareas están marcadas y la tarea no lo está, marcarla
    if (allChecked && subtaskCheckboxes.length > 0 && taskCheckbox && !taskCheckbox.checked) {
        console.log('Todas las subtareas completadas, marcando tarea padre');
        taskCheckbox.checked = true;

        // Actualizar visualmente
        const badge = document.getElementById('task-status-badge-' + taskId);
        const titleElement = taskCheckbox.closest('.flex').querySelector('h4');
        const descriptionElement = taskCheckbox.closest('.flex').querySelector('p');

        if (badge) {
            badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800';
            badge.innerHTML = '<i class="fas fa-check-double mr-1"></i>Completada';
        }
        if (titleElement) {
            titleElement.classList.add('line-through', 'text-gray-500');
        }
        if (descriptionElement) {
            descriptionElement.classList.add('text-gray-400');
        }

        // Actualizar en servidor
        updateTaskInBackground(taskId, true);
    }
}

function updateTaskInBackground(taskId, completed) {
    fetch(`/tasks/${taskId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            completed: completed
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error al actualizar tarea:', data.message);
        }
    })
    .catch(error => {
        console.error('Error al actualizar tarea en background:', error);
    });
}

function updateSubtaskCounter(taskId) {
    // Contar subtareas completadas de esta tarea
    const taskElement = document.getElementById('task-' + taskId).closest('.border');
    const subtaskCheckboxes = taskElement.querySelectorAll('input[id^="subtask-"]');

    let completed = 0;
    let total = subtaskCheckboxes.length;

    subtaskCheckboxes.forEach(checkbox => {
        if (checkbox.checked) completed++;
    });

    // Actualizar el texto del contador
    const counterElement = taskElement.querySelector('.text-purple-600.font-medium span');
    if (counterElement) {
        counterElement.textContent = `${completed}/${total} subtareas completadas`;
    }
}

function showNotification(message, type) {
    console.log('Mostrando notificación:', message, type);

    // Crear notificación toast
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.style.animation = 'slideIn 0.3s ease-out';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Agregar estilos para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>

