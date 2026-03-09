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

    <!-- Filtros Sidebar -->
    @php
        $activeFilterCount = collect([
            $filters['q'] ?? '',
            $filters['status'] ?? '',
            $filters['type'] ?? '',
            $filters['priority'] ?? '',
        ])->filter(fn($v) => filled($v))->count();
    @endphp
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-5 mb-4 sm:mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                <span class="font-semibold text-gray-800">Filtros de agenda</span>
                <span class="ml-2">Activos: {{ $activeFilterCount }}</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('technician-schedule.my-agenda', array_filter(['date' => $date, 'technician_id' => request('technician_id')])) }}"
                    class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg transition-colors">
                    Limpiar
                </a>
                <button type="button" id="openAgendaFiltersSidebar"
                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sliders-h mr-1"></i>Abrir filtros
                </button>
            </div>
        </div>
    </div>

    <div id="agendaFiltersOverlay" class="hidden fixed inset-0 bg-black/40 z-40"></div>
    <aside id="agendaFiltersSidebar" class="fixed top-0 right-0 h-full w-full sm:w-[430px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-out">
        <div class="h-full flex flex-col">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Filtrar Mi Agenda</h3>
                <button type="button" id="closeAgendaFiltersSidebar" class="text-gray-500 hover:text-gray-800">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('technician-schedule.my-agenda') }}" class="flex-1 overflow-y-auto p-5 space-y-4">
                <input type="hidden" name="date" value="{{ $date }}">
                @if(request('technician_id'))
                    <input type="hidden" name="technician_id" value="{{ request('technician_id') }}">
                @endif

                <div>
                    <label for="filter_q" class="block text-xs font-semibold text-gray-600 mb-1">Buscar</label>
                    <input type="text" id="filter_q" name="q"
                        value="{{ $filters['q'] ?? request('q') }}"
                        placeholder="Código, título o descripción"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filter_status" class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                    <select id="filter_status" name="status"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="confirmed" {{ ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmada</option>
                        <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>En progreso</option>
                        <option value="blocked" {{ ($filters['status'] ?? '') === 'blocked' ? 'selected' : '' }}>Bloqueada</option>
                        <option value="in_review" {{ ($filters['status'] ?? '') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completada</option>
                        <option value="rescheduled" {{ ($filters['status'] ?? '') === 'rescheduled' ? 'selected' : '' }}>Reprogramada</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>

                <div>
                    <label for="filter_type" class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                    <select id="filter_type" name="type"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="impact" {{ ($filters['type'] ?? '') === 'impact' ? 'selected' : '' }}>Impacto</option>
                        <option value="regular" {{ ($filters['type'] ?? '') === 'regular' ? 'selected' : '' }}>Regular</option>
                    </select>
                </div>

                <div>
                    <label for="filter_priority" class="block text-xs font-semibold text-gray-600 mb-1">Prioridad</label>
                    <select id="filter_priority" name="priority"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <option value="critical" {{ ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' }}>Crítica</option>
                        <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>Alta</option>
                        <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Media</option>
                        <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Baja</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg transition-colors">
                        Aplicar filtros
                    </button>
                    <a href="{{ route('technician-schedule.my-agenda', array_filter(['date' => $date, 'technician_id' => request('technician_id')])) }}"
                        class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg transition-colors">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </aside>

    @php
        $totalTasks = $tasks->count();
        $activeTaskCount = $tasks->whereNotIn('status', ['completed', 'cancelled'])->count();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Timeline de tareas -->
        <div class="lg:col-span-6 xl:col-span-7 space-y-6">
            <div class="bg-white rounded-lg shadow-md p-6 relative" id="tasksDropZone" data-dropzone>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Tareas del Día</h2>
                        <p class="text-sm text-gray-500">Arrastra las tareas para ordenarlas</p>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                            <i class="fas fa-list-ul"></i><span id="tasksTotalCount">{{ $totalTasks }}</span> en agenda
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                            <i class="fas fa-layer-group"></i>{{ $activeTaskCount }} activas
                        </span>
                    </div>
                </div>
                <div id="dropHint" class="hidden absolute inset-3 border-2 border-dashed border-blue-400 rounded-lg bg-blue-50/60 flex items-center justify-center text-blue-700 font-semibold text-sm">
                    Suelta aquí para agendar la tarea en este día
                </div>


        @if($tasks->isEmpty())
            <div id="tasksEmptyState" class="text-center py-12">
                <i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No tienes tareas programadas para este día</p>
                <p class="text-gray-400 text-sm mt-2">Arrastra tareas desde "Tareas Abiertas" para construir tu agenda de hoy.</p>
            </div>
        @else
                    @php
                        $activeTasks = $tasks->where('status', '!=', 'completed')->values();
                    @endphp

                    @if($autoQueueMode)
                        @php
                            $ordered = $activeTasks->sortByDesc(fn($task) => (int) ($task->queue_score ?? 0))->values();
                            $nowTask = $ordered->first();
                            $nextTasks = $ordered->slice(1, 3)->values();
                            $backlogTasks = $ordered->slice(4)->values();
                        @endphp

                        @if($ordered->isEmpty())
                            <div class="text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-sm text-gray-500">No hay tareas activas para priorizar.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @if($nowTask)
                                    <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-bold text-emerald-800"><i class="fas fa-play-circle mr-1"></i>Ahora</h3>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold cursor-help"
                                                  title="Urgencia: {{ (int) ($nowTask->queue_priority_score ?? 0) }} | Criticidad: {{ (int) ($nowTask->queue_criticality_score ?? 0) }} | Servicio: {{ (int) ($nowTask->queue_service_score ?? 0) }} | Tipo: {{ (int) ($nowTask->queue_type_score ?? 0) }} | Antigüedad: {{ (int) ($nowTask->queue_age_score ?? 0) }}">
                                                {{ (int) ($nowTask->queue_score ?? 0) }}
                                            </span>
                                        </div>
                                        <p class="font-mono text-[11px] text-gray-500">{{ $nowTask->task_code }}</p>
                                        <p class="text-base font-semibold text-gray-900">{{ $nowTask->title }}</p>
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-xs text-gray-600">{{ substr($nowTask->scheduled_start_time, 0, 5) }} · {{ $nowTask->formatted_duration }}</span>
                                            <div class="flex items-center gap-2">
                                                @if($nowTask->status === 'pending')
                                                    <form action="{{ route('tasks.start', $nowTask) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-semibold">Iniciar</button>
                                                    </form>
                                                @elseif($nowTask->status === 'in_progress')
                                                    <button onclick="openCompleteModal({{ $nowTask->id }})" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold">Completar</button>
                                                @endif
                                                <a href="{{ route('tasks.show', $nowTask) }}" class="text-xs text-emerald-700 hover:text-emerald-900 font-semibold">Ver</a>
                                            </div>
                                        </div>
                                    </section>
                                @endif

                                <section class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-sm font-bold text-blue-800"><i class="fas fa-forward mr-1"></i>Siguiente</h3>
                                        <span class="text-xs font-semibold text-blue-800">{{ $nextTasks->count() }}</span>
                                    </div>
                                    @if($nextTasks->isEmpty())
                                        <p class="text-xs text-gray-500">No hay tareas siguientes.</p>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($nextTasks as $task)
                                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-sm font-semibold text-gray-800">{{ Str::limit($task->title, 72) }}</p>
                                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-semibold">{{ (int) ($task->queue_score ?? 0) }}</span>
                                                    </div>
                                                    <div class="mt-1 flex items-center justify-between">
                                                        <span class="text-xs text-gray-500">{{ substr($task->scheduled_start_time, 0, 5) }}</span>
                                                        <a href="{{ route('tasks.show', $task) }}" class="text-xs text-blue-700 hover:text-blue-900 font-semibold">Ver</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>

                                <section class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-sm font-bold text-gray-800"><i class="fas fa-list mr-1"></i>Backlog de hoy</h3>
                                        <span class="text-xs font-semibold text-gray-700">{{ $backlogTasks->count() }}</span>
                                    </div>
                                    @if($backlogTasks->isEmpty())
                                        <p class="text-xs text-gray-500">No hay backlog pendiente.</p>
                                    @else
                                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                            @foreach($backlogTasks as $task)
                                                <div class="bg-white border border-gray-200 rounded-lg p-2.5 flex items-center justify-between gap-2">
                                                    <p class="text-sm text-gray-800">{{ Str::limit($task->title, 70) }}</p>
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-semibold">{{ (int) ($task->queue_score ?? 0) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            </div>
                        @endif
                    @else
                    <div class="space-y-4" id="tasksList">
                        @foreach($activeTasks as $task)
                            <div class="border border-{{ $task->type === 'impact' ? 'red' : 'blue' }}-200 bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-50/50 rounded-lg p-4"
                                 draggable="{{ $manualQueueMode ? 'true' : 'false' }}"
                                 data-day-task
                                 data-task-id="{{ $task->id }}"
                                 data-task-status="{{ $task->status }}"
                                 data-task-show-url="{{ route('tasks.show', $task) }}">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            <div class="flex items-start gap-2 lg:gap-3">
                                @if($manualQueueMode)
                                    <div class="text-gray-400 mt-1 cursor-grab active:cursor-grabbing select-none"
                                         draggable="true" data-drag-handle data-task-id="{{ $task->id }}"
                                         title="Arrastra para ordenar">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                @endif
                                <div class="lg:w-24 px-1 py-0.5">
                                <div class="text-lg font-bold text-gray-800 leading-none">{{ substr($task->scheduled_start_time, 0, 5) }}</div>
                                <div class="text-[10px] uppercase tracking-wide text-gray-400 mt-1">Inicio</div>
                                <div class="mt-1.5 text-xs text-gray-600 leading-none">
                                    <i class="fas fa-hourglass-half text-gray-400 mr-1"></i>{{ $task->formatted_duration }}
                                </div>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
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
                                <h3 class="text-[17px] font-semibold text-gray-800 mb-1.5 leading-snug overflow-hidden"
                                    style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                                    {{ $task->title }}
                                </h3>

                                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                    <span class="font-mono text-[11px] text-gray-600">
                                        {{ $task->task_code }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800 font-semibold">
                                        {{ $statusLabels[$task->status] ?? strtoupper($task->status) }}
                                    </span>
                                    @if($task->priority)
                                        <span class="px-2 py-0.5 text-[11px] rounded-full bg-{{ $task->priority_color }}-100 text-{{ $task->priority_color }}-800 font-semibold">
                                            <i class="fas fa-flag mr-1"></i>{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 text-[11px] rounded-full {{ $autoQueueMode ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }} font-semibold cursor-help"
                                          title="Urgencia: {{ (int) ($task->queue_priority_score ?? 0) }} | Criticidad: {{ (int) ($task->queue_criticality_score ?? 0) }} | Servicio: {{ (int) ($task->queue_service_score ?? 0) }} | Tipo: {{ (int) ($task->queue_type_score ?? 0) }} | Antigüedad: {{ (int) ($task->queue_age_score ?? 0) }}">
                                        <i class="fas fa-calculator mr-1"></i>Score {{ (int) ($task->queue_score ?? 0) }}
                                    </span>
                                </div>

                                @php
                                    $serviceName = $task->serviceRequest?->subService?->service?->name;
                                    $subServiceName = $task->serviceRequest?->subService?->name;
                                    $serviceLabel = $serviceName && $subServiceName
                                        ? "{$serviceName} · {$subServiceName}"
                                        : ($subServiceName ?? $serviceName);
                                @endphp
                                <div class="flex items-center gap-2 text-xs text-gray-600 min-w-0" title="{{ $serviceLabel ?: 'Sin servicio' }}">
                                    @if($task->serviceRequest)
                                        <span class="text-green-700 font-medium shrink-0">{{ $task->serviceRequest->ticket_number }}</span>
                                        <span class="text-gray-300 shrink-0">|</span>
                                    @endif
                                    <span class="truncate">{{ $serviceLabel ?: 'Sin servicio' }}</span>
                                </div>

                                <!-- SLA si aplica -->
                                @if($task->slaCompliance)
                                    <div class="mt-1 inline-flex items-center gap-2 px-2 py-0.5 rounded bg-{{ $task->slaCompliance->compliance_status === 'within_sla' ? 'green' : ($task->slaCompliance->compliance_status === 'at_risk' ? 'yellow' : 'red') }}-100">
                                        <i class="fas fa-clock"></i>
                                        <span class="text-[11px] font-semibold">
                                            SLA: {{ $slaLabels[$task->slaCompliance->compliance_status] ?? $task->slaCompliance->compliance_status }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Acciones -->
                            <div class="flex flex-row lg:flex-col gap-2 lg:items-stretch shrink-0">
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
                                <a href="{{ route('tasks.show', $task) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-2 rounded-lg text-center whitespace-nowrap">
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
                        <p class="text-gray-400 text-sm mt-2">Arrastra tareas desde "Tareas Abiertas" para construir tu agenda de hoy.</p>
                    </div>
                    @endif
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
                        @foreach($completedTasks->sortBy(function ($task) {
                            $order = $task->scheduled_order ?? 0;
                            $time = $task->scheduled_start_time ?? '';
                            return sprintf('%05d-%s', $order, $time);
                        }) as $task)
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
        <aside class="lg:col-span-6 xl:col-span-5 lg:sticky lg:top-4 h-fit">
            <div class="bg-white rounded-lg shadow-md p-5" id="openTasksPanel">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-tasks text-blue-600 mr-1"></i>
                        Tareas Abiertas
                    </h3>
                    <span class="text-xs text-gray-500">{{ $openTasks->count() }}</span>
                </div>
                <p class="text-xs text-gray-500 mb-3">Tareas abiertas sin agenda asignada.</p>
                @php
                    $openUrgentTasks = $openTasks->filter(fn($task) => (int) ($task->queue_score ?? 0) >= 700)->values();
                    $openHighTasks = $openTasks->filter(fn($task) => (int) ($task->queue_score ?? 0) >= 520 && (int) ($task->queue_score ?? 0) < 700)->values();
                    $openNormalTasks = $openTasks->filter(fn($task) => (int) ($task->queue_score ?? 0) < 520)->values();
                    $openSections = [
                        ['title' => 'Urgentes', 'icon' => 'fa-bolt', 'header' => 'text-red-700', 'count' => $openUrgentTasks->count(), 'tasks' => $openUrgentTasks],
                        ['title' => 'Alta prioridad', 'icon' => 'fa-arrow-up', 'header' => 'text-amber-700', 'count' => $openHighTasks->count(), 'tasks' => $openHighTasks],
                        ['title' => 'Resto', 'icon' => 'fa-list', 'header' => 'text-slate-700', 'count' => $openNormalTasks->count(), 'tasks' => $openNormalTasks],
                    ];
                @endphp

                <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1" data-open-list>
                    @if($openTasks->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-gray-300 mb-3"></i>
                            <p class="text-sm text-gray-500">No hay tareas abiertas</p>
                        </div>
                    @else
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm border border-gray-200 rounded-lg p-2">
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" data-open-jump="urgent" class="text-left px-2 py-1.5 rounded-md bg-red-50 hover:bg-red-100 transition-colors">
                                    <p class="text-[10px] uppercase tracking-wide text-red-700 font-bold">Urgentes</p>
                                    <p class="text-sm font-semibold text-red-900">{{ $openUrgentTasks->count() }}</p>
                                </button>
                                <button type="button" data-open-jump="high" class="text-left px-2 py-1.5 rounded-md bg-amber-50 hover:bg-amber-100 transition-colors">
                                    <p class="text-[10px] uppercase tracking-wide text-amber-700 font-bold">Alta</p>
                                    <p class="text-sm font-semibold text-amber-900">{{ $openHighTasks->count() }}</p>
                                </button>
                                <button type="button" data-open-jump="normal" class="text-left px-2 py-1.5 rounded-md bg-slate-50 hover:bg-slate-100 transition-colors">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-700 font-bold">Resto</p>
                                    <p class="text-sm font-semibold text-slate-900">{{ $openNormalTasks->count() }}</p>
                                </button>
                            </div>
                        </div>

                        @foreach($openSections as $section)
                            @if($section['count'] > 0)
                                @php
                                    $sectionKey = $loop->index === 0 ? 'urgent' : ($loop->index === 1 ? 'high' : 'normal');
                                @endphp
                                <section id="open-section-{{ $sectionKey }}" class="rounded-lg border border-gray-200 bg-white" data-open-section="{{ $sectionKey }}">
                                    <button type="button" class="w-full flex items-center justify-between px-3 py-2" data-open-toggle="{{ $sectionKey }}">
                                        <h4 class="text-xs font-bold uppercase tracking-wide {{ $section['header'] }}">
                                            <i class="fas {{ $section['icon'] }} mr-1"></i>{{ $section['title'] }}
                                        </h4>
                                        <span class="inline-flex items-center gap-2">
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-semibold">{{ $section['count'] }}</span>
                                            <i class="fas fa-chevron-up text-[10px] text-gray-500 transition-transform" data-open-toggle-icon="{{ $sectionKey }}"></i>
                                        </span>
                                    </button>
                                    <div class="space-y-3 p-3 pt-0" data-open-section-body="{{ $sectionKey }}">
                                        @foreach($section['tasks'] as $task)
                                            @php
                                                $score = (int) ($task->queue_score ?? 0);
                                                $scoreBadgeClass = $score >= 700
                                                    ? 'bg-red-100 text-red-800'
                                                    : ($score >= 520 ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800');
                                            @endphp
                                            <a href="{{ route('tasks.show', $task) }}"
                                               draggable="true"
                                               data-open-task
                                               data-task-id="{{ $task->id }}"
                                               data-task-status="{{ $task->status }}"
                                               class="block border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <p class="text-xs font-semibold text-gray-500">{{ $task->task_code }}</p>
                                                    @php
                                                        $openStatusLabels = [
                                                            'pending' => 'Pendiente',
                                                            'confirmed' => 'Confirmada',
                                                            'in_progress' => 'En progreso',
                                                            'blocked' => 'Bloqueada',
                                                            'in_review' => 'En revisión',
                                                            'completed' => 'Completada',
                                                            'rescheduled' => 'Reprogramada',
                                                            'cancelled' => 'Cancelada',
                                                        ];
                                                    @endphp
                                                    <div class="shrink-0 flex justify-end">
                                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[10px] px-2 py-1 rounded-full {{ $scoreBadgeClass }} font-semibold leading-none cursor-help"
                                                              title="Urgencia: {{ (int) ($task->queue_priority_score ?? 0) }} | Criticidad: {{ (int) ($task->queue_criticality_score ?? 0) }} | Servicio: {{ (int) ($task->queue_service_score ?? 0) }} | Tipo: {{ (int) ($task->queue_type_score ?? 0) }} | Antigüedad: {{ (int) ($task->queue_age_score ?? 0) }}">
                                                            <span>{{ $openStatusLabels[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                                            <span class="opacity-60">•</span>
                                                            <span>Score {{ $score }}</span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <p class="text-sm font-medium text-gray-800 leading-snug overflow-hidden"
                                                   style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">
                                                    {{ $task->title }}
                                                </p>

                                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                                                    @php
                                                        $openServiceName = $task->serviceRequest?->subService?->service?->name;
                                                        $openSubServiceName = $task->serviceRequest?->subService?->name;
                                                        $openServiceLabel = $openServiceName && $openSubServiceName
                                                            ? "{$openServiceName} · {$openSubServiceName}"
                                                            : ($openSubServiceName ?? $openServiceName);
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 min-w-0 max-w-full">
                                                        <i class="fas fa-concierge-bell text-indigo-500"></i>
                                                        <span class="truncate" title="{{ $openServiceLabel ?: 'Sin servicio' }}">{{ $openServiceLabel ?: 'Sin servicio' }}</span>
                                                    </span>
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
                                    </div>
                                </section>
                            @endif
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

<!-- Menú contextual de tareas -->
<div id="taskContextMenu" class="hidden fixed z-[70] min-w-[200px] bg-white border border-gray-200 rounded-lg shadow-xl py-1">
    <button type="button" data-context-action="view" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <i class="fas fa-eye mr-2 text-gray-500"></i>Ver tarea
    </button>
    <button type="button" data-context-action="schedule" class="hidden w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <i class="fas fa-calendar-plus mr-2 text-blue-500"></i>Agendar hoy
    </button>
    <button type="button" data-context-action="start" class="hidden w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <i class="fas fa-play mr-2 text-green-600"></i>Iniciar
    </button>
    <button type="button" data-context-action="complete" class="hidden w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <i class="fas fa-check mr-2 text-indigo-600"></i>Completar
    </button>
    <button type="button" data-context-action="unschedule" class="hidden w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <i class="fas fa-undo mr-2 text-amber-600"></i>Devolver a abiertas
    </button>
</div>

<script>
    function changeTechnician() {
        const technicianId = document.getElementById('technicianSelector').value;
        const date = document.getElementById('dateSelector').value;
        const params = new URLSearchParams(window.location.search);
        params.delete('queue_strategy');
        params.set('date', date);
        if (technicianId) {
            params.set('technician_id', technicianId);
        } else {
            params.delete('technician_id');
        }
        window.location.href = `{{ route('technician-schedule.my-agenda') }}?${params.toString()}`;
    }

    function changeDate() {
        const date = document.getElementById('dateSelector').value;
        const technicianId = document.getElementById('technicianSelector')?.value;
        const params = new URLSearchParams(window.location.search);
        params.delete('queue_strategy');
        params.set('date', date);
        if (technicianId) {
            params.set('technician_id', technicianId);
        } else {
            params.delete('technician_id');
        }
        window.location.href = `{{ route('technician-schedule.my-agenda') }}?${params.toString()}`;
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
    const startTaskUrlTemplate = @json(route('tasks.start', ['task' => '__ID__']));
    const reorderDayTasksUrl = @json(route('technician-schedule.reorder-day-tasks'));

    function buildScheduleQuickUrl(taskId) {
        return scheduleQuickUrlTemplate.replace('__ID__', taskId);
    }

    function buildUnscheduleTaskUrl(taskId) {
        return unscheduleTaskUrlTemplate.replace('__ID__', taskId);
    }

    function buildStartTaskUrl(taskId) {
        return startTaskUrlTemplate.replace('__ID__', taskId);
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

    const manualQueueModeActive = @json($manualQueueMode);

    function initializeTaskDragBetweenLists() {
        const openTasks = document.querySelectorAll('[data-open-task]');
        const dayTasks = document.querySelectorAll('[data-day-task]');
        const dayTaskHandles = document.querySelectorAll('[data-drag-handle]');
        const dropZone = document.getElementById('tasksDropZone');
        const dropHint = document.getElementById('dropHint');
        const openList = document.querySelector('[data-open-list]');
        const tasksList = document.getElementById('tasksList');
        let draggingDayTask = null;
        let initialDayOrder = [];
        let dropPlaceholder = null;
        let currentDragOrigin = null;

        const getDayTaskIds = () => {
            if (!tasksList) return [];
            return Array.from(tasksList.querySelectorAll('[data-day-task]'))
                .map((item) => item.dataset.taskId)
                .filter(Boolean);
        };

        const getDragAfterElement = (container, y) => {
            const draggableElements = [...container.querySelectorAll('[data-day-task]:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        };

        const ensurePlaceholder = (item) => {
            if (!tasksList || !item) return null;
            if (!dropPlaceholder) {
                dropPlaceholder = document.createElement('div');
                dropPlaceholder.className = 'border-2 border-dashed border-amber-300 rounded-lg bg-amber-50/70';
            }
            const rect = item.getBoundingClientRect();
            dropPlaceholder.style.height = `${rect.height}px`;
            dropPlaceholder.style.marginBottom = '1rem';
            return dropPlaceholder;
        };

        const clearPlaceholder = () => {
            if (dropPlaceholder && dropPlaceholder.parentNode) {
                dropPlaceholder.parentNode.removeChild(dropPlaceholder);
            }
        };

        const saveDayTaskOrder = async (taskIds) => {
            if (!taskIds || taskIds.length === 0) return;
            const dateValue = document.getElementById('dateSelector')?.value;
            if (!dateValue) return;
            const technicianId = document.getElementById('technicianSelector')?.value;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const payload = {
                task_ids: taskIds,
                scheduled_date: dateValue,
            };

            if (technicianId) {
                payload.technician_id = technicianId;
            }

            try {
                const response = await fetch(reorderDayTasksUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    showToast(data.message || 'No se pudo guardar el orden.', 'error');
                    return;
                }
                showToast('Orden actualizado.', 'success');
            } catch (error) {
                console.error(error);
                showToast('Error al guardar el orden.', 'error');
            }
        };

        const startDayDrag = (item, event) => {
            if (!item) return;
            event.dataTransfer.setData('application/x-task-id', item.dataset.taskId);
            event.dataTransfer.setData('application/x-task-origin', 'day');
            event.dataTransfer.setData('text/plain', item.dataset.taskId);
            event.dataTransfer.effectAllowed = 'move';
            draggingDayTask = item;
            initialDayOrder = getDayTaskIds();
            currentDragOrigin = 'day';
            item.classList.add('ring-2', 'ring-amber-400', 'opacity-70', 'dragging');
            ensurePlaceholder(item);
        };

        const endDayDrag = (item) => {
            if (!item) return;
            item.classList.remove('ring-2', 'ring-amber-400', 'opacity-70', 'dragging');
            clearPlaceholder();
            currentDragOrigin = null;
            if (tasksList && !tasksList.contains(item)) {
                draggingDayTask = null;
                initialDayOrder = [];
                return;
            }
            const newOrder = getDayTaskIds();
            if (JSON.stringify(initialDayOrder) !== JSON.stringify(newOrder)) {
                saveDayTaskOrder(newOrder);
            }
            draggingDayTask = null;
            initialDayOrder = [];
        };

        if (dropZone) {
            dropZone.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (currentDragOrigin === 'open') {
                    event.dataTransfer.dropEffect = 'move';
                    dropHint?.classList.remove('hidden');
                } else {
                    dropHint?.classList.add('hidden');
                }
            });

            dropZone.addEventListener('dragleave', (event) => {
                if (!dropZone.contains(event.relatedTarget)) {
                    dropHint?.classList.add('hidden');
                }
            });

            dropZone.addEventListener('drop', async (event) => {
                event.preventDefault();
                dropHint?.classList.add('hidden');
                const origin = currentDragOrigin;
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
                        const params = new URLSearchParams(window.location.search);
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

        if (tasksList) {
            tasksList.addEventListener('dragover', (event) => {
                if (currentDragOrigin !== 'day' || !draggingDayTask) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                dropHint?.classList.add('hidden');
                const afterElement = getDragAfterElement(tasksList, event.clientY);
                const placeholder = ensurePlaceholder(draggingDayTask);
                if (!afterElement) {
                    if (placeholder && placeholder.parentNode !== tasksList) {
                        tasksList.appendChild(placeholder);
                    } else if (placeholder) {
                        tasksList.appendChild(placeholder);
                    }
                    tasksList.appendChild(draggingDayTask);
                } else {
                    if (placeholder && placeholder !== afterElement) {
                        tasksList.insertBefore(placeholder, afterElement);
                    }
                    tasksList.insertBefore(draggingDayTask, afterElement);
                }
            });

            tasksList.addEventListener('drop', (event) => {
                event.preventDefault();
                event.stopPropagation();
                clearPlaceholder();
            });
        }

        openTasks.forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('application/x-task-id', item.dataset.taskId);
                event.dataTransfer.setData('application/x-task-origin', 'open');
                event.dataTransfer.setData('text/plain', item.dataset.taskId);
                event.dataTransfer.effectAllowed = 'move';
                currentDragOrigin = 'open';
                item.classList.add('ring-2', 'ring-blue-400');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('ring-2', 'ring-blue-400');
                currentDragOrigin = null;
            });
        });

        if (openList) {
            openList.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                if (currentDragOrigin === 'day') {
                    openList.classList.add('ring-2', 'ring-amber-300', 'bg-amber-50');
                }
            });

            openList.addEventListener('dragleave', (event) => {
                if (!openList.contains(event.relatedTarget)) {
                    openList.classList.remove('ring-2', 'ring-amber-300', 'bg-amber-50');
                }
            });

            openList.addEventListener('drop', async (event) => {
                event.preventDefault();
                openList.classList.remove('ring-2', 'ring-amber-300', 'bg-amber-50');
                const origin = currentDragOrigin;
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

        if (manualQueueModeActive) {
            dayTasks.forEach((item) => {
                item.addEventListener('dragstart', (event) => {
                    startDayDrag(item, event);
                });

                item.addEventListener('dragend', () => {
                    endDayDrag(item);
                });
            });

            dayTaskHandles.forEach((handle) => {
                handle.addEventListener('dragstart', (event) => {
                    const item = handle.closest('[data-day-task]');
                    startDayDrag(item, event);
                });

                handle.addEventListener('dragend', () => {
                    const item = handle.closest('[data-day-task]');
                    endDayDrag(item);
                });
            });
        }
    }

    function initializeTaskContextMenu() {
        const menu = document.getElementById('taskContextMenu');
        if (!menu) return;

        let currentTask = null;

        const hideMenu = () => {
            menu.classList.add('hidden');
            currentTask = null;
        };

        const setActionVisible = (action, visible) => {
            const button = menu.querySelector(`[data-context-action="${action}"]`);
            if (!button) return;
            button.classList.toggle('hidden', !visible);
        };

        const showMenu = (event, taskElement, sourceType) => {
            event.preventDefault();

            currentTask = {
                id: taskElement.dataset.taskId,
                status: taskElement.dataset.taskStatus || '',
                showUrl: taskElement.dataset.taskShowUrl || taskElement.getAttribute('href') || '',
                source: sourceType,
            };

            setActionVisible('view', true);
            setActionVisible('schedule', sourceType === 'open');
            setActionVisible('unschedule', sourceType === 'day');
            setActionVisible('start', sourceType === 'day' && currentTask.status === 'pending');
            setActionVisible('complete', sourceType === 'day' && currentTask.status === 'in_progress');

            menu.classList.remove('hidden');

            const menuRect = menu.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const left = Math.min(event.clientX, viewportWidth - menuRect.width - 12);
            const top = Math.min(event.clientY, viewportHeight - menuRect.height - 12);
            menu.style.left = `${Math.max(8, left)}px`;
            menu.style.top = `${Math.max(8, top)}px`;
        };

        const scheduleTaskToToday = async (taskId) => {
            const dateValue = document.getElementById('dateSelector')?.value;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                throw new Error(data.message || 'No se pudo agendar la tarea.');
            }
            showToast(`Tarea agendada (${data.scheduled_at}).`, 'success');
            setTimeout(() => window.location.reload(), 500);
        };

        const unscheduleTask = async (taskId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                throw new Error(data.message || 'No se pudo devolver la tarea.');
            }
            showToast('Tarea devuelta a tareas abiertas.', 'success');
            setTimeout(() => window.location.reload(), 500);
        };

        const startTask = async (taskId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(buildStartTaskUrl(taskId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                },
            });
            if (!response.ok) {
                throw new Error('No se pudo iniciar la tarea.');
            }
            showToast('Tarea iniciada.', 'success');
            setTimeout(() => window.location.reload(), 500);
        };

        document.querySelectorAll('[data-open-task]').forEach((item) => {
            item.addEventListener('contextmenu', (event) => showMenu(event, item, 'open'));
        });

        document.querySelectorAll('[data-day-task]').forEach((item) => {
            item.addEventListener('contextmenu', (event) => showMenu(event, item, 'day'));
        });

        menu.addEventListener('click', async (event) => {
            const actionButton = event.target.closest('[data-context-action]');
            if (!actionButton || !currentTask) return;

            const action = actionButton.dataset.contextAction;
            const taskCtx = { ...currentTask };
            hideMenu();

            try {
                if (action === 'view' && taskCtx.showUrl) {
                    window.location.href = taskCtx.showUrl;
                    return;
                }
                if (action === 'schedule') {
                    await scheduleTaskToToday(taskCtx.id);
                    return;
                }
                if (action === 'unschedule') {
                    await unscheduleTask(taskCtx.id);
                    return;
                }
                if (action === 'start') {
                    await startTask(taskCtx.id);
                    return;
                }
                if (action === 'complete') {
                    openCompleteModal(taskCtx.id);
                }
            } catch (error) {
                console.error(error);
                showToast(error.message || 'No se pudo ejecutar la acción.', 'error');
            }
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('#taskContextMenu')) {
                hideMenu();
            }
        });

        window.addEventListener('scroll', hideMenu, true);
        window.addEventListener('resize', hideMenu);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') hideMenu();
        });
    }

    function initializeOpenTaskSections() {
        const openList = document.querySelector('[data-open-list]');
        if (!openList) return;

        const toggleButtons = document.querySelectorAll('[data-open-toggle]');
        const jumpButtons = document.querySelectorAll('[data-open-jump]');

        const setSectionState = (key, expanded) => {
            const body = document.querySelector(`[data-open-section-body="${key}"]`);
            const icon = document.querySelector(`[data-open-toggle-icon="${key}"]`);
            if (!body || !icon) return;
            body.classList.toggle('hidden', !expanded);
            icon.classList.toggle('rotate-180', !expanded);
        };

        toggleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-open-toggle');
                const body = document.querySelector(`[data-open-section-body="${key}"]`);
                if (!body) return;
                const expanded = body.classList.contains('hidden');
                setSectionState(key, expanded);
            });
        });

        jumpButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-open-jump');
                const section = document.getElementById(`open-section-${key}`);
                if (!section) return;
                setSectionState(key, true);
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // Reducir ruido inicial: mantener "Resto" colapsado por defecto.
        setSectionState('normal', false);
    }

    const agendaFiltersSidebar = document.getElementById('agendaFiltersSidebar');
    const agendaFiltersOverlay = document.getElementById('agendaFiltersOverlay');
    const openAgendaFiltersSidebar = document.getElementById('openAgendaFiltersSidebar');
    const closeAgendaFiltersSidebar = document.getElementById('closeAgendaFiltersSidebar');

    function showAgendaFiltersSidebar() {
        if (!agendaFiltersSidebar || !agendaFiltersOverlay) return;
        agendaFiltersOverlay.classList.remove('hidden');
        agendaFiltersSidebar.classList.remove('translate-x-full');
        document.body.classList.add('overflow-hidden');
    }

    function hideAgendaFiltersSidebar() {
        if (!agendaFiltersSidebar || !agendaFiltersOverlay) return;
        agendaFiltersSidebar.classList.add('translate-x-full');
        agendaFiltersOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    if (openAgendaFiltersSidebar) {
        openAgendaFiltersSidebar.addEventListener('click', showAgendaFiltersSidebar);
    }

    if (closeAgendaFiltersSidebar) {
        closeAgendaFiltersSidebar.addEventListener('click', hideAgendaFiltersSidebar);
    }

    if (agendaFiltersOverlay) {
        agendaFiltersOverlay.addEventListener('click', hideAgendaFiltersSidebar);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideAgendaFiltersSidebar();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        initializeTaskDragBetweenLists();
        initializeOpenTaskSections();
        initializeTaskContextMenu();
    });
</script>

<div id="srToast" class="hidden fixed bottom-5 right-5 text-white text-sm px-4 py-2 rounded-lg shadow-lg bg-blue-600"></div>
@endsection
