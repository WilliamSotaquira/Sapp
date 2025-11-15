@extends('layouts.app')

@section('title', "Timeline - {$request->ticket_number}")

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-history text-xl"></i>
                <h1 class="text-xl font-bold">Línea de Tiempo - {{ $request->ticket_number }}</h1>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('reports.timeline.export', [$request->id, 'pdf']) }}"
                    class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </a>
                <a href="{{ route('reports.timeline.export', [$request->id, 'excel']) }}"
                    class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Excel
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Información de la solicitud -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Información principal -->
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
                                {{ $request->ticket_number }}
                            </span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">Título:</span>
                            <span class="text-gray-900 font-semibold text-right">{{ $request->title }}</span>
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
                            'CANCELADA' => 'bg-red-100 text-red-800'
                            ];
                            $statusColor = $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="{{ $statusColor }} px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-{{ getStatusIcon($request->status) }} mr-1"></i>
                                {{ $request->status }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Prioridad:</span>
                            @php
                            $priorityColors = [
                            'BAJA' => 'bg-green-100 text-green-800',
                            'MEDIA' => 'bg-yellow-100 text-yellow-800',
                            'ALTA' => 'bg-orange-100 text-orange-800',
                            'CRITICA' => 'bg-red-100 text-red-800'
                            ];
                            $priorityColor = $priorityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="{{ $priorityColor }} px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-{{ $request->criticality_level == 'ALTA' ? 'exclamation-triangle' : 'flag' }} mr-1"></i>
                                {{ $request->criticality_level }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Fecha Creación:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $request->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-gray-500 text-sm">{{ $request->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asignaciones -->
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
                                <span class="text-gray-900">{{ $request->requester->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Asignado a:</span>
                            @if($request->assignee)
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-tie text-green-600 text-sm"></i>
                                </div>
                                <span class="text-gray-900">{{ $request->assignee->name }}</span>
                            </div>
                            @else
                            <span class="text-gray-500 italic">No asignado</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">Sub-Servicio:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
                                @if($request->subService && $request->subService->service)
                                <div class="text-gray-500 text-sm">
                                    {{ $request->subService->service->name ?? '' }}
                                    @if($request->subService->service->family)
                                    - {{ $request->subService->service->family->name ?? '' }}
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">SLA:</span>
                            <div class="text-right">
                                <div class="text-gray-900">{{ $request->sla->name ?? 'N/A' }}</div>
                                @if($request->sla)
                                <div class="text-gray-500 text-sm">
                                    {{ $request->sla->criticality_level }} -
                                    Resolución: {{ $request->sla->resolution_time_minutes }} min
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Tiempo -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Estadísticas de Tiempo</h3>

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
                    <div class="text-gray-600 text-sm mb-1">Estados</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $timeInStatus ? (is_countable($timeInStatus) ? count($timeInStatus) : 0) : 0 }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-600 text-sm mb-1">Eventos</div>
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $timelineEvents ? (is_countable($timelineEvents) ? count($timelineEvents) : 0) : 0 }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tiempo por Estado -->
        @if(!empty($timeInStatus) && (is_countable($timeInStatus) ? count($timeInStatus) > 0 : false))
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tiempo por Estado</h3>

            <div class="space-y-3">
                @foreach($timeInStatus as $status => $data)
                <div class="flex justify-between items-center">
                    <span class="text-gray-700">{{ $status }}</span>
                    <div class="text-right">
                        <span class="font-semibold">{{ $data['formatted'] ?? 'N/A' }}</span>
                        <span class="text-sm text-gray-500 ml-2">({{ $data['percentage'] ?? '0' }}%)</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Resumen por Tipo de Evento -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen de Timeline</h3>

            @if(!empty($timeSummary) && is_array($timeSummary))
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
            @else
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-chart-bar text-4xl mb-4"></i>
                <p>No hay datos de resumen disponibles</p>
            </div>
            @endif
        </div>

        <!-- Línea de Tiempo -->
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
                <div class="relative">
                    <!-- Línea central -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 w-0.5 bg-gray-300 h-full"></div>

                    <!-- Eventos -->
                    <div class="space-y-8">
                        @foreach($timelineEvents as $index => $event)
                        <div class="relative flex items-start {{ $index % 2 == 0 ? 'justify-start' : 'justify-end' }}">
                            <!-- Contenido del evento -->
                            <div class="{{ $index % 2 == 0 ? 'mr-8' : 'ml-8' }} w-5/12">
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                                    <!-- Header -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 mb-1">{{ $event['title'] }}</h3>
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $event['timestamp']->format('d/m/Y H:i:s') }}
                                                <span class="mx-2">•</span>
                                                {{ $event['timestamp']->diffForHumans() }}
                                            </div>
                                        </div>
                                        <div class="bg-{{ $event['color'] ?? 'gray' }}-100 text-{{ $event['color'] ?? 'gray' }}-700 px-2 py-1 rounded text-sm">
                                            <i class="fas fa-{{ $event['icon'] ?? 'circle' }} mr-1"></i>
                                            {{ ucfirst($event['type']) }}
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div class="text-gray-700 mb-3">{{ $event['description'] }}</div>

                                    <!-- Footer -->
                                    <div class="flex justify-between items-center">
                                        @if($event['user'])
                                        <div class="flex items-center space-x-2">
                                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">{{ $event['user'] }}</span>
                                        </div>
                                        @endif

                                        @if(isset($event['evidence_type']))
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm">
                                            <i class="fas fa-{{ getEvidenceTypeIcon($event['evidence_type']) }} mr-1"></i>
                                            {{ getEvidenceTypeLabel($event['evidence_type']) }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Marcador -->
                            <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 rounded-full border-4 border-white
                                bg-{{ $event['color'] ?? 'gray' }}-500 shadow-lg z-10"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <div class="flex space-x-3">
                <a href="{{ route('reports.timeline.index') }}"
                    class="bg-gray-600 text-white hover:bg-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Listado
                </a>
                <a href="{{ route('service-requests.show', $request->id) }}"
                    class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-eye mr-2"></i>Ver Detalles de Solicitud
                </a>
            </div>
            <div class="text-sm text-gray-500">
                <i class="fas fa-sync-alt mr-1"></i>
                Actualizado: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</div>

@php
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
@endsection
