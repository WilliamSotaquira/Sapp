@extends('layouts.app')

@section('title', 'Panel principal')

@section('hidePageHeader', true)

@section('content')
@php
    $workspaceName = $currentWorkspace->name ?? '';
    $workspaceKey = Str::lower($workspaceName);
    $workspaceAccent = $currentWorkspace->primary_color ?? '#DC2626';
    $workspaceAccentBg = $workspaceAccent . '1A';

    if (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'movilidad')) {
        $workspaceAccent = '#BED000';
        $workspaceAccentBg = '#BED0002E';
    } elseif (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'cultura')) {
        $workspaceAccent = '#493D86';
        $workspaceAccentBg = '#493D861F';
    }

    $heroKpis = \App\Models\ServiceRequest::query()
        ->when($currentWorkspace?->id, function ($query) use ($currentWorkspace) {
            $query->where('company_id', $currentWorkspace->id);
        })
        ->selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN status != 'CERRADA' THEN 1 END) as open_count,
            COUNT(CASE WHEN status = 'EN_PROCESO' THEN 1 END) as in_process_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' AND status != 'CERRADA' THEN 1 END) as critical_count
        ")
        ->first();
@endphp
<div class="dashboard-surface -mx-3 sm:-mx-4 md:-mx-6 lg:-mx-8 px-3 sm:px-4 md:px-6 lg:px-8 py-6" role="main">
    <a href="#recent-requests-heading" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-blue-600 text-white px-3 py-1 rounded">Saltar a solicitudes recientes</a>
    <!-- Encabezado con breadcrumb y título -->
    <div class="mb-8 reveal" style="--delay: .02s">
        <div class="dashboard-hero rounded-3xl border shadow-xl px-5 sm:px-7 py-6 sm:py-7"
             style="--hero-bg-start: {{ $workspaceAccentBg }}; --hero-bg-end: #ffffff; --hero-border: {{ $workspaceAccent }}38; --hero-glow: {{ $workspaceAccent }}24;">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm">
                    <li><a href="{{ url('/') }}" class="text-slate-500 hover:text-slate-700">Inicio</a></li>
                    <li class="flex items-center">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="ml-2 text-slate-700 font-medium">Panel principal</span>
                    </li>
                </ol>
            </nav>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                <div class="space-y-2">
                    <p class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] tracking-[0.14em] uppercase font-semibold border bg-blue-50 text-blue-800 border-blue-200">
                        Centro de control
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                        Gestión operativa en tiempo real
                    </h1>
                    <p class="text-sm text-slate-600 max-w-2xl">
                        Supervisa solicitudes, agenda técnica y estados clave desde un panel único y más visual.
                    </p>
                    <span class="inline-flex items-center text-xs sm:text-sm text-slate-600 bg-white/80 border px-3 py-1 rounded-full"
                          style="border-color: {{ $workspaceAccent }}40;">
                        <i class="fas fa-clock mr-2" style="color: {{ $workspaceAccent }};"></i>
                        Última actualización: {{ now()->format('d/m/Y H:i') }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2.5 min-w-[280px] sm:min-w-[340px]">
                    <a href="{{ route('service-requests.index', ['open' => 1, 'exclude_closed' => 1]) }}"
                       class="hero-kpi text-center bg-white/90 border border-slate-200 rounded-2xl px-3 py-2.5 hover:bg-white transition">
                        <div class="text-[10px] uppercase tracking-wide text-slate-600">Abiertas</div>
                        <div class="text-xl font-bold leading-tight text-slate-900">{{ (int) ($heroKpis->open_count ?? 0) }}</div>
                    </a>
                    <a href="{{ route('service-requests.index', ['status' => 'EN_PROCESO']) }}"
                       class="hero-kpi text-center bg-blue-50/80 border border-blue-200 rounded-2xl px-3 py-2.5 hover:bg-blue-50 transition">
                        <div class="text-[10px] uppercase tracking-wide text-blue-700">En proceso</div>
                        <div class="text-xl font-bold text-blue-900 leading-tight">{{ (int) ($heroKpis->in_process_count ?? 0) }}</div>
                    </a>
                    <a href="{{ route('service-requests.index', ['criticality' => 'CRITICA', 'exclude_closed' => 1]) }}"
                       class="hero-kpi text-center bg-rose-50/80 border border-rose-200 rounded-2xl px-3 py-2.5 hover:bg-rose-50 transition">
                        <div class="text-[10px] uppercase tracking-wide text-rose-700">Críticas</div>
                        <div class="text-xl font-bold text-rose-900 leading-tight">{{ (int) ($heroKpis->critical_count ?? 0) }}</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-card reveal bg-white rounded-2xl shadow p-6 border border-gray-100 mb-8" style="--delay: .08s">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400">Productividad</p>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Acciones Rápidas</h2>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full"
                  style="background-color: {{ $workspaceAccentBg }}; color: {{ $workspaceAccent }};">
                Acceso directo
            </span>
        </div>
        @php
            $isAdmin = auth()->user()?->isAdmin() ?? false;
            $hasTechnician = (bool) (auth()->user()?->technician);

            $quickLinks = [
                [
                    'href' => route('technician-schedule.my-agenda'),
                    'border' => 'border-indigo-100',
                    'bg' => 'bg-indigo-50/50',
                    'iconWrap' => 'text-indigo-600',
                    'icon' => 'fas fa-calendar-alt',
                    'title' => 'Mi Agenda',
                    'subtitle' => 'Calendario',
                    'arrow' => 'text-indigo-500',
                ],
                [
                    'href' => route('service-requests.index'),
                    'border' => 'border-purple-100',
                    'bg' => 'bg-purple-50/50',
                    'iconWrap' => 'text-purple-600',
                    'icon' => 'fas fa-list',
                    'title' => 'Ver Solicitudes',
                    'subtitle' => 'Panel operativo',
                    'arrow' => 'text-purple-500',
                ],
                [
                    'href' => route('service-requests.create'),
                    'border' => 'border-blue-100',
                    'bg' => 'bg-blue-50/50',
                    'iconWrap' => 'text-blue-600',
                    'icon' => 'fas fa-plus-circle',
                    'title' => 'Nueva Solicitud',
                    'subtitle' => 'Alta inmediata',
                    'arrow' => 'text-blue-500',
                ],
                [
                    'href' => route('tasks.index'),
                    'border' => 'border-emerald-100',
                    'bg' => 'bg-emerald-50/50',
                    'iconWrap' => 'text-emerald-600',
                    'icon' => 'fas fa-tasks',
                    'title' => 'Mis Tareas',
                    'subtitle' => 'Ejecución diaria',
                    'arrow' => 'text-emerald-500',
                ],
            ];

            if (!$hasTechnician) {
                $quickLinks = array_values(array_filter($quickLinks, function ($link) {
                    return !in_array($link['title'], ['Mi Agenda', 'Mis Tareas'], true);
                }));
            }

            if ($isAdmin) {
                $quickLinks[] = [
                    'href' => route('service-families.create'),
                    'border' => 'border-green-100',
                    'bg' => 'bg-green-50/50',
                    'iconWrap' => 'text-green-600',
                    'icon' => 'fas fa-layer-group',
                    'title' => 'Nueva Familia',
                    'subtitle' => 'Agregar catálogo',
                    'arrow' => 'text-green-500',
                ];
                $quickLinks[] = [
                    'href' => route('slas.index'),
                    'border' => 'border-orange-100',
                    'bg' => 'bg-orange-50/50',
                    'iconWrap' => 'text-orange-600',
                    'icon' => 'fas fa-handshake',
                    'title' => 'Gestionar SLAs',
                    'subtitle' => 'Compromisos',
                    'arrow' => 'text-orange-500',
                ];
            }
        @endphp
        <div class="grid grid-flow-col auto-cols-[minmax(180px,1fr)] gap-3 overflow-x-auto px-1 py-3 text-sm">
            @foreach($quickLinks as $link)
                <a href="{{ $link['href'] }}" class="quick-link-card flex items-center gap-3 rounded-xl border {{ $link['border'] }} {{ $link['bg'] }} px-4 py-3 hover:bg-opacity-80 transition">
                    <div class="quick-link-icon p-2 rounded-lg bg-white {{ $link['iconWrap'] }} shadow-inner">
                        <i class="{{ $link['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $link['title'] }}</p>
                        <span class="text-xs text-gray-600">{{ $link['subtitle'] }}</span>
                    </div>
                    <i class="quick-link-arrow fas fa-arrow-right {{ $link['arrow'] }} ml-auto"></i>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Alertas de Tareas Críticas (solo para admin) -->
    @if(auth()->user()?->isAdmin())
        @include('partials.task-alerts')
    @endif

    <!-- Dos Columnas: Agenda y Resumen de Solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Agenda -->
        <div class="dash-card reveal bg-white rounded-2xl shadow p-6 border border-gray-100" style="--delay: .14s">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Hoy</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Mi Agenda</h2>
                </div>
                <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full">Próximos</span>
            </div>
            @php
                if(!isset($agenda)) {
                    $technicianId = auth()->user()?->technician?->id ?? null;
                    if($technicianId) {
                        $agenda = \App\Models\Task::with('serviceRequest.subService.service')
                            ->where('technician_id', $technicianId)
                            ->whereDate('scheduled_date', '>=', now()->toDateString())
                            ->orderBy('scheduled_date')
                            ->orderByRaw("COALESCE(scheduled_start_time, scheduled_time, '23:59')")
                            ->take(6)
                            ->get()
                            ->map(function($task) {
                                $serviceRequest = $task->serviceRequest;
                                $serviceName = $serviceRequest?->subService?->service?->name;
                                $subServiceName = $serviceRequest?->subService?->name;
                                $serviceLabel = $serviceName && $subServiceName
                                    ? "{$serviceName} · {$subServiceName}"
                                    : ($subServiceName ?? $serviceName);
                                $taskUrl = $serviceRequest
                                    ? route('service-requests.show', $serviceRequest)
                                    : route('tasks.show', $task);

                                return [
                                    'time' => $task->scheduled_start_time ?? $task->scheduled_time ?? '—',
                                    'date' => optional($task->scheduled_date)->format('d/m') ?? '',
                                    'title' => $task->title ?? 'Tarea sin título',
                                    'location' => $serviceRequest?->ticket_number ? 'Ticket '.$serviceRequest->ticket_number : 'Sin ticket',
                                    'status' => $task->status ? ucfirst(str_replace('_', ' ', $task->status)) : 'Pendiente',
                                    'url' => $taskUrl,
                                    'service' => $serviceLabel ?: 'Sin servicio',
                                ];
                            })
                            ->toArray();
                    } else {
                        $agenda = [];
                    }
                }
                $statusColors = [
                    'Pendiente' => 'bg-amber-100 text-amber-800',
                    'Programada' => 'bg-blue-100 text-blue-800',
                    'En curso' => 'bg-green-100 text-green-800',
                ];
            @endphp
            @if(count($agenda) > 0)
                <ul class="space-y-3">
                    @foreach($agenda as $item)
                        <li>
                            <a href="{{ $item['url'] ?? '#' }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-indigo-100 hover:bg-indigo-50/40 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <div class="flex flex-col items-center justify-center w-20 rounded-lg bg-gray-50 text-gray-900 font-semibold">
                                    <span class="text-xs text-gray-500">Fecha</span>
                                    <span class="text-sm">{{ $item['date'] ?? '' }}</span>
                                    <span class="text-xs text-gray-600">{{ $item['time'] ?? '' }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900 truncate">{{ $item['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $item['location'] }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $item['service'] }}</p>
                                </div>
                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$item['status']] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $item['status'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-right">
                    <a href="{{ route('technician-schedule.my-agenda') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                        Ver calendario
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @else
                <div class="text-center py-8 text-gray-600">
                    <i class="fas fa-sun text-3xl text-amber-300 mb-3"></i>
                    <p class="font-semibold text-gray-800">Sin tareas pendientes 🎉</p>
                    <p class="text-sm text-gray-500">Aprovecha para adelantar documentación o capacitarte.</p>
                    <a href="{{ route('technician-schedule.my-agenda') }}" class="mt-3 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Abrir calendario
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @endif
        </div>

        <!-- Resumen de Solicitudes por Estado (pasar $statuses y $totalRequests desde controlador para optimizar) -->
        <div class="dash-card reveal bg-white rounded-2xl shadow p-4 sm:p-5 border border-gray-100" style="--delay: .18s">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-widest text-gray-400">Visión general</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Resumen de Solicitudes</h2>
                </div>
                <span class="text-xs font-semibold text-gray-500">Total: {{ $totalRequests ?? 0 }}</span>
            </div>

            <!-- Mini gráfico de barras -->
            @php
                if(!isset($statuses)) {
                    $statuses = [
                        'PENDIENTE' => ['color' => 'yellow', 'icon' => 'fas fa-clock', 'count' => \App\Models\ServiceRequest::where('status', 'PENDIENTE')->count()],
                        'ACEPTADA' => ['color' => 'blue', 'icon' => 'fas fa-check-circle', 'count' => \App\Models\ServiceRequest::where('status', 'ACEPTADA')->count()],
                        'EN_PROCESO' => ['color' => 'purple', 'icon' => 'fas fa-play-circle', 'count' => \App\Models\ServiceRequest::where('status', 'EN_PROCESO')->count()],
                        'PAUSADA' => ['color' => 'orange', 'icon' => 'fas fa-pause-circle', 'count' => \App\Models\ServiceRequest::where('status', 'PAUSADA')->count()],
                        'RESUELTA' => ['color' => 'green', 'icon' => 'fas fa-check-double', 'count' => \App\Models\ServiceRequest::where('status', 'RESUELTA')->count()],
                        'CERRADA' => ['color' => 'gray', 'icon' => 'fas fa-lock', 'count' => \App\Models\ServiceRequest::where('status', 'CERRADA')->count()],
                    ];
                }
                if(!isset($totalRequests)) {
                    $totalRequests = \App\Models\ServiceRequest::count();
                }
            @endphp

            @if($totalRequests > 0)
            <div class="mb-4 bg-gray-50 border border-gray-100 p-2.5 rounded-2xl">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Distribución por estado</h3>
                    <span class="text-xs text-gray-500">Proporción %</span>
                </div>
                <div class="flex h-2 sm:h-2.5 bg-gray-200 rounded-full overflow-hidden">
                    @foreach($statuses as $status => $data)
                        @if($data['count'] > 0)
                            <div
                                class="h-full bg-{{ $data['color'] }}-500"
                                style="width: {{ ($data['count'] / $totalRequests) * 100 }}%"
                                title="{{ $status }}: {{ $data['count'] }} ({{ round(($data['count'] / $totalRequests) * 100, 1) }}%)"
                            ></div>
                        @endif
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-x-2 gap-y-1 mt-2">
                    @foreach($statuses as $status => $data)
                        @if($data['count'] > 0)
                            <div class="flex items-center text-[10px] text-gray-600">
                                <div class="w-2 h-2 bg-{{ $data['color'] }}-500 rounded mr-1"></div>
                                <span>{{ $status }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Lista de estados -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @php
                    $visibleStatuses = array_filter($statuses, fn ($d) => ($d['count'] ?? 0) > 0);
                @endphp

                @forelse($visibleStatuses as $status => $data)
                    <a href="{{ route('service-requests.index', ['status' => $status]) }}"
                       class="flex items-center justify-between p-2.5 rounded-2xl border border-{{ $data['color'] }}-100 bg-{{ $data['color'] }}-50/40 shadow hover:-translate-y-0.5 transition-transform duration-200 focus:outline-none focus:ring-2 focus:ring-{{ $data['color'] }}-400"
                       aria-label="Ver solicitudes en estado {{ $status }}">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-xl bg-white/70 text-{{ $data['color'] }}-600 mr-3">
                                <i class="{{ $data['icon'] }} text-sm"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-{{ $data['color'] }}-900 text-xs sm:text-sm">{{ $status }}</span>
                                <p class="text-[10px] text-{{ $data['color'] }}-600/80">Estado operativo</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-semibold text-{{ $data['color'] }}-800 mr-1.5">{{ $data['count'] }}</span>
                            @if($totalRequests > 0)
                                <span class="text-xs text-{{ $data['color'] }}-600/80">
                                    ({{ round(($data['count'] / $totalRequests) * 100, 1) }}%)
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="sm:col-span-2 p-3 rounded-2xl border border-gray-100 bg-gray-50 text-sm text-gray-600">
                        No hay solicitudes para mostrar por estado.
                        <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-800 font-medium ml-1">Ver listado</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Solicitudes Recientes con filtros y búsqueda -->
    @php
        $recentSearch = trim((string) request('recent_search', ''));
        $allowedRecentStatuses = array_merge(['__OPEN__', '__ALL__'], array_keys($statuses ?? []));
        $recentStatus = (string) request('recent_status', '__OPEN__');
        if (!in_array($recentStatus, $allowedRecentStatuses, true)) {
            $recentStatus = '__OPEN__';
        }
        $recentPerPage = (int) request('recent_per_page', 5);
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (!in_array($recentPerPage, $allowedPerPage, true)) {
            $recentPerPage = 5;
        }
    @endphp
    <div id="recent-requests-card" class="dash-card reveal bg-white rounded-2xl shadow" aria-labelledby="recent-requests-heading" style="--delay: .22s">
        <div class="px-6 py-4 border-b border-gray-200 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 id="recent-requests-heading" class="text-lg font-semibold text-gray-900">Solicitudes Recientes</h2>
                <span class="text-[11px] text-gray-500">Busca, filtra y ajusta la vista</span>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(280px,1.4fr)_auto_auto] gap-2 items-center text-xs sm:text-sm" role="toolbar" aria-label="Herramientas de filtrado de solicitudes">
                <div class="relative">
                    <input
                        type="text"
                        id="search-requests"
                        value="{{ request('recent_search', '') }}"
                        placeholder="Buscar ticket, título, solicitante, servicio, familia o contrato"
                        aria-label="Buscar solicitud por ticket o título"
                        class="pl-9 pr-4 py-2.5 border border-gray-300 rounded-xl text-xs sm:text-sm focus:ring-blue-500 focus:border-blue-500 w-full"
                    >
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>

                <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-2 py-1.5">
                    <select id="filter-status" aria-label="Filtrar por estado" class="border border-gray-300 bg-white rounded-lg text-xs sm:text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 min-w-[170px]">
                        <option value="__OPEN__" {{ $recentStatus === '__OPEN__' ? 'selected' : '' }}>Abiertas</option>
                        <option value="__ALL__" {{ $recentStatus === '__ALL__' ? 'selected' : '' }}>Todos los estados</option>
                        @foreach(array_keys($statuses) as $status)
                            <option value="{{ $status }}" {{ $recentStatus === $status ? 'selected' : '' }}>{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }}</option>
                        @endforeach
                    </select>
                    <select id="filter-per-page" aria-label="Cantidad por página" class="border border-gray-300 bg-white rounded-lg text-xs sm:text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500 min-w-[88px]">
                        @foreach([5, 10, 20, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ (int) request('recent_per_page', 5) === $opt ? 'selected' : '' }}>{{ $opt }}/pág</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 justify-start lg:justify-end">
                    <button id="clear-filters" type="button" class="border border-gray-300 rounded-lg text-xs sm:text-sm px-3 py-2 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500" aria-label="Limpiar filtros">Limpiar</button>
                    <button id="toggle-density" type="button" class="border border-gray-300 rounded-lg text-xs sm:text-sm px-3 py-2 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500" aria-label="Alternar densidad de filas">Densidad</button>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-md whitespace-nowrap">
                    <span id="visible-requests-count">{{ isset($recentRequests) ? $recentRequests->count() : 0 }}</span> visibles
                    </span>
                </div>
            </div>
        </div>
        <div class="px-6 py-3 border-b border-gray-100 bg-slate-50/50">
            <div class="flex flex-wrap items-center gap-2" aria-label="Filtros rápidos por estado">
                <span class="text-xs text-gray-500">Atajos:</span>
                @php
                    $chipActive = ' ring-2 ring-blue-400 font-semibold';
                @endphp
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100{{ $recentStatus === '__OPEN__' ? $chipActive : '' }}" data-status="__OPEN__">Abiertas</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-slate-300 bg-white text-slate-700 hover:bg-slate-50{{ $recentStatus === '__ALL__' ? $chipActive : '' }}" data-status="__ALL__">Todas</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-yellow-300 bg-yellow-50 text-yellow-700 hover:bg-yellow-100{{ $recentStatus === 'PENDIENTE' ? $chipActive : '' }}" data-status="PENDIENTE">Pendiente</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100{{ $recentStatus === 'ACEPTADA' ? $chipActive : '' }}" data-status="ACEPTADA">Aceptada</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100{{ $recentStatus === 'EN_PROCESO' ? $chipActive : '' }}" data-status="EN_PROCESO">En proceso</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-green-300 bg-green-50 text-green-700 hover:bg-green-100{{ $recentStatus === 'RESUELTA' ? $chipActive : '' }}" data-status="RESUELTA">Resuelta</button>
                <button type="button" class="quick-status-chip px-2.5 py-1 rounded-full text-xs border border-gray-300 bg-gray-100 text-gray-700 hover:bg-gray-200{{ $recentStatus === 'CERRADA' ? $chipActive : '' }}" data-status="CERRADA">Cerrada</button>
            </div>
        </div>
        @php
            if(!isset($recentRequests)) {
                $recentQuery = \App\Models\ServiceRequest::query()
                    ->with(['subService.service.family.contract', 'requester'])
                    ->latest();

                if ($recentSearch !== '') {
                    $recentQuery->where(function ($q) use ($recentSearch) {
                        $q->where('ticket_number', 'like', "%{$recentSearch}%")
                            ->orWhere('title', 'like', "%{$recentSearch}%")
                            ->orWhere('description', 'like', "%{$recentSearch}%")
                            ->orWhereHas('requester', function ($rq) use ($recentSearch) {
                                $rq->where('name', 'like', "%{$recentSearch}%")
                                    ->orWhere('email', 'like', "%{$recentSearch}%")
                                    ->orWhere('department', 'like', "%{$recentSearch}%");
                            })
                            ->orWhereHas('subService', function ($subQ) use ($recentSearch) {
                                $subQ->where('name', 'like', "%{$recentSearch}%")
                                    ->orWhere('code', 'like', "%{$recentSearch}%")
                                    ->orWhereHas('service', function ($serviceQ) use ($recentSearch) {
                                        $serviceQ->where('name', 'like', "%{$recentSearch}%")
                                            ->orWhere('code', 'like', "%{$recentSearch}%")
                                            ->orWhereHas('family', function ($familyQ) use ($recentSearch) {
                                                $familyQ->where('name', 'like', "%{$recentSearch}%")
                                                    ->orWhere('code', 'like', "%{$recentSearch}%")
                                                    ->orWhereHas('contract', function ($contractQ) use ($recentSearch) {
                                                        $contractQ->where('number', 'like', "%{$recentSearch}%")
                                                            ->orWhere('name', 'like', "%{$recentSearch}%");
                                                    });
                                            });
                                    });
                            });
                    });
                }

                if ($recentStatus === '__OPEN__') {
                    $recentQuery->whereNotIn('status', ['CERRADA', 'CANCELADA', 'RECHAZADA']);
                } elseif ($recentStatus === '__ALL__') {
                    // Sin filtro de estado
                } elseif ($recentStatus !== '') {
                    $recentQuery->where('status', $recentStatus);
                }

                $recentRequests = $recentQuery
                    ->paginate($recentPerPage, ['*'], 'recent_page')
                    ->withQueryString();
            }
        @endphp

        @if($recentRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed" id="recent-requests-table" role="table" aria-describedby="recent-requests-caption">
                    <caption id="recent-requests-caption" class="sr-only">Tabla con las solicitudes recientes y su estado.</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="ticket" aria-sort="none" tabindex="0">Ticket <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="title" aria-sort="none" tabindex="0">Título <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-56">Servicio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="status" aria-sort="none" tabindex="0">Estado <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="date" aria-sort="none" tabindex="0">Fecha <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentRequests as $request)
                            @php
                                $familyName = $request->subService?->service?->family?->name ?? '';
                                $contractNumber = $request->subService?->service?->family?->contract?->number ?? '';
                                $requesterName = $request->requester?->name ?? '';
                            @endphp
                            <tr class="hover:bg-gray-50 request-row {{ strtoupper((string) $request->status) === 'CERRADA' ? 'request-row-ghost' : '' }}"
                                data-status="{{ $request->status }}"
                                data-ticket="{{ $request->ticket_number }}"
                                data-title="{{ strtolower($request->title) }}"
                                data-description="{{ strtolower($request->description ?? '') }}"
                                data-service="{{ strtolower($request->subService->name ?? '') }}"
                                data-family="{{ strtolower($familyName) }}"
                                data-contract="{{ strtolower($contractNumber) }}"
                                data-requester="{{ strtolower($requesterName) }}"
                                data-status-label="{{ strtolower(str_replace('_', ' ', $request->status)) }}"
                                data-date="{{ $request->created_at->getTimestamp() }}"
                                tabindex="0" role="row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('service-requests.show', $request) }}" class="font-semibold {{ strtoupper((string) $request->status) === 'CERRADA' ? 'text-gray-700 hover:text-gray-800' : 'text-blue-700 hover:text-blue-900' }} hover:underline underline-offset-2 flex items-center">
                                        {{ $request->ticket_number }}
                                        @if($request->priority === 'ALTA')
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Alta
                                            </span>
                                        @elseif($request->priority === 'MEDIA')
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Media
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ Str::limit($request->title, 35) }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($request->description, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php
                                        $serviceLabel = $request->subService->name ?? 'N/A';
                                    @endphp
                                    <div class="flex items-center min-w-0" title="{{ $serviceLabel }}">
                                        <span class="truncate">{{ Str::limit($serviceLabel, 32) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                                            'RESUELTA' => 'bg-green-100 text-green-800',
                                            'CERRADA' => 'bg-gray-100 text-gray-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800',
                                            'RECHAZADA' => 'bg-gray-100 text-gray-800'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }} flex items-center w-fit">
                                        {{ ucfirst(strtolower(str_replace('_', ' ', $request->status))) }}
                                        @if($request->is_paused && $request->status === 'PAUSADA')
                                            <i class="fas fa-pause ml-1"></i>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $request->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                                    @if($request->created_at->diffInDays(now()) <= 1)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                            <i class="fas fa-clock mr-1"></i> Reciente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr id="no-recent-results-row" class="hidden">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No hay solicitudes que coincidan con los filtros aplicados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <div class="text-sm text-gray-500" aria-live="polite">
                    Página {{ $recentRequests->currentPage() }} de {{ $recentRequests->lastPage() }}
                </div>
                <a href="{{ route('service-requests.index') }}" class="text-blue-700 hover:text-blue-900 font-semibold hover:underline underline-offset-2 inline-flex items-center">
                    Ver todas las solicitudes
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            @if(method_exists($recentRequests, 'hasPages') && $recentRequests->hasPages())
                <div class="px-6 py-3 border-t border-gray-100 bg-white">
                    {{ $recentRequests->fragment('recent-requests-heading')->onEachSide(1)->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay solicitudes</h3>
                <p class="text-gray-500 mb-4">Aún no se han creado solicitudes de servicio.</p>
                <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Crear Primera Solicitud
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Script para funcionalidades de UX mejoradas (filtrado + ordenamiento accesible + animaciones + persistencia) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY = 'dashboard_density_v1';
    const densityClass = 'dense-rows';
    const FILTER_DELAY_MS = 350;
    let filterTimeout = null;
    let requestSeq = 0;

    function readDensityPreference() {
        try {
            const stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
            return !!(stored && stored.density);
        } catch (e) {
            return false;
        }
    }

    function persistDensityPreference(enabled) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ density: !!enabled }));
        } catch (e) {}
    }

    function buildRecentUrl({ term, status, perPage, resetPage, pageUrl }) {
        const url = new URL(pageUrl || window.location.href);

        if (term) url.searchParams.set('recent_search', term);
        else url.searchParams.delete('recent_search');

        if (status) url.searchParams.set('recent_status', status);
        else url.searchParams.delete('recent_status');

        if (perPage) url.searchParams.set('recent_per_page', perPage);
        else url.searchParams.delete('recent_per_page');

        if (resetPage) url.searchParams.delete('recent_page');

        url.hash = 'recent-requests-heading';
        return url;
    }

    function applyRecentCardBindings() {
        const card = document.getElementById('recent-requests-card');
        if (!card) return;

        const searchInput = card.querySelector('#search-requests');
        const statusFilter = card.querySelector('#filter-status');
        const perPageFilter = card.querySelector('#filter-per-page');
        const table = card.querySelector('#recent-requests-table');
        const clearBtn = card.querySelector('#clear-filters');
        const densityBtn = card.querySelector('#toggle-density');
        const quickStatusChips = Array.from(card.querySelectorAll('.quick-status-chip'));

        if (!searchInput || !statusFilter || !perPageFilter) return;

        if (table && readDensityPreference()) {
            table.classList.add(densityClass);
        }

        function scheduleApply() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => applyServerFilters({ resetPage: true }), FILTER_DELAY_MS);
        }

        async function applyServerFilters({ resetPage = true, pageUrl = null } = {}) {
            const seq = ++requestSeq;
            const url = buildRecentUrl({
                term: searchInput.value.trim(),
                status: statusFilter.value,
                perPage: perPageFilter.value,
                resetPage,
                pageUrl,
            });

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) return;

                const html = await response.text();
                if (seq !== requestSeq) return;

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const incoming = doc.getElementById('recent-requests-card');
                const current = document.getElementById('recent-requests-card');
                if (!incoming || !current) return;

                current.replaceWith(incoming);
                window.history.replaceState({}, '', url.toString());
                applyRecentCardBindings();
            } catch (e) {
                // Mantener UX estable sin romper interacción.
            }
        }

        searchInput.addEventListener('input', scheduleApply);
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(filterTimeout);
                applyServerFilters({ resetPage: true });
            }
        });

        statusFilter.addEventListener('change', () => applyServerFilters({ resetPage: true }));
        perPageFilter.addEventListener('change', () => applyServerFilters({ resetPage: true }));

        quickStatusChips.forEach((chip) => {
            chip.addEventListener('click', () => {
                statusFilter.value = chip.dataset.status || '__ALL__';
                applyServerFilters({ resetPage: true });
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                statusFilter.value = '__OPEN__';
                perPageFilter.value = '5';
                applyServerFilters({ resetPage: true });
            });
        }

        if (densityBtn && table) {
            densityBtn.addEventListener('click', () => {
                table.classList.toggle(densityClass);
                persistDensityPreference(table.classList.contains(densityClass));
            });
        }

        card.querySelectorAll('a[href*="recent_page="]').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                applyServerFilters({ resetPage: false, pageUrl: link.href });
            });
        });

        if (!table) return;
        const tbody = table.querySelector('tbody');
        let rows = Array.from(tbody.querySelectorAll('.request-row'));
        const headers = table.querySelectorAll('.sortable');
        let currentSort = { key: null, direction: 'asc' };

        function sortRows(key) {
            const direction = (currentSort.key === key && currentSort.direction === 'asc') ? 'desc' : 'asc';
            currentSort = { key, direction };

            headers.forEach(h => h.setAttribute('aria-sort', h.dataset.sort === key ? direction === 'asc' ? 'ascending' : 'descending' : 'none'));

            const multiplier = direction === 'asc' ? 1 : -1;
            rows.sort((a, b) => {
                let aVal, bVal;
                switch(key) {
                    case 'ticket':
                        aVal = a.dataset.ticket.toLowerCase();
                        bVal = b.dataset.ticket.toLowerCase();
                        break;
                    case 'title':
                        aVal = a.dataset.title;
                        bVal = b.dataset.title;
                        break;
                    case 'status':
                        aVal = a.dataset.status;
                        bVal = b.dataset.status;
                        break;
                    case 'date':
                        aVal = parseInt(a.dataset.date, 10);
                        bVal = parseInt(b.dataset.date, 10);
                        break;
                    default:
                        aVal = '';
                        bVal = '';
                }
                if (aVal < bVal) return -1 * multiplier;
                if (aVal > bVal) return 1 * multiplier;
                return 0;
            });
            const fragment = document.createDocumentFragment();
            rows.forEach(r => fragment.appendChild(r));
            tbody.appendChild(fragment);
        }

        headers.forEach(header => {
            header.addEventListener('click', () => sortRows(header.dataset.sort));
            header.addEventListener('keydown', (e) => {
                if(e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sortRows(header.dataset.sort);
                }
            });
        });
    }

    applyRecentCardBindings();

    // Animación de contadores (IntersectionObserver)
    const counters = document.querySelectorAll('.count-up');
    const animateCounter = el => {
        const target = parseInt(el.dataset.count, 10);
        const duration = 800;
        const start = performance.now();
        const initial = 0;
        function step(ts) {
            const progress = Math.min((ts - start) / duration, 1);
            const value = Math.floor(progress * (target - initial) + initial);
            el.textContent = value.toLocaleString('es-ES');
            if(progress < 1) requestAnimationFrame(step); else el.textContent = target.toLocaleString('es-ES');
        }
        requestAnimationFrame(step);
    };
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });
    counters.forEach(c => observer.observe(c));

});
</script>

