@extends('layouts.app')

@section('title', 'Inicio')

@section('hidePageHeader', true)

@section('content')
@php
    $currentWorkspace = $currentWorkspace ?? null;
@endphp
<div class="my-space-surface -mx-3 sm:-mx-4 md:-mx-6 lg:-mx-8 px-3 sm:px-4 md:px-6 lg:px-8 py-6"
     x-data="mySpaceApp()" x-init="init()" role="main">

    {{-- ===== CABECERA ===== --}}
    <div class="mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 sm:px-8 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="far fa-calendar mr-1"></i>
                        {{ now()->translatedFormat('l, d \d\e F Y') }}
                    </p>
                    <div class="flex items-center gap-3 mt-1">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                            Centro de Gestión
                        </h1>
                        {{-- Captura rápida: crear solicitud sin salir del inicio (modo alta demanda) --}}
                        <a href="{{ route('service-requests.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-red-700 active:bg-red-800 transition focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1"
                           title="Registrar una nueva solicitud">
                            <i class="fas fa-plus"></i>
                            <span>Nueva solicitud</span>
                        </a>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        @if($stats['today_tasks'] > 0)
                            {{ $stats['today_tasks'] }} {{ $stats['today_tasks'] === 1 ? 'tarea programada' : 'tareas programadas' }} para hoy.
                            @if($stats['overdue_tasks'] > 0)
                                <span class="text-red-600 font-medium">{{ $stats['overdue_tasks'] }} vencida{{ $stats['overdue_tasks'] > 1 ? 's' : '' }}.</span>
                            @endif
                        @elseif($stats['pending_tasks'] > 0)
                            {{ $stats['pending_tasks'] }} tareas pendientes por organizar.
                        @else
                            Sin tareas programadas hoy.
                        @endif
                    </p>
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 min-w-0 lg:min-w-[480px]">
                    <button type="button" @click="activeTab = 'today'" class="text-center rounded-xl px-3 py-3 border border-gray-200 bg-gray-50 hover:bg-gray-100 transition">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Hoy</div>
                        <div class="text-xl font-bold text-gray-900" x-text="stats.today_tasks">{{ $stats['today_tasks'] }}</div>
                    </button>
                    <button type="button" @click="activeTab = 'today'" class="text-center rounded-xl px-3 py-3 border border-green-200 bg-green-50 hover:bg-green-100 transition">
                        <div class="text-xs uppercase tracking-wide text-green-700">Hechas</div>
                        <div class="text-xl font-bold text-green-700" x-text="stats.today_completed">{{ $stats['today_completed'] }}</div>
                    </button>
                    <button type="button" @click="activeTab = 'sla'" class="text-center rounded-xl px-3 py-3 border {{ $stats['at_risk_families'] > 0 ? 'border-red-200 bg-red-50 hover:bg-red-100' : 'border-blue-200 bg-blue-50 hover:bg-blue-100' }} transition">
                        <div class="text-xs uppercase tracking-wide {{ $stats['at_risk_families'] > 0 ? 'text-red-700' : 'text-blue-700' }}">Cobertura</div>
                        <div class="text-xl font-bold {{ $stats['at_risk_families'] > 0 ? 'text-red-700' : 'text-blue-700' }}">{{ $coverageGlobal['covered_families'] }}/{{ $coverageGlobal['total_families'] }}</div>
                    </button>
                    <button type="button" @click="activeTab = 'alerts'" class="text-center rounded-xl px-3 py-3 border {{ $stats['active_alerts'] > 0 ? 'border-amber-200 bg-amber-50 hover:bg-amber-100' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }} transition">
                        <div class="text-xs uppercase tracking-wide {{ $stats['active_alerts'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">Alertas</div>
                        <div class="text-xl font-bold {{ $stats['active_alerts'] > 0 ? 'text-amber-700' : 'text-gray-900' }}" x-text="stats.active_alerts">{{ $stats['active_alerts'] }}</div>
                    </button>
                    <button type="button" @click="activeTab = 'requests'" class="text-center rounded-xl px-3 py-3 border border-gray-200 bg-gray-50 hover:bg-gray-100 transition">
                        <div class="text-xs uppercase tracking-wide text-gray-500">SRs</div>
                        <div class="text-xl font-bold text-gray-900">{{ $stats['my_srs'] }}</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Botón flotante de captura rápida (solo móvil/tablet, siempre accesible tras el scroll) --}}
    <a href="{{ route('service-requests.create') }}"
       class="lg:hidden fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 pl-4 pr-5 py-3.5 bg-red-600 text-white text-sm font-semibold rounded-full shadow-lg hover:bg-red-700 active:bg-red-800 transition focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
       aria-label="Registrar una nueva solicitud" title="Nueva solicitud">
        <i class="fas fa-plus text-base"></i>
        <span>Nueva solicitud</span>
    </a>

    {{-- ===== TABS ===== --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-0.5 overflow-x-auto pb-px scrollbar-hide" aria-label="Secciones de Mi Espacio">
            @php
                $tabs = [
                    ['key' => 'today', 'icon' => 'fa-bolt', 'label' => 'Mi Día', 'color' => 'indigo', 'count' => $stats['today_tasks']],
                    ['key' => 'meetings', 'icon' => 'fa-users', 'label' => 'Reuniones', 'color' => 'teal', 'count' => $stats['upcoming_meetings'] + $stats['pending_commitments'] ?: null],
                    ['key' => 'pending', 'icon' => 'fa-inbox', 'label' => 'Pendientes', 'color' => 'amber', 'count' => $stats['pending_tasks'] + $stats['overdue_tasks']],
                    ['key' => 'requests', 'icon' => 'fa-headset', 'label' => 'Solicitudes', 'color' => 'purple', 'count' => $stats['my_srs']],
                    ['key' => 'sla', 'icon' => 'fa-shield-halved', 'label' => 'SLA', 'color' => 'cyan', 'count' => null],
                    ['key' => 'alerts', 'icon' => 'fa-bell', 'label' => 'Alertas', 'color' => 'rose', 'count' => $stats['active_alerts']],
                    ['key' => 'activity', 'icon' => 'fa-clock-rotate-left', 'label' => 'Actividad', 'color' => 'emerald', 'count' => null],
                    ['key' => 'week', 'icon' => 'fa-calendar-week', 'label' => 'Semana', 'color' => 'blue', 'count' => $stats['week_tasks']],
                    ['key' => 'reminders', 'icon' => 'fa-sticky-note', 'label' => 'Recordatorios', 'color' => 'violet', 'count' => $stats['reminders_due']],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button type="button" @click="activeTab = '{{ $tab['key'] }}'"
                        :class="activeTab === '{{ $tab['key'] }}' ? 'border-{{ $tab['color'] }}-600 text-{{ $tab['color'] }}-700 bg-{{ $tab['color'] }}-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-3 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition whitespace-nowrap">
                    <i class="fas {{ $tab['icon'] }} mr-1"></i>
                    <span class="hidden sm:inline">{{ $tab['label'] }}</span>
                    @if($tab['count'])
                        <span class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold rounded-full bg-{{ $tab['color'] }}-100 text-{{ $tab['color'] }}-700">{{ $tab['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ===== TAB: MI DÍA ===== --}}
    <div x-show="activeTab === 'today'" x-transition.opacity>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Columna principal: tareas --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Agenda visual del día --}}
                @if($todayBlocks->isNotEmpty() || $todayMeetings->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3"><i class="far fa-clock mr-1"></i> Agenda de hoy</h3>
                        <div class="flex gap-1.5 overflow-x-auto pb-1">
                            @foreach($todayMeetings as $meeting)
                                @php $srUrl = $currentWorkspace ? route('service-requests.show', $meeting->service_request_id) : null; @endphp
                                <a href="{{ $srUrl ?? '#' }}" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg border text-xs border-teal-200 bg-teal-50 hover:bg-teal-100 transition">
                                    <i class="fas fa-users text-teal-600"></i>
                                    <div>
                                        <div class="font-medium text-gray-800">{{ Str::limit($meeting->serviceRequest?->title, 25) }}</div>
                                        <div class="text-gray-500">{{ $meeting->start_time }} · {{ $meeting->expected_duration_minutes }}min</div>
                                    </div>
                                </a>
                            @endforeach
                            @foreach($todayBlocks as $block)
                                @php $info = $block->block_info; @endphp
                                <div class="flex-shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg border text-xs"
                                     style="border-color: {{ $info['color'] }}40; background: {{ $info['color'] }}10;">
                                    <i class="fas {{ $info['icon'] }}" style="color: {{ $info['color'] }};"></i>
                                    <div>
                                        <div class="font-medium text-gray-800">{{ $block->title ?: $info['label'] }}</div>
                                        <div class="text-gray-500">{{ \Carbon\Carbon::parse($block->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($block->end_time)->format('H:i') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Lista de tareas de hoy --}}
                @if($todayTasks->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-indigo-50 flex items-center justify-center">
                            <i class="fas fa-coffee text-xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Sin tareas programadas hoy</h3>
                        <p class="text-sm text-gray-500 mb-3">Organiza tareas pendientes o revisa la semana.</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            @if($currentWorkspace)
                                <a href="{{ route('technician-schedule.my-agenda') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                    <i class="fas fa-calendar-plus"></i> Mi Agenda
                                </a>
                            @endif
                            @if($stats['pending_tasks'] > 0)
                                <button type="button" @click="activeTab = 'pending'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 text-xs font-medium rounded-lg border border-amber-200 hover:bg-amber-100 transition">
                                    <i class="fas fa-inbox"></i> {{ $stats['pending_tasks'] }} pendientes
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($todayTasks as $task)
                            @include('my-space.partials._task-card', ['task' => $task, 'showActions' => true])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Sidebar: evidencia + mini stats --}}
            <div class="space-y-4">
                {{-- Evidencia pendiente --}}
                @if($needsEvidenceTasks->isNotEmpty())
                    <div class="bg-amber-50/60 rounded-xl border border-amber-200 p-4">
                        <h3 class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">
                            <i class="fas fa-camera mr-1"></i> Evidencia pendiente ({{ $needsEvidenceTasks->count() }})
                        </h3>
                        <div class="space-y-2">
                            @foreach($needsEvidenceTasks->take(5) as $task)
                                @php $taskUrl = $currentWorkspace ? route('tasks.show', $task) : null; @endphp
                                <div class="bg-white rounded-lg border border-amber-100 p-2.5">
                                    @if($taskUrl)
                                        <a href="{{ $taskUrl }}" class="text-xs font-medium text-gray-900 hover:text-indigo-700 transition line-clamp-1">{{ $task->title }}</a>
                                    @else
                                        <p class="text-xs font-medium text-gray-900 line-clamp-1">{{ $task->title }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $task->task_code }} • Completada {{ $task->completed_at->diffForHumans(short:true) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Resumen rápido --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Resumen</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-600">Tareas bloqueadas</dt>
                            <dd class="text-base font-bold {{ $stats['blocked_tasks'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $stats['blocked_tasks'] }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-600">Sin evidencia</dt>
                            <dd class="text-base font-bold {{ $stats['needs_evidence'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $stats['needs_evidence'] }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-600">Solicitudes activas</dt>
                            <dd class="text-base font-bold text-purple-600">{{ $stats['my_srs'] }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-600">Cobertura familias</dt>
                            <dd class="text-base font-bold {{ $stats['at_risk_families'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $coverageGlobal['covered_families'] }}/{{ $coverageGlobal['total_families'] }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-600">Próximos 7 días</dt>
                            <dd class="text-base font-bold text-blue-600">{{ $stats['week_tasks'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TAB: PENDIENTES ===== --}}
    <div x-show="activeTab === 'pending'" x-cloak x-transition.opacity>
        @if($pendingTasks->isEmpty() && $overdueTasks->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-green-50 flex items-center justify-center"><i class="fas fa-check-double text-xl text-green-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Todo al día</h3>
                <p class="text-sm text-gray-500">No tienes tareas pendientes ni vencidas.</p>
            </div>
        @else
            @if($overdueTasks->isNotEmpty())
                <div class="mb-6">
                    <h3 class="flex items-center gap-2 text-xs font-semibold text-rose-700 uppercase tracking-wide mb-3"><i class="fas fa-exclamation-triangle"></i> Vencidas ({{ $overdueTasks->count() }})</h3>
                    <div class="space-y-2">
                        @foreach($overdueTasks as $task)
                            @include('my-space.partials._task-card', ['task' => $task, 'showActions' => true, 'showOverdue' => true])
                        @endforeach
                    </div>
                </div>
            @endif
            @if($pendingTasks->isNotEmpty())
                <div>
                    <h3 class="flex items-center gap-2 text-xs font-semibold text-amber-700 uppercase tracking-wide mb-3"><i class="fas fa-inbox"></i> Sin programar ({{ $pendingTasks->count() }})</h3>
                    <div class="space-y-2">
                        @foreach($pendingTasks as $task)
                            @include('my-space.partials._task-card', ['task' => $task, 'showActions' => true])
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ===== TAB: SOLICITUDES ===== --}}
    <div x-show="activeTab === 'requests'" x-cloak x-transition.opacity>
        @if($myServiceRequests->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-purple-50 flex items-center justify-center"><i class="fas fa-headset text-xl text-purple-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Sin solicitudes asignadas</h3>
                <p class="text-sm text-gray-500">No tienes solicitudes activas asignadas.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($myServiceRequests as $sr)
                    @php
                        $statusColors = [
                            'PENDIENTE' => 'bg-gray-100 text-gray-700',
                            'ACEPTADA' => 'bg-blue-100 text-blue-700',
                            'EN_PROCESO' => 'bg-indigo-100 text-indigo-700',
                            'RESUELTA' => 'bg-green-100 text-green-700',
                            'PAUSADA' => 'bg-amber-100 text-amber-700',
                            'REABIERTO' => 'bg-orange-100 text-orange-700',
                        ];
                        $critColors = [
                            'CRITICA' => 'border-l-rose-500 bg-rose-50/30',
                            'ALTA' => 'border-l-orange-500 bg-orange-50/20',
                            'MEDIA' => 'border-l-blue-400 bg-white',
                            'BAJA' => 'border-l-gray-300 bg-white',
                        ];
                        $srUrl = $currentWorkspace ? route('service-requests.show', $sr) : null;
                        $familyName = $sr->subService?->service?->family?->name;
                    @endphp
                    <div class="group relative bg-white rounded-xl border border-gray-100 shadow-sm border-l-4 {{ $critColors[$sr->criticality_level] ?? 'bg-white' }} p-4 hover:shadow-md transition"
                         style="cursor: context-menu;"
                         data-sr-id="{{ $sr->id }}"
                         data-sr-ticket="{{ $sr->ticket_number }}"
                         data-sr-status="{{ $sr->status }}"
                         data-sr-url="{{ $srUrl ?? '' }}"
                         @contextmenu.prevent="openContextMenu($event)"
                         title="Clic derecho para acciones rápidas">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                @if($srUrl)
                                    <a href="{{ $srUrl }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-700 transition line-clamp-1">{{ $sr->title }}</a>
                                @else
                                    <h4 class="text-sm font-semibold text-gray-900 line-clamp-1">{{ $sr->title }}</h4>
                                @endif
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    @if($srUrl)
                                        <a href="{{ $srUrl }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-mono font-medium transition">{{ $sr->ticket_number }}</a>
                                    @else
                                        <span class="text-xs text-gray-500 font-mono">{{ $sr->ticket_number }}</span>
                                    @endif
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold uppercase {{ $statusColors[$sr->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ str_replace('_', ' ', $sr->status) }}
                                    </span>
                                    @if($familyName)
                                        <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ $familyName }}</span>
                                    @endif
                                    @if($sr->company)
                                        <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ Str::limit($sr->company->name, 18) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                @if($sr->resolution_deadline)
                                    @php $remaining = now()->diffInHours($sr->resolution_deadline, false); @endphp
                                    <span class="text-xs font-medium {{ $remaining < 0 ? 'text-rose-600' : ($remaining < 24 ? 'text-amber-600' : 'text-gray-500') }}">
                                        <i class="far fa-clock mr-0.5"></i>
                                        @if($remaining < 0)
                                            Vencida {{ abs($remaining) }}h
                                        @else
                                            {{ $remaining }}h restantes
                                        @endif
                                    </span>
                                @endif
                                <div class="flex items-center gap-1">
                                    <button type="button"
                                            @click.stop="openContextMenuFromButton($event)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                            aria-label="Acciones rápidas" title="Acciones rápidas">
                                        <i class="fas fa-ellipsis-vertical text-xs"></i>
                                    </button>
                                    @if($srUrl)
                                        <a href="{{ $srUrl }}" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" aria-label="Ver solicitud" title="Ver detalle">
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($currentWorkspace)
                <div class="mt-4 text-center">
                    <a href="{{ route('service-requests.index') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">Ver todas las solicitudes <i class="fas fa-arrow-right text-xs"></i></a>
                </div>
            @endif
        @endif
    </div>

    {{-- ===== TAB: COBERTURA POR FAMILIA ===== --}}
    <div x-show="activeTab === 'sla'" x-cloak x-transition.opacity>
        @if($coverageByEntity->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-cyan-50 flex items-center justify-center"><i class="fas fa-shield-halved text-xl text-cyan-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Sin datos de cobertura</h3>
                <p class="text-sm text-gray-500">No hay un contrato activo con corte abierto o no hay familias configuradas.</p>
            </div>
        @else
            {{-- Resumen global --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Cobertura contractual</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Estado de los compromisos por entidad en el corte actual</p>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="text-center">
                            <div class="text-xl font-bold {{ $coverageGlobal['at_risk_families'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $coverageGlobal['covered_families'] }}/{{ $coverageGlobal['total_families'] }}</div>
                            <div class="text-xs text-gray-400">familias activas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-indigo-600">{{ $coverageGlobal['total_requests_in_cut'] }}</div>
                            <div class="text-xs text-gray-400">SRs en corte</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-600">{{ $coverageGlobal['active_subservices'] }}/{{ $coverageGlobal['total_subservices'] }}</div>
                            <div class="text-xs text-gray-400">servicios tocados</div>
                        </div>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    @if($coverageGlobal['total_families'] > 0)
                        <div class="h-full rounded-full {{ $coverageGlobal['coverage_percentage'] >= 100 ? 'bg-green-500' : ($coverageGlobal['coverage_percentage'] >= 50 ? 'bg-amber-400' : 'bg-red-500') }}"
                             style="width: {{ min($coverageGlobal['coverage_percentage'], 100) }}%"></div>
                    @endif
                </div>
                @if($coverageGlobal['at_risk_families'] > 0)
                    <p class="text-xs text-red-600 font-medium mt-2"><i class="fas fa-triangle-exclamation mr-1"></i>{{ $coverageGlobal['at_risk_families'] }} familia{{ $coverageGlobal['at_risk_families'] > 1 ? 's' : '' }} sin actividad en el corte</p>
                @endif
            </div>

            {{-- Por entidad (colapsable con info relevante) --}}
            <div class="space-y-3">
                @foreach($coverageByEntity as $entity)
                    @php
                        $daysLeft = (int) now()->diffInDays($entity->cut_end, false);
                        $entityPct = $entity->total_families > 0 ? round(($entity->covered_families / $entity->total_families) * 100, 0) : 0;
                        $entityAtRisk = $entity->total_families - $entity->covered_families;
                        $entityStatus = $entityAtRisk === 0 ? 'ok' : ($entityPct >= 50 ? 'warning' : 'danger');
                    @endphp
                    <div x-data="{ entityOpen: false }" class="bg-white rounded-xl border shadow-sm overflow-hidden
                        {{ $entityStatus === 'danger' ? 'border-red-200' : ($entityStatus === 'warning' ? 'border-amber-200' : 'border-gray-200') }}">

                        {{-- Cabecera con info relevante visible sin abrir --}}
                        <button type="button" @click="entityOpen = !entityOpen"
                                class="w-full px-4 py-3.5 text-left hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                                    {{ $entityStatus === 'ok' ? 'bg-green-100' : ($entityStatus === 'warning' ? 'bg-amber-100' : 'bg-red-100') }}">
                                    <i class="fas fa-building text-sm {{ $entityStatus === 'ok' ? 'text-green-600' : ($entityStatus === 'warning' ? 'text-amber-600' : 'text-red-600') }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 truncate">{{ $entity->company_name }}</span>
                                        @if($entityAtRisk > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">{{ $entityAtRisk }} sin actividad</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5 text-xs text-gray-500">
                                        <span>{{ $entity->cut_name }}</span>
                                        <span class="text-gray-300">·</span>
                                        <span class="{{ $daysLeft <= 5 ? 'text-red-600 font-medium' : ($daysLeft <= 10 ? 'text-amber-600' : '') }}">
                                            @if($daysLeft < 0) Vencido hace {{ abs($daysLeft) }}d
                                            @elseif($daysLeft === 0) Vence hoy
                                            @else {{ $daysLeft }}d restantes
                                            @endif
                                        </span>
                                        <span class="text-gray-300">·</span>
                                        <span>{{ $entity->total_requests }} SRs completadas</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="hidden sm:flex items-center gap-2 w-24">
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $entityStatus === 'ok' ? 'bg-green-500' : ($entityStatus === 'warning' ? 'bg-amber-400' : 'bg-red-500') }}"
                                                 style="width: {{ $entityPct }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold {{ $entityStatus === 'ok' ? 'text-green-600' : ($entityStatus === 'warning' ? 'text-amber-600' : 'text-red-600') }}">{{ $entity->covered_families }}/{{ $entity->total_families }}</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200" :class="entityOpen && 'rotate-180'"></i>
                                </div>
                            </div>
                        </button>

                        {{-- Familias --}}
                        <div x-show="entityOpen" x-cloak x-transition.opacity class="border-t border-gray-100">
                            <div class="p-3 space-y-1.5">
                                @foreach($entity->families as $family)
                                    <div x-data="{ familyOpen: false }" class="rounded-lg border overflow-hidden
                                        {{ !$family->has_activity ? 'border-red-200 bg-red-50/20' : 'border-gray-200' }}">

                                        <button type="button" @click="familyOpen = !familyOpen"
                                                class="w-full px-3 py-2 flex items-center gap-2.5 text-left hover:bg-gray-50/50 transition">
                                            <div class="w-1.5 h-1.5 rounded-full shrink-0
                                                {{ !$family->has_activity ? 'bg-red-500' : ($family->active_subservices === $family->total_subservices ? 'bg-green-500' : 'bg-amber-400') }}"></div>
                                            <span class="flex-1 text-xs font-medium text-gray-800 truncate">{{ $family->family_name }}</span>
                                            <div class="flex items-center gap-3 text-xs shrink-0">
                                                @if(!$family->has_activity)
                                                    <span class="font-semibold text-red-600">Sin actividad</span>
                                                @else
                                                    <span class="text-gray-600"><span class="font-bold">{{ $family->requests_in_cut }}</span> SRs</span>
                                                @endif
                                                <span class="text-gray-400">{{ $family->active_subservices }}/{{ $family->total_subservices }}</span>
                                                <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200" :class="familyOpen && 'rotate-180'"></i>
                                            </div>
                                        </button>

                                        <div x-show="familyOpen" x-cloak x-transition.opacity class="border-t border-gray-100 bg-gray-50/50 px-3 py-2">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-gray-400">
                                                        <th class="text-left font-medium pb-1 pl-4">Sub-servicio</th>
                                                        <th class="text-left font-medium pb-1">Servicio</th>
                                                        <th class="text-right font-medium pb-1">SRs</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($family->subservices->sortByDesc('requests_in_cut') as $sub)
                                                        <tr class="{{ $sub->has_activity ? 'text-gray-700' : 'text-gray-400' }}">
                                                            <td class="py-1 pl-1">
                                                                <span class="inline-flex items-center gap-1.5">
                                                                    @if($sub->has_activity)
                                                                        <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                                                    @else
                                                                        <i class="far fa-circle text-gray-300 text-xs"></i>
                                                                    @endif
                                                                    {{ $sub->name }}
                                                                </span>
                                                            </td>
                                                            <td class="py-1 text-gray-400">{{ $sub->service_name }}</td>
                                                            <td class="py-1 text-right font-semibold {{ $sub->has_activity ? 'text-green-700' : '' }}">{{ $sub->requests_in_cut }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===== TAB: ACTIVIDAD RECIENTE ===== --}}
    <div x-show="activeTab === 'activity'" x-cloak x-transition.opacity>
        @if($recentActivity->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-emerald-50 flex items-center justify-center"><i class="fas fa-clock-rotate-left text-xl text-emerald-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Sin actividad reciente</h3>
                <p class="text-sm text-gray-500">No se registró actividad en las últimas 24 horas.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4"><i class="fas fa-stream mr-1"></i> Últimas 24 horas</h3>
                <div class="relative border-l-2 border-gray-200 ml-3 space-y-4">
                    @foreach($recentActivity as $activity)
                        @php
                            $actionIcons = [
                                'started' => 'fas fa-play text-blue-500',
                                'completed' => 'fas fa-check text-green-500',
                                'blocked' => 'fas fa-ban text-red-500',
                                'unblocked' => 'fas fa-lock-open text-teal-500',
                                'created' => 'fas fa-plus text-indigo-500',
                                'assigned' => 'fas fa-user-check text-purple-500',
                                'rescheduled' => 'fas fa-calendar text-amber-500',
                            ];
                            $actionLabels = [
                                'started' => 'Iniciaste',
                                'completed' => 'Completaste',
                                'blocked' => 'Bloqueaste',
                                'unblocked' => 'Desbloqueaste',
                                'created' => 'Creaste',
                                'assigned' => 'Asignaste',
                                'rescheduled' => 'Reprogramaste',
                            ];
                            $icon = $actionIcons[$activity->action] ?? 'fas fa-circle text-gray-400';
                            $label = $actionLabels[$activity->action] ?? ucfirst($activity->action);
                            $taskUrl = ($currentWorkspace && $activity->task) ? route('tasks.show', $activity->task_id) : null;
                        @endphp
                        <div class="relative pl-6">
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-white border-2 border-gray-200 flex items-center justify-center">
                                <i class="{{ $icon }} text-[8px]"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">
                                    <span class="font-medium">{{ $label }}</span>
                                    @if($activity->task)
                                        @if($taskUrl)
                                            <a href="{{ $taskUrl }}" class="text-indigo-600 hover:text-indigo-800 font-medium transition">{{ $activity->task->title }}</a>
                                        @else
                                            <span class="font-medium text-gray-900">{{ $activity->task->title }}</span>
                                        @endif
                                        <span class="text-gray-400 text-xs font-mono ml-1">{{ $activity->task->task_code }}</span>
                                    @endif
                                </p>
                                @if($activity->notes)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($activity->notes, 80) }}</p>
                                @endif
                                <span class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans(short: true) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ===== TAB: REUNIONES ===== --}}
    <div x-show="activeTab === 'meetings'" x-cloak x-transition.opacity>
        @if($upcomingMeetings->isEmpty() && $pendingCommitments->isEmpty() && $meetingsWithoutAttendance->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-teal-50 flex items-center justify-center"><i class="fas fa-users text-xl text-teal-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Sin reuniones próximas</h3>
                <p class="text-sm text-gray-500">No tienes reuniones programadas ni compromisos pendientes.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Columna principal: reuniones próximas --}}
                <div class="lg:col-span-2 space-y-4">
                    @if($upcomingMeetings->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3"><i class="fas fa-calendar-check mr-1"></i> Próximas reuniones</h3>
                            <div class="space-y-2">
                                @foreach($upcomingMeetings as $meeting)
                                    @php
                                        $isToday = $meeting->scheduled_date->isToday();
                                        $isTomorrow = $meeting->scheduled_date->isTomorrow();
                                        $srUrl = $currentWorkspace ? route('service-requests.show', $meeting->service_request_id) : null;
                                    @endphp
                                    <div class="bg-white rounded-xl border {{ $isToday ? 'border-teal-300 bg-teal-50/30' : 'border-gray-200' }} p-4 hover:shadow-md transition">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $isToday ? 'bg-teal-100' : 'bg-gray-100' }}">
                                                <i class="fas fa-users text-sm {{ $isToday ? 'text-teal-600' : 'text-gray-500' }}"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                @if($srUrl)
                                                    <a href="{{ $srUrl }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-700 transition line-clamp-1">{{ $meeting->serviceRequest?->title }}</a>
                                                @else
                                                    <h4 class="text-sm font-semibold text-gray-900 line-clamp-1">{{ $meeting->serviceRequest?->title }}</h4>
                                                @endif
                                                <div class="flex items-center gap-2 mt-1 flex-wrap text-xs">
                                                    <span class="font-medium {{ $isToday ? 'text-teal-700' : ($isTomorrow ? 'text-amber-600' : 'text-gray-600') }}">
                                                        @if($isToday) Hoy
                                                        @elseif($isTomorrow) Mañana
                                                        @else {{ $meeting->scheduled_date->translatedFormat('D d M') }}
                                                        @endif
                                                        · {{ $meeting->start_time }}
                                                    </span>
                                                    <span class="text-gray-400">{{ $meeting->expected_duration_minutes }} min</span>
                                                    @if($meeting->serviceRequest?->company)
                                                        <span class="text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ Str::limit($meeting->serviceRequest->company->name, 15) }}</span>
                                                    @endif
                                                    @if($meeting->location)
                                                        <span class="text-gray-500"><i class="fas fa-location-dot mr-0.5"></i>{{ Str::limit($meeting->location, 20) }}</span>
                                                    @endif
                                                </div>
                                                @if($meeting->participants->isNotEmpty())
                                                    <div class="flex items-center gap-1 mt-1.5">
                                                        <span class="text-xs text-gray-400">{{ $meeting->participants->count() }} participantes</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex flex-col items-end gap-1 shrink-0">
                                                @if($meeting->virtual_meeting_url)
                                                    <a href="{{ $meeting->virtual_meeting_url }}" target="_blank" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Unirse a reunión virtual">
                                                        <i class="fas fa-video text-xs"></i>
                                                    </a>
                                                @endif
                                                @if($srUrl)
                                                    <a href="{{ $srUrl }}" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Ver solicitud">
                                                        <i class="fas fa-arrow-right text-xs"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Compromisos pendientes --}}
                    @if($pendingCommitments->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3"><i class="fas fa-handshake mr-1"></i> Compromisos pendientes ({{ $pendingCommitments->count() }})</h3>
                            <div class="space-y-2">
                                @foreach($pendingCommitments as $commitment)
                                    @php
                                        $taskUrl = $currentWorkspace ? route('tasks.show', $commitment) : null;
                                        $srUrl = ($currentWorkspace && $commitment->serviceRequest) ? route('service-requests.show', $commitment->service_request_id) : null;
                                        $isOverdue = $commitment->due_date && $commitment->due_date->isPast();
                                    @endphp
                                    <div class="bg-white rounded-lg border {{ $isOverdue ? 'border-red-200 bg-red-50/20' : 'border-gray-200' }} p-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            @if($taskUrl)
                                                <a href="{{ $taskUrl }}" class="text-xs font-semibold text-gray-900 hover:text-indigo-700 transition truncate block">{{ $commitment->title }}</a>
                                            @else
                                                <p class="text-xs font-semibold text-gray-900 truncate">{{ $commitment->title }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-0.5 text-xs">
                                                @if($srUrl)
                                                    <a href="{{ $srUrl }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $commitment->serviceRequest?->ticket_number }}</a>
                                                @endif
                                                @if($commitment->due_date)
                                                    <span class="{{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                                        <i class="far fa-calendar mr-0.5"></i>{{ $commitment->due_date->format('d/m') }}
                                                    </span>
                                                @endif
                                                @if($commitment->serviceRequest?->company)
                                                    <span class="text-gray-500 bg-gray-100 px-1 py-0.5 rounded">{{ Str::limit($commitment->serviceRequest->company->name, 12) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <form action="{{ route('my-space.tasks.start', $commitment) }}" method="POST" class="inline">@csrf
                                                <button type="submit" class="p-1.5 rounded text-blue-600 hover:bg-blue-50 transition" title="Iniciar"><i class="fas fa-play text-xs"></i></button>
                                            </form>
                                            <form action="{{ route('my-space.tasks.complete', $commitment) }}" method="POST" class="inline">@csrf
                                                <button type="submit" class="p-1.5 rounded text-green-600 hover:bg-green-50 transition" title="Completar"><i class="fas fa-check text-xs"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar: sin asistencia --}}
                <div class="space-y-4">
                    @if($meetingsWithoutAttendance->isNotEmpty())
                        <div class="bg-amber-50/60 rounded-xl border border-amber-200 p-4">
                            <h3 class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">
                                <i class="fas fa-clipboard-check mr-1"></i> Pendiente de asistencia ({{ $meetingsWithoutAttendance->count() }})
                            </h3>
                            <p class="text-xs text-amber-700 mb-2">Reuniones pasadas sin registro de asistencia.</p>
                            <div class="space-y-2">
                                @foreach($meetingsWithoutAttendance as $meeting)
                                    @php $srUrl = $currentWorkspace ? route('service-requests.show', $meeting->service_request_id) : null; @endphp
                                    <div class="bg-white rounded-lg border border-amber-100 p-2.5">
                                        @if($srUrl)
                                            <a href="{{ $srUrl }}" class="text-xs font-medium text-gray-900 hover:text-indigo-700 transition line-clamp-1">{{ $meeting->serviceRequest?->title }}</a>
                                        @else
                                            <p class="text-xs font-medium text-gray-900 line-clamp-1">{{ $meeting->serviceRequest?->title }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $meeting->scheduled_date->format('d/m') }} · {{ $meeting->participants->whereNull('attended')->count() }} sin registrar</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Resumen rápido reuniones --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Resumen</h3>
                        <dl class="space-y-2">
                            <div class="flex justify-between items-center">
                                <dt class="text-sm text-gray-600">Reuniones próximas</dt>
                                <dd class="text-base font-bold text-teal-600">{{ $stats['upcoming_meetings'] }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-sm text-gray-600">Compromisos pendientes</dt>
                                <dd class="text-base font-bold {{ $stats['pending_commitments'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $stats['pending_commitments'] }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-sm text-gray-600">Sin asistencia</dt>
                                <dd class="text-base font-bold {{ $stats['meetings_without_attendance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $stats['meetings_without_attendance'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===== TAB: ALERTAS ===== --}}
    <div x-show="activeTab === 'alerts'" x-cloak x-transition.opacity>
        @if($activeAlerts->isEmpty() && $taskAlerts->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-green-50 flex items-center justify-center"><i class="fas fa-bell-slash text-xl text-green-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900">Sin alertas activas</h3>
                <p class="text-sm text-gray-500">Todo en orden.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($activeAlerts as $alert)
                    @php
                        $sevStyles = ['critica' => 'border-l-rose-500 bg-rose-50/40', 'alta' => 'border-l-orange-500 bg-orange-50/30', 'media' => 'border-l-amber-400 bg-amber-50/20', 'baja' => 'border-l-blue-400 bg-blue-50/20'];
                        $sevIcons = ['critica' => 'fas fa-radiation text-rose-600', 'alta' => 'fas fa-exclamation-triangle text-orange-600', 'media' => 'fas fa-exclamation-circle text-amber-600', 'baja' => 'fas fa-info-circle text-blue-500'];
                        $alertUrl = null;
                        if ($alert->alertable_type === \App\Models\ServiceRequest::class && $currentWorkspace) { $alertUrl = route('service-requests.show', $alert->alertable_id); }
                        elseif ($alert->alertable_type === \App\Models\Task::class && $currentWorkspace) { $alertUrl = route('tasks.show', $alert->alertable_id); }
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm border-l-4 {{ $sevStyles[$alert->severity] ?? '' }} p-3.5 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <i class="{{ $sevIcons[$alert->severity] ?? 'fas fa-bell text-gray-400' }} mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                @if($alertUrl)
                                    <a href="{{ $alertUrl }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-700 transition">{{ $alert->title }}</a>
                                @else
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $alert->title }}</h4>
                                @endif
                                <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">{{ $alert->message }}</p>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span class="text-xs text-gray-400"><i class="far fa-clock mr-0.5"></i>{{ $alert->alert_at->diffForHumans(short:true) }}</span>
                                    @if($alertUrl)
                                        <a href="{{ $alertUrl }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"><i class="fas fa-external-link-alt mr-0.5"></i>Ver</a>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-col gap-0.5 shrink-0">
                                <form action="{{ route('operational-alerts.mark-read', $alert) }}" method="POST">@csrf<button type="submit" class="p-1.5 rounded text-gray-400 hover:text-green-600 hover:bg-green-50 transition" title="Leída"><i class="fas fa-check text-xs"></i></button></form>
                                <form action="{{ route('operational-alerts.dismiss', $alert) }}" method="POST">@csrf<button type="submit" class="p-1.5 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Descartar"><i class="fas fa-times text-xs"></i></button></form>
                            </div>
                        </div>
                    </div>
                @endforeach
                @foreach($taskAlerts as $alert)
                    @php
                        $typeInfo = \App\Models\TaskAlert::$alertTypes[$alert->alert_type] ?? ['color'=>'gray','icon'=>'fa-bell','label'=>'Alerta'];
                        $taskAlertUrl = ($currentWorkspace && $alert->task) ? route('tasks.show', $alert->task_id) : null;
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm border-l-4 border-l-{{ $typeInfo['color'] }}-400 p-3.5">
                        <div class="flex items-start gap-3">
                            <i class="fas {{ $typeInfo['icon'] }} text-{{ $typeInfo['color'] }}-500 mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-bold uppercase text-{{ $typeInfo['color'] }}-600 bg-{{ $typeInfo['color'] }}-50 px-1.5 py-0.5 rounded">{{ $typeInfo['label'] }}</span>
                                <p class="text-sm text-gray-700 mt-1">{{ $alert->message }}</p>
                                @if($alert->task)
                                    <div class="mt-1">
                                        @if($taskAlertUrl)
                                            <a href="{{ $taskAlertUrl }}" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition"><i class="fas fa-arrow-right"></i>{{ $alert->task->task_code }}</a>
                                        @else
                                            <span class="text-xs text-gray-500">{{ $alert->task->task_code }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <form action="{{ route('task-alerts.dismiss', $alert) }}" method="POST">@csrf<button type="submit" class="p-1.5 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Descartar"><i class="fas fa-times text-xs"></i></button></form>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($currentWorkspace)
                <div class="mt-4 text-center"><a href="{{ route('operational-alerts.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Ver todas las alertas <i class="fas fa-arrow-right text-xs"></i></a></div>
            @endif
        @endif
    </div>

    {{-- ===== TAB: SEMANA ===== --}}
    <div x-show="activeTab === 'week'" x-cloak x-transition.opacity>
        @if($weekTasks->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-blue-50 flex items-center justify-center"><i class="fas fa-calendar-week text-xl text-blue-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Semana libre</h3>
                <p class="text-sm text-gray-500">No hay tareas en los próximos 7 días.</p>
                @if($currentWorkspace)
                    <a href="{{ route('technician-schedule.my-agenda') }}" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition"><i class="fas fa-calendar-plus"></i> Planificar</a>
                @endif
            </div>
        @else
            @php $tasksByDay = $weekTasks->groupBy(fn($t) => $t->scheduled_date->format('Y-m-d')); @endphp
            <div class="space-y-5">
                @foreach($tasksByDay as $date => $dayTasks)
                    @php $carbonDate = \Carbon\Carbon::parse($date); @endphp
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold">{{ $carbonDate->format('d') }}</span>
                            {{ $carbonDate->translatedFormat('l') }}
                            <span class="text-xs text-gray-400 ml-auto">{{ $dayTasks->count() }} tarea{{ $dayTasks->count() > 1 ? 's' : '' }}</span>
                        </h3>
                        <div class="space-y-2 pl-9">
                            @foreach($dayTasks as $task)
                                @include('my-space.partials._task-card', ['task' => $task, 'showActions' => false, 'compact' => true])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @if($currentWorkspace)
                <div class="mt-4 text-center"><a href="{{ route('technician-schedule.my-agenda') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Ver agenda completa <i class="fas fa-arrow-right text-xs"></i></a></div>
            @endif
        @endif
    </div>

    {{-- ===== TAB: RECORDATORIOS ===== --}}
    <div x-show="activeTab === 'reminders'" x-cloak x-transition.opacity>
        @if($reminders->isEmpty() && $upcomingReminders->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-violet-50 flex items-center justify-center"><i class="fas fa-sticky-note text-xl text-violet-400"></i></div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Sin recordatorios</h3>
                <p class="text-sm text-gray-500">Crea uno abajo.</p>
            </div>
        @else
            @if($reminders->isNotEmpty())
                <div class="mb-5">
                    <h3 class="text-xs font-semibold text-violet-700 uppercase tracking-wide mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Activos ({{ $reminders->count() }})</h3>
                    <div class="space-y-2">
                        @foreach($reminders as $reminder)
                            <div class="bg-violet-50/50 rounded-xl border border-violet-200 p-3.5 flex items-start gap-3">
                                <i class="fas fa-bell text-violet-500 mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 font-medium">{{ $reminder->message }}</p>
                                    <span class="text-xs text-gray-500"><i class="far fa-clock mr-0.5"></i>{{ $reminder->alert_at->diffForHumans() }}</span>
                                </div>
                                <div class="flex gap-0.5 shrink-0">
                                    <form action="{{ route('operational-alerts.resolve', $reminder) }}" method="POST">@csrf<input type="hidden" name="resolution_notes" value="Resuelto desde Mi Espacio"><button type="submit" class="p-1.5 rounded text-green-600 hover:bg-green-50 transition" title="Resolver"><i class="fas fa-check text-xs"></i></button></form>
                                    <form action="{{ route('operational-alerts.dismiss', $reminder) }}" method="POST">@csrf<button type="submit" class="p-1.5 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Descartar"><i class="fas fa-times text-xs"></i></button></form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($upcomingReminders->isNotEmpty())
                <div class="mb-5">
                    <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2"><i class="far fa-clock mr-1"></i> Próximos</h3>
                    <div class="space-y-2">
                        @foreach($upcomingReminders as $reminder)
                            <div class="bg-white rounded-xl border border-gray-200 p-3.5 flex items-start gap-3">
                                <i class="far fa-bell text-gray-400 mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700">{{ $reminder->message }}</p>
                                    <span class="text-xs text-gray-400"><i class="far fa-calendar mr-0.5"></i>{{ $reminder->alert_at->translatedFormat('d M Y') }}</span>
                                </div>
                                <form action="{{ route('operational-alerts.dismiss', $reminder) }}" method="POST">@csrf<button type="submit" class="p-1.5 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Cancelar"><i class="fas fa-trash-alt text-xs"></i></button></form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
        {{-- Crear recordatorio --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 mt-4">
            <form action="{{ route('operational-alerts.reminder.store') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="reminder_note" class="text-xs font-medium text-gray-600 mb-1 block">Nuevo recordatorio</label>
                    <input type="text" name="reminder_note" id="reminder_note" required minlength="3" maxlength="500" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500" placeholder="Ej: Revisar respuesta del cliente...">
                </div>
                <div class="w-full sm:w-36">
                    <label for="reminder_date" class="text-xs font-medium text-gray-600 mb-1 block">Fecha</label>
                    <input type="date" name="reminder_date" id="reminder_date" required min="{{ now()->format('Y-m-d') }}" value="{{ now()->addDay()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition whitespace-nowrap"><i class="fas fa-plus mr-1"></i> Crear</button>
            </form>
        </div>
    </div>

    {{-- ===== ACCESOS RÁPIDOS ===== --}}
    <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @if($currentWorkspace)
                @php
                    $quickLinks = [
                        ['route' => 'technician-schedule.my-agenda', 'icon' => 'fa-calendar-alt', 'color' => 'indigo', 'label' => 'Mi Agenda'],
                        ['route' => 'tasks.index', 'icon' => 'fa-tasks', 'color' => 'emerald', 'label' => 'Tareas'],
                        ['route' => 'service-requests.index', 'icon' => 'fa-list', 'color' => 'purple', 'label' => 'Solicitudes'],
                        ['route' => 'operational-alerts.index', 'icon' => 'fa-bell', 'color' => 'rose', 'label' => 'Alertas'],
                        ['route' => 'operational-alerts.reminders', 'icon' => 'fa-clock', 'color' => 'amber', 'label' => 'Recordatorios'],
                    ];
                @endphp
                @foreach($quickLinks as $link)
                    <a href="{{ route($link['route']) }}" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-gray-200 bg-white hover:border-{{ $link['color'] }}-300 hover:shadow-sm transition text-center group">
                        <div class="w-9 h-9 rounded-lg bg-{{ $link['color'] }}-50 flex items-center justify-center group-hover:bg-{{ $link['color'] }}-100 transition"><i class="fas {{ $link['icon'] }} text-{{ $link['color'] }}-600 text-sm"></i></div>
                        <span class="text-xs font-medium text-gray-700">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            @else
                <a href="{{ route('workspaces.select') }}" class="col-span-full flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 bg-white hover:border-blue-300 hover:shadow-sm transition text-center group">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition"><i class="fas fa-building text-blue-600"></i></div>
                    <span class="text-xs font-medium text-gray-700">Seleccionar Entidad para más funciones</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ===== WORKSPACES ===== --}}
    @if($userCompanies->count() > 1)
        <div class="mt-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Mis Entidades</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($userCompanies as $company)
                    <form action="{{ route('workspaces.switch') }}" method="POST">@csrf<input type="hidden" name="company_id" value="{{ $company->id }}">
                        <button type="submit" class="w-full flex items-center gap-3 p-3 rounded-xl border {{ ($currentWorkspace?->id === $company->id) ? 'border-indigo-300 bg-indigo-50/50' : 'border-gray-200 bg-white hover:border-gray-300' }} transition text-left">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center shrink-0"><i class="fas fa-building text-gray-500 text-sm"></i></div>
                            <div class="min-w-0"><p class="text-sm font-medium text-gray-900 truncate">{{ $company->name }}</p>@if($company->pivot->entity_position)<p class="text-xs text-gray-500 truncate">{{ $company->pivot->entity_position }}</p>@endif</div>
                            @if($currentWorkspace?->id === $company->id)<i class="fas fa-check-circle text-indigo-600 ml-auto shrink-0"></i>@endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== MENÚ CONTEXTUAL (clic derecho / botón ⋮ sobre solicitudes) ===== --}}
    <div x-show="contextMenu.open"
         x-ref="srMenu"
         style="display: none;"
         @click.outside="closeContextMenu()"
         @keydown.escape.window="closeContextMenu()"
         @keydown.down.prevent="focusMenuItem($refs.srMenu, 1)"
         @keydown.up.prevent="focusMenuItem($refs.srMenu, -1)"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed z-[9999] w-60 bg-white rounded-xl shadow-2xl ring-1 ring-black/5 border border-gray-200 py-1.5 text-sm origin-top-left focus:outline-none"
         :style="`display:${contextMenu.open ? 'block':'none'}; top:${contextMenu.y}px; left:${contextMenu.x}px;`"
         role="menu" aria-label="Acciones rápidas de solicitud" tabindex="-1">

        {{-- Encabezado --}}
        <div class="px-3 py-2 border-b border-gray-100 mb-1">
            <p class="text-xs font-mono font-semibold text-indigo-600 truncate" x-text="contextMenu.item.ticket"></p>
            <p class="text-[11px] text-gray-400" x-text="contextMenu.item.status ? contextMenu.item.status.replace('_',' ') : ''"></p>
        </div>

        {{-- Vista normal del menú --}}
        <div x-show="!contextMenu.confirming">
            {{-- === ACCIONES DIRECTAS (sin datos obligatorios): confirmación inline === --}}

            {{-- Aceptar (solo PENDIENTE) — POST directo, auto-asigna al usuario --}}
            <button type="button" x-show="contextMenu.item.status === 'PENDIENTE'"
                    @click="askConfirm('accept', 'PATCH', 'Aceptar')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition text-left" role="menuitem">
                <i class="fas fa-check w-4 text-center text-blue-500"></i> Aceptar
            </button>

            {{-- Iniciar (solo ACEPTADA, con técnico) — POST directo --}}
            <button type="button" x-show="contextMenu.item.status === 'ACEPTADA'"
                    @click="askConfirm('start', 'PATCH', 'Iniciar trabajo')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left" role="menuitem">
                <i class="fas fa-play w-4 text-center text-indigo-500"></i> Iniciar trabajo
            </button>

            {{-- Reanudar (solo PAUSADA) — POST directo --}}
            <button type="button" x-show="contextMenu.item.status === 'PAUSADA'"
                    @click="askConfirm('resume', 'POST', 'Reanudar')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 transition text-left" role="menuitem">
                <i class="fas fa-play w-4 text-center text-green-500"></i> Reanudar
            </button>

            {{-- === ACCIONES CON DATOS OBLIGATORIOS: navegan al detalle (modal) === --}}

            {{-- Resolver (solo EN_PROCESO) — requiere notas/evidencia → abre el detalle --}}
            <button type="button" x-show="contextMenu.item.status === 'EN_PROCESO'"
                    @click="goToAction(contextMenu.item.url, 'resolve')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 transition text-left" role="menuitem">
                <i class="fas fa-check-circle w-4 text-center text-green-500"></i> Resolver
            </button>

            {{-- Pausar (solo EN_PROCESO) — requiere razón → abre el detalle --}}
            <button type="button" x-show="contextMenu.item.status === 'EN_PROCESO'"
                    @click="goToAction(contextMenu.item.url, 'pause')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition text-left" role="menuitem">
                <i class="fas fa-pause w-4 text-center text-amber-500"></i> Pausar
            </button>

            {{-- Rechazar (solo PENDIENTE) — requiere razón → abre el detalle --}}
            <button type="button" x-show="contextMenu.item.status === 'PENDIENTE'"
                    @click="goToAction(contextMenu.item.url, 'reject')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-rose-50 hover:text-rose-700 transition text-left" role="menuitem">
                <i class="fas fa-times-circle w-4 text-center text-rose-500"></i> Rechazar
            </button>

            {{-- Cerrar (RESUELTA / PAUSADA) — requiere datos → abre el detalle --}}
            <button type="button" x-show="['RESUELTA','PAUSADA'].includes(contextMenu.item.status)"
                    @click="goToAction(contextMenu.item.url, 'close')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 transition text-left" role="menuitem">
                <i class="fas fa-lock w-4 text-center text-gray-400"></i> Cerrar
            </button>

            {{-- Reabrir (RESUELTA / CERRADA) — requiere razón → abre el detalle --}}
            <button type="button" x-show="['RESUELTA','CERRADA'].includes(contextMenu.item.status)"
                    @click="goToAction(contextMenu.item.url, 'reopen')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-700 transition text-left" role="menuitem">
                <i class="fas fa-undo w-4 text-center text-orange-500"></i> Reabrir
            </button>

            {{-- Reasignar (PENDIENTE / ACEPTADA / EN_PROCESO / PAUSADA) — navega al formulario --}}
            <button type="button" x-show="['PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA'].includes(contextMenu.item.status)"
                    @click="goTo('{{ url('service-requests') }}/' + contextMenu.item.id + '/reassign')"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 transition text-left" role="menuitem">
                <i class="fas fa-user-pen w-4 text-center text-gray-400"></i> Reasignar
            </button>

            <div class="border-t border-gray-100 my-1"></div>

            {{-- Ver detalle --}}
            <button type="button" x-show="contextMenu.item.url" @click="goTo(contextMenu.item.url)"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left" role="menuitem">
                <i class="fas fa-eye w-4 text-center text-gray-400"></i> Ver detalle
            </button>

            {{-- Copiar número de ticket --}}
            <button type="button" @click="copyTicket(contextMenu.item.ticket)"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 transition text-left" role="menuitem">
                <i class="w-4 text-center" :class="contextMenu.copied ? 'fas fa-check text-green-500' : 'fas fa-copy text-gray-400'"></i>
                <span x-text="contextMenu.copied ? 'Copiado' : 'Copiar N° de ticket'"></span>
            </button>
        </div>

        {{-- Vista de confirmación --}}
        <div x-show="contextMenu.confirming" class="px-3 py-2">
            <p class="text-sm text-gray-700 mb-3">
                ¿Confirmas <span class="font-semibold" x-text="contextMenu.confirmLabel"></span> esta solicitud?
            </p>
            <div class="flex items-center gap-2">
                <button type="button" @click="confirmAction()" data-confirm-btn
                        class="flex-1 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    Confirmar
                </button>
                <button type="button" @click="contextMenu.confirming = false"
                        class="flex-1 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 transition">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    {{-- ===== MENÚ CONTEXTUAL DE TAREAS (avanzar flujo) ===== --}}
    <div x-show="taskMenu.open"
         x-ref="taskMenu"
         style="display: none;"
         @click.outside="closeTaskMenu()"
         @keydown.escape.window="closeTaskMenu()"
         @keydown.down.prevent="focusMenuItem($refs.taskMenu, 1)"
         @keydown.up.prevent="focusMenuItem($refs.taskMenu, -1)"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed z-[9999] w-60 bg-white rounded-xl shadow-2xl ring-1 ring-black/5 border border-gray-200 py-1.5 text-sm origin-top-left focus:outline-none"
         :style="`display:${taskMenu.open ? 'block':'none'}; top:${taskMenu.y}px; left:${taskMenu.x}px;`"
         role="menu" aria-label="Acciones rápidas de tarea" tabindex="-1">

        {{-- Encabezado --}}
        <div class="px-3 py-2 border-b border-gray-100 mb-1">
            <p class="text-xs font-mono font-semibold text-indigo-600 truncate" x-text="taskMenu.item.code"></p>
            <p class="text-[11px] text-gray-400 capitalize" x-text="taskMenu.item.status ? taskMenu.item.status.replace('_',' ') : ''"></p>
        </div>

        {{-- Flujo principal: paso al siguiente proceso --}}
        {{-- Iniciar (pending / confirmed) --}}
        <button type="button" x-show="['pending','confirmed'].includes(taskMenu.item.status)"
                @click="runTaskAction(taskMenu.item.id, 'start')"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition text-left" role="menuitem">
            <i class="fas fa-play w-4 text-center text-blue-500"></i> Iniciar tarea
        </button>

        {{-- Completar (pending / confirmed / in_progress) --}}
        <button type="button" x-show="['pending','confirmed','in_progress'].includes(taskMenu.item.status)"
                @click="runTaskAction(taskMenu.item.id, 'complete')"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 transition text-left" role="menuitem">
            <i class="fas fa-check w-4 text-center text-green-500"></i> Completar tarea
        </button>

        {{-- Programar para hoy (si no está programada) --}}
        <button type="button" x-show="taskMenu.item.scheduled !== '1'"
                @click="scheduleTaskToday(taskMenu.item.id)"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition text-left" role="menuitem">
            <i class="fas fa-calendar-day w-4 text-center text-amber-500"></i> Programar para hoy
        </button>

        {{-- Quitar de programación (si está programada) --}}
        <button type="button" x-show="taskMenu.item.scheduled === '1'"
                @click="clearTaskSchedule(taskMenu.item.id)"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 transition text-left" role="menuitem">
            <i class="fas fa-calendar-xmark w-4 text-center text-gray-400"></i> Quitar de la agenda
        </button>

        <div class="border-t border-gray-100 my-1"></div>

        {{-- Ver detalle --}}
        <button type="button" @click="goTo(taskMenu.item.url)"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition text-left" role="menuitem">
            <i class="fas fa-eye w-4 text-center text-gray-400"></i> Ver detalle
        </button>

        {{-- Copiar código --}}
        <button type="button" @click="copyTaskCode(taskMenu.item.code)"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 transition text-left" role="menuitem">
            <i class="w-4 text-center" :class="taskMenu.copied ? 'fas fa-check text-green-500' : 'fas fa-copy text-gray-400'"></i>
            <span x-text="taskMenu.copied ? 'Copiado' : 'Copiar código'"></span>
        </button>
    </div>
</div>

@push('styles')
<style>
    .my-space-surface { min-height: calc(100vh - 100px); }
    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    /* Cursor y foco visible en las acciones de los menús contextuales */
    [role="menu"] [role="menuitem"] {
        cursor: pointer;
    }
    [role="menu"] [role="menuitem"]:focus,
    [role="menu"] [role="menuitem"]:focus-visible {
        outline: none;
        background-color: rgb(238 242 255); /* indigo-50 */
        color: rgb(67 56 202); /* indigo-700 */
    }
</style>
@endpush

<script>
function mySpaceApp() {
    return {
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'today',
        stats: {
            today_tasks: {{ $stats['today_tasks'] }},
            today_completed: {{ $stats['today_completed'] }},
            active_alerts: {{ $stats['active_alerts'] }},
            reminders_due: {{ $stats['reminders_due'] }},
        },
        contextMenu: {
            open: false, x: 0, y: 0, copied: false,
            confirming: false, pendingAction: null, pendingMethod: null, confirmLabel: '',
            item: { id: null, ticket: '', status: '', url: null }
        },
        // Extrae los datos de la tarjeta (elemento con data-sr-*)
        readCardData(cardEl) {
            return {
                id: cardEl.dataset.srId,
                ticket: cardEl.dataset.srTicket || '',
                status: cardEl.dataset.srStatus || '',
                url: cardEl.dataset.srUrl || null,
            };
        },
        positionMenu(clientX, clientY) {
            const menuW = 240, menuH = 340;
            let x = clientX, y = clientY;
            if (x + menuW > window.innerWidth) x = window.innerWidth - menuW - 8;
            if (y + menuH > window.innerHeight) y = window.innerHeight - menuH - 8;
            this.contextMenu.x = Math.max(8, x);
            this.contextMenu.y = Math.max(8, y);
        },
        resetMenuState(item) {
            this.contextMenu.item = item;
            this.contextMenu.confirming = false;
            this.contextMenu.copied = false;
        },
        // Apertura por clic derecho
        openContextMenu(event) {
            const card = event.currentTarget;
            this.resetMenuState(this.readCardData(card));
            this.positionMenu(event.clientX, event.clientY);
            this.$nextTick(() => {
                this.contextMenu.open = true;
                this.$nextTick(() => this.focusFirstItem(this.$refs.srMenu));
            });
        },
        // Apertura por botón ⋮ (posiciona junto al botón)
        openContextMenuFromButton(event) {
            const card = event.currentTarget.closest('[data-sr-id]');
            if (!card) return;
            this.resetMenuState(this.readCardData(card));
            const rect = event.currentTarget.getBoundingClientRect();
            this.positionMenu(rect.left - 200, rect.bottom + 4);
            this.$nextTick(() => {
                this.contextMenu.open = true;
                this.$nextTick(() => this.focusFirstItem(this.$refs.srMenu));
            });
        },
        closeContextMenu() {
            this.contextMenu.open = false;
            this.contextMenu.confirming = false;
        },
        goTo(url) {
            this.closeContextMenu();
            if (url) window.location.href = url;
        },
        // Navega al detalle de la solicitud indicando qué acción abrir (modal con datos obligatorios)
        goToAction(url, action) {
            this.closeContextMenu();
            if (!url) return;
            const sep = url.includes('?') ? '&' : '?';
            window.location.href = `${url}${sep}action=${action}`;
        },
        // Pide confirmación en lugar de ejecutar de inmediato
        askConfirm(action, method, label) {
            this.contextMenu.pendingAction = action;
            this.contextMenu.pendingMethod = method;
            this.contextMenu.confirmLabel = label;
            this.contextMenu.confirming = true;
            // Llevar el foco al botón Confirmar
            this.$nextTick(() => {
                const btn = this.$refs.srMenu?.querySelector('[data-confirm-btn]');
                if (btn) btn.focus();
            });
        },
        confirmAction() {
            this.runAction(this.contextMenu.item.id, this.contextMenu.pendingAction, this.contextMenu.pendingMethod);
        },
        runAction(id, action, method) {
            this.closeContextMenu();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('service-requests') }}/${id}/${action}`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            if (method !== 'POST') {
                const spoof = document.createElement('input');
                spoof.type = 'hidden';
                spoof.name = '_method';
                spoof.value = method;
                form.appendChild(spoof);
            }

            document.body.appendChild(form);
            form.submit();
        },
        async copyTicket(ticket) {
            try {
                await navigator.clipboard.writeText(ticket);
                this.contextMenu.copied = true;
                setTimeout(() => { this.closeContextMenu(); }, 700);
            } catch (e) {
                this.closeContextMenu();
            }
        },

        // ===== MENÚ CONTEXTUAL DE TAREAS =====
        taskMenu: {
            open: false, x: 0, y: 0, copied: false,
            item: { id: null, code: '', status: '', scheduled: '0', url: null }
        },
        readTaskData(cardEl) {
            return {
                id: cardEl.dataset.taskId,
                code: cardEl.dataset.taskCode || '',
                status: cardEl.dataset.taskStatus || '',
                scheduled: cardEl.dataset.taskScheduled || '0',
                url: cardEl.dataset.taskUrl || null,
            };
        },
        positionTaskMenu(clientX, clientY) {
            const menuW = 240, menuH = 340;
            let x = clientX, y = clientY;
            if (x + menuW > window.innerWidth) x = window.innerWidth - menuW - 8;
            if (y + menuH > window.innerHeight) y = window.innerHeight - menuH - 8;
            this.taskMenu.x = Math.max(8, x);
            this.taskMenu.y = Math.max(8, y);
        },
        openTaskMenu(event) {
            // Cerrar el menú de solicitudes si estuviera abierto
            this.contextMenu.open = false;
            const card = event.currentTarget;
            this.taskMenu.item = this.readTaskData(card);
            this.taskMenu.copied = false;
            this.positionTaskMenu(event.clientX, event.clientY);
            this.$nextTick(() => {
                this.taskMenu.open = true;
                this.$nextTick(() => this.focusFirstItem(this.$refs.taskMenu));
            });
        },
        openTaskMenuFromButton(event) {
            this.contextMenu.open = false;
            const card = event.currentTarget.closest('[data-task-id]');
            if (!card) return;
            this.taskMenu.item = this.readTaskData(card);
            this.taskMenu.copied = false;
            const rect = event.currentTarget.getBoundingClientRect();
            this.positionTaskMenu(rect.left - 200, rect.bottom + 4);
            this.$nextTick(() => {
                this.taskMenu.open = true;
                this.$nextTick(() => this.focusFirstItem(this.$refs.taskMenu));
            });
        },
        closeTaskMenu() {
            this.taskMenu.open = false;
        },
        // Envía un POST a una acción de flujo de tarea (start/complete)
        runTaskAction(id, action) {
            this.closeTaskMenu();
            this.submitForm(`{{ url('inicio/tasks') }}/${id}/${action}`);
        },
        scheduleTaskToday(id) {
            this.closeTaskMenu();
            this.submitForm(`{{ url('tasks') }}/${id}/schedule-quick`);
        },
        clearTaskSchedule(id) {
            this.closeTaskMenu();
            this.submitForm(`{{ url('tasks') }}/${id}/clear-schedule`);
        },
        async copyTaskCode(code) {
            try {
                await navigator.clipboard.writeText(code);
                this.taskMenu.copied = true;
                setTimeout(() => { this.closeTaskMenu(); }, 700);
            } catch (e) {
                this.closeTaskMenu();
            }
        },
        // Helper genérico para enviar formularios POST con CSRF
        submitForm(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        },

        // ===== FOCO / NAVEGACIÓN POR TECLADO EN MENÚS =====
        // Devuelve solo las acciones visibles del menú (no las ocultas por x-show)
        visibleMenuItems(menuEl) {
            if (!menuEl) return [];
            return Array.from(menuEl.querySelectorAll('[role="menuitem"]'))
                .filter(el => el.offsetParent !== null && !el.disabled);
        },
        // Enfoca la primera acción al abrir el menú
        focusFirstItem(menuEl) {
            const items = this.visibleMenuItems(menuEl);
            if (items.length) items[0].focus();
        },
        // Mueve el foco entre acciones con las flechas (con wrap-around)
        focusMenuItem(menuEl, direction) {
            const items = this.visibleMenuItems(menuEl);
            if (!items.length) return;
            const current = document.activeElement;
            let index = items.indexOf(current);
            if (index === -1) {
                index = direction > 0 ? 0 : items.length - 1;
            } else {
                index = (index + direction + items.length) % items.length;
            }
            items[index].focus();
        },
        init() {
            @if($stats['today_tasks'] === 0 && $stats['overdue_tasks'] > 0)
                this.activeTab = 'pending';
            @elseif($stats['today_tasks'] === 0 && $stats['active_alerts'] > 0)
                this.activeTab = 'alerts';
            @elseif($stats['today_tasks'] === 0 && $stats['pending_tasks'] > 0)
                this.activeTab = 'pending';
            @endif
            setInterval(() => this.refreshStats(), 300000);
        },
        async refreshStats() {
            try {
                const r = await fetch('{{ route("my-space.api.refresh") }}');
                if (r.ok) { const d = await r.json(); this.stats = {...this.stats, ...d}; }
            } catch(e) {}
        }
    };
}
</script>
@endsection
