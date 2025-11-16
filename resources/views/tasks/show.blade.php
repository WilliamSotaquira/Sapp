@extends('layouts.app')

@section('title', 'Detalle de Tarea')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header con acciones -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <span class="text-sm font-mono bg-gray-100 px-3 py-1 rounded">{{ $task->task_code }}</span>
                    @php
                        $statusColors = [
                            'pending' => 'bg-gray-100 text-gray-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'blocked' => 'bg-red-100 text-red-800',
                            'in_review' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$task->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($task->status) }}
                    </span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $task->title }}</h2>
                <p class="text-gray-600 mt-2">{{ $task->description }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('tasks.edit', $task) }}"
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
                <a href="{{ route('tasks.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Grid de información -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Información General -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-info-circle mr-2 text-red-600"></i>
                Información General
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tipo</dt>
                    <dd class="mt-1">
                        @if($task->type === 'impact')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                <i class="fas fa-star"></i> Impacto (90 min)
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                Regular (25 min)
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Prioridad</dt>
                    <dd class="mt-1">
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
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Fecha Programada</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <i class="fas fa-calendar mr-2"></i>
                        {{ $task->scheduled_date->format('d/m/Y') }}
                        @if($task->scheduled_start_time)
                            a las {{ substr($task->scheduled_start_time, 0, 5) }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Duración Estimada</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <i class="fas fa-clock mr-2"></i>
                        {{ $task->estimated_hours }} horas
                    </dd>
                </div>
                @if($task->actual_hours)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tiempo Real</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <i class="fas fa-hourglass-half mr-2"></i>
                            {{ $task->actual_hours }} horas
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <!-- Asignación -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-user-check mr-2 text-red-600"></i>
                Asignación
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Técnico Asignado</dt>
                    <dd class="mt-1">
                        <div class="flex items-center">
                            <i class="fas fa-user-circle text-2xl text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $task->technician->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($task->technician->specialization) }}</p>
                            </div>
                        </div>
                    </dd>
                </div>
                @if($task->service_request_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Solicitud Asociada</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('service-requests.show', $task->service_request_id) }}"
                               class="text-blue-600 hover:text-blue-800 flex items-center">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                Solicitud #{{ $task->service_request_id }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if($task->project_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Proyecto</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <i class="fas fa-project-diagram mr-2"></i>
                            {{ $task->project->name }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Historial de cambios -->
    @if($task->history && $task->history->count() > 0)
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-red-600"></i>
                Historial de Cambios
            </h3>
            <div class="space-y-4">
                @foreach($task->history as $history)
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $history->action }}</p>
                                @if($history->notes)
                                    <p class="text-sm text-gray-600 mt-1">{{ $history->notes }}</p>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $history->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Acciones de workflow -->
    @if($task->status !== 'completed' && $task->status !== 'cancelled')
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-cogs mr-2 text-red-600"></i>
                Acciones
            </h3>
            <div class="flex flex-wrap gap-3">
                @if($task->status === 'pending')
                    <form action="{{ route('tasks.start', $task) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-play mr-2"></i>
                            Iniciar Tarea
                        </button>
                    </form>
                @endif

                @if($task->status === 'in_progress')
                    <form action="{{ route('tasks.complete', $task) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-check mr-2"></i>
                            Completar Tarea
                        </button>
                    </form>

                    <form action="{{ route('tasks.block', $task) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-ban mr-2"></i>
                            Bloquear
                        </button>
                    </form>
                @endif

                @if($task->status === 'blocked')
                    <form action="{{ route('tasks.unblock', $task) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-unlock mr-2"></i>
                            Desbloquear
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
