@extends('layouts.app')

@section('title', 'Tarea ' . $task->task_code)

@php
    $statusConfig = [
        'pending' => ['bg' => 'bg-slate-600', 'light' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-clock', 'label' => 'Pendiente'],
        'confirmed' => ['bg' => 'bg-slate-700', 'light' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'fa-check-circle', 'label' => 'Confirmada'],
        'in_progress' => ['bg' => 'bg-blue-700', 'light' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fa-play-circle', 'label' => 'En Progreso'],
        'blocked' => ['bg' => 'bg-red-700', 'light' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'fa-ban', 'label' => 'Bloqueada'],
        'in_review' => ['bg' => 'bg-amber-600', 'light' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-eye', 'label' => 'En Revisión'],
        'completed' => ['bg' => 'bg-emerald-700', 'light' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-check-double', 'label' => 'Completada'],
        'cancelled' => ['bg' => 'bg-gray-600', 'light' => 'bg-gray-100 text-gray-600 border-gray-200', 'icon' => 'fa-times-circle', 'label' => 'Cancelada'],
        'rescheduled' => ['bg' => 'bg-orange-600', 'light' => 'bg-orange-50 text-orange-700 border-orange-200', 'icon' => 'fa-calendar-alt', 'label' => 'Reprogramada']
    ];
    $status = $statusConfig[$task->status] ?? $statusConfig['pending'];
    
    $priorityConfig = [
        'critical' => ['color' => 'red', 'label' => 'Crítica', 'icon' => 'fa-exclamation-circle'],
        'high' => ['color' => 'orange', 'label' => 'Alta', 'icon' => 'fa-arrow-up'],
        'medium' => ['color' => 'amber', 'label' => 'Media', 'icon' => 'fa-minus'],
        'low' => ['color' => 'slate', 'label' => 'Baja', 'icon' => 'fa-arrow-down'],
        'urgent' => ['color' => 'red', 'label' => 'Urgente', 'icon' => 'fa-bolt']
    ];
    $priority = $priorityConfig[$task->priority] ?? $priorityConfig['medium'];
    
    $completedSubtasks = $task->subtasks->filter(function($subtask) {
        return $subtask->is_completed || $subtask->status === 'completed';
    })->count();
    $totalSubtasks = $task->subtasks->count();
    $progressPercent = $totalSubtasks > 0 ? round(($completedSubtasks / $totalSubtasks) * 100) : 0;
    
    $isOverdue = $task->due_date && $task->due_date->isPast() && !in_array($task->status, ['completed', 'cancelled']);
    $isDueSoon = $task->due_date && $task->due_date->isFuture() && $task->due_date->diffInHours(now()) <= 24;
@endphp

@section('content')
<!-- Flash Messages -->
@if(session('success'))
    <div class="fixed top-20 right-4 z-50 animate-slide-in">
        <div class="bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 max-w-md">
            <i class="fas fa-check-circle text-2xl"></i>
            <div>
                <p class="font-semibold">¡Éxito!</p>
                <p class="text-sm">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="fixed top-20 right-4 z-50 animate-slide-in">
        <div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 max-w-md">
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p class="text-sm">{{ session('error') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif

@if(session('warning'))
    <div class="fixed top-20 right-4 z-50 animate-slide-in">
        <div class="bg-orange-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 max-w-md">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
            <div>
                <p class="font-semibold">Advertencia</p>
                <p class="text-sm">{{ session('warning') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Inicio</a>
        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
        <a href="{{ route('tasks.index') }}" class="hover:text-gray-700">Tareas</a>
        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
        <span class="text-gray-900 font-medium">{{ $task->task_code }}</span>
    </nav>

    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 {{ $status['bg'] }} rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas {{ $status['icon'] }} text-lg"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ $task->task_code }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium border {{ $status['light'] }}">
                                {{ $status['label'] }}
                            </span>
                            @if($task->is_critical)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Crítica
                                </span>
                            @endif
                        </div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $task->title }}</h1>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('tasks.edit', $task) }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <i class="fas fa-pencil-alt mr-2 text-gray-400"></i>Editar
                    </a>
                    <button onclick="confirmDelete()" 
                            class="inline-flex items-center px-3 py-2 border border-red-200 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                        <i class="fas fa-trash-alt mr-2"></i>Eliminar
                    </button>
                    <a href="{{ route('tasks.index') }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left mr-2 text-gray-400"></i>Volver
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Meta Info Bar -->
        <div class="px-6 py-3 bg-gray-50 flex flex-wrap items-center gap-6 text-sm">
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-calendar text-gray-400"></i>
                <span>{{ $task->scheduled_date->format('d/m/Y') }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-clock text-gray-400"></i>
                <span>{{ $task->scheduled_start_time ? substr($task->scheduled_start_time, 0, 5) : '--:--' }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-hourglass-half text-gray-400"></i>
                <span>{{ $task->estimated_hours }}h estimadas</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas {{ $priority['icon'] }} text-{{ $priority['color'] }}-500"></i>
                <span class="text-{{ $priority['color'] }}-700 font-medium">{{ $priority['label'] }}</span>
            </div>
            @if($task->technician && $task->technician->user)
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-user text-gray-400"></i>
                <span>{{ $task->technician->user->name }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Alert Banners -->
    @if($isOverdue)
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-600"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-red-800">Tarea vencida</p>
            <p class="text-sm text-red-600">Fecha límite: {{ $task->due_date->format('d/m/Y') }}. Requiere atención inmediata.</p>
        </div>
    </div>
    @elseif($isDueSoon)
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-3">
        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-clock text-amber-600"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-amber-800">Próxima a vencer</p>
            <p class="text-sm text-amber-600">Vence {{ $task->due_date->diffForHumans() }} ({{ $task->due_date->format('d/m/Y H:i') }})</p>
        </div>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            @if($task->description)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Descripción</h3>
                </div>
                <div class="px-5 py-4">
                    <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $task->description }}</p>
                </div>
            </div>
            @endif

            <!-- Subtasks -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Subtareas</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $completedSubtasks }} de {{ $totalSubtasks }} completadas</p>
                    </div>
                    <button onclick="toggleSubtaskForm()" 
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                        <i class="fas fa-plus mr-1.5"></i>Agregar
                    </button>
                </div>
                
                <!-- Progress Bar -->
                @if($totalSubtasks > 0)
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-600 min-w-[2.5rem] text-right">{{ $progressPercent }}%</span>
                    </div>
                </div>
                @endif
                
                <!-- Add Form -->
                <div id="subtaskForm" class="hidden px-5 py-4 bg-blue-50 border-b border-blue-100">
                    <form action="{{ route('tasks.subtasks.store', $task) }}" method="POST" class="flex flex-wrap gap-2">
                        @csrf
                        <input type="text" name="title" placeholder="Descripción de la subtarea..." required
                               class="flex-1 min-w-[200px] rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="number" name="estimated_minutes" value="25" min="5" step="5" 
                               class="w-20 rounded border-gray-300 text-sm text-center" title="Minutos">
                        <select name="priority" class="rounded border-gray-300 text-sm">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                        </select>
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">
                            Agregar
                        </button>
                        <button type="button" onclick="toggleSubtaskForm()" class="px-2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Subtasks List -->
                <div class="divide-y divide-gray-100">
                    @forelse($task->subtasks as $subtask)
                        <div class="group px-5 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3"
                             id="subtask-item-{{ $subtask->id }}">
                            <button type="button"
                                    class="subtask-toggle w-5 h-5 rounded border-2 flex items-center justify-center transition-all {{ $subtask->isCompleted() ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 hover:border-blue-500' }}"
                                    data-task-id="{{ $task->id }}"
                                    data-subtask-id="{{ $subtask->id }}"
                                    data-url="{{ route('tasks.subtasks.toggle', [$task, $subtask]) }}"
                                    {{ $task->status === 'completed' ? 'disabled' : '' }}>
                                @if($subtask->isCompleted())
                                    <i class="fas fa-check text-xs"></i>
                                @endif
                            </button>
                            
                            <div class="flex-1 min-w-0">
                                <p class="subtask-title text-sm {{ $subtask->isCompleted() ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                    {{ $subtask->title }}
                                </p>
                                <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500">
                                    <span>{{ $subtask->estimated_minutes ?? 25 }} min</span>
                                    @if($subtask->isCompleted() && $subtask->completed_at)
                                        <span class="text-green-600">Completada {{ $subtask->completed_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            @php
                                $subtaskPriorityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded border {{ $subtask->priority === 'high' ? 'border-orange-200 bg-orange-50 text-orange-700' : ($subtask->priority === 'low' ? 'border-gray-200 bg-gray-50 text-gray-600' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                                {{ $subtaskPriorityLabels[$subtask->priority] ?? 'Media' }}
                            </span>
                            
                            <button type="button"
                                    class="subtask-delete opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all p-1"
                                    data-task-id="{{ $task->id }}"
                                    data-subtask-id="{{ $subtask->id }}"
                                    data-url="{{ route('tasks.subtasks.destroy', [$task, $subtask]) }}">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center">
                            <i class="fas fa-tasks text-2xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">No hay subtareas definidas</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- History Timeline -->
            @if($task->history && $task->history->count() > 0)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Historial de Actividad</h3>
                </div>
                <div class="px-5 py-4">
                    <div class="relative">
                        <div class="absolute left-2 top-0 bottom-0 w-px bg-gray-200"></div>
                        <div class="space-y-4">
                            @foreach($task->history as $history)
                                @php
                                    $actionConfig = [
                                        'created' => ['icon' => 'fa-plus', 'color' => 'green', 'label' => 'Creada'],
                                        'assigned' => ['icon' => 'fa-user-plus', 'color' => 'blue', 'label' => 'Asignada'],
                                        'started' => ['icon' => 'fa-play', 'color' => 'blue', 'label' => 'Iniciada'],
                                        'completed' => ['icon' => 'fa-check', 'color' => 'green', 'label' => 'Completada'],
                                        'blocked' => ['icon' => 'fa-ban', 'color' => 'red', 'label' => 'Bloqueada'],
                                        'unblocked' => ['icon' => 'fa-unlock', 'color' => 'blue', 'label' => 'Desbloqueada'],
                                        'updated' => ['icon' => 'fa-edit', 'color' => 'gray', 'label' => 'Actualizada'],
                                        'rescheduled' => ['icon' => 'fa-calendar-alt', 'color' => 'orange', 'label' => 'Reprogramada']
                                    ];
                                    $action = $actionConfig[$history->action] ?? ['icon' => 'fa-circle', 'color' => 'gray', 'label' => ucfirst($history->action)];
                                @endphp
                                <div class="relative pl-7">
                                    <div class="absolute left-0 top-1 w-4 h-4 rounded-full bg-{{ $action['color'] }}-100 border-2 border-{{ $action['color'] }}-500 flex items-center justify-center">
                                        <i class="fas {{ $action['icon'] }} text-{{ $action['color'] }}-600" style="font-size: 8px;"></i>
                                    </div>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $action['label'] }}</span>
                                            @if($history->notes)
                                                <p class="text-sm text-gray-600 mt-0.5">{{ $history->notes }}</p>
                                            @endif
                                            @if($history->user)
                                                <p class="text-xs text-gray-400 mt-1">por {{ $history->user->name }}</p>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-400 flex-shrink-0 ml-4">{{ $history->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions Card -->
            @if(!in_array($task->status, ['completed', 'cancelled']))
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Acciones</h3>
                </div>
                <div class="p-4 space-y-2">
                    @if(in_array($task->status, ['pending', 'confirmed']))
                        <form action="{{ route('tasks.start', $task) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-md text-sm font-medium transition">
                                <i class="fas fa-play"></i>
                                Iniciar Tarea
                            </button>
                        </form>
                    @endif
                    
                    @if($task->status === 'in_progress')
                        <form action="{{ route('tasks.complete', $task) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 px-4 rounded-md text-sm font-medium transition">
                                <i class="fas fa-check-double"></i>
                                Completar Tarea
                            </button>
                        </form>
                        
                        <button onclick="showBlockModal()" class="w-full flex items-center justify-center gap-2 border border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100 py-2.5 px-4 rounded-md text-sm font-medium transition">
                            <i class="fas fa-ban"></i>
                            Reportar Bloqueo
                        </button>
                    @endif
                    
                    @if($task->status === 'blocked')
                        <form action="{{ route('tasks.unblock', $task) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-md text-sm font-medium transition">
                                <i class="fas fa-unlock"></i>
                                Resolver Bloqueo
                            </button>
                        </form>
                        @if($task->block_reason)
                            <div class="p-3 bg-red-50 border border-red-100 rounded-md mt-3">
                                <p class="text-xs font-medium text-red-700 mb-1">Motivo del bloqueo:</p>
                                <p class="text-sm text-red-600">{{ $task->block_reason }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            @else
                <div class="bg-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-50 border border-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-200 rounded-lg p-5 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-100 flex items-center justify-center">
                        <i class="fas {{ $task->status === 'completed' ? 'fa-check-double' : 'fa-times-circle' }} text-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-600 text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-800">
                        {{ $task->status === 'completed' ? 'Tarea Completada' : 'Tarea Cancelada' }}
                    </h3>
                    @if($task->completed_at)
                        <p class="text-sm text-{{ $task->status === 'completed' ? 'emerald' : 'gray' }}-600 mt-1">
                            {{ $task->completed_at->format('d/m/Y H:i') }}
                        </p>
                    @endif
                </div>
            @endif

            <!-- Details Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Detalles</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Técnico</span>
                        <span class="text-sm font-medium text-gray-900">{{ $task->technician->user?->name ?? '—' }}</span>
                    </div>
                    @if($task->service_request_id)
                    <a href="{{ route('service-requests.show', $task->service_request_id) }}" 
                       class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition">
                        <span class="text-sm text-gray-500">Solicitud</span>
                        <span class="text-sm font-medium text-blue-600 hover:underline">
                            #{{ $task->serviceRequest?->ticket_number ?? $task->service_request_id }}
                            <i class="fas fa-external-link-alt text-xs ml-1"></i>
                        </span>
                    </a>
                    @endif
                    @if($task->project_id)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Proyecto</span>
                        <span class="text-sm font-medium text-gray-900">{{ $task->project?->name }}</span>
                    </div>
                    @endif
                    @if($task->due_date)
                    <div class="px-5 py-3 flex items-center justify-between {{ $isOverdue ? 'bg-red-50' : ($isDueSoon ? 'bg-amber-50' : '') }}">
                        <span class="text-sm {{ $isOverdue ? 'text-red-600' : ($isDueSoon ? 'text-amber-600' : 'text-gray-500') }}">
                            Fecha límite
                        </span>
                        <span class="text-sm font-medium {{ $isOverdue ? 'text-red-700' : ($isDueSoon ? 'text-amber-700' : 'text-gray-900') }}">
                            {{ $task->due_date->format('d/m/Y') }}
                            @if($task->due_time) {{ $task->due_time }} @endif
                        </span>
                    </div>
                    @endif
                    @if($task->requires_evidence)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Evidencia</span>
                        <span class="text-sm font-medium {{ $task->evidence_completed ? 'text-green-600' : 'text-orange-600' }}">
                            <i class="fas {{ $task->evidence_completed ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-1"></i>
                            {{ $task->evidence_completed ? 'Completada' : 'Requerida' }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Block Modal -->
<div id="blockModal" class="fixed inset-0 bg-gray-900/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-ban text-orange-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Reportar Bloqueo</h3>
        </div>
        <form action="{{ route('tasks.block', $task) }}" method="POST" class="p-5">
            @csrf
            <p class="text-sm text-gray-600 mb-4">Describa el motivo por el cual esta tarea no puede continuar:</p>
            <textarea name="block_reason" rows="3" required
                      class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                      placeholder="Ej: Esperando accesos, dependencia de otro equipo..."></textarea>
            <div class="flex gap-3 mt-5">
                <button type="button" onclick="hideBlockModal()" 
                        class="flex-1 py-2 px-4 border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 rounded-md text-sm font-medium transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 py-2 px-4 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-sm font-medium transition">
                    Confirmar Bloqueo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" action="{{ route('tasks.destroy', $task) }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<style>
.toast {
    position: fixed;
    top: 80px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    color: white;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: slideIn 0.3s ease;
}
.toast-success { background: #059669; }
.toast-error { background: #dc2626; }
.toast-info { background: #2563eb; }
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}
.animate-slide-in {
    animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.animate-fade-out {
    animation: fadeOut 0.3s ease-out forwards;
}
</style>

<script>
// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.animate-slide-in');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.classList.add('animate-fade-out');
            setTimeout(function() {
                message.remove();
            }, 300);
        }, 5000);
    });
});

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
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

function showBlockModal() {
    document.getElementById('blockModal').classList.remove('hidden');
}

function hideBlockModal() {
    document.getElementById('blockModal').classList.add('hidden');
}

function confirmDelete() {
    if (confirm('¿Está seguro de eliminar esta tarea? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteForm').submit();
    }
}

function updateProgressBar() {
    const allSubtasks = document.querySelectorAll('[id^="subtask-item-"]');
    const completedSubtasks = document.querySelectorAll('.subtask-toggle.bg-blue-600');
    const total = allSubtasks.length;
    const completed = completedSubtasks.length;
    
    if (total > 0) {
        const percent = Math.round((completed / total) * 100);
        
        // Actualizar barra de progreso
        const progressBar = document.querySelector('.bg-blue-600.rounded-full.transition-all');
        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }
        
        // Actualizar porcentaje
        const percentText = document.querySelector('.text-xs.font-semibold.text-gray-600.min-w-\\[2\\.5rem\\]');
        if (percentText) {
            percentText.textContent = `${percent}%`;
        }
        
        // Actualizar contador de subtareas
        const subtaskCounter = document.querySelector('h3.text-sm.font-semibold.text-gray-900.uppercase + p.text-xs.text-gray-500');
        if (subtaskCounter) {
            subtaskCounter.textContent = `${completed} de ${total} completadas`;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle subtareas
    document.querySelectorAll('.subtask-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            const subtaskId = this.dataset.subtaskId;
            const item = document.getElementById(`subtask-item-${subtaskId}`);
            const isCompleted = this.classList.contains('bg-blue-600');
            
            this.disabled = true;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_completed: !isCompleted })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const title = item.querySelector('.subtask-title');
                    
                    if (data.is_completed) {
                        this.classList.remove('border-gray-300');
                        this.classList.add('bg-blue-600', 'border-blue-600', 'text-white');
                        this.innerHTML = '<i class="fas fa-check text-xs"></i>';
                        title.classList.add('line-through', 'text-gray-400');
                        title.classList.remove('text-gray-900');
                        showToast('Subtarea completada', 'success');
                    } else {
                        this.classList.add('border-gray-300');
                        this.classList.remove('bg-blue-600', 'border-blue-600', 'text-white');
                        this.innerHTML = '';
                        title.classList.remove('line-through', 'text-gray-400');
                        title.classList.add('text-gray-900');
                        showToast('Subtarea marcada como pendiente', 'info');
                    }
                    
                    // Actualizar barra de progreso
                    updateProgressBar();
                    
                    if (data.status_changed) {
                        setTimeout(() => window.location.reload(), 500);
                    }
                }
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al actualizar', 'error');
                this.disabled = false;
            });
        });
    });

    // Eliminar subtareas
    document.querySelectorAll('.subtask-delete').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('¿Eliminar esta subtarea?')) return;
            
            const url = this.dataset.url;
            const subtaskId = this.dataset.subtaskId;
            const item = document.getElementById(`subtask-item-${subtaskId}`);
            
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    item.style.opacity = '0';
                    item.style.transition = 'opacity 0.3s';
                    setTimeout(() => {
                        item.remove();
                        showToast('Subtarea eliminada', 'success');
                        // Actualizar barra de progreso
                        updateProgressBar();
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al eliminar', 'error');
            });
        });
    });

    // Close modal handlers
    document.getElementById('blockModal').addEventListener('click', function(e) {
        if (e.target === this) hideBlockModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') hideBlockModal();
    });
});
</script>
@endsection
