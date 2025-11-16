@extends('layouts.app')

@section('title', 'Mi Agenda')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Encabezado -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📋 Mi Agenda</h1>
        <p class="text-gray-600 mt-1">{{ $technician->user->name }} - {{ \Carbon\Carbon::parse($date)->format('l, j \d\e F \d\e Y') }}</p>
    </div>

    <!-- Estadísticas del día -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-yellow-700">{{ $stats['pending'] }}</div>
                    <div class="text-sm text-yellow-600">Pendientes</div>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-400"></i>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-blue-700">{{ $stats['in_progress'] }}</div>
                    <div class="text-sm text-blue-600">En Progreso</div>
                </div>
                <i class="fas fa-spinner text-3xl text-blue-400"></i>
            </div>
        </div>

        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-green-700">{{ $stats['completed'] }}</div>
                    <div class="text-sm text-green-600">Completadas</div>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-400"></i>
            </div>
        </div>

        <div class="bg-gray-50 border-l-4 border-gray-500 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-700">{{ round($stats['total_estimated_minutes'] / 60, 1) }}h</div>
                    <div class="text-sm text-gray-600">Tiempo Total</div>
                </div>
                <i class="fas fa-hourglass-half text-3xl text-gray-400"></i>
            </div>
        </div>
    </div>

    <!-- Selector de fecha -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center gap-4">
            <button onclick="changeDate(-1)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <input type="date" id="dateSelector" value="{{ $date }}"
                   onchange="window.location.href='{{ route('technician-schedule.my-agenda') }}?date=' + this.value"
                   class="border-gray-300 rounded-lg">
            <button onclick="changeDate(1)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
            <button onclick="window.location.href='{{ route('technician-schedule.my-agenda') }}'"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Hoy
            </button>
        </div>
    </div>

    <!-- Timeline de tareas -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Tareas del Día</h2>

        @if($tasks->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No tienes tareas programadas para este día</p>
                <p class="text-gray-400 text-sm mt-2">¡Disfruta tu día libre de tareas! 🎉</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($tasks->where('status', '!=', 'completed')->sortBy('scheduled_start_time') as $task)
                    <div class="border-l-4 border-{{ $task->type === 'impact' ? 'red' : 'blue' }}-500 pl-4 py-3 bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-50 rounded">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <!-- Hora y código -->
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-lg font-bold text-gray-800">
                                        {{ substr($task->scheduled_start_time, 0, 5) }}
                                    </span>
                                    <span class="font-mono text-xs bg-gray-800 text-white px-3 py-1 rounded">
                                        {{ $task->task_code }}
                                    </span>
                                    <span class="px-3 py-1 text-xs rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800 font-semibold">
                                        {{ strtoupper($task->status) }}
                                    </span>
                                    @if($task->type === 'impact')
                                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800 font-semibold">
                                            🔴 IMPACTO
                                        </span>
                                    @else
                                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800 font-semibold">
                                            🟡 REGULAR
                                        </span>
                                    @endif
                                </div>

                                <!-- Título y descripción -->
                                <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $task->title }}</h3>
                                @if($task->description)
                                    <p class="text-sm text-gray-600 mb-2">{{ Str::limit($task->description, 150) }}</p>
                                @endif

                                <!-- Metadatos -->
                                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                    <span><i class="fas fa-clock text-blue-500"></i> {{ $task->formatted_duration }}</span>
                                    @if($task->priority)
                                        <span><i class="fas fa-flag text-{{ $task->priority_color }}-500"></i> {{ ucfirst($task->priority) }}</span>
                                    @endif
                                    @if($task->serviceRequest)
                                        <span><i class="fas fa-ticket-alt text-green-500"></i> {{ $task->serviceRequest->ticket_number }}</span>
                                    @endif
                                    @if($task->technologies)
                                        <span>
                                            <i class="fas fa-code text-purple-500"></i>
                                            {{ implode(', ', array_slice($task->technologies, 0, 3)) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- SLA si aplica -->
                                @if($task->slaCompliance)
                                    <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded bg-{{ $task->slaCompliance->compliance_status === 'within_sla' ? 'green' : ($task->slaCompliance->compliance_status === 'at_risk' ? 'yellow' : 'red') }}-100">
                                        <i class="fas fa-clock"></i>
                                        <span class="text-xs font-semibold">
                                            SLA: {{ $task->slaCompliance->compliance_status }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Acciones -->
                            <div class="flex flex-col gap-2 ml-4">
                                @if($task->status === 'pending')
                                    <form action="{{ route('tasks.start', $task) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                                            <i class="fas fa-play"></i> Iniciar
                                        </button>
                                    </form>
                                @elseif($task->status === 'in_progress')
                                    <button onclick="openCompleteModal({{ $task->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                                        <i class="fas fa-check"></i> Completar
                                    </button>
                                @endif
                                <a href="{{ route('tasks.show', $task) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-center">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Tareas Completadas (Sección colapsable) -->
    @php
        $completedTasks = $tasks->where('status', 'completed');
    @endphp

    @if($completedTasks->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <button onclick="toggleCompleted()" class="w-full flex justify-between items-center text-left">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-check-circle text-green-600"></i> Tareas Completadas ({{ $completedTasks->count() }})
                </h2>
                <i id="completedIcon" class="fas fa-chevron-down text-gray-600"></i>
            </button>

            <div id="completedSection" class="hidden mt-4 space-y-3">
                @foreach($completedTasks->sortBy('scheduled_start_time') as $task)
                    <div class="border-l-4 border-green-500 pl-4 py-2 bg-green-50 rounded opacity-75">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-sm font-bold text-gray-600">
                                        {{ substr($task->scheduled_start_time, 0, 5) }}
                                    </span>
                                    <span class="font-mono text-xs bg-gray-600 text-white px-2 py-1 rounded">
                                        {{ $task->task_code }}
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-semibold">
                                        ✓ COMPLETADA
                                    </span>
                                </div>
                                <h3 class="text-base font-semibold text-gray-700">{{ $task->title }}</h3>
                                <div class="flex gap-3 text-xs text-gray-500 mt-1">
                                    <span><i class="fas fa-hourglass-half"></i> {{ $task->formatted_duration }}</span>
                                    @if($task->completed_at)
                                        <span><i class="fas fa-clock"></i> Completada: {{ $task->completed_at->format('H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('tasks.show', $task) }}" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Modal para completar tarea -->
<div id="completeModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Completar Tarea</h3>
        <form id="completeForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notas Técnicas</label>
                <textarea name="technical_notes" rows="4" class="w-full border-gray-300 rounded-lg" placeholder="Describe lo realizado, soluciones aplicadas, etc."></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    Completar Tarea
                </button>
                <button type="button" onclick="closeCompleteModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function changeDate(days) {
        const dateInput = document.getElementById('dateSelector');
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        dateInput.value = currentDate.toISOString().split('T')[0];
        window.location.href = '{{ route('technician-schedule.my-agenda') }}?date=' + dateInput.value;
    }

    function openCompleteModal(taskId) {
        const modal = document.getElementById('completeModal');
        const form = document.getElementById('completeForm');
        form.action = `/tasks/${taskId}/complete`;
        modal.classList.remove('hidden');
    }

    function closeCompleteModal() {
        const modal = document.getElementById('completeModal');
        modal.classList.add('hidden');
    }

    function toggleCompleted() {
        const section = document.getElementById('completedSection');
        const icon = document.getElementById('completedIcon');

        if (section.classList.contains('hidden')) {
            section.classList.remove('hidden');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            section.classList.add('hidden');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
</script>
@endsection
