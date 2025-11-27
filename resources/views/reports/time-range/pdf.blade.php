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
            padding: 10px 0;
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

    <!-- Lista Completa de Solicitudes -->
    <div class="section">
        <div class="section-title">LISTADO COMPLETO DE SOLICITUDES ({{ $statistics['total'] }})</div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ticket</th>
                    <th>Estado</th>
                    <th>Título</th>
                    <th>Familia / Servicio / Subservicio</th>
                    <th>Solicitante</th>
                    <th>Técnico</th>
                    <th>Criticidad</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groupedData as $familyName => $requests)
                    @foreach($requests as $request)
                        @php
                            $fileEvidences = $request->evidences
                                ->where('evidence_type', '!=', 'SISTEMA')
                                ->where('file_path', '!=', null);
                        @endphp
                        <tr>
                            <td>{{ $request->id }}</td>
                            <td>{{ $request->ticket_number }}</td>
                            <td>{{ $request->status }}</td>
                            <td>{{ $request->title ?? 'Sin titulo' }}</td>
                            <td>
                                {{ $familyName }} /
                                {{ $request->subService->service->name ?? 'N/A' }} /
                                {{ $request->subService->name ?? 'N/A' }}
                            </td>
                            <td>{{ $request->requester->name ?? 'N/A' }}</td>
                            <td>{{ $request->assignedTechnician->name ?? 'Sin asignar' }}</td>
                            <td>{{ $request->criticality_level ?? 'N/A' }}</td>
                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($request->tasks->count() > 0 || $fileEvidences->count() > 0)
                            <tr>
                                <td colspan="9">
                                    <div style="display: grid; gap: 6px; font-size: 10px;">
                                        @if($request->tasks->count() > 0)
                                            <div>
                                                <strong>Tareas ({{ $request->tasks->count() }}):</strong>
                                                <ul style="margin: 4px 0 0 15px; padding: 0;">
                                                    @foreach($request->tasks as $task)
                                                        <li style="margin-bottom: 2px;">
                                                            {{ $task->title ?? 'Sin título' }}
                                                            @if(!empty($task->status))
                                                                <span style="color:#555;">[{{ $task->status }}]</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if($fileEvidences->count() > 0)
                                            <div>
                                                <strong>Archivos ({{ $fileEvidences->count() }}):</strong>
                                                <ul style="margin: 4px 0 0 15px; padding: 0;">
                                                    @foreach($fileEvidences as $evidence)
                                                        @php
                                                            $storedName = basename($evidence->file_path ?? '');
                                                            $originalName = $evidence->file_original_name ?? $storedName ?? 'N/A';
                                                        @endphp
                                                        <li style="margin-bottom: 2px;">
                                                            {{ Str::limit($storedName ?: $originalName, 70) }}
                                                            @if($storedName && $storedName !== $originalName)
                                                                <span style="color:#555;">({{ Str::limit($originalName, 40) }})</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @empty
                    <tr>
                        <td colspan="11" class="no-data">No hay solicitudes en el rango seleccionado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pie de pagina -->
    <div class="footer">
        Reporte generado automaticamente por el Sistema de Gestion de Servicios
    </div>
</body>
</html>
