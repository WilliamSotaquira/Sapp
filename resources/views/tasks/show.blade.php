@extends('layouts.app')

@section('title', 'Detalle de Tarea')

@section('content')
<!-- Header con acciones -->
<div class="bg-white shadow rounded-lg mb-4">
    <div class="px-4 py-3 sm:px-6 flex flex-wrap justify-between items-center gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{ $task->task_code }}</span>
            @php
                $statusConfig = [
                    'pending' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-clock'],
                    'confirmed' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'icon' => 'fa-check-circle'],
                    'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-spinner'],
                    'blocked' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-ban'],
                    'in_review' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-eye'],
                    'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-double'],
                    'cancelled' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-600', 'icon' => 'fa-times-circle']
                ];
                $status = $statusConfig[$task->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question'];
            @endphp
            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded {{ $status['bg'] }} {{ $status['text'] }}">
                <i class="fas {{ $status['icon'] }} mr-1"></i>
                {{ ucfirst($task->status) }}
            </span>
            <span class="text-lg font-bold text-gray-900">{{ $task->title }}</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tasks.edit', $task) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-sm">
                <i class="fas fa-edit"></i>
            </a>
            <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar tarea?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <a href="{{ route('tasks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded text-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>
</div>

<!-- Información principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <!-- Columna principal (2/3) -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Descripción -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Descripción</h3>
            <p class="text-gray-600 text-sm">{{ $task->description }}</p>
        </div>

        <!-- Subtareas -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-tasks text-red-600"></i>
                    Subtareas
                    @php
                        $completedCount = $task->subtasks->where('is_completed', true)->count();
                        $totalCount = $task->subtasks->count();
                    @endphp
                    <span class="text-xs text-gray-500">({{ $completedCount }}/{{ $totalCount }})</span>
                </h3>
                <button onclick="toggleSubtaskForm()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                    <i class="fas fa-plus mr-1"></i>Nueva
                </button>
            </div>

            <!-- Barra de progreso -->
            @if($task->subtasks->count() > 0)
                @php
                    $progressPercent = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">Progreso</span>
                        <span class="font-semibold text-red-600">{{ $progressPercent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            @endif

            <!-- Formulario -->
            <div id="subtaskForm" class="hidden mb-3">
                <form action="{{ route('tasks.subtasks.store', $task) }}" method="POST">
                    @csrf
                    <div class="flex gap-2">
                        <input type="text" name="title" placeholder="Nueva subtarea..." required
                               class="flex-1 rounded border-gray-300 text-sm focus:border-red-500 focus:ring-red-500">
                        <select name="priority" class="rounded border-gray-300 text-sm focus:border-red-500 focus:ring-red-500 px-3 py-1.5">
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                            <option value="low">Baja</option>
                        </select>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" onclick="toggleSubtaskForm()" class="text-gray-400 hover:text-gray-600 px-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista -->
            @if($task->subtasks->count() > 0)
                <div class="space-y-1.5">
                    @foreach($task->subtasks as $subtask)
                        <div class="group flex items-center gap-3 p-2 border rounded hover:bg-gray-50 transition-colors"
                             id="subtask-item-{{ $subtask->id }}">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        class="subtask-toggle text-lg {{ $subtask->isCompleted() ? 'text-green-600' : 'text-gray-300' }}"
                                        data-task-id="{{ $task->id }}"
                                        data-subtask-id="{{ $subtask->id }}"
                                        data-url="{{ route('tasks.subtasks.toggle', [$task, $subtask]) }}"
                                        {{ $task->status === 'completed' ? 'disabled' : '' }}>
                                    <i class="fas {{ $subtask->isCompleted() ? 'fa-check-circle' : 'fa-circle' }}"></i>
                                </button>
                                <button type="button"
                                        class="subtask-action px-3 py-1 rounded-full text-xs font-semibold border transition-colors {{ $subtask->isCompleted() ? 'bg-green-100 text-green-700 border-green-200' : 'bg-blue-50 text-blue-600 border-blue-100' }}"
                                        data-task-id="{{ $task->id }}"
                                        data-subtask-id="{{ $subtask->id }}"
                                        data-url="{{ route('tasks.subtasks.toggle', [$task, $subtask]) }}"
                                        {{ $task->status === 'completed' ? 'disabled' : '' }}>
                                    {{ $subtask->isCompleted() ? 'Completada' : 'Marcar como completa' }}
                                </button>
                            </div>
                            <span class="flex-1 subtask-title text-sm break-words whitespace-pre-line {{ $subtask->isCompleted() ? 'line-through text-gray-500' : 'text-gray-700' }}">
                                {{ $subtask->title }}
                            </span>
                            @php
                                $priorityConfig = [
                                    'high' => ['icon' => '🔴'],
                                    'medium' => ['icon' => '🟡'],
                                    'low' => ['icon' => '🟢']
                                ];
                                $priority = $priorityConfig[$subtask->priority] ?? $priorityConfig['medium'];
                            @endphp
                            <span class="subtask-indicator text-xs {{ $subtask->isCompleted() ? 'text-green-500' : 'text-red-500' }}" data-default-icon="{{ $priority['icon'] }}">
                                {{ $subtask->isCompleted() ? '✓' : $priority['icon'] }}
                            </span>
                            <button type="button"
                                    class="subtask-delete opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 transition-opacity"
                                    data-task-id="{{ $task->id }}"
                                    data-subtask-id="{{ $subtask->id }}"
                                    data-url="{{ route('tasks.subtasks.destroy', [$task, $subtask]) }}">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-400 text-sm text-center py-4">No hay subtareas</p>
            @endif
        </div>

        <!-- Historial -->
        @if($task->history && $task->history->count() > 0)
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-history text-red-600"></i>
                    Historial
                </h3>
                <div class="space-y-2">
                    @foreach($task->history as $history)
                        <div class="border-l-2 border-blue-500 pl-3 py-1">
                            <div class="flex justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $history->action }}</p>
                                <span class="text-xs text-gray-500">{{ $history->created_at->diffForHumans() }}</span>
                            </div>
                            @if($history->notes)
                                <p class="text-xs text-gray-600 mt-1">{{ $history->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Columna lateral (1/3) -->
    <div class="space-y-4">
        <!-- Información General -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-info-circle text-red-600"></i>
                Información
            </h3>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500">Tipo</dt>
                    <dd class="mt-1">
                        @if($task->type === 'impact')
                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800">
                                <i class="fas fa-star"></i> Impacto
                            </span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                Regular
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Prioridad</dt>
                    <dd class="mt-1">
                        @php
                            $priorityColors = [
                                'urgent' => 'bg-red-100 text-red-800',
                                'high' => 'bg-orange-100 text-orange-800',
                                'medium' => 'bg-yellow-100 text-yellow-800',
                                'low' => 'bg-green-100 text-green-800'
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $priorityColors[$task->priority] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($task->priority) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fecha</dt>
                    <dd class="mt-1 text-gray-900">
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $task->scheduled_date->format('d/m/Y') }}
                        @if($task->scheduled_start_time)
                            {{ substr($task->scheduled_start_time, 0, 5) }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Duración</dt>
                    <dd class="mt-1 text-gray-900">
                        <i class="fas fa-clock mr-1"></i>
                        {{ $task->estimated_hours }} hrs
                        @if($task->actual_hours)
                            <span class="text-xs text-gray-500">(Real: {{ $task->actual_hours }} hrs)</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Asignación -->
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-user-check text-red-600"></i>
                Asignación
            </h3>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-500">Técnico</dt>
                    <dd class="mt-1">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-circle text-xl text-gray-400"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $task->technician->user?->name ?? 'Sin asignar' }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($task->technician->specialization) }}</p>
                            </div>
                        </div>
                    </dd>
                </div>
                @if($task->service_request_id)
                    <div>
                        <dt class="text-gray-500">Solicitud</dt>
                        <dd class="mt-1">
                            <a href="{{ route('service-requests.show', $task->service_request_id) }}" class="text-blue-600 hover:underline text-sm">
                                <i class="fas fa-ticket-alt mr-1"></i>
                                #{{ $task->service_request_id }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if($task->project_id)
                    <div>
                        <dt class="text-gray-500">Proyecto</dt>
                        <dd class="mt-1 text-gray-900">
                            <i class="fas fa-project-diagram mr-1"></i>
                            {{ $task->project->name }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        @php
            $totalSubtasks = $task->subtasks->count();
            $pendingSubtasks = $task->subtasks->where('status', '!=', 'completed')->count();
            $shouldShowCompleteButton = $totalSubtasks === 0 || $pendingSubtasks > 0;
        @endphp

        <!-- Acciones -->
        @if($task->status !== 'completed' && $task->status !== 'cancelled')
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-cogs text-red-600"></i>
                    Acciones
                </h3>
                <div class="flex flex-wrap gap-2">
                    @if($task->status === 'confirmed' || $task->status === 'pending')
                        <form action="{{ route('tasks.start', $task) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm">
                                <i class="fas fa-play mr-1"></i>
                                Iniciar
                            </button>
                        </form>
                    @endif

                    @if($task->status === 'in_progress')
                        @if($shouldShowCompleteButton)
                        <form action="{{ route('tasks.complete', $task) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm">
                                <i class="fas fa-check mr-1"></i>
                                Completar
                            </button>
                        </form>
                        @endif

                        <form action="{{ route('tasks.block', $task) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded text-sm">
                                <i class="fas fa-ban mr-1"></i>
                                Bloquear
                            </button>
                        </form>
                    @endif

                    @if($task->status === 'blocked')
                        <form action="{{ route('tasks.unblock', $task) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm">
                                <i class="fas fa-unlock mr-1"></i>
                                Desbloquear
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.toast {
    position: fixed;
    top: 80px;
    right: 20px;
    padding: 12px 16px;
    border-radius: 6px;
    color: white;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
}
.toast-success { background: #10b981; }
.toast-error { background: #ef4444; }
.toast-info { background: #3b82f6; }
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<script>
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    toast.innerHTML = `<i class="fas ${icon} mr-2"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function toggleSubtaskForm() {
    const form = document.getElementById('subtaskForm');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        form.querySelector('input[name="title"]').focus();
    }
}

function updateProgress() {
    const subtasks = document.querySelectorAll('[id^="subtask-item-"]');
    const completed = document.querySelectorAll('.subtask-toggle.text-green-600').length;
    const total = subtasks.length;
    if (total > 0) {
        const percent = Math.round((completed / total) * 100);
        const progressBar = document.querySelector('.bg-red-600.h-2');
        if (progressBar) {
            progressBar.style.width = `${percent}%`;
            progressBar.parentElement.previousElementSibling.querySelector('.text-red-600').textContent = `${percent}%`;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle subtareas
    document.querySelectorAll('.subtask-toggle, .subtask-action').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            const subtaskId = this.dataset.subtaskId;
            const item = document.getElementById(`subtask-item-${subtaskId}`);
            const icon = item.querySelector('.subtask-toggle i');
            const toggleBtn = item.querySelector('.subtask-toggle');
            const actionBtn = item.querySelector('.subtask-action');
            const title = item.querySelector('.subtask-title');
            const willComplete = !icon.classList.contains('fa-check-circle');

            [toggleBtn, actionBtn].forEach(btn => btn.disabled = true);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_completed: willComplete })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.is_completed) {
                        icon.classList.remove('fa-circle');
                        icon.classList.add('fa-check-circle');
                        toggleBtn.classList.remove('text-gray-300');
                        toggleBtn.classList.add('text-green-600');
                        title.classList.add('line-through', 'text-gray-500');
                        title.classList.remove('text-gray-700');
                        actionBtn.textContent = 'Completada';
                        actionBtn.classList.remove('bg-blue-50', 'text-blue-600', 'border-blue-100');
                        actionBtn.classList.add('bg-green-100', 'text-green-700', 'border-green-200');
                        showToast('✓ Completada', 'success');
                    } else {
                        icon.classList.remove('fa-check-circle');
                        icon.classList.add('fa-circle');
                        toggleBtn.classList.remove('text-green-600');
                        toggleBtn.classList.add('text-gray-300');
                        title.classList.remove('line-through', 'text-gray-500');
                        title.classList.add('text-gray-700');
                        actionBtn.textContent = 'Marcar como completa';
                        actionBtn.classList.remove('bg-green-100', 'text-green-700', 'border-green-200');
                        actionBtn.classList.add('bg-blue-50', 'text-blue-600', 'border-blue-100');
                        showToast('Pendiente', 'info');
                    }
                    updateProgress();
                    const indicator = item.querySelector('.subtask-indicator');
                    if (indicator) {
                        if (data.is_completed) {
                            indicator.textContent = '✓';
                            indicator.classList.remove('text-red-500');
                            indicator.classList.add('text-green-500');
                        } else {
                            indicator.textContent = indicator.dataset.defaultIcon || '🟡';
                            indicator.classList.remove('text-green-500');
                            indicator.classList.add('text-red-500');
                        }
                    }
                    if (data.status_changed) {
                        window.location.reload();
                        return;
                    }
                }
                [toggleBtn, actionBtn].forEach(btn => btn.disabled = false);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'error');
                [toggleBtn, actionBtn].forEach(btn => btn.disabled = false);
            });
        });
    });

    // Eliminar subtareas
    document.querySelectorAll('.subtask-delete').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('¿Eliminar?')) return;

            const url = this.dataset.url;
            const subtaskId = this.dataset.subtaskId;
            const item = document.getElementById(`subtask-item-${subtaskId}`);

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.remove();
                        showToast('Eliminada', 'success');
                        updateProgress();
                        if (document.querySelectorAll('[id^="subtask-item-"]').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'error');
            });
        });
    });
});
</script>
@endsection
