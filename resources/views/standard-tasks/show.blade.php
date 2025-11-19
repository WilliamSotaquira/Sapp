@extends('layouts.app')

@section('title', 'Detalle Tarea Predefinida')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header con navegación -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ route('standard-tasks.index') }}" class="text-gray-600 hover:text-gray-900 text-lg">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex-1">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $standardTask->title }}</h2>
                <p class="text-gray-600 text-sm mt-1">{{ $standardTask->subService->name }} - {{ $standardTask->subService->service->name }}</p>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('standard-tasks.edit', $standardTask) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                Editar
            </a>
            <form action="{{ route('standard-tasks.destroy', $standardTask) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta tarea predefinida?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Información Principal -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- Descripción -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-align-left text-blue-600"></i>
                    Descripción
                </h3>
                <p class="text-gray-700">{{ $standardTask->description ?? 'Sin descripción' }}</p>
            </div>

            <!-- Detalles Técnicos -->
            @if($standardTask->technologies || $standardTask->required_accesses || $standardTask->technical_notes)
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-cog text-purple-600"></i>
                        Detalles Técnicos
                    </h3>
                    <dl class="space-y-4">
                        @if($standardTask->technologies)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Tecnologías</dt>
                                <dd class="text-sm text-gray-900">{{ $standardTask->technologies }}</dd>
                            </div>
                        @endif
                        @if($standardTask->required_accesses)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Accesos Requeridos</dt>
                                <dd class="text-sm text-gray-900">{{ $standardTask->required_accesses }}</dd>
                            </div>
                        @endif
                        @if($standardTask->environment)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Ambiente</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                        {{ strtoupper($standardTask->environment) }}
                                    </span>
                                </dd>
                            </div>
                        @endif
                        @if($standardTask->technical_notes)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Notas Técnicas</dt>
                                <dd class="text-sm text-gray-900">{{ $standardTask->technical_notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Subtareas -->
            @if($standardTask->standardSubtasks->count() > 0)
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-tasks text-green-600"></i>
                        Subtareas ({{ $standardTask->standardSubtasks->count() }})
                    </h3>
                    <div class="space-y-3">
                        @foreach($standardTask->standardSubtasks as $subtask)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fas fa-check-circle text-green-600"></i>
                                            <span class="font-medium text-gray-900">{{ $subtask->title }}</span>
                                            @php
                                                $priorityClasses = [
                                                    'high' => 'bg-red-100 text-red-800',
                                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                                    'low' => 'bg-green-100 text-green-800'
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded {{ $priorityClasses[$subtask->priority] }}">
                                                {{ strtoupper($subtask->priority) }}
                                            </span>
                                        </div>
                                        @if($subtask->description)
                                            <p class="text-sm text-gray-600 ml-6">{{ $subtask->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Información General -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Información</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 mb-1">Subservicio</dt>
                        <dd class="font-medium text-gray-900">{{ $standardTask->subService->name }}</dd>
                        <dd class="text-xs text-gray-500">{{ $standardTask->subService->service->name }}</dd>
                    </div>
                    <div class="border-t pt-3">
                        <dt class="text-gray-500 mb-1">Tipo</dt>
                        <dd>
                            @if($standardTask->type === 'impact')
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800">
                                    <i class="fas fa-star mr-1"></i>IMPACTO
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                    REGULAR
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="border-t pt-3">
                        <dt class="text-gray-500 mb-1">Prioridad</dt>
                        <dd>
                            @php
                                $priorityClasses = [
                                    'critical' => 'bg-red-100 text-red-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'low' => 'bg-green-100 text-green-800'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded {{ $priorityClasses[$standardTask->priority] }}">
                                {{ strtoupper($standardTask->priority) }}
                            </span>
                        </dd>
                    </div>
                    <div class="border-t pt-3">
                        <dt class="text-gray-500 mb-1">Horas Estimadas</dt>
                        <dd class="font-medium text-gray-900">{{ $standardTask->estimated_hours }} hrs</dd>
                    </div>
                    @if($standardTask->technical_complexity)
                        <div class="border-t pt-3">
                            <dt class="text-gray-500 mb-1">Complejidad Técnica</dt>
                            <dd class="font-medium text-gray-900">{{ $standardTask->technical_complexity }} / 5</dd>
                        </div>
                    @endif
                    <div class="border-t pt-3">
                        <dt class="text-gray-500 mb-1">Orden</dt>
                        <dd class="font-medium text-gray-900">{{ $standardTask->order }}</dd>
                    </div>
                    <div class="border-t pt-3">
                        <dt class="text-gray-500 mb-1">Estado</dt>
                        <dd>
                            @if($standardTask->is_active)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>ACTIVA
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">
                                    <i class="fas fa-times-circle mr-1"></i>INACTIVA
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Estadísticas -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Estadísticas</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500 mb-1">Total Subtareas</dt>
                        <dd class="text-2xl font-bold text-purple-600">{{ $standardTask->standardSubtasks->count() }}</dd>
                    </div>
                    <div class="border-t pt-4">
                        <dt class="text-sm text-gray-500 mb-1">Subtareas Activas</dt>
                        <dd class="text-2xl font-bold text-green-600">{{ $standardTask->standardSubtasks->where('is_active', true)->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
