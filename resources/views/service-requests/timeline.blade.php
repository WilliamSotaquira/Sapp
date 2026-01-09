@extends('layouts.app')

@section('title', "Timeline - {$serviceRequest->ticket_number}")

@php
    $timeStatistics = array_merge([
        'total_time' => '0m',
        'active_time' => '0m',
        'paused_time' => '0m',
        'efficiency' => '0%',
        'efficiency_raw' => 0
    ], $timeStatistics ?? []);
    $timeSummary = $timeSummary ?? [];

    function twTone($color) {
        $map = [
            'primary' => 'red',
            'info' => 'sky',
            'success' => 'emerald',
            'warning' => 'amber',
            'danger' => 'rose',
            'secondary' => 'slate',
            'dark' => 'gray'
        ];
        return $map[$color] ?? 'slate';
    }

    function badgeTw($color) {
        $tone = twTone($color);
        return "inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold bg-{$tone}-100 text-{$tone}-800 border-{$tone}-200";
    }

    function chipTw($color) {
        $tone = twTone($color);
        return "inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-{$tone}-50 text-{$tone}-700";
    }

    function markerTw($color) {
        $tone = twTone($color);
        return "bg-{$tone}-500";
    }

    function pillTw($isActive) {
        return $isActive
            ? 'sr-tab-btn bg-red-600 text-white border-red-600 shadow-sm'
            : 'sr-tab-btn bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-700';
    }

    function getStatusIcon($status) {
        $icons = [
            'PENDIENTE' => 'clock',
            'ACEPTADA' => 'check-circle',
            'EN_PROCESO' => 'cogs',
            'PAUSADA' => 'pause-circle',
            'RESUELTA' => 'check-double',
            'CERRADA' => 'lock',
            'CANCELADA' => 'times-circle'
        ];
        return $icons[$status] ?? 'question-circle';
    }

    function getEventTypeIcon($eventType) {
        $icons = [
            'Creación' => 'plus-circle',
            'Asignación' => 'user-check',
            'Aceptación' => 'check-circle',
            'Respuesta Inicial' => 'reply',
            'Pausa' => 'pause-circle',
            'Reanudación' => 'play-circle',
            'Resolución' => 'check-double',
            'Cierre' => 'lock',
            'Evidencia' => 'file-alt',
            'Incumplimiento SLA' => 'exclamation-triangle'
        ];
        return $icons[$eventType] ?? 'circle';
    }

    function getEvidenceTypeIcon($evidenceType) {
        $icons = [
            'PASO_A_PASO' => 'list-ol',
            'ARCHIVO' => 'paperclip',
            'COMENTARIO' => 'comment',
            'SISTEMA' => 'cog'
        ];
        return $icons[$evidenceType] ?? 'file-alt';
    }

    function getEvidenceTypeLabel($evidenceType) {
        $labels = [
            'PASO_A_PASO' => 'Paso a Paso',
            'ARCHIVO' => 'Archivo',
            'COMENTARIO' => 'Comentario',
            'SISTEMA' => 'Sistema'
        ];
        return $labels[$evidenceType] ?? $evidenceType;
    }

    function getEvidenceTypeColor($evidenceType) {
        $colors = [
            'PASO_A_PASO' => 'primary',
            'ARCHIVO' => 'info',
            'COMENTARIO' => 'secondary',
            'SISTEMA' => 'dark'
        ];
        return $colors[$evidenceType] ?? 'secondary';
    }
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-900 via-slate-900 to-red-800 text-white shadow-lg">
        <div class="absolute inset-0 opacity-40 bg-[radial-gradient(circle_at_20%_20%,rgba(248,113,113,0.35),transparent_35%),radial-gradient(circle_at_80%_20%,rgba(248,113,113,0.25),transparent_35%)]"></div>
        <div class="relative p-5 sm:p-6 md:p-8 flex flex-col gap-4 md:gap-0 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-[0.2em] text-red-200/80">Línea de tiempo</p>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 backdrop-blur text-xl">
                        <i class="fas fa-ticket-alt"></i>
                    </span>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-semibold flex items-center gap-2">
                            Ticket {{ $serviceRequest->ticket_number }}
                            <span class="text-xs font-medium uppercase tracking-wide text-red-100 bg-white/10 rounded-full px-2 py-1">
                                {{ $serviceRequest->title }}
                            </span>
                        </h1>
                        <div class="flex flex-wrap gap-3 text-sm text-red-100/90">
                            <span class="flex items-center gap-2"><i class="fas fa-calendar"></i>{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</span>
                            <span class="flex items-center gap-2"><i class="fas fa-user"></i>{{ $serviceRequest->requester->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 md:justify-end">
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'pdf']) }}" class="inline-flex items-center gap-2 rounded-full bg-white/90 text-slate-800 px-4 py-2 text-sm font-semibold shadow hover:bg-white">
                    <i class="fas fa-file-pdf text-red-500"></i> PDF
                </a>
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'excel']) }}" class="inline-flex items-center gap-2 rounded-full bg-white/90 text-slate-800 px-4 py-2 text-sm font-semibold shadow hover:bg-white">
                    <i class="fas fa-file-excel text-emerald-500"></i> Excel
                </a>
                <a href="{{ route('service-requests.show', $serviceRequest->id) }}" class="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="relative border-t border-white/10 px-5 sm:px-6 md:px-8 pb-5 sm:pb-6 md:pb-8">
            <div class="flex flex-wrap gap-2">
                <span class="{{ badgeTw($serviceRequest->status_color ?? 'secondary') }}">
                    <i class="fas fa-{{ getStatusIcon($serviceRequest->status) }}"></i>
                    {{ $serviceRequest->status }}
                </span>
                <span class="{{ badgeTw($serviceRequest->criticality_level_color ?? 'secondary') }}">
                    <i class="fas fa-{{ $serviceRequest->criticality_level == 'ALTA' ? 'exclamation-triangle' : 'flag' }}"></i>
                    {{ $serviceRequest->criticality_level }}
                </span>
                @if($serviceRequest->sla)
                <span class="{{ chipTw('info') }}">
                    <i class="fas fa-stopwatch text-sky-500"></i>
                    SLA: {{ $serviceRequest->sla->name }}
                </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tiempo Total</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $timeStatistics['total_time'] ?? '0m' }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-clock"></i>
                </span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tiempo Activo</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $timeStatistics['active_time'] ?? '0m' }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <i class="fas fa-play"></i>
                </span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tiempo Pausado</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $timeStatistics['paused_time'] ?? '0m' }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <i class="fas fa-pause"></i>
                </span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            @php $eff = $timeStatistics['efficiency_raw'] ?? 0; @endphp
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Eficiencia</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $timeStatistics['efficiency'] ?? '0%' }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $eff > 80 ? 'bg-emerald-100 text-emerald-600' : ($eff > 60 ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600') }}">
                    <i class="fas fa-chart-line"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-6">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Navegación de timeline">
                <button type="button" class="{{ pillTw(true) }}" data-tab-target="timeline" aria-controls="tab-timeline" aria-selected="true">
                    <i class="fas fa-stream"></i>
                    <span>Línea de tiempo</span>
                </button>
                <button type="button" class="{{ pillTw(false) }}" data-tab-target="stats" aria-controls="tab-stats" aria-selected="false">
                    <i class="fas fa-chart-pie"></i>
                    <span>Estadísticas</span>
                </button>
                <button type="button" class="{{ pillTw(false) }}" data-tab-target="details" aria-controls="tab-details" aria-selected="false">
                    <i class="fas fa-info-circle"></i>
                    <span>Detalles</span>
                </button>
            </div>
            <span class="text-xs font-medium text-slate-500">Actualizado: {{ now()->format('d/m/Y H:i') }}</span>
        </div>

        <div class="p-4 sm:p-6">
            <div data-tab-panel="timeline" id="tab-timeline">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2 text-slate-700 font-semibold">
                        <i class="fas fa-stream text-red-500"></i>
                        Cronología de eventos
                    </div>
                    <span class="{{ chipTw('primary') }}">{{ count($timelineEvents) }} eventos</span>
                </div>
                <div class="timeline-shell">
                    <div class="timeline-line"></div>
                    @php $currentDate = null; @endphp
                    @forelse($timelineEvents as $event)
                        @php
                            $tone = twTone($event['color'] ?? 'secondary');
                            $eventDate = $event['timestamp']->format('d/m/Y');
                        @endphp
                        @if($currentDate !== $eventDate)
                            <div class="timeline-date">
                                <span>{{ $event['timestamp']->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-400">{{ $event['timestamp']->locale('es')->diffForHumans() }}</span>
                            </div>
                            @php $currentDate = $eventDate; @endphp
                        @endif
                        <article class="timeline-item">
                            <div class="timeline-dot {{ markerTw($event['color'] ?? 'secondary') }}">
                                <span class="timeline-dot-inner"></span>
                            </div>
                            <div class="timeline-card">
                                <div class="timeline-card-header">
                                    <div>
                                        <div class="flex items-center gap-2 text-sm font-semibold text-{{ $tone }}-700">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-{{ $tone }}-100 text-{{ $tone }}-700 shadow-inner">
                                                <i class="fas fa-{{ $event['icon'] }}"></i>
                                            </span>
                                            <span>{{ $event['event'] ?? 'Evento' }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                            <i class="fas fa-clock"></i>
                                            {{ $event['timestamp']->format('H:i:s') }}
                                            @if(isset($event['status']) && isset($timeInStatus[$event['status']]))
                                                <span class="text-slate-400">•</span>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-hourglass-half text-slate-400"></i>
                                                    {{ $timeInStatus[$event['status']]['formatted'] }}
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        @if(isset($event['evidence_type']))
                                            <span class="{{ chipTw(getEvidenceTypeColor($event['evidence_type'])) }}">
                                                <i class="fas fa-{{ getEvidenceTypeIcon($event['evidence_type']) }}"></i>
                                                {{ getEvidenceTypeLabel($event['evidence_type']) }}
                                            </span>
                                        @endif
                                        @if(isset($event['status']))
                                            <span class="{{ chipTw($event['color'] ?? 'secondary') }}">
                                                <i class="fas fa-tag"></i>
                                                {{ $event['status'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if(!empty($event['description']))
                                    <p class="timeline-description">{{ $event['description'] }}</p>
                                @endif

                                @if(!empty($event['user']))
                                    <div class="timeline-meta">
                                        <div class="timeline-meta-item">
                                            <span class="timeline-meta-icon bg-{{ $tone }}-100 text-{{ $tone }}-700">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <div>
                                                <p class="text-xs text-slate-500">Responsable</p>
                                                <p class="text-sm font-semibold text-slate-800">
                                                    @if(is_object($event['user']) && isset($event['user']->name))
                                                        {{ $event['user']->name }}
                                                    @elseif(is_array($event['user']) && isset($event['user']['name']))
                                                        {{ $event['user']['name'] }}
                                                    @else
                                                        {{ (string) $event['user'] }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="text-center text-slate-500 py-8">
                            <i class="fas fa-box-open text-2xl mb-2"></i>
                            <p>No hay eventos registrados.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div data-tab-panel="stats" id="tab-stats" class="hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                            <i class="fas fa-chart-bar text-sky-500"></i>
                            Distribución de tiempos
                        </div>
                        @if(count($timeSummary) > 0)
                        <div class="overflow-hidden border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-100 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2">Tipo de evento</th>
                                        <th class="px-3 py-2 w-28">Duración</th>
                                        <th class="px-3 py-2 w-32">Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach($timeSummary as $summary)
                                        @if(is_array($summary) && isset($summary['event_type'], $summary['duration'], $summary['percentage']))
                                            <tr>
                                                <td class="px-3 py-2 flex items-center gap-2 text-slate-700">
                                                    <i class="fas fa-{{ getEventTypeIcon($summary['event_type']) }} text-slate-400"></i>
                                                    {{ $summary['event_type'] }}
                                                </td>
                                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $summary['duration'] }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                                                        <div class="{{ $summary['percentage'] > 50 ? 'bg-emerald-400' : ($summary['percentage'] > 25 ? 'bg-sky-400' : 'bg-amber-400') }} h-3" style="width: {{ $summary['percentage'] }}%;"></div>
                                                    </div>
                                                    <span class="text-xs text-slate-600 font-medium">{{ $summary['percentage'] }}%</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center text-slate-500 py-6">
                            <i class="fas fa-chart-bar text-xl mb-2"></i>
                            <p>No hay datos de distribución disponibles</p>
                        </div>
                        @endif
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 font-semibold text-slate-700">
                            <i class="fas fa-tachometer-alt text-emerald-500"></i>
                            Métricas de rendimiento
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-slate-200 bg-white p-3 text-center">
                                <p class="text-xs uppercase text-slate-500">Tiempo Total</p>
                                <p class="text-xl font-semibold text-slate-900">{{ $timeStatistics['total_time'] ?? '0m' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 text-center">
                                <p class="text-xs uppercase text-slate-500">Tiempo Activo</p>
                                <p class="text-xl font-semibold text-emerald-600">{{ $timeStatistics['active_time'] ?? '0m' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 text-center">
                                <p class="text-xs uppercase text-slate-500">Tiempo Pausado</p>
                                <p class="text-xl font-semibold text-amber-600">{{ $timeStatistics['paused_time'] ?? '0m' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 text-center">
                                <p class="text-xs uppercase text-slate-500">Eficiencia</p>
                                <p class="text-xl font-semibold {{ $eff > 80 ? 'text-emerald-600' : ($eff > 60 ? 'text-amber-600' : 'text-rose-600') }}">
                                    {{ $timeStatistics['efficiency'] ?? '0%' }}
                                </p>
                            </div>
                        </div>

                        @if($serviceRequest->sla)
                        <div class="border-t border-slate-200 pt-3">
                            <p class="text-xs uppercase text-slate-500 mb-2">Cumplimiento de SLA</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Aceptación</span>
                                    <span class="{{ ($serviceRequest->accepted_at && $serviceRequest->acceptance_deadline && $serviceRequest->accepted_at->lte($serviceRequest->acceptance_deadline)) ? 'text-emerald-600' : 'text-rose-600' }} font-semibold flex items-center gap-1">
                                        <i class="fas fa-{{ ($serviceRequest->accepted_at && $serviceRequest->acceptance_deadline && $serviceRequest->accepted_at->lte($serviceRequest->acceptance_deadline)) ? 'check' : 'times' }}"></i>
                                        {{ $serviceRequest->accepted_at ? $serviceRequest->accepted_at->format('d/m/Y H:i') : 'Pendiente' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Respuesta</span>
                                    <span class="{{ ($serviceRequest->responded_at && $serviceRequest->response_deadline && $serviceRequest->responded_at->lte($serviceRequest->response_deadline)) ? 'text-emerald-600' : 'text-rose-600' }} font-semibold flex items-center gap-1">
                                        <i class="fas fa-{{ ($serviceRequest->responded_at && $serviceRequest->response_deadline && $serviceRequest->responded_at->lte($serviceRequest->response_deadline)) ? 'check' : 'times' }}"></i>
                                        {{ $serviceRequest->responded_at ? $serviceRequest->responded_at->format('d/m/Y H:i') : 'Pendiente' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Resolución</span>
                                    <span class="{{ ($serviceRequest->resolved_at && $serviceRequest->resolution_deadline && $serviceRequest->resolved_at->lte($serviceRequest->resolution_deadline)) ? 'text-emerald-600' : 'text-rose-600' }} font-semibold flex items-center gap-1">
                                        <i class="fas fa-{{ ($serviceRequest->resolved_at && $serviceRequest->resolution_deadline && $serviceRequest->resolved_at->lte($serviceRequest->resolution_deadline)) ? 'check' : 'times' }}"></i>
                                        {{ $serviceRequest->resolved_at ? $serviceRequest->resolved_at->format('d/m/Y H:i') : 'Pendiente' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div data-tab-panel="details" id="tab-details" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                            <i class="fas fa-info-circle text-red-500"></i>
                            Información de la solicitud
                        </div>
                        <dl class="divide-y divide-slate-200 text-sm">
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Ticket #</dt>
                                <dd class="font-semibold text-slate-800">{{ $serviceRequest->ticket_number }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Título</dt>
                                <dd class="font-semibold text-slate-800 text-right">{{ $serviceRequest->title }}</dd>
                            </div>
                            <div class="py-2">
                                <dt class="text-slate-500">Descripción</dt>
                                <dd class="mt-1 text-slate-700 leading-relaxed">{{ $serviceRequest->description }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Estado</dt>
                                <dd><span class="{{ chipTw($serviceRequest->status_color ?? 'secondary') }}">{{ $serviceRequest->status }}</span></dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Prioridad</dt>
                                <dd><span class="{{ chipTw($serviceRequest->criticality_level_color ?? 'secondary') }}">{{ $serviceRequest->criticality_level }}</span></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <div class="flex items-center gap-2 font-semibold text-slate-700 mb-3">
                            <i class="fas fa-users text-emerald-500"></i>
                            Asignaciones y servicio
                        </div>
                        <dl class="divide-y divide-slate-200 text-sm">
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Solicitante</dt>
                                <dd class="font-semibold text-slate-800 text-right">{{ $serviceRequest->requester->name ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Asignado a</dt>
                                <dd class="text-right">
                                    @if($serviceRequest->assignee)
                                        <span class="font-semibold text-slate-800">{{ $serviceRequest->assignee->name }}</span>
                                    @else
                                        <span class="text-slate-500 italic">No asignado</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Sub-servicio</dt>
                                <dd class="font-semibold text-slate-800 text-right">{{ $serviceRequest->subService->name ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">SLA</dt>
                                <dd class="font-semibold text-slate-800 text-right">{{ $serviceRequest->sla->name ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-500">Fecha creación</dt>
                                <dd class="font-semibold text-slate-800 text-right">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('service-requests.show', $serviceRequest->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-red-300 hover:text-red-700">
                        <i class="fas fa-arrow-left"></i> Volver a detalles
                    </a>
                    <a href="{{ route('reports.timeline.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-700">
                        <i class="fas fa-list"></i> Ver todas las solicitudes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style type="text/tailwindcss">
    @layer components {
        .sr-tab-btn {
            @apply inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 focus:ring-offset-white;
        }
        .timeline-shell {
            @apply relative pl-7 md:pl-10 space-y-6;
        }
        .timeline-line {
            @apply absolute left-3.5 md:left-4 top-0 bottom-0 w-px bg-gradient-to-b from-red-200 via-slate-200 to-slate-200;
        }
        .timeline-date {
            @apply inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 border border-slate-200 shadow-sm;
        }
        .timeline-item {
            @apply relative flex gap-3 md:gap-4;
        }
        .timeline-dot {
            @apply absolute -left-[19px] md:-left-5 top-2 flex h-4 w-4 items-center justify-center rounded-full shadow-sm;
        }
        .timeline-dot-inner {
            @apply h-2 w-2 rounded-full bg-white/90;
        }
        .timeline-card {
            @apply flex-1 rounded-xl border border-slate-200 bg-white/90 backdrop-blur px-4 py-3 shadow-sm;
        }
        .timeline-card-header {
            @apply flex flex-wrap items-start justify-between gap-3 pb-3 border-b border-slate-200;
        }
        .timeline-description {
            @apply mt-3 text-sm text-slate-700 leading-relaxed;
        }
        .timeline-meta {
            @apply mt-3 pt-3 border-t border-slate-200;
        }
        .timeline-meta-item {
            @apply flex items-center gap-3;
        }
        .timeline-meta-icon {
            @apply flex h-9 w-9 items-center justify-center rounded-full shadow-inner;
        }
    }
</style>
@endpush

@section('scripts')
<script>
    (function() {
        var tabButtons = document.querySelectorAll('[data-tab-target]');
        var tabPanels = document.querySelectorAll('[data-tab-panel]');

        function activateTab(target) {
            tabButtons.forEach(function(btn) {
                var isActive = btn.getAttribute('data-tab-target') === target;
                btn.className = isActive ? ' ' + "{{ pillTw(true) }}" : ' ' + "{{ pillTw(false) }}";
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            tabPanels.forEach(function(panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
            });
        }

        tabButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                activateTab(btn.getAttribute('data-tab-target'));
            });
        });
    })();
</script>
@endsection

<style>
    #main-wrapper .content .title{
        font-family: work-sans !important;
        font-weight: 600;
        font-size: 28px;
        color: #4c531e;
        margin-bottom: 20px;
    }

</style>
