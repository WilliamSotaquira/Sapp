<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Rango de Tiempo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .info-item {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
        }

        .status {
            font-weight: bold;
            text-transform: uppercase;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }

        .table th,
        .table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        .table th {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            font-style: italic;
            padding: 20px;
            color: #666;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-card {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>REPORTE POR RANGO DE TIEMPO</h1>
        <div><strong>Periodo:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</div>
        <div><strong>Generado el:</strong> {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <!-- Resumen Ejecutivo -->
    <div class="section">
        <div class="section-title">RESUMEN EJECUTIVO</div>
        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-value">{{ $statistics['total'] }}</span>
                <span class="stat-label">Total Solicitudes</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ $statistics['byStatus']['Completada']['count'] ?? 0 }}</span>
                <span class="stat-label">Completadas</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ $statistics['byStatus']['En Proceso']['count'] ?? 0 }}</span>
                <span class="stat-label">En Proceso</span>
            </div>
            <div class="stat-card">
                <span class="stat-value">{{ number_format($statistics['avgResolutionTime'], 1) }}</span>
                <span class="stat-label">Dias Promedio</span>
            </div>
        </div>
    </div>

    <!-- Lista Completa de Solicitudes -->
    <div class="section">
        <div class="section-title">LISTADO COMPLETO DE SOLICITUDES ({{ $statistics['total'] }})</div>
        @foreach($groupedData as $familyName => $requests)
            <div style="margin-bottom: 20px;">
                <div style="background-color: #e9e9e9; padding: 8px; font-weight: bold; margin-bottom: 10px;">
                    {{ strtoupper($familyName) }} ({{ $requests->count() }} solicitudes)
                </div>

                @foreach($requests as $request)
                    <!-- Información de la Solicitud -->
                    <table class="table" style="margin-bottom: 5px;">
                        <tbody>
                            <tr>
                                <td style="width: 20%;"><strong>Ticket:</strong></td>
                                <td style="width: 30%;">#{{ $request->ticket_number }}</td>
                                <td style="width: 20%;"><strong>Estado:</strong></td>
                                <td style="width: 30%;"><strong>{{ $request->status }}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Titulo:</strong></td>
                                <td colspan="3">{{ $request->title ?? 'Sin titulo' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Solicitante:</strong></td>
                                <td>{{ $request->requester->name ?? 'N/A' }}</td>
                                <td><strong>Tecnico:</strong></td>
                                <td>{{ $request->assignedTechnician->name ?? 'Sin asignar' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sub-servicio:</strong></td>
                                <td>{{ $request->subService->name ?? 'N/A' }}</td>
                                <td><strong>Criticidad:</strong></td>
                                <td>{{ $request->criticality_level ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha:</strong></td>
                                <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                                <td><strong>Tareas:</strong></td>
                                <td>{{ $request->tasks->count() }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Evidencias de la Solicitud -->
                    @php
                        $fileEvidences = $request->evidences
                            ->where('evidence_type', '!=', 'SISTEMA')
                            ->where('file_path', '!=', null);
                    @endphp
                    @if($fileEvidences->count() > 0)
                        <div style="margin-left: 20px; margin-bottom: 15px;">
                            <div style="font-weight: bold; margin-bottom: 5px; font-size: 11px;">
                                Archivos adjuntos ({{ $fileEvidences->count() }}):
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Titulo</th>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Subido Por</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fileEvidences as $evidence)
                                    <tr>
                                        <td>{{ $evidence->evidence_type }}</td>
                                        <td>{{ $evidence->title ?? 'Sin titulo' }}</td>
                                        <td>{{ Str::limit($evidence->file_original_name ?? 'N/A', 30) }}</td>
                                        <td>{{ $evidence->formatted_file_size ?? 'N/A' }}</td>
                                        <td>{{ $evidence->uploadedBy->name ?? 'N/A' }}</td>
                                        <td>{{ $evidence->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="margin-left: 20px; margin-bottom: 15px; font-style: italic; color: #666; font-size: 10px;">
                            Sin archivos adjuntos
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>

    <!-- Pie de pagina -->
    <div class="footer">
        Reporte generado automaticamente por el Sistema de Gestion de Servicios
    </div>
</body>
</html>
