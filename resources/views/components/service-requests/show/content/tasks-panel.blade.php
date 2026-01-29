@props(['serviceRequest'])

@php
    $tasks = $serviceRequest->tasks()
        ->with(['technician.user', 'subtasks'])
        ->orderBy('created_at', 'desc')
        ->get();
    $canManageTasks = in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO']);
    $hasTechnicianAssigned = (bool) $serviceRequest->assigned_to;
    $quickTaskEnabled = $canManageTasks && $hasTechnicianAssigned;
@endphp

<div class="bg-white shadow rounded-lg overflow-hidden" data-service-request-id="{{ $serviceRequest->id }}">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg mr-3">
                    <i class="fas fa-tasks text-purple-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Tareas Asociadas</h3>
                    <p id="tasksCountLabel"
                       data-count="{{ $tasks->count() }}"
                       class="text-xs text-gray-500 mt-0.5">
                        {{ $tasks->count() }} tarea(s) {{ $tasks->count() === 1 ? 'registrada' : 'registradas' }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.create', ['service_request_id' => $serviceRequest->id]) }}"
                   class="inline-flex items-center px-3 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    Abrir Gestor
                </a>
                <button type="button"
                        class="open-quick-task inline-flex items-center px-3 py-2 border {{ $quickTaskEnabled ? 'border-purple-600 text-purple-700 bg-white hover:bg-purple-50' : 'border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed' }} rounded-md text-xs font-semibold uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"
                        data-disabled="{{ $quickTaskEnabled ? 'false' : 'true' }}"
                        data-enabled-class="border-purple-600 text-purple-700 bg-white hover:bg-purple-50"
                        data-disabled-class="border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed">
                    <i class="fas fa-bolt mr-2"></i>
                    Tarea Rápida
                </button>
            </div>
        </div>
        @if(!$hasTechnicianAssigned)
            <p class="mt-2 text-xs text-amber-600 flex items-center" data-quick-task-warning>
                <i class="fas fa-info-circle mr-1"></i>
                Debes asignar un técnico para poder crear tareas rápidas.
            </p>
        @endif
    </div>

    <div class="p-4 sm:p-6">
        <div id="tasksList" class="space-y-3 {{ $tasks->isEmpty() ? 'hidden' : '' }}">
            @foreach($tasks as $task)
                @include('components.service-requests.show.content.partials.task-card', ['task' => $task])
            @endforeach
        </div>

        <div id="tasksEmptyState" class="{{ $tasks->isNotEmpty() ? 'hidden' : '' }} py-10 text-center text-gray-500">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-clipboard-list text-gray-400 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-gray-600">No hay tareas asociadas a esta solicitud.</p>
            @if($canManageTasks)
                <p class="text-xs text-gray-500 mt-1">Utiliza “Tarea Rápida” para crear la primera sin salir de esta vista.</p>
            @endif
        </div>
    </div>
</div>

<div id="quickTaskModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Nueva Tarea Rápida</h3>
                    <p class="text-xs text-gray-500">Se vinculará automáticamente a la solicitud #{{ $serviceRequest->ticket_number }}</p>
                </div>
                <button type="button" class="close-quick-task text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="quickTaskForm" action="{{ route('service-requests.quick-task', $serviceRequest) }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div id="quickTaskError" class="hidden px-4 py-2 rounded-lg border border-red-200 bg-red-50 text-sm text-red-700"></div>
                <div>
                    <label for="quick_task_title" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" id="quick_task_title" name="title" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Ej. Revisar logs del portal" required>
                </div>
                <div>
                    <label for="quick_task_description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="quick_task_description" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Contexto o pasos a ejecutar (opcional)"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="quick_task_priority" class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                        <select id="quick_task_priority" name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                    <div>
                        <label for="quick_task_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select id="quick_task_type" name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="regular" selected>Regular (25 min)</option>
                            <option value="impact">Impacto (90 min)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="quick_task_duration" class="block text-sm font-medium text-gray-700 mb-1">Duración estimada (minutos)</label>
                    <input type="number" id="quick_task_duration" name="duration_minutes" min="5" max="480" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Se programará automáticamente en la agenda del técnico asignado.</p>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" class="close-quick-task px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 flex items-center gap-2">
                        <span id="quickTaskSubmitText">Crear Tarea</span>
                        <span id="quickTaskSpinner" class="hidden">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
            </form>
        </div>
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

function setupQuickTaskModal() {
    const modal = document.getElementById('quickTaskModal');
    const form = document.getElementById('quickTaskForm');
    const openButtons = document.querySelectorAll('.open-quick-task');
    const closeButtons = document.querySelectorAll('.close-quick-task');
    const submitText = document.getElementById('quickTaskSubmitText');
    const spinner = document.getElementById('quickTaskSpinner');
    const tasksList = document.getElementById('tasksList');
    const emptyState = document.getElementById('tasksEmptyState');
    const tasksCountLabel = document.getElementById('tasksCountLabel');
    const typeSelect = document.getElementById('quick_task_type');
    const durationInput = document.getElementById('quick_task_duration');
    const errorBox = document.getElementById('quickTaskError');
    const titleInput = document.getElementById('quick_task_title');

    if (!modal || !form) {
        return;
    }

    const toggleModal = (show) => {
        if (show) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            form.reset();
            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
            }
            if (titleInput) {
                titleInput.classList.remove('border-red-500');
            }
            submitText.textContent = 'Crear Tarea';
            spinner.classList.add('hidden');
        }
    };

    openButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.disabled === 'true') {
                alert('Debes asignar un técnico y tener la solicitud en un estado editable para crear tareas rápidas.');
                return;
            }
            toggleModal(true);
            document.getElementById('quick_task_title').focus();
        });
    });

    closeButtons.forEach(button => button.addEventListener('click', () => toggleModal(false)));

    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            if (typeSelect.value === 'impact') {
                durationInput.value = 90;
            } else {
                durationInput.value = 25;
            }
        });
    }

    const updateTaskCount = (delta) => {
        if (!tasksCountLabel) return;
        const current = parseInt(tasksCountLabel.dataset.count || '0', 10) + delta;
        tasksCountLabel.dataset.count = current;
        tasksCountLabel.textContent = `${current} tarea(s) ${current === 1 ? 'registrada' : 'registradas'}`;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (errorBox) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }
        submitText.textContent = 'Creando...';
        spinner.classList.remove('hidden');
        const formData = new FormData(form);
        form.querySelectorAll('input, textarea, select, button').forEach(el => el.disabled = true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                if (errorBox) {
                    errorBox.textContent = data.message || 'Error al crear la tarea.';
                    errorBox.classList.remove('hidden');
                }
                if (data.errors && data.errors.title && titleInput) {
                    titleInput.focus();
                    titleInput.classList.add('border-red-500');
                } else if (titleInput) {
                    titleInput.classList.remove('border-red-500');
                }
                return;
            } else if (titleInput) {
                titleInput.classList.remove('border-red-500');
            }

            if (tasksList && data.html) {
                tasksList.insertAdjacentHTML('afterbegin', data.html);
                tasksList.classList.remove('hidden');
            }
            if (emptyState) {
                emptyState.classList.add('hidden');
            }
            updateTaskCount(1);
            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
            }

            showNotification(data.message, 'success');
            toggleModal(false);
        } catch (error) {
            console.error(error);
            if (errorBox) {
                errorBox.textContent = error.message || 'Error al crear la tarea.';
                errorBox.classList.remove('hidden');
            } else {
                showNotification(error.message || 'Error al crear la tarea', 'error');
            }
        } finally {
            spinner.classList.add('hidden');
            submitText.textContent = 'Crear Tarea';
            form.querySelectorAll('input, textarea, select, button').forEach(el => el.disabled = false);
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            toggleModal(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            toggleModal(false);
        }
    });
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

document.addEventListener('DOMContentLoaded', setupQuickTaskModal);
</script>
