@extends('layouts.app')

@section('title', 'Gestión de Tareas')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li>
            <a href="{{ route('technician-schedule.index') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-calendar-alt"></i>
                <span class="ml-1">Calendario</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-tasks"></i>
            <span class="ml-1">Gestión de Tareas</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <p class="text-gray-600 text-sm sm:text-base">Administra todas las tareas del equipo técnico</p>
            </div>
            <a href="{{ route('tasks.create') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>
                Nueva Tarea
            </a>
        </div>
    </div>

    <!-- Filtros Sidebar -->
    @php
        $activeFilterCount = collect([
            request('status'),
            request('type'),
            request('priority'),
            request('technician_id'),
            request('date'),
        ])->filter(fn($v) => filled($v))->count();
    @endphp
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                <span class="font-semibold text-gray-800">Filtros</span>
                <span class="ml-2">Activos: {{ $activeFilterCount }}</span>
                @if(filled(request('queue_strategy')))
                    <span class="ml-2 px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs uppercase">
                        {{ request('queue_strategy') === 'auto' ? 'Automatica' : 'Manual' }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tasks.index') }}"
                   class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition-colors text-sm">
                    Limpiar
                </a>
                <button type="button"
                        id="openFiltersSidebar"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                    <i class="fas fa-sliders-h mr-2"></i>Abrir filtros
                </button>
            </div>
        </div>
    </div>

    <div id="filtersOverlay" class="hidden fixed inset-0 bg-black/40 z-40"></div>
    <aside id="filtersSidebar" class="fixed top-0 right-0 h-full w-full sm:w-[440px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-out">
        <div class="h-full flex flex-col">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Filtrar tareas</h3>
                <button type="button" id="closeFiltersSidebar" class="text-gray-500 hover:text-gray-800">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('tasks.index') }}" class="flex-1 overflow-y-auto p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">Todos</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                        <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Bloqueada</option>
                        <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>En Revisión</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">Todos</option>
                        <option value="impact" {{ request('type') == 'impact' ? 'selected' : '' }}>Impacto</option>
                        <option value="regular" {{ request('type') == 'regular' ? 'selected' : '' }}>Regular</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                    <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">Todas</option>
                        <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Crítica</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Media</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Técnico</label>
                    <select name="technician_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">Todos</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" {{ (string) request('technician_id') === (string) $technician->id ? 'selected' : '' }}>
                                {{ $technician->user?->name ?? 'Sin usuario' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                    <input type="date"
                           name="date"
                           value="{{ request('date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estrategia</label>
                    <select name="queue_strategy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="manual" {{ ($queueStrategy ?? 'manual') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="auto" {{ ($queueStrategy ?? 'manual') === 'auto' ? 'selected' : '' }}>Automática</option>
                    </select>
                </div>

                <input type="hidden" name="weight_priority_critical" value="{{ $queueWeights['priority_critical'] ?? 400 }}">
                <input type="hidden" name="weight_priority_high" value="{{ $queueWeights['priority_high'] ?? 300 }}">
                <input type="hidden" name="weight_priority_medium" value="{{ $queueWeights['priority_medium'] ?? 200 }}">
                <input type="hidden" name="weight_priority_low" value="{{ $queueWeights['priority_low'] ?? 100 }}">
                <input type="hidden" name="weight_priority_default" value="{{ $queueWeights['priority_default'] ?? 50 }}">
                <input type="hidden" name="weight_type_impact" value="{{ $queueWeights['type_impact'] ?? 120 }}">
                <input type="hidden" name="weight_type_regular" value="{{ $queueWeights['type_regular'] ?? 60 }}">
                <input type="hidden" name="weight_status_pending" value="{{ $queueWeights['status_pending'] ?? 140 }}">
                <input type="hidden" name="weight_status_in_progress" value="{{ $queueWeights['status_in_progress'] ?? 110 }}">
                <input type="hidden" name="weight_status_confirmed" value="{{ $queueWeights['status_confirmed'] ?? 90 }}">
                <input type="hidden" name="weight_status_blocked" value="{{ $queueWeights['status_blocked'] ?? 60 }}">
                <input type="hidden" name="weight_status_in_review" value="{{ $queueWeights['status_in_review'] ?? 40 }}">
                <input type="hidden" name="weight_status_completed" value="{{ $queueWeights['status_completed'] ?? -200 }}">
                <input type="hidden" name="weight_status_cancelled" value="{{ $queueWeights['status_cancelled'] ?? -300 }}">
                <input type="hidden" name="weight_status_default" value="{{ $queueWeights['status_default'] ?? 20 }}">
                <input type="hidden" name="weight_age_per_hour" value="{{ $queueWeights['age_per_hour'] ?? 1 }}">
                <input type="hidden" name="weight_age_cap" value="{{ $queueWeights['age_cap'] ?? 240 }}">

                <div class="pt-4 border-t border-gray-200 flex items-center gap-2">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>Aplicar filtros
                    </button>
                    <a href="{{ route('tasks.index') }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition-colors">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </aside>

    @if($manualQueueMode)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-3 text-sm">
            <i class="fas fa-list-ol mr-1"></i>
            Modo manual activo. Puedes agregar tareas a la lista y arrastrarlas para definir el orden.
        </div>
    @elseif($autoQueueMode)
        <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg px-4 py-3 text-sm flex items-center justify-between gap-4">
            <span>
                <i class="fas fa-brain mr-1"></i>
                Modo automático activo. El sistema calcula importancia por prioridad, tipo, estado y antigüedad.
            </span>
            <div class="flex items-center gap-2">
                <button type="button"
                        id="openWeightsModal"
                        class="px-3 py-1.5 bg-white hover:bg-gray-100 border border-blue-300 text-blue-800 rounded-lg text-xs font-semibold">
                    Configurar pesos
                </button>
                <form method="POST" action="{{ route('tasks.apply-auto-queue') }}" class="inline">
                    @csrf
                    <input type="hidden" name="scheduled_date" value="{{ request('date') }}">
                    <input type="hidden" name="technician_id" value="{{ request('technician_id') }}">
                    <input type="hidden" name="weight_priority_critical" value="{{ $queueWeights['priority_critical'] ?? 400 }}">
                    <input type="hidden" name="weight_priority_high" value="{{ $queueWeights['priority_high'] ?? 300 }}">
                    <input type="hidden" name="weight_priority_medium" value="{{ $queueWeights['priority_medium'] ?? 200 }}">
                    <input type="hidden" name="weight_priority_low" value="{{ $queueWeights['priority_low'] ?? 100 }}">
                    <input type="hidden" name="weight_priority_default" value="{{ $queueWeights['priority_default'] ?? 50 }}">
                    <input type="hidden" name="weight_type_impact" value="{{ $queueWeights['type_impact'] ?? 120 }}">
                    <input type="hidden" name="weight_type_regular" value="{{ $queueWeights['type_regular'] ?? 60 }}">
                    <input type="hidden" name="weight_status_pending" value="{{ $queueWeights['status_pending'] ?? 140 }}">
                    <input type="hidden" name="weight_status_in_progress" value="{{ $queueWeights['status_in_progress'] ?? 110 }}">
                    <input type="hidden" name="weight_status_confirmed" value="{{ $queueWeights['status_confirmed'] ?? 90 }}">
                    <input type="hidden" name="weight_status_blocked" value="{{ $queueWeights['status_blocked'] ?? 60 }}">
                    <input type="hidden" name="weight_status_in_review" value="{{ $queueWeights['status_in_review'] ?? 40 }}">
                    <input type="hidden" name="weight_status_completed" value="{{ $queueWeights['status_completed'] ?? -200 }}">
                    <input type="hidden" name="weight_status_cancelled" value="{{ $queueWeights['status_cancelled'] ?? -300 }}">
                    <input type="hidden" name="weight_status_default" value="{{ $queueWeights['status_default'] ?? 20 }}">
                    <input type="hidden" name="weight_age_per_hour" value="{{ $queueWeights['age_per_hour'] ?? 1 }}">
                    <input type="hidden" name="weight_age_cap" value="{{ $queueWeights['age_cap'] ?? 240 }}">
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold">
                        Aplicar orden automático
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Lista de Tareas -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $autoQueueMode ? 'Importancia' : 'Orden' }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Técnico</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tasks as $task)
                        @php
                            $queueOrder = $task->scheduled_order ?: ($loop->index + 1);
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $manualQueueMode ? 'cursor-move' : '' }}"
                            @if($manualQueueMode)
                                draggable="true"
                                data-queue-row
                                data-task-id="{{ $task->id }}"
                            @endif>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($autoQueueMode)
                                    <span class="font-semibold text-blue-700 cursor-help"
                                          title="Prioridad: {{ (int) ($task->queue_priority_score ?? 0) }} | Tipo: {{ (int) ($task->queue_type_score ?? 0) }} | Estado: {{ (int) ($task->queue_status_score ?? 0) }} | Antigüedad: {{ (int) ($task->queue_age_score ?? 0) }}">
                                        {{ (int) ($task->queue_score ?? 0) }}
                                    </span>
                                @elseif($manualQueueMode)
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                        <span class="font-semibold" data-order-number>{{ $queueOrder }}</span>
                                    </span>
                                @else
                                    <span class="font-semibold">{{ $queueOrder }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('tasks.show', $task) }}" 
                                   class="text-sm font-mono text-blue-600 hover:text-blue-800 hover:underline font-semibold">
                                    {{ $task->task_code }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $task->title }}</div>
                                @if($task->service_request_id)
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-link"></i> Solicitud #{{ $task->service_request_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                                    <span class="text-sm text-gray-900">{{ $task->technician->user?->name ?? 'Sin asignar' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($task->type === 'impact')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        <i class="fas fa-star"></i> Impacto
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Regular
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $priorityColors = [
                                        'critical' => 'bg-red-600 text-white',
                                        'urgent' => 'bg-red-100 text-red-800',
                                        'high' => 'bg-orange-100 text-orange-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'low' => 'bg-green-100 text-green-800'
                                    ];
                                    $priorityLabels = [
                                        'critical' => 'Crítica',
                                        'urgent' => 'Urgente',
                                        'high' => 'Alta',
                                        'medium' => 'Media',
                                        'low' => 'Baja'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $priorityColors[$task->priority] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-gray-100 text-gray-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'blocked' => 'bg-red-100 text-red-800',
                                        'in_review' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendiente',
                                        'in_progress' => 'En Progreso',
                                        'blocked' => 'Bloqueada',
                                        'in_review' => 'En Revisión',
                                        'completed' => 'Completada',
                                        'cancelled' => 'Cancelada'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$task->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$task->status] ?? $task->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $task->scheduled_date ? $task->scheduled_date->format('d/m/Y') : 'Sin fecha' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('tasks.show', $task) }}"
                                   class="text-blue-600 hover:text-blue-900"
                                   title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('tasks.edit', $task) }}"
                                   class="text-yellow-600 hover:text-yellow-900"
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button"
                                        class="text-indigo-600 hover:text-indigo-900 js-clear-schedule"
                                        data-task-id="{{ $task->id }}"
                                        title="Limpiar programación">
                                    <i class="fas fa-eraser"></i>
                                </button>
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar esta tarea?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                <p>No hay tareas registradas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($tasks->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>

    @if($manualQueueMode)
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Tareas disponibles para agregar a la cola</h3>
                <span class="text-xs text-gray-500">{{ $availableTasksForQueue->count() }} disponibles</span>
            </div>

            @if($availableTasksForQueue->isEmpty())
                <p class="text-sm text-gray-500">No hay tareas pendientes fuera de la agenda de este día para este técnico.</p>
            @else
                <div class="space-y-2">
                    @foreach($availableTasksForQueue as $availableTask)
                        <div class="border border-gray-200 rounded-lg px-4 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-mono text-gray-500">{{ $availableTask->task_code }}</p>
                                <p class="text-sm font-semibold text-gray-900">{{ $availableTask->title }}</p>
                                <p class="text-xs text-gray-500">
                                    Estado: {{ $availableTask->status }} |
                                    Fecha actual: {{ optional($availableTask->scheduled_date)->format('d/m/Y') ?? 'Sin fecha' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('tasks.enqueue-day', $availableTask) }}">
                                @csrf
                                <input type="hidden" name="scheduled_date" value="{{ request('date') }}">
                                <input type="hidden" name="technician_id" value="{{ request('technician_id') }}">
                                <button type="submit" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold">
                                    Agregar a cola
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>

@if($autoQueueMode)
    <style>
        #weightsModal .weight-input {
            display: block;
            width: 100%;
            margin-top: 0.375rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #111827;
        }

        #weightsModal .weight-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
    <div id="weightsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Configurar pesos del cálculo automático</h3>
                <button type="button" id="closeWeightsModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('tasks.index') }}" class="p-6 space-y-6">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="type" value="{{ request('type') }}">
                <input type="hidden" name="priority" value="{{ request('priority') }}">
                <input type="hidden" name="technician_id" value="{{ request('technician_id') }}">
                <input type="hidden" name="date" value="{{ request('date') }}">
                <input type="hidden" name="queue_strategy" value="auto">

                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Prioridad</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="text-sm block">Crítica <input type="number" name="weight_priority_critical" data-weight-key="priority_critical" value="{{ $queueWeights['priority_critical'] }}" class="weight-input"></label>
                        <label class="text-sm block">Alta <input type="number" name="weight_priority_high" data-weight-key="priority_high" value="{{ $queueWeights['priority_high'] }}" class="weight-input"></label>
                        <label class="text-sm block">Media <input type="number" name="weight_priority_medium" data-weight-key="priority_medium" value="{{ $queueWeights['priority_medium'] }}" class="weight-input"></label>
                        <label class="text-sm block">Baja <input type="number" name="weight_priority_low" data-weight-key="priority_low" value="{{ $queueWeights['priority_low'] }}" class="weight-input"></label>
                        <label class="text-sm block">Default <input type="number" name="weight_priority_default" data-weight-key="priority_default" value="{{ $queueWeights['priority_default'] }}" class="weight-input"></label>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Tipo</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="text-sm block">Impacto <input type="number" name="weight_type_impact" data-weight-key="type_impact" value="{{ $queueWeights['type_impact'] }}" class="weight-input"></label>
                        <label class="text-sm block">Regular <input type="number" name="weight_type_regular" data-weight-key="type_regular" value="{{ $queueWeights['type_regular'] }}" class="weight-input"></label>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Estado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="text-sm block">Pendiente <input type="number" name="weight_status_pending" data-weight-key="status_pending" value="{{ $queueWeights['status_pending'] }}" class="weight-input"></label>
                        <label class="text-sm block">En progreso <input type="number" name="weight_status_in_progress" data-weight-key="status_in_progress" value="{{ $queueWeights['status_in_progress'] }}" class="weight-input"></label>
                        <label class="text-sm block">Confirmada <input type="number" name="weight_status_confirmed" data-weight-key="status_confirmed" value="{{ $queueWeights['status_confirmed'] }}" class="weight-input"></label>
                        <label class="text-sm block">Bloqueada <input type="number" name="weight_status_blocked" data-weight-key="status_blocked" value="{{ $queueWeights['status_blocked'] }}" class="weight-input"></label>
                        <label class="text-sm block">En revisión <input type="number" name="weight_status_in_review" data-weight-key="status_in_review" value="{{ $queueWeights['status_in_review'] }}" class="weight-input"></label>
                        <label class="text-sm block">Completada <input type="number" name="weight_status_completed" data-weight-key="status_completed" value="{{ $queueWeights['status_completed'] }}" class="weight-input"></label>
                        <label class="text-sm block">Cancelada <input type="number" name="weight_status_cancelled" data-weight-key="status_cancelled" value="{{ $queueWeights['status_cancelled'] }}" class="weight-input"></label>
                        <label class="text-sm block">Default <input type="number" name="weight_status_default" data-weight-key="status_default" value="{{ $queueWeights['status_default'] }}" class="weight-input"></label>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Antigüedad</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="text-sm block">Peso por hora <input type="number" name="weight_age_per_hour" data-weight-key="age_per_hour" value="{{ $queueWeights['age_per_hour'] }}" class="weight-input"></label>
                        <label class="text-sm block">Tope de horas <input type="number" name="weight_age_cap" data-weight-key="age_cap" value="{{ $queueWeights['age_cap'] }}" class="weight-input"></label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-200">
                    <button type="button" id="resetWeightsModal" class="px-4 py-2 rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-50">
                        Restaurar por defecto
                    </button>
                    <button type="button" id="cancelWeightsModal" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="button" id="applyCloseWeightsModal" class="px-4 py-2 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 font-semibold">
                        Aplicar y cerrar
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                        Aplicar pesos
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    function showTaskToast(message, type = 'info') {
        const toast = document.getElementById('taskToast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-gray-900', 'bg-green-600', 'bg-blue-600', 'bg-red-600');
        toast.classList.add(type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600');
        clearTimeout(window.__taskToastTimer);
        window.__taskToastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 2200);
    }

    document.querySelectorAll('.js-clear-schedule').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!confirm('¿Limpiar programación y volver a estado inicial?')) {
                return;
            }

            const taskId = button.dataset.taskId;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(`{{ route('tasks.clear-schedule', ['task' => '__ID__']) }}`.replace('__ID__', taskId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    showTaskToast(data.message || 'No se pudo limpiar la programación.', 'error');
                    return;
                }

                const row = button.closest('tr');
                if (row) {
                    const statusCell = row.querySelector('td:nth-child(7) span');
                    if (statusCell) {
                        statusCell.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800';
                        statusCell.textContent = 'Pendiente';
                    }

                    const dateCell = row.querySelector('td:nth-child(8)');
                    if (dateCell) {
                        dateCell.textContent = 'Sin fecha';
                    }
                }
                showTaskToast('Programación limpiada.', 'success');
            } catch (error) {
                console.error(error);
                showTaskToast('Error al limpiar la programación.', 'error');
            }
        });
    });

    function showQueueToast(message, type = 'info') {
        showTaskToast(message, type);
    }

    const queueModeActive = @json($manualQueueMode);
    const reorderDayTasksUrl = @json(route('technician-schedule.reorder-day-tasks'));

    function updateQueueOrderNumbers() {
        const rows = document.querySelectorAll('[data-queue-row]');
        rows.forEach((row, index) => {
            const orderNode = row.querySelector('[data-order-number]');
            if (orderNode) {
                orderNode.textContent = String(index + 1);
            }
        });
    }

    async function saveQueueOrder() {
        const rows = Array.from(document.querySelectorAll('[data-queue-row]'));
        const taskIds = rows.map((row) => Number(row.dataset.taskId)).filter(Boolean);
        const scheduledDate = @json(request('date'));
        const technicianId = @json(request('technician_id'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!scheduledDate || !technicianId || taskIds.length === 0) {
            return;
        }

        try {
            const response = await fetch(reorderDayTasksUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                },
                body: JSON.stringify({
                    task_ids: taskIds,
                    scheduled_date: scheduledDate,
                    technician_id: Number(technicianId),
                }),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                showQueueToast(data.message || 'No se pudo guardar el orden.', 'error');
                return;
            }

            showQueueToast('Cola actualizada correctamente.', 'success');
        } catch (error) {
            console.error(error);
            showQueueToast('Error al guardar el encolamiento.', 'error');
        }
    }

    if (queueModeActive) {
        const tbody = document.querySelector('tbody');
        let draggedRow = null;

        if (tbody) {
            tbody.addEventListener('dragstart', (event) => {
                const row = event.target.closest('[data-queue-row]');
                if (!row) return;
                draggedRow = row;
                row.classList.add('opacity-60');
                event.dataTransfer.effectAllowed = 'move';
            });

            tbody.addEventListener('dragend', () => {
                if (draggedRow) {
                    draggedRow.classList.remove('opacity-60');
                }
                draggedRow = null;
            });

            tbody.addEventListener('dragover', (event) => {
                if (!draggedRow) return;
                event.preventDefault();
                const targetRow = event.target.closest('[data-queue-row]');
                if (!targetRow || targetRow === draggedRow) return;

                const targetRect = targetRow.getBoundingClientRect();
                const shouldPlaceAfter = event.clientY > targetRect.top + (targetRect.height / 2);
                if (shouldPlaceAfter) {
                    targetRow.after(draggedRow);
                } else {
                    targetRow.before(draggedRow);
                }
            });

            tbody.addEventListener('drop', async (event) => {
                if (!draggedRow) return;
                event.preventDefault();
                updateQueueOrderNumbers();
                await saveQueueOrder();
            });
        }
    }

    const weightsModal = document.getElementById('weightsModal');
    const openWeightsModal = document.getElementById('openWeightsModal');
    const closeWeightsModal = document.getElementById('closeWeightsModal');
    const cancelWeightsModal = document.getElementById('cancelWeightsModal');
    const resetWeightsModal = document.getElementById('resetWeightsModal');
    const applyCloseWeightsModal = document.getElementById('applyCloseWeightsModal');
    const defaultQueueWeights = @json($queueDefaultWeights ?? []);

    function syncWeightInputsFromModal() {
        if (!weightsModal) return;
        const modalWeightInputs = weightsModal.querySelectorAll('[data-weight-key]');

        modalWeightInputs.forEach((input) => {
            const name = input.getAttribute('name');
            if (!name) return;
            document.querySelectorAll(`input[type="hidden"][name="${name}"]`).forEach((hiddenInput) => {
                hiddenInput.value = input.value;
            });
        });
    }

    if (weightsModal && openWeightsModal) {
        openWeightsModal.addEventListener('click', () => {
            weightsModal.classList.remove('hidden');
        });
    }

    if (weightsModal && closeWeightsModal) {
        closeWeightsModal.addEventListener('click', () => {
            weightsModal.classList.add('hidden');
        });
    }

    if (weightsModal && cancelWeightsModal) {
        cancelWeightsModal.addEventListener('click', () => {
            weightsModal.classList.add('hidden');
        });
    }

    if (weightsModal && resetWeightsModal) {
        resetWeightsModal.addEventListener('click', () => {
            const weightInputs = weightsModal.querySelectorAll('[data-weight-key]');
            weightInputs.forEach((input) => {
                const key = input.getAttribute('data-weight-key');
                if (Object.prototype.hasOwnProperty.call(defaultQueueWeights, key)) {
                    input.value = defaultQueueWeights[key];
                }
            });
        });
    }

    if (weightsModal && applyCloseWeightsModal) {
        applyCloseWeightsModal.addEventListener('click', () => {
            syncWeightInputsFromModal();
            weightsModal.classList.add('hidden');
            showTaskToast('Pesos aplicados en el formulario actual.', 'success');
        });
    }

    const filtersSidebar = document.getElementById('filtersSidebar');
    const filtersOverlay = document.getElementById('filtersOverlay');
    const openFiltersSidebar = document.getElementById('openFiltersSidebar');
    const closeFiltersSidebar = document.getElementById('closeFiltersSidebar');

    function showFiltersSidebar() {
        if (!filtersSidebar || !filtersOverlay) return;
        filtersOverlay.classList.remove('hidden');
        filtersSidebar.classList.remove('translate-x-full');
        document.body.classList.add('overflow-hidden');
    }

    function hideFiltersSidebar() {
        if (!filtersSidebar || !filtersOverlay) return;
        filtersSidebar.classList.add('translate-x-full');
        filtersOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    if (openFiltersSidebar) {
        openFiltersSidebar.addEventListener('click', showFiltersSidebar);
    }

    if (closeFiltersSidebar) {
        closeFiltersSidebar.addEventListener('click', hideFiltersSidebar);
    }

    if (filtersOverlay) {
        filtersOverlay.addEventListener('click', hideFiltersSidebar);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideFiltersSidebar();
        }
    });
</script>

<div id="taskToast" class="hidden fixed bottom-5 right-5 text-white text-sm px-4 py-2 rounded-lg shadow-lg bg-blue-600"></div>
@endsection
