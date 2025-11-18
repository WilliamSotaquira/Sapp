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

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Media</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Tareas -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-900">{{ $task->task_code }}</span>
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
                                        'urgent' => 'bg-red-100 text-red-800',
                                        'high' => 'bg-orange-100 text-orange-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'low' => 'bg-green-100 text-green-800'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $priorityColors[$task->priority] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($task->priority) }}
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
                                {{ $task->scheduled_date->format('d/m/Y') }}
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
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
</div>
@endsection
