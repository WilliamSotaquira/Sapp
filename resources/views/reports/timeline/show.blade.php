@extends('layouts.app')

@section('title', "Línea de Tiempo - {$serviceRequest->ticket_number}")

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-history text-xl"></i>
                <h1 class="text-xl font-bold">Línea de Tiempo - {{ $serviceRequest->ticket_number }}</h1>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'pdf']) }}"
                   class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </a>
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'excel']) }}"
                   class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>Excel
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Service Request Info -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Main Info -->
            <div class="bg-gray-50 rounded-lg border border-gray-200">
                <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>Información de la Solicitud
                    </h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Ticket #:</span>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                {{ $serviceRequest->ticket_number }}
                            </span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">Título:</span>
                            <span class="text-gray-900 font-semibold text-right max-w-[60%]">{{ $serviceRequest->title }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Estado:</span>
                            @php
                                $statusColors = [
                                    'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                    'ASIGNADA' => 'bg-blue-100 text-blue-800',
                                    'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                    'PAUSADA' => 'bg-gray-100 text-gray-800',
                                    'RESUELTA' => 'bg-green-100 text-green-800',
                                    'CERRADA' => 'bg-gray-200 text-gray-800',
                                    'CANCELADA' => 'bg-red-100 text-red-800',
                                    'RECHAZADA' => 'bg-gray-200 text-gray-800',
                                ];
                                $statusColor = $statusColors[$serviceRequest->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="{{ $statusColor }} px-3 py-1 rounded-full text-sm font-medium">
                                {{ $serviceRequest->status }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Prioridad:</span>
                            @php
                                $priorityColors = [
                                    'BAJA' => 'bg-green-100 text-green-800',
                                    'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                    'ALTA' => 'bg-orange-100 text-orange-800',
                                    'CRITICA' => 'bg-red-100 text-red-800',
                                ];
                                $priorityColor = $priorityColors[$serviceRequest->criticality_level] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="{{ $priorityColor }} px-3 py-1 rounded-full text-sm font-medium">
                                {{ $serviceRequest->criticality_level ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Fecha Creación:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-gray-500 text-sm">{{ $serviceRequest->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments -->
            <div class="bg-gray-50 rounded-lg border border-gray-200">
                <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-users mr-2 text-blue-500"></i>Asignaciones
                    </h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Solicitante:</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <span class="text-gray-900">{{ $serviceRequest->requester->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Asignado a:</span>
                            @if($serviceRequest->assignee)
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-tie text-green-600 text-sm"></i>
                                </div>
                                <span class="text-gray-900">{{ $serviceRequest->assignee->name }}</span>
                            </div>
                            @else
                            <span class="text-gray-500 italic">No asignado</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">Sub-Servicio:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $serviceRequest->subService->name ?? 'N/A' }}</div>
                                @if($serviceRequest->subService && $serviceRequest->subService->service)
                                <div class="text-gray-500 text-sm">
                                    {{ $serviceRequest->subService->service->name ?? '' }}
                                    @if($serviceRequest->subService->service->family)
                                    - {{ $serviceRequest->subService->service->family->name ?? '' }}
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">SLA:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $serviceRequest->sla->name ?? 'N/A' }}</div>
                                @if($serviceRequest->sla)
                                <div class="text-gray-500 text-sm">
                                    {{ $serviceRequest->sla->criticality_level ?? '' }} -
                                    Resolución: {{ $serviceRequest->sla->resolution_time_minutes ?? 'N/A' }} min
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolution Statistics -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>Estadísticas de Resolución
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Tiempo Total</div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $totalResolutionTime && isset($totalResolutionTime['formatted']) ? $totalResolutionTime['formatted'] : 'N/A' }}
                    </div>
                </div>

                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Tiempo Activo</div>
                    <div class="text-2xl font-bold text-green-600">
                        @php
                            $activeTime = 0;
                            if (!empty($timeInStatus)) {
                                foreach ($timeInStatus as $status => $data) {
                                    if (!in_array($status, ['PAUSADA']) && isset($data['minutes'])) {
                                        $activeTime += $data['minutes'];
                                    }
                                }
                            }
                        @endphp
                        @if($activeTime > 0)
                            {{ $activeTime < 60 ? $activeTime . ' min' : (round($activeTime / 60, 1) . 'h') }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Estados Transitados</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $timeInStatus ? (is_countable($timeInStatus) ? count($timeInStatus) : 0) : 0 }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-600 text-sm mb-1">Total Eventos</div>
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $timelineEvents ? (is_countable($timelineEvents) ? count($timelineEvents) : 0) : 0 }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Time in Status -->
        @if(!empty($timeInStatus) && (is_countable($timeInStatus) ? count($timeInStatus) > 0 : false))
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-stopwatch mr-2 text-blue-500"></i>Tiempo por Estado
            </h3>

            <div class="space-y-3">
                @foreach($timeInStatus as $status => $data)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                    <div class="flex items-center">
                        @php
                            $statusBadgeColors = [
                                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                'ASIGNADA' => 'bg-blue-100 text-blue-800',
                                'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                'PAUSADA' => 'bg-gray-100 text-gray-800',
                                'RESUELTA' => 'bg-green-100 text-green-800',
                                'CERRADA' => 'bg-gray-200 text-gray-800',
                                'CANCELADA' => 'bg-red-100 text-red-800',
                                'RECHAZADA' => 'bg-gray-200 text-gray-800',
                            ];
                            $badgeColor = $statusBadgeColors[$status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="{{ $badgeColor }} px-2 py-1 rounded text-xs font-medium">{{ $status }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-gray-900">{{ $data['formatted'] ?? 'N/A' }}</span>
                        <span class="text-sm text-gray-500 ml-2">({{ $data['percentage'] ?? '0' }}%)</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Time Statistics -->
        @if(!empty($timeStatistics))
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock mr-2 text-blue-500"></i>Métricas de Tiempo
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @if(isset($timeStatistics['first_response_time']))
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-blue-600 text-sm font-medium mb-1">Tiempo Primera Respuesta</div>
                    <div class="text-xl font-bold text-blue-800">
                        {{ $timeStatistics['first_response_time']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
                @endif

                @if(isset($timeStatistics['resolution_time']))
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-green-600 text-sm font-medium mb-1">Tiempo de Resolución</div>
                    <div class="text-xl font-bold text-green-800">
                        {{ $timeStatistics['resolution_time']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
                @endif

                @if(isset($timeStatistics['total_pause_time']))
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="text-gray-600 text-sm font-medium mb-1">Tiempo en Pausa</div>
                    <div class="text-xl font-bold text-gray-800">
                        {{ $timeStatistics['total_pause_time']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
                @endif

                @if(isset($timeStatistics['effective_time']))
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-purple-600 text-sm font-medium mb-1">Tiempo Efectivo</div>
                    <div class="text-xl font-bold text-purple-800">
                        {{ $timeStatistics['effective_time']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
                @endif

                @if(isset($timeStatistics['sla_deadline']))
                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="text-orange-600 text-sm font-medium mb-1">Plazo SLA</div>
                    <div class="text-xl font-bold text-orange-800">
                        {{ $timeStatistics['sla_deadline']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
                @endif

                @if(isset($timeStatistics['sla_compliance']))
                <div class="{{ ($timeStatistics['sla_compliance']['compliant'] ?? false) ? 'bg-green-50' : 'bg-red-50' }} rounded-lg p-4">
                    <div class="{{ ($timeStatistics['sla_compliance']['compliant'] ?? false) ? 'text-green-600' : 'text-red-600' }} text-sm font-medium mb-1">Cumplimiento SLA</div>
                    <div class="text-xl font-bold {{ ($timeStatistics['sla_compliance']['compliant'] ?? false) ? 'text-green-800' : 'text-red-800' }}">
                        {{ ($timeStatistics['sla_compliance']['compliant'] ?? false) ? 'Cumplido' : 'Incumplido' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Timeline Summary -->
        @if(!empty($timeSummary) && is_array($timeSummary))
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-list-alt mr-2 text-blue-500"></i>Resumen de Timeline
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Total Eventos</div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $timeSummary['total_events'] ?? 0 }}
                    </div>
                </div>

                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Evidencias</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $timeSummary['evidence_events'] ?? 0 }}
                    </div>
                </div>

                <div class="border-r border-gray-200 last:border-r-0">
                    <div class="text-gray-600 text-sm mb-1">Cambios Estado</div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ $timeSummary['status_changes'] ?? 0 }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-600 text-sm mb-1">Duración Timeline</div>
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $timeSummary['timeline_duration']['formatted'] ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Chronological Timeline Events -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-stream mr-2 text-blue-500"></i>Línea de Tiempo de Eventos
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium ml-2">
                        {{ $timelineEvents ? (is_countable($timelineEvents) ? count($timelineEvents) : 0) : 0 }} eventos
                    </span>
                </h2>
            </div>
            <div class="p-6">
                @if(!empty($timelineEvents) && (is_countable($timelineEvents) ? count($timelineEvents) > 0 : false))
                <div class="relative">
                    <!-- Central line -->
                    <div class="absolute left-4 md:left-1/2 md:transform md:-translate-x-1/2 w-0.5 bg-gray-300 h-full"></div>

                    <!-- Events -->
                    <div class="space-y-8">
                        @foreach($timelineEvents as $index => $event)
                        @php
                            $eventColors = [
                                'creation' => 'blue',
                                'assignment' => 'green',
                                'status_change' => 'purple',
                                'resolution' => 'green',
                                'evidence' => 'indigo',
                                'pause' => 'gray',
                                'resume' => 'teal',
                                'breach' => 'red',
                                'comment' => 'yellow',
                                'system' => 'gray',
                            ];
                            $eventColor = $eventColors[$event['type'] ?? 'system'] ?? 'gray';
                            $eventIcons = [
                                'creation' => 'plus-circle',
                                'assignment' => 'user-check',
                                'status_change' => 'exchange-alt',
                                'resolution' => 'check-double',
                                'evidence' => 'file-alt',
                                'pause' => 'pause-circle',
                                'resume' => 'play-circle',
                                'breach' => 'exclamation-triangle',
                                'comment' => 'comment',
                                'system' => 'cog',
                            ];
                            $eventIcon = $eventIcons[$event['type'] ?? 'system'] ?? 'circle';
                        @endphp
                        <!-- Mobile: left-aligned, Desktop: alternating -->
                        <div class="relative flex items-start pl-10 md:pl-0 {{ $index % 2 == 0 ? 'md:justify-start' : 'md:justify-end' }}">
                            <!-- Event content -->
                            <div class="w-full md:w-5/12 {{ $index % 2 == 0 ? 'md:mr-8' : 'md:ml-8' }}">
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                                    <!-- Header -->
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 text-sm">{{ $event['title'] ?? 'Evento' }}</h4>
                                            <div class="flex items-center text-xs text-gray-500 mt-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                @if(isset($event['timestamp']))
                                                    @if($event['timestamp'] instanceof \DateTime || $event['timestamp'] instanceof \Carbon\Carbon)
                                                        {{ $event['timestamp']->format('d/m/Y H:i:s') }}
                                                        <span class="mx-1">•</span>
                                                        {{ $event['timestamp']->diffForHumans() }}
                                                    @else
                                                        {{ $event['timestamp'] }}
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <span class="bg-{{ $eventColor }}-100 text-{{ $eventColor }}-700 px-2 py-0.5 rounded text-xs font-medium ml-2">
                                            <i class="fas fa-{{ $eventIcon }} mr-1"></i>{{ ucfirst($event['type'] ?? 'sistema') }}
                                        </span>
                                    </div>

                                    <!-- Description -->
                                    @if(!empty($event['description']))
                                    <p class="text-gray-700 text-sm mb-2">{{ $event['description'] }}</p>
                                    @endif

                                    <!-- User -->
                                    @if(!empty($event['user']))
                                    <div class="flex items-center space-x-2 mt-2">
                                        <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600 text-xs"></i>
                                        </div>
                                        <span class="text-xs text-gray-600">
                                            {{ is_object($event['user']) ? $event['user']->name : $event['user'] }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Marker -->
                            <div class="absolute left-2.5 md:left-1/2 md:transform md:-translate-x-1/2 w-4 h-4 rounded-full border-4 border-white bg-{{ $eventColor }}-500 shadow-lg z-10 top-4"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-stream text-4xl mb-3 block text-gray-300"></i>
                    <p class="font-medium">No hay eventos de timeline disponibles</p>
                    <p class="text-sm mt-1">Esta solicitud aún no tiene eventos registrados.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <div class="flex space-x-3">
                <a href="{{ route('reports.timeline.index') }}"
                   class="bg-gray-600 text-white hover:bg-gray-700 px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Listado
                </a>
                <a href="{{ route('service-requests.show', $serviceRequest->id) }}"
                   class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center">
                    <i class="fas fa-eye mr-2"></i>Ver Solicitud
                </a>
            </div>
            <div class="text-sm text-gray-500">
                <i class="fas fa-sync-alt mr-1"></i>
                Actualizado: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</div>
@endsection
