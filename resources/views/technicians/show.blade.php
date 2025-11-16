@extends('layouts.app')

@section('title', 'Detalle del Técnico')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header con avatar y acciones -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="flex justify-between items-start">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="h-24 w-24 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                        {{ strtoupper(substr($technician->user->name, 0, 2)) }}
                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $technician->user->name }}</h2>
                    <p class="text-gray-600">{{ $technician->user->email }}</p>
                    <div class="flex items-center space-x-3 mt-2">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($technician->specialization) }}
                        </span>
                        @php
                            $userRole = $technician->user->role ?? 'user';
                            $roleConfig = [
                                'admin' => ['icon' => 'fa-user-shield', 'label' => 'Administrador', 'class' => 'bg-purple-100 text-purple-800'],
                                'technician' => ['icon' => 'fa-user-cog', 'label' => 'Técnico', 'class' => 'bg-blue-100 text-blue-800'],
                                'user' => ['icon' => 'fa-user', 'label' => 'Usuario', 'class' => 'bg-gray-100 text-gray-600']
                            ];
                            $config = $roleConfig[$userRole] ?? $roleConfig['user'];
                        @endphp
                        <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $config['class'] }}">
                            <i class="fas {{ $config['icon'] }}"></i> {{ $config['label'] }}
                        </span>
                        @if($technician->status === 'active')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle"></i> Activo
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                <i class="fas fa-times-circle"></i> Inactivo
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('technician-schedule.index', ['technician_id' => $technician->id]) }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Ver Agenda
                </a>
                <a href="{{ route('technicians.edit', $technician) }}"
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
                <a href="{{ route('technicians.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Grid de información -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Información Profesional -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-briefcase mr-2 text-red-600"></i>
                Información Profesional
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nivel de Habilidad</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ ucfirst($technician->skill_level) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Experiencia</dt>
                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                        <i class="fas fa-award text-yellow-500 mr-2"></i>
                        {{ $technician->years_experience }} años
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Capacidad Diaria</dt>
                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                        {{ $technician->max_daily_capacity_hours }} horas
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Disponibilidad -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-user-check mr-2 text-red-600"></i>
                Disponibilidad
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado Actual</dt>
                    <dd class="mt-1">
                        @php
                            $availabilityColors = [
                                'available' => 'bg-green-100 text-green-800',
                                'busy' => 'bg-yellow-100 text-yellow-800',
                                'on_leave' => 'bg-red-100 text-red-800',
                                'unavailable' => 'bg-gray-100 text-gray-800'
                            ];
                            $availabilityLabels = [
                                'available' => 'Disponible',
                                'busy' => 'Ocupado',
                                'on_leave' => 'De Permiso',
                                'unavailable' => 'No Disponible'
                            ];
                        @endphp
                        <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $availabilityColors[$technician->availability_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $availabilityLabels[$technician->availability_status] ?? $technician->availability_status }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tareas Activas</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $technician->tasks()->whereIn('status', ['pending', 'in_progress'])->count() }} tareas
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Estadísticas -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-chart-bar mr-2 text-red-600"></i>
                Estadísticas
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Tareas</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $technician->tasks()->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tareas Completadas</dt>
                    <dd class="mt-1 text-sm text-green-600 font-semibold">
                        {{ $technician->tasks()->where('status', 'completed')->count() }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">En Progreso</dt>
                    <dd class="mt-1 text-sm text-blue-600 font-semibold">
                        {{ $technician->tasks()->where('status', 'in_progress')->count() }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Habilidades -->
    @if($technician->skills && $technician->skills->count() > 0)
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-code mr-2 text-red-600"></i>
                Habilidades Técnicas
            </h3>
            <div class="flex flex-wrap gap-2">
                @foreach($technician->skills as $skill)
                    <span class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 text-sm font-medium">
                        {{ $skill->skill_name }}
                        @if($skill->proficiency_level)
                            <span class="ml-2 text-xs">({{ ucfirst($skill->proficiency_level) }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Tareas Recientes -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                <i class="fas fa-tasks mr-2 text-red-600"></i>
                Tareas Recientes
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($technician->tasks()->latest()->take(10)->get() as $task)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ $task->task_code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $task->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($task->type === 'impact')
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Impacto</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Regular</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-gray-100 text-gray-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'blocked' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$task->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $task->scheduled_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('tasks.show', $task) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                                <p>No hay tareas asignadas aún</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
