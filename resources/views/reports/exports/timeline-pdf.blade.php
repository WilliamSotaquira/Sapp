<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Timeline - {{ $request->ticket_number }}</title>
    <style>
        /* Estilos generales */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2C3E50;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2C3E50;
            margin: 0;
            font-size: 18px;
        }
        .header .subtitle {
            color: #7F8C8D;
            font-size: 14px;
            margin: 5px 0;
        }

        /* Información de la solicitud */
        .request-info {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table th, .info-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .info-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 25%;
        }

        /* Estadísticas */
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 1;
            min-width: 23%;
            margin: 0 1%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            background: #f8f9fa;
        }
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        /* Distribución de tiempos */
        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .distribution-table th, .distribution-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .distribution-table th {
            background-color: #2C3E50;
            color: white;
        }
        .progress-bar {
            background-color: #e9ecef;
            border-radius: 3px;
            height: 15px;
            position: relative;
        }
        .progress-fill {
            background-color: #3498DB;
            height: 100%;
            border-radius: 3px;
            text-align: center;
            color: white;
            font-size: 10px;
            line-height: 15px;
        }

        /* Timeline */
        .timeline {
            margin: 20px 0;
            position: relative;
            padding-left: 20px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #3498DB;
        }
        .timeline-event {
            position: relative;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f8f9fa;
            page-break-inside: avoid;
        }
        .timeline-event::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 15px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3498DB;
            border: 2px solid white;
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .event-title {
            font-weight: bold;
            color: #2C3E50;
            font-size: 13px;
        }
        .event-date {
            color: #666;
            font-size: 11px;
        }
        .event-description {
            color: #555;
            margin-bottom: 5px;
        }
        .event-user {
            color: #777;
            font-style: italic;
            font-size: 11px;
        }
        .event-duration {
            color: #27AE60;
            font-size: 11px;
            margin-top: 5px;
        }
        .event-type {
            display: inline-block;
            background: #95A5A6;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-top: 5px;
        }

        /* Colores para tipos de evento */
        .event-created { border-left: 4px solid #3498DB; }
        .event-assigned { border-left: 4px solid #2980B9; }
        .event-accepted { border-left: 4px solid #27AE60; }
        .event-responded { border-left: 4px solid #16A085; }
        .event-paused { border-left: 4px solid #F39C12; }
        .event-resumed { border-left: 4px solid #D35400; }
        .event-resolved { border-left: 4px solid #2ECC71; }
        .event-closed { border-left: 4px solid #7F8C8D; }
        .event-evidence { border-left: 4px solid #9B59B6; }
        .event-breach { border-left: 4px solid #E74C3C; }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #7F8C8D;
            font-size: 10px;
        }

        /* Utilidades */
        .page-break { page-break-before: always; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-0 { margin-bottom: 0; }
        .mt-10 { margin-top: 10px; }
        .bold { font-weight: bold; }
        .color-primary { color: #3498DB; }
        .color-success { color: #27AE60; }
        .color-warning { color: #F39C12; }
        .color-danger { color: #E74C3C; }

        /* Estados */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            color: white;
            margin-right: 5px;
        }
        .status-pending { background: #F39C12; }
        .status-accepted { background: #3498DB; }
        .status-in-progress { background: #2980B9; }
        .status-paused { background: #95A5A6; }
        .status-resolved { background: #27AE60; }
        .status-closed { background: #7F8C8D; }
        .status-cancelled { background: #E74C3C; }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>LÍNEA DE TIEMPO - SOLICITUD DE SERVICIO</h1>
        <div class="subtitle">Ticket: {{ $request->ticket_number }} | {{ $request->title }}</div>
        <div class="subtitle">Generado el: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- Información de la solicitud -->
    <div class="request-info">
        <table class="info-table">
            <tr>
                <th>Información de la Solicitud</th>
                <th>Asignaciones y Servicio</th>
            </tr>
            <tr>
                <td>
                    <strong>Ticket #:</strong> {{ $request->ticket_number }}<br>
                    <strong>Título:</strong> {{ $request->title }}<br>
                    <strong>Estado:</strong>
                    <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $request->status)) }}">
                        {{ $request->status }}
                    </span><br>
                    <strong>Prioridad:</strong> {{ $request->criticality_level }}<br>
                    <strong>Fecha Creación:</strong> {{ $request->created_at->format('d/m/Y H:i') }}
                </td>
                <td>
                    <strong>Solicitante:</strong> {{ $request->requester->name ?? 'N/A' }}<br>
                    <strong>Asignado a:</strong> {{ $request->assignee->name ?? 'No asignado' }}<br>
                    <strong>Sub-Servicio:</strong> {{ $request->subService->name ?? 'N/A' }}<br>
                    <strong>SLA:</strong> {{ $request->sla->name ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Estadísticas de Tiempo -->
    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-label">Tiempo Total</div>
            <div class="stat-value color-primary">{{ $timeStatistics['total_time'] }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Tiempo Activo</div>
            <div class="stat-value color-success">{{ $timeStatistics['active_time'] }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Tiempo Pausado</div>
            <div class="stat-value color-warning">{{ $timeStatistics['paused_time'] }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Eficiencia</div>
            <div class="stat-value {{ $timeStatistics['efficiency_raw'] > 80 ? 'color-success' : ($timeStatistics['efficiency_raw'] > 60 ? 'color-warning' : 'color-danger') }}">
                {{ $timeStatistics['efficiency'] }}
            </div>
        </div>
    </div>

    <!-- Distribución de Tiempos -->
    @if(count($timeSummary) > 0)
    <div class="mt-10">
        <h3 style="color: #2C3E50; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Distribución de Tiempos por Estado</h3>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th width="40%">Tipo de Evento</th>
                    <th width="20%">Duración</th>
                    <th width="20%">Minutos</th>
                    <th width="20%">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeSummary as $summary)
                <tr>
                    <td>{{ $summary['event_type'] }}</td>
                    <td class="bold">{{ $summary['duration'] }}</td>
                    <td>{{ $summary['minutes'] }} min</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $summary['percentage'] }}%;">
                                {{ $summary['percentage'] }}%
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Línea de Tiempo -->
    <div class="page-break"></div>
    <div class="header">
        <h1>DETALLE DE LÍNEA DE TIEMPO</h1>
        <div class="subtitle">Ticket: {{ $request->ticket_number }} - Eventos Cronológicos</div>
    </div>

    <div class="timeline">
        @foreach($timelineEvents as $event)
        <div class="timeline-event event-{{ $event['status'] }}">
            <div class="event-header">
                <div class="event-title">{{ $event['event'] }}</div>
                <div class="event-date">{{ $event['timestamp']->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="event-description">{{ $event['description'] }}</div>
            @if($event['user'])
            <div class="event-user">
                <strong>Usuario:</strong> {{ $event['user']->name }}
            </div>
            @endif
            @if(isset($timeInStatus[$event['status']]))
            <div class="event-duration">
                <strong>Tiempo en estado:</strong> {{ $timeInStatus[$event['status']]['formatted'] }}
            </div>
            @endif
            @if(isset($event['evidence_type']))
            <div class="event-type">
                {{ $this->getEvidenceTypeLabel($event['evidence_type']) }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Sistema de Gestión de Servicios - Reporte generado automáticamente</p>
        <p>Página <span class="page-number"></span></p>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>

@php
    function getEvidenceTypeLabel($evidenceType) {
        $labels = [
            'PASO_A_PASO' => 'Paso a Paso',
            'ARCHIVO' => 'Archivo Adjunto',
            'COMENTARIO' => 'Comentario',
            'SISTEMA' => 'Sistema'
        ];
        return $labels[$evidenceType] ?? $evidenceType;
    }
@endphp