<style>
.dashboard-surface {
    background-color: #f3f4f6;
    min-height: calc(100vh - 4rem);
    overflow: visible;
}
.dashboard-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--hero-bg-start), #f8fafc 38%, var(--hero-bg-end));
    border-color: var(--hero-border);
}
.dashboard-hero::after {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    border-radius: 9999px;
    right: -120px;
    top: -150px;
    background: radial-gradient(circle, var(--hero-glow), transparent 68%);
    pointer-events: none;
}
.hero-kpi {
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}
.hero-kpi:hover {
    transform: translateY(-1px);
}
.dash-card {
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
}
.reveal {
    opacity: 0;
    transform: translateY(10px) scale(0.995);
    animation: dash-reveal 540ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    animation-delay: var(--delay, 0s);
}
@keyframes dash-reveal {
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.dash-card:hover {
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.10);
    transition: box-shadow .22s ease;
}
.quick-link-card {
    position: relative;
    transition: border-color .2s ease, background-color .2s ease;
}
.quick-link-card:hover {
    border-color: rgba(99, 102, 241, 0.35);
    background-color: rgba(255, 255, 255, 0.92);
}
.quick-link-icon {
    transition: transform .22s ease;
}
.quick-link-arrow {
    transition: transform .22s ease;
}
.quick-link-card:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
.quick-link-card:hover .quick-link-icon {
    transform: none;
}
.quick-link-card:hover .quick-link-arrow {
    transform: translateX(2px);
}
/* Mejoras visuales adicionales */
.sortable:hover {
    background-color: #f9fafb;
}

.request-row {
    transition: background-color 0.2s ease;
}

.request-row-ghost {
    filter: grayscale(85%);
    opacity: 0.82;
    background-color: #f8fafc;
}

/* Asegurar que los colores de Tailwind se muestren correctamente */
.bg-yellow-50 { background-color: #fefce8; }
.bg-blue-50 { background-color: #eff6ff; }
.bg-purple-50 { background-color: #faf5ff; }
.bg-orange-50 { background-color: #fff7ed; }
.bg-green-50 { background-color: #f0fdf4; }
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
.sortable:focus { outline:2px solid #3b82f6; outline-offset:2px; }
.dense-rows tbody tr td { padding-top:0.35rem; padding-bottom:0.35rem; }
.request-row:focus { background-color:#f0f9ff; box-shadow:inset 0 0 0 2px #3b82f6; }
</style>
@endsection
