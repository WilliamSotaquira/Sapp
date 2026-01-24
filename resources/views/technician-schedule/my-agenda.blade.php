@extends('layouts.app')

@section('title', 'Mi Agenda')

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
            <i class="fas fa-clipboard-list"></i>
            <span class="ml-1">Mi Agenda</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto px-3 sm:px-4 md:px-6">
    <!-- Encabezado con controles -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-5 mb-4 sm:mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="flex-1">
                @if(isset($isViewingOther) && $isViewingOther)
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded mb-3">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            Viendo la agenda de: <strong>{{ $technician->user->name }}</strong>
                        </p>
                    </div>
                @endif
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Mi Agenda</h1>
                <p class="text-sm sm:text-base text-gray-600 mt-1">
                    {{ $technician->user->name }} · {{ \Carbon\Carbon::parse($date)->format('l, j \d\e F \d\e Y') }}
                </p>
            </div>

            <div class="w-full lg:w-auto space-y-2">
                @if(auth()->user()->isAdmin() && isset($technicians) && $technicians->count() > 0)
                    <!-- Selector de técnico para administradores -->
                    <div class="flex flex-col sm:flex-row gap-2 w-full">
                        <select id="technicianSelector"
                                onchange="changeTechnician()"
                                class="w-full sm:w-64 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Mi Agenda</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->user->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date"
                               id="dateSelector"
                               value="{{ $date }}"
                               onchange="changeDate()"
                               class="w-full sm:w-auto text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                @else
                    <!-- Solo selector de fecha para técnicos -->
                    <div class="w-full sm:w-auto">
                        <input type="date"
                               id="dateSelector"
                               value="{{ $date }}"
                               onchange="changeDate()"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                @endif

                <div class="flex items-center justify-start sm:justify-end gap-2">
                    <button onclick="navigateDate('prev')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="navigateDate('today')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                        Hoy
                    </button>
                    <button onclick="navigateDate('next')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <a href="{{ route('technician-schedule.index') }}" class="bg-gray-900 hover:bg-gray-800 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="hidden sm:inline ml-1">Calendario</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $totalTasks = $stats['pending'] + $stats['in_progress'] + $stats['completed'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Timeline de tareas -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            <div class="bg-white rounded-lg shadow-md p-6 relative" id="tasksDropZone" data-dropzone>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Tareas del Día</h2>
                        <p class="text-sm text-gray-500">Ordenadas por hora de inicio</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-list-ul mr-1"></i><span id="tasksTotalCount">{{ $totalTasks }}</span> tareas en total
                    </div>
                </div>
                <div id="dropHint" class="hidden absolute inset-3 border-2 border-dashed border-blue-400 rounded-lg bg-blue-50/60 flex items-center justify-center text-blue-700 font-semibold text-sm">
                    Suelta aquí para agendar la tarea en este día
                </div>


        @if($tasks->isEmpty())
            <div id="tasksEmptyState" class="text-center py-12">
                <i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No tienes tareas programadas para este día</p>
                <p class="text-gray-400 text-sm mt-2">¡Disfruta tu día libre de tareas! 🎉</p>
            </div>
        @else
                    <div class="space-y-4" id="tasksList">
                        @foreach($tasks->where('status', '!=', 'completed')->sortBy('scheduled_start_time') as $task)
                            <div class="border border-{{ $task->type === 'impact' ? 'red' : 'blue' }}-200 bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-50/50 rounded-lg p-4"
                                 draggable="true" data-day-task data-task-id="{{ $task->id }}">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            <div class="lg:w-28">
                                <div class="text-lg font-bold text-gray-800">{{ substr($task->scheduled_start_time, 0, 5) }}</div>
                                <div class="text-xs uppercase tracking-wide text-gray-400">Inicio</div>
                                <div class="mt-2 text-xs text-gray-600">
                                    <i class="fas fa-hourglass-half text-gray-400 mr-1"></i>{{ $task->formatted_duration }}
                                </div>
                            </div>

                            <div class="flex-1">
                                @php
                                    $statusLabels = [
                                        'pending' => 'PENDIENTE',
                                        'confirmed' => 'CONFIRMADA',
                                        'in_progress' => 'EN PROGRESO',
                                        'blocked' => 'BLOQUEADA',
                                        'in_review' => 'EN REVISIÓN',
                                        'completed' => 'COMPLETADA',
                                        'cancelled' => 'CANCELADA',
                                        'rescheduled' => 'REPROGRAMADA',
                                    ];
                                    $priorityLabels = [
                                        'critical' => 'Crítica',
                                        'high' => 'Alta',
                                        'medium' => 'Media',
                                        'low' => 'Baja',
                                        'urgent' => 'Urgente',
                                    ];
                                    $slaLabels = [
                                        'within_sla' => 'Dentro de SLA',
                                        'at_risk' => 'En riesgo',
                                        'breached' => 'Incumplida',
                                    ];
                                @endphp
                                <!-- Código y estado -->
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-mono text-[11px] bg-gray-800 text-white px-2 py-0.5 rounded">
                                        {{ $task->task_code }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800 font-semibold">
                                        {{ $statusLabels[$task->status] ?? strtoupper($task->status) }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] rounded-full bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-100 text-{{ $task->type === 'impact' ? 'red' : 'blue' }}-800 font-semibold">
                                        {{ $task->type === 'impact' ? 'IMPACTO' : 'REGULAR' }}
                                    </span>
                                    @if($task->priority)
                                        <span class="px-2 py-0.5 text-[11px] rounded-full bg-{{ $task->priority_color }}-100 text-{{ $task->priority_color }}-800 font-semibold">
                                            <i class="fas fa-flag mr-1"></i>{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Título y descripción -->
                                <h3 class="text-base font-semibold text-gray-800 mb-0.5">{{ $task->title }}</h3>
                                @if($task->description)
                                    <p class="text-xs text-gray-600 mb-2">{{ Str::limit($task->description, 110) }}</p>
                                @endif

                                <!-- Metadatos -->
                                <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                                    @if($task->serviceRequest)
                                        <a href="{{ route('service-requests.show', $task->serviceRequest) }}" class="inline-flex items-center gap-1 text-green-700 hover:text-green-900">
                                            <i class="fas fa-ticket-alt text-green-500"></i> {{ $task->serviceRequest->ticket_number }}
                                        </a>
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
                                    <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-0.5 rounded bg-{{ $task->slaCompliance->compliance_status === 'within_sla' ? 'green' : ($task->slaCompliance->compliance_status === 'at_risk' ? 'yellow' : 'red') }}-100">
                                        <i class="fas fa-clock"></i>
                                        <span class="text-[11px] font-semibold">
                                            SLA: {{ $slaLabels[$task->slaCompliance->compliance_status] ?? $task->slaCompliance->compliance_status }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Acciones -->
                            <div class="flex flex-row lg:flex-col gap-2 lg:items-stretch">
                                @if($task->status === 'pending')
                                    <form action="{{ route('tasks.start', $task) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
                                            <i class="fas fa-play"></i> Iniciar
                                        </button>
                                    </form>
                                @elseif($task->status === 'in_progress')
                                    <button onclick="openCompleteModal({{ $task->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
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
            <div id="tasksEmptyState" class="hidden text-center py-12">
                <i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No tienes tareas programadas para este día</p>
                <p class="text-gray-400 text-sm mt-2">¡Disfruta tu día libre de tareas! 🎉</p>
            </div>
        @endif
            </div>

            <!-- Tareas Completadas (Sección colapsable) -->
            @php
                $completedTasks = $tasks->where('status', 'completed');
            @endphp

            @if($completedTasks->count() > 0)
                <div class="bg-white rounded-lg shadow-md p-6">
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

        <!-- Barra lateral: Tareas abiertas -->
        <aside class="lg:col-span-5 xl:col-span-4">
            <div class="bg-white rounded-lg shadow-md p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-tasks text-blue-600 mr-1"></i>
                        Tareas Abiertas
                    </h3>
                    <span class="text-xs text-gray-500">{{ $openTasks->count() }}</span>
                </div>
                <p class="text-xs text-gray-500 mb-3">Tareas abiertas sin agenda asignada.</p>

                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1" data-open-list>
                    @if($openTasks->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-gray-300 mb-3"></i>
                            <p class="text-sm text-gray-500">No hay tareas abiertas</p>
                        </div>
                    @else
                        @foreach($openTasks as $task)
                            <a href="{{ route('tasks.show', $task) }}"
                               draggable="true"
                               data-open-task
                               data-task-id="{{ $task->id }}"
                               class="block border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500">{{ $task->task_code }}</p>
                                        <p class="text-sm font-medium text-gray-800 leading-snug">{{ Str::limit($task->title, 70) }}</p>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800 font-semibold">
                                        {{ strtoupper($task->status) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                                    @if($task->priority)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fas fa-flag text-{{ $task->priority_color }}-500"></i>{{ ucfirst($task->priority) }}
                                        </span>
                                    @endif
                                    @if($task->scheduled_date)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fas fa-calendar-alt text-blue-500"></i>{{ $task->scheduled_date->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($task->scheduled_start_time)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fas fa-clock text-gray-500"></i>{{ substr($task->scheduled_start_time, 0, 5) }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </aside>
    </div>
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
    function changeTechnician() {
        const technicianId = document.getElementById('technicianSelector').value;
        const date = document.getElementById('dateSelector').value;

        let url = '{{ route('technician-schedule.my-agenda') }}?date=' + date;
        if (technicianId) {
            url += '&technician_id=' + technicianId;
        }
        window.location.href = url;
    }

    function changeDate() {
        const date = document.getElementById('dateSelector').value;
        const technicianId = document.getElementById('technicianSelector')?.value;

        let url = '{{ route('technician-schedule.my-agenda') }}?date=' + date;
        if (technicianId) {
            url += '&technician_id=' + technicianId;
        }
        window.location.href = url;
    }

    function navigateDate(direction) {
        const dateInput = document.getElementById('dateSelector');
        const currentDate = new Date(dateInput.value);
        let newDate = new Date(currentDate);

        if (direction === 'prev') {
            newDate.setDate(currentDate.getDate() - 1);
        } else if (direction === 'next') {
            newDate.setDate(currentDate.getDate() + 1);
        } else if (direction === 'today') {
            newDate = new Date();
        }

        dateInput.value = newDate.toISOString().split('T')[0];
        changeDate();
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

    const scheduleQuickUrlTemplate = @json(route('tasks.schedule-quick', ['task' => '__ID__']));
    const unscheduleTaskUrlTemplate = @json(route('tasks.unschedule', ['task' => '__ID__']));

    function buildScheduleQuickUrl(taskId) {
        return scheduleQuickUrlTemplate.replace('__ID__', taskId);
    }

    function buildUnscheduleTaskUrl(taskId) {
        return unscheduleTaskUrlTemplate.replace('__ID__', taskId);
    }

    function showToast(message, type = 'info') {
        const toast = document.getElementById('srToast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-gray-900', 'bg-green-600', 'bg-blue-600', 'bg-red-600');
        toast.classList.add(type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600');
        clearTimeout(window.__srToastTimer);
        window.__srToastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 2200);
    }

    function initializeTaskDragBetweenLists() {
        const openTasks = document.querySelectorAll('[data-open-task]');
        const dayTasks = document.querySelectorAll('[data-day-task]');
        const dropZone = document.getElementById('tasksDropZone');
        const dropHint = document.getElementById('dropHint');
        const openList = document.querySelector('[data-open-list]');

        if (dropZone) {
            dropZone.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                dropHint?.classList.remove('hidden');
            });

            dropZone.addEventListener('dragleave', (event) => {
                if (!dropZone.contains(event.relatedTarget)) {
                    dropHint?.classList.add('hidden');
                }
            });

            dropZone.addEventListener('drop', async (event) => {
                event.preventDefault();
                dropHint?.classList.add('hidden');
                const origin = event.dataTransfer.getData('application/x-task-origin');
                const taskId = event.dataTransfer.getData('application/x-task-id') || event.dataTransfer.getData('text/plain');

                if (!taskId || origin !== 'open') {
                    return;
                }

                const dateValue = document.getElementById('dateSelector')?.value;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const response = await fetch(buildScheduleQuickUrl(taskId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf || '',
                        },
                        body: JSON.stringify({ scheduled_date: dateValue || null }),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        showToast(data.message || 'No se pudo agendar la tarea.', 'error');
                        return;
                    }

                    showToast(`Tarea agendada (${data.scheduled_at}).`, 'success');
                    if (data.scheduled_date) {
                        const params = new URLSearchParams();
                        params.set('date', data.scheduled_date);
                        const technicianId = document.getElementById('technicianSelector')?.value;
                        if (technicianId) {
                            params.set('technician_id', technicianId);
                        }
                        setTimeout(() => {
                            window.location.href = `{{ route('technician-schedule.my-agenda') }}?${params.toString()}`;
                        }, 500);
                    } else {
                        setTimeout(() => window.location.reload(), 700);
                    }
                } catch (error) {
                    console.error(error);
                    showToast('Error al agendar la tarea.', 'error');
                }
            });
        }

        openTasks.forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('application/x-task-id', item.dataset.taskId);
                event.dataTransfer.setData('application/x-task-origin', 'open');
                event.dataTransfer.setData('text/plain', item.dataset.taskId);
                event.dataTransfer.effectAllowed = 'move';
                item.classList.add('ring-2', 'ring-blue-400');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('ring-2', 'ring-blue-400');
            });
        });

        if (openList) {
            openList.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                openList.classList.add('ring-2', 'ring-amber-300', 'bg-amber-50');
            });

            openList.addEventListener('dragleave', (event) => {
                if (!openList.contains(event.relatedTarget)) {
                    openList.classList.remove('ring-2', 'ring-amber-300', 'bg-amber-50');
                }
            });

            openList.addEventListener('drop', async (event) => {
                event.preventDefault();
                openList.classList.remove('ring-2', 'ring-amber-300', 'bg-amber-50');
                const origin = event.dataTransfer.getData('application/x-task-origin');
                const taskId = event.dataTransfer.getData('application/x-task-id');

                if (!taskId || origin !== 'day') {
                    return;
                }

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const response = await fetch(buildUnscheduleTaskUrl(taskId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf || '',
                        },
                        body: JSON.stringify({}),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        showToast(data.message || 'No se pudo devolver la tarea.', 'error');
                        return;
                    }

                    showToast('Tarea devuelta a tareas abiertas.', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } catch (error) {
                    console.error(error);
                    showToast('Error al devolver la tarea.', 'error');
                }
            });
        }

        dayTasks.forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('application/x-task-id', item.dataset.taskId);
                event.dataTransfer.setData('application/x-task-origin', 'day');
                event.dataTransfer.setData('text/plain', item.dataset.taskId);
                event.dataTransfer.effectAllowed = 'move';
                item.classList.add('ring-2', 'ring-amber-400');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('ring-2', 'ring-amber-400');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initializeTaskDragBetweenLists);
</script>

<div id="srToast" class="hidden fixed bottom-5 right-5 text-white text-sm px-4 py-2 rounded-lg shadow-lg bg-blue-600"></div>
@endsection
