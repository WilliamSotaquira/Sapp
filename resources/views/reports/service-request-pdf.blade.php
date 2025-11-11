<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }

        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .section-title {
            background-color: #f8fafc;
            padding: 8px 12px;
            margin: -15px -15px 15px -15px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: bold;
            color: #374151;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #6b7280;
        }

        .info-value {
            color: #374151;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #f9fafb;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Solicitud de Servicio</h1>
        <div class="subtitle">Ticket #{{ $serviceRequest->ticket_number }}</div>
        <div class="subtitle">Generado el: {{ $generated_at }}</div>
    </div>

    <!-- Información Básica -->
    <div class="section">
        <div class="section-title">Información Básica</div>
        <div class="grid-3">
            <div class="info-item">
                <span class="info-label">Ticket:</span>
                <span class="info-value">#{{ $serviceRequest->ticket_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado:</span>
                <span class="info-value">
                    @php
                        $statusColors = [
                            'PENDIENTE' => '#f59e0b',
                            'ACEPTADA' => '#3b82f6',
                            'EN_PROCESO' => '#8b5cf6',
                            'PAUSADA' => '#f97316',
                            'COMPLETADA' => '#10b981',
                            'CANCELADA' => '#ef4444',
                        ];
                        $color = $statusColors[$serviceRequest->status] ?? '#6b7280';
                    @endphp
                    <span class="status-badge" style="background-color: {{ $color }};">
                        {{ $serviceRequest->status }}
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Solicitante:</span>
                <span class="info-value">{{ $serviceRequest->requester->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Técnico Asignado:</span>
                <span class="info-value">{{ $serviceRequest->assignedTechnician->name ?? 'No asignado' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Título:</span>
                <span class="info-value">{{ $serviceRequest->title ?? 'Sin título' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Sub-servicio:</span>
                <span class="info-value">{{ $serviceRequest->subService->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">SLA:</span>
                <span class="info-value">{{ $serviceRequest->sla->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Nivel de Criticidad:</span>
                <span class="info-value">{{ $serviceRequest->criticality_level ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha de Creación:</span>
                <span class="info-value">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Descripción -->
    <div class="section">
        <div class="section-title">Descripción del Servicio</div>
        <div class="info-item">
            <p>{{ $serviceRequest->description ?? 'Sin descripción' }}</p>
        </div>
    </div>

    <!-- Fechas Límite -->
    <div class="section">
        <div class="section-title">Fechas Límite</div>
        <div class="grid-3">
            <div class="info-item">
                <span class="info-label">Límite de Aceptación:</span>
                <span class="info-value">
                    @if ($serviceRequest->acceptance_deadline)
                        {{ \Carbon\Carbon::parse($serviceRequest->acceptance_deadline)->format('d/m/Y H:i') }}
                    @else
                        No definido
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Límite de Respuesta:</span>
                <span class="info-value">
                    @if ($serviceRequest->response_deadline)
                        {{ \Carbon\Carbon::parse($serviceRequest->response_deadline)->format('d/m/Y H:i') }}
                    @else
                        No definido
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Límite de Resolución:</span>
                <span class="info-value">
                    @if ($serviceRequest->resolution_deadline)
                        {{ \Carbon\Carbon::parse($serviceRequest->resolution_deadline)->format('d/m/Y H:i') }}
                    @else
                        No definido
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Aceptada el:</span>
                <span class="info-value">
                    @if ($serviceRequest->accepted_at)
                        {{ \Carbon\Carbon::parse($serviceRequest->accepted_at)->format('d/m/Y H:i') }}
                    @else
                        No aceptada
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Respondida el:</span>
                <span class="info-value">
                    @if ($serviceRequest->responded_at)
                        {{ \Carbon\Carbon::parse($serviceRequest->responded_at)->format('d/m/Y H:i') }}
                    @else
                        No respondida
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Resuelta el:</span>
                <span class="info-value">
                    @if ($serviceRequest->resolved_at)
                        {{ \Carbon\Carbon::parse($serviceRequest->resolved_at)->format('d/m/Y H:i') }}
                    @else
                        No resuelta
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Evidencias -->
    <div class="section">
        <div class="section-title">Evidencias</div>
        @if ($serviceRequest->evidences && $serviceRequest->evidences->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceRequest->evidences as $evidence)
                        <tr>
                            <td>{{ $evidence->title ?? 'Sin título' }}</td>
                            <td>{{ $evidence->evidence_type ?? 'N/A' }}</td>
                            <td>{{ $evidence->description ?? 'Sin descripción' }}</td>
                            <td>{{ $evidence->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="info-item">
                <span class="info-label">Total de evidencias:</span>
                <span class="info-value">{{ $serviceRequest->evidences->count() }}</span>
            </div>
        @else
            <div class="no-data">
                No hay evidencias registradas para esta solicitud
            </div>
        @endif
    </div>

    <!-- Notas de Resolución -->
    @if ($serviceRequest->resolution_notes)
        <div class="section">
            <div class="section-title">Notas de Resolución</div>
            <div class="info-item">
                <p>{{ $serviceRequest->resolution_notes }}</p>
            </div>
        </div>
    @endif

    <div class="footer">
        Reporte generado automáticamente por el Sistema de Gestión de Servicios
    </div>
</body>

</html>
