@extends('layouts.app')

@section('title', "Timeline - {$serviceRequest->ticket_number}")

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Línea de Tiempo - {{ $serviceRequest->ticket_number }}
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <a href="{{ route('reports.export-timeline', [$serviceRequest->id, 'pdf']) }}"
                               class="btn btn-sm btn-light mr-2">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </a>
                            <a href="{{ route('reports.export-timeline', [$serviceRequest->id, 'excel'])"
                               class="btn btn-sm btn-light">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </a>
                            <a href="{{ route('service-requests.show', $serviceRequest->id) }}"
                               class="btn btn-sm btn-light ml-2">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-circle symbol-50 mr-3">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="fas fa-ticket-alt text-primary"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="mb-1">{{ $serviceRequest->title }}</h4>
                                    <div class="text-muted">
                                        <span class="mr-3">
                                            <i class="fas fa-hashtag"></i> {{ $serviceRequest->ticket_number }}
                                        </span>
                                        <span class="mr-3">
                                            <i class="fas fa-calendar"></i>
                                            {{ $serviceRequest->created_at->format('d/m/Y H:i') }}
                                        </span>
                                        <span class="mr-3">
                                            <i class="fas fa-user"></i>
                                            {{ $serviceRequest->requester->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="d-flex flex-column align-items-end">
                                <span class="badge badge-{{ $serviceRequest->getStatusColor() }} badge-lg mb-2">
                                    <i class="fas fa-{{ $this->getStatusIcon($serviceRequest->status) }} mr-1"></i>
                                    {{ $serviceRequest->status }}
                                </span>
                                <span class="badge badge-{{ $serviceRequest->getPriorityColor() }}">
                                    <i class="fas fa-{{ $serviceRequest->criticality_level == 'ALTA' ? 'exclamation-triangle' : 'flag' }} mr-1"></i>
                                    {{ $serviceRequest->criticality_level }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-gradient-info">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tiempo Total</span>
                                    <span class="info-box-number">{{ $timeStatistics['total_time'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-gradient-success">
                                <span class="info-box-icon"><i class="fas fa-play-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tiempo Activo</span>
                                    <span class="info-box-number">{{ $timeStatistics['active_time'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-gradient-warning">
                                <span class="info-box-icon"><i class="fas fa-pause-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tiempo Pausado</span>
                                    <span class="info-box-number">{{ $timeStatistics['paused_time'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-gradient-{{ $timeStatistics['efficiency_raw'] > 80 ? 'success' : ($timeStatistics['efficiency_raw'] > 60 ? 'warning' : 'danger') }}">
                                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Eficiencia</span>
                                    <span class="info-box-number">{{ $timeStatistics['efficiency'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Navigation -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="timeline-navigation">
                                <div class="nav nav-pills nav-pills-custom" id="v-pills-tab" role="tablist">
                                    <a class="nav-link active" id="v-pills-timeline-tab" data-toggle="pill" href="#v-pills-timeline" role="tab">
                                        <i class="fas fa-stream mr-2"></i>Línea de Tiempo
                                    </a>
                                    <a class="nav-link" id="v-pills-stats-tab" data-toggle="pill" href="#v-pills-stats" role="tab">
                                        <i class="fas fa-chart-pie mr-2"></i>Estadísticas
                                    </a>
                                    <a class="nav-link" id="v-pills-details-tab" data-toggle="pill" href="#v-pills-details" role="tab">
                                        <i class="fas fa-info-circle mr-2"></i>Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="v-pills-tabContent">
                        <!-- Timeline Tab -->
                        <div class="tab-pane fade show active" id="v-pills-timeline" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-stream text-primary"></i>
                                        Cronología de Eventos
                                        <span class="badge badge-primary ml-2">{{ count($timelineEvents) }} eventos</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="timeline-vertical">
                                        @foreach($timelineEvents as $event)
                                        <div class="timeline-item-vertical">
                                            <div class="timeline-marker-vertical bg-{{ $event['color'] ?? 'secondary' }}">
                                                <i class="fas fa-{{ $event['icon'] }}"></i>
                                            </div>
                                            <div class="timeline-content-vertical">
                                                <div class="timeline-header-vertical">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1 text-{{ $event['color'] ?? 'secondary' }}">
                                                                {{ $event['event'] }}
                                                            </h6>
                                                            <p class="mb-1 text-muted small">
                                                                <i class="fas fa-clock mr-1"></i>
                                                                {{ $event['timestamp']->format('d/m/Y H:i:s') }}
                                                                <span class="mx-2">•</span>
                                                                {{ $event['timestamp']->diffForHumans() }}
                                                            </p>
                                                        </div>
                                                        @if(isset($timeInStatus[$event['status']]))
                                                        <div class="text-right">
                                                            <span class="badge badge-light">
                                                                <i class="fas fa-hourglass-half mr-1"></i>
                                                                {{ $timeInStatus[$event['status']]['formatted'] }}
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="timeline-body-vertical">
                                                    <p class="mb-2">{{ $event['description'] }}</p>
                                                    @if($event['user'])
                                                    <div class="d-flex align-items-center mt-2">
                                                        <div class="symbol symbol-circle symbol-25 mr-2">
                                                            <div class="symbol-label bg-light-{{ $event['color'] ?? 'secondary' }}">
                                                                <i class="fas fa-user text-{{ $event['color'] ?? 'secondary' }}"></i>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">
                                                            <strong>Usuario:</strong> {{ $event['user']->name }}
                                                        </small>
                                                    </div>
                                                    @endif
                                                    @if(isset($event['evidence_type']))
                                                    <div class="mt-2">
                                                        <span class="badge badge-{{ $this->getEvidenceTypeColor($event['evidence_type']) }}">
                                                            <i class="fas fa-{{ $this->getEvidenceTypeIcon($event['evidence_type']) }} mr-1"></i>
                                                            {{ $this->getEvidenceTypeLabel($event['evidence_type']) }}
                                                        </span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Tab -->
                        <div class="tab-pane fade" id="v-pills-stats" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-chart-bar text-info"></i>
                                                Distribución de Tiempos
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            @if(count($timeSummary) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Tipo de Evento</th>
                                                            <th width="25%">Duración</th>
                                                            <th width="20%">Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($timeSummary as $summary)
                                                        <tr>
                                                            <td>
                                                                <i class="fas fa-{{ $this->getEventTypeIcon($summary['event_type']) }} text-muted mr-2"></i>
                                                                {{ $summary['event_type'] }}
                                                            </td>
                                                            <td class="font-weight-bold">{{ $summary['duration'] }}</td>
                                                            <td>
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar
                                                                        {{ $summary['percentage'] > 50 ? 'bg-success' :
                                                                           ($summary['percentage'] > 25 ? 'bg-info' : 'bg-warning') }}"
                                                                        role="progressbar"
                                                                        style="width: {{ $summary['percentage'] }}%"
                                                                        aria-valuenow="{{ $summary['percentage'] }}"
                                                                        aria-valuemin="0"
                                                                        aria-valuemax="100">
                                                                        {{ $summary['percentage'] }}%
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <div class="text-center text-muted py-4">
                                                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                                <p>No hay datos de distribución disponibles</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-tachometer-alt text-success"></i>
                                                Métricas de Rendimiento
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="metrics-grid">
                                                <div class="metric-item">
                                                    <div class="metric-value text-primary">{{ $timeStatistics['total_time'] }}</div>
                                                    <div class="metric-label">Tiempo Total</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-value text-success">{{ $timeStatistics['active_time'] }}</div>
                                                    <div class="metric-label">Tiempo Activo</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-value text-warning">{{ $timeStatistics['paused_time'] }}</div>
                                                    <div class="metric-label">Tiempo Pausado</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-value {{ $timeStatistics['efficiency_raw'] > 80 ? 'text-success' : ($timeStatistics['efficiency_raw'] > 60 ? 'text-warning' : 'text-danger') }}">
                                                        {{ $timeStatistics['efficiency'] }}
                                                    </div>
                                                    <div class="metric-label">Eficiencia</div>
                                                </div>
                                            </div>

                                            <!-- SLA Compliance -->
                                            @if($serviceRequest->sla)
                                            <div class="sla-compliance mt-4">
                                                <h6 class="text-muted mb-3">Cumplimiento de SLA</h6>
                                                <div class="compliance-item">
                                                    <span class="compliance-label">Aceptación:</span>
                                                    <span class="compliance-status {{ $serviceRequest->accepted_at && $serviceRequest->acceptance_deadline && $serviceRequest->accepted_at->lte($serviceRequest->acceptance_deadline) ? 'text-success' : 'text-danger' }}">
                                                        <i class="fas fa-{{ $serviceRequest->accepted_at && $serviceRequest->acceptance_deadline && $serviceRequest->accepted_at->lte($serviceRequest->acceptance_deadline) ? 'check' : 'times' }} mr-1"></i>
                                                        {{ $serviceRequest->accepted_at ? $serviceRequest->accepted_at->format('d/m/Y H:i') : 'Pendiente' }}
                                                    </span>
                                                </div>
                                                <div class="compliance-item">
                                                    <span class="compliance-label">Respuesta:</span>
                                                    <span class="compliance-status {{ $serviceRequest->responded_at && $serviceRequest->response_deadline && $serviceRequest->responded_at->lte($serviceRequest->response_deadline) ? 'text-success' : 'text-danger' }}">
                                                        <i class="fas fa-{{ $serviceRequest->responded_at && $serviceRequest->response_deadline && $serviceRequest->responded_at->lte($serviceRequest->response_deadline) ? 'check' : 'times' }} mr-1"></i>
                                                        {{ $serviceRequest->responded_at ? $serviceRequest->responded_at->format('d/m/Y H:i') : 'Pendiente' }}
                                                    </span>
                                                </div>
                                                <div class="compliance-item">
                                                    <span class="compliance-label">Resolución:</span>
                                                    <span class="compliance-status {{ $serviceRequest->resolved_at && $serviceRequest->resolution_deadline && $serviceRequest->resolved_at->lte($serviceRequest->resolution_deadline) ? 'text-success' : 'text-danger' }}">
                                                        <i class="fas fa-{{ $serviceRequest->resolved_at && $serviceRequest->resolution_deadline && $serviceRequest->resolved_at->lte($serviceRequest->resolution_deadline) ? 'check' : 'times' }} mr-1"></i>
                                                        {{ $serviceRequest->resolved_at ? $serviceRequest->resolved_at->format('d/m/Y H:i') : 'Pendiente' }}
                                                    </span>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div class="tab-pane fade" id="v-pills-details" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-info-circle text-primary"></i>
                                                Información de la Solicitud
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <th width="40%" class="text-muted">Ticket #:</th>
                                                    <td>
                                                        <span class="badge badge-primary">{{ $serviceRequest->ticket_number }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Título:</th>
                                                    <td class="font-weight-bold">{{ $serviceRequest->title }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Descripción:</th>
                                                    <td>{{ $serviceRequest->description }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Estado:</th>
                                                    <td>
                                                        <span class="badge badge-{{ $serviceRequest->getStatusColor() }}">
                                                            {{ $serviceRequest->status }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Prioridad:</th>
                                                    <td>
                                                        <span class="badge badge-{{ $serviceRequest->getPriorityColor() }}">
                                                            {{ $serviceRequest->criticality_level }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-users text-success"></i>
                                                Asignaciones y Servicio
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <th width="40%" class="text-muted">Solicitante:</th>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-circle symbol-30 mr-2">
                                                                <div class="symbol-label bg-light-primary">
                                                                    <i class="fas fa-user text-primary"></i>
                                                                </div>
                                                            </div>
                                                            {{ $serviceRequest->requester->name ?? 'N/A' }}
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Asignado a:</th>
                                                    <td>
                                                        @if($serviceRequest->assignee)
                                                            <div class="d-flex align-items-center">
                                                                <div class="symbol symbol-circle symbol-30 mr-2">
                                                                    <div class="symbol-label bg-light-success">
                                                                        <i class="fas fa-user-tie text-success"></i>
                                                                    </div>
                                                                </div>
                                                                {{ $serviceRequest->assignee->name }}
                                                            </div>
                                                        @else
                                                            <span class="text-muted font-italic">No asignado</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Sub-Servicio:</th>
                                                    <td>{{ $serviceRequest->subService->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">SLA:</th>
                                                    <td>{{ $serviceRequest->sla->name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Fecha Creación:</th>
                                                    <td>{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('service-requests.show', $serviceRequest->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Detalles
                            </a>
                            <a href="{{ route('reports.request-timeline') }}" class="btn btn-info ml-2">
                                <i class="fas fa-list"></i> Ver Todas las Solicitudes
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                <i class="fas fa-sync-alt"></i>
                                Actualizado: {{ now()->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-navigation .nav-pills-custom .nav-link {
    border-radius: 25px;
    margin-right: 10px;
    margin-bottom: 10px;
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    background: white;
    color: #6c757d;
    transition: all 0.3s ease;
}

.timeline-navigation .nav-pills-custom .nav-link.active {
    background: #007bff;
    border-color: #007bff;
    color: white;
    box-shadow: 0 2px 5px rgba(0,123,255,0.3);
}

.timeline-vertical {
    position: relative;
    padding-left: 30px;
}

.timeline-vertical::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item-vertical {
    position: relative;
    margin-bottom: 25px;
    display: flex;
    align-items: flex-start;
}

.timeline-marker-vertical {
    position: absolute;
    left: -30px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
    z-index: 2;
    box-shadow: 0 0 0 3px white;
}

.timeline-content-vertical {
    flex: 1;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-left: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timeline-header-vertical {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    background: #fff;
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: 0.5rem;
    position: relative;
}

.info-box .info-box-icon {
    border-radius: 0.25rem;
    align-items: center;
    display: flex;
    font-size: 1.875rem;
    justify-content: center;
    text-align: center;
    width: 70px;
}

.info-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.8;
    flex: 1;
    padding: 0 10px;
}

.info-box .info-box-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-transform: uppercase;
    font-size: 0.875rem;
}

.info-box .info-box-number {
    display: block;
    font-weight: bold;
    font-size: 1.5rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.metric-item {
    text-align: center;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
}

.sla-compliance .compliance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.compliance-label {
    font-weight: 500;
    color: #6c757d;
}

.compliance-status {
    font-weight: bold;
}

.symbol {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.symbol-25 { width: 25px; height: 25px; }
.symbol-30 { width: 30px; height: 30px; }
.symbol-50 { width: 50px; height: 50px; }

.symbol-label {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.bg-light-primary { background-color: #e1f0ff !important; }
.bg-light-success { background-color: #e8f5e8 !important; }
.bg-light-warning { background-color: #fff3cd !important; }
.bg-light-danger { background-color: #f8d7da !important; }
.bg-light-info { background-color: #d1ecf1 !important; }
.bg-light-secondary { background-color: #e2e3e5 !important; }

.badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.bg-gradient-info { background: linear-gradient(45deg, #17a2b8, #6f42c1) !important; color: white; }
.bg-gradient-success { background: linear-gradient(45deg, #28a745, #20c997) !important; color: white; }
.bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #fd7e14) !important; color: white; }
.bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #e83e8c) !important; color: white; }

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }

    .timeline-navigation .nav-pills-custom .nav-link {
        display: block;
        margin-right: 0;
        text-align: center;
    }
}
</style>

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
