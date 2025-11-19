@extends('layouts.app')

@section('title', 'Tareas Predefinidas')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Tareas Predefinidas</h2>
                <p class="text-gray-600 text-sm mt-1">Plantillas de tareas asociadas a subservicios</p>
            </div>
            <a href="{{ route('standard-tasks.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Nueva Tarea Predefinida
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subservicio</label>
                <select name="sub_service_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                    <option value="">Todos</option>
                    @foreach($subServices as $subService)
                        <option value="{{ $subService->id }}" {{ request('sub_service_id') == $subService->id ? 'selected' : '' }}>
                            {{ $subService->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                    <option value="">Todos</option>
                    <option value="regular" {{ request('type') == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="impact" {{ request('type') == 'impact' ? 'selected' : '' }}>Impacto</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                    <option value="">Todas</option>
                    <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Crítica</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Media</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="{{ route('standard-tasks.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-tasks text-purple-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Tareas</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\StandardTask::count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Activas</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\StandardTask::active()->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-list text-green-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Subtareas</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\StandardSubtask::count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-layer-group text-orange-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Subservicios</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\SubService::whereHas('standardTasks')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        @if($standardTasks->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($standardTasks as $task)
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <a href="{{ route('standard-tasks.show', $task) }}" class="text-lg font-semibold text-gray-900 hover:text-red-600 transition-colors">
                                        {{ $task->title }}
                                    </a>
                                    @php
                                        $priorityClasses = [
                                            'critical' => 'bg-red-100 text-red-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'low' => 'bg-green-100 text-green-800'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded {{ $priorityClasses[$task->priority] }}">
                                        {{ strtoupper($task->priority) }}
                                    </span>
                                    @if($task->type === 'impact')
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800">
                                            <i class="fas fa-star mr-1"></i>IMPACTO
                                        </span>
                                    @endif
                                    @if(!$task->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 text-gray-600">
                                            INACTIVA
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-4 text-sm text-gray-600 mb-2">
                                    <span><i class="fas fa-layer-group text-blue-600 mr-1"></i>{{ $task->subService->name }}</span>
                                    <span><i class="fas fa-clock text-purple-600 mr-1"></i>{{ $task->estimated_hours }} hrs</span>
                                    @if($task->standardSubtasks->count() > 0)
                                        <span><i class="fas fa-tasks text-green-600 mr-1"></i>{{ $task->standardSubtasks->count() }} subtareas</span>
                                    @endif
                                </div>

                                @if($task->description)
                                    <p class="text-sm text-gray-600 mb-2">{{ Str::limit($task->description, 150) }}</p>
                                @endif

                                @if($task->standardSubtasks->count() > 0)
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($task->standardSubtasks->take(3) as $subtask)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">
                                                <i class="fas fa-check-circle mr-1"></i>{{ $subtask->title }}
                                            </span>
                                        @endforeach
                                        @if($task->standardSubtasks->count() > 3)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">
                                                +{{ $task->standardSubtasks->count() - 3 }} más
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="flex gap-3">
                                <a href="{{ route('standard-tasks.show', $task) }}" class="text-blue-600 hover:text-blue-800 text-lg" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('standard-tasks.edit', $task) }}" class="text-yellow-600 hover:text-yellow-800 text-lg" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('standard-tasks.destroy', $task) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta tarea predefinida?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-lg" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $standardTasks->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-tasks text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg mb-4">No hay tareas predefinidas</p>
                <a href="{{ route('standard-tasks.create') }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus-circle mr-2"></i>Crear Primera Tarea
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
