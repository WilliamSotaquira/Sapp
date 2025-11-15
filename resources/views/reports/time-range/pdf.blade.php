<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Rango de Tiempo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }

        .header {
            background-color: #667eea;
            color: white;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
        }

        .report-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 3px solid #007bff;
        }

        .info-row {
            width: 100%;
            margin-bottom: 8px;
        }

        .info-row::after {
            content: "";
            display: table;
            clear: both;
        }

        .info-item {
            float: left;
            width: 33%;
            text-align: center;
            padding: 5px;
        }

        .info-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            display: block;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e9ecef;
        }

        .stats-row {
            width: 100%;
            margin-bottom: 10px;
        }

        .stats-row::after {
            content: "";
            display: table;
            clear: both;
        }

        .stat-card {
            float: left;
            width: 24%;
            margin-right: 1%;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: center;
            background-color: #fff;
        }

        .stat-card:last-child {
            margin-right: 0;
        }

        .stat-icon {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 3px;
        }

        .stat-label {
            font-size: 8px;
            color: #6c757d;
            text-transform: uppercase;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        table thead tr {
            background-color: #f8f9fa;
        }

        table th {
            padding: 8px 5px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #dee2e6;
            color: #495057;
        }

        table td {
            padding: 6px 5px;
            border: 1px solid #dee2e6;
            font-size: 8px;
        }

        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 7px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .family-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .family-header {
            background-color: #e9ecef;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            border-left: 3px solid #007bff;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
        }

        .page-break {
            page-break-after: always;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            margin-top: 3px;
        }

        .progress-fill {
            height: 100%;
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>REPORTE POR RANGO DE TIEMPO</h1>
        <p>Sistema de Gestión de Solicitudes de Servicio</p>
    </div>

    <!-- Report Info -->
    <div class="report-info">
        <div class="info-row">
            <div class="info-item">
                <span class="info-label">Fecha Inicio</span>
                <span class="info-value">{{ $dateRange['start']->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha Fin</span>
                <span class="info-value">{{ $dateRange['end']->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Generado</span>
                <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="section">
        <div class="section-title">RESUMEN EJECUTIVO</div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <span class="stat-value">{{ $statistics['total'] }}</span>
                <span class="stat-label">Total Solicitudes</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <span class="stat-value">{{ $statistics['byStatus']['Completada']['count'] ?? 0 }}</span>
                <span class="stat-label">Completadas</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <span class="stat-value">{{ $statistics['byStatus']['En Proceso']['count'] ?? 0 }}</span>
                <span class="stat-label">En Proceso</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <span class="stat-value">{{ number_format($statistics['avgResolutionTime'], 1) }}</span>
                <span class="stat-label">Días Promedio</span>
            </div>
        </div>
    </div>

    <!-- Distribution by Status -->
    <div class="section">
        <div class="section-title">DISTRIBUCIÓN POR ESTADO</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%">Estado</th>
                    <th style="width: 20%" class="text-center">Cantidad</th>
                    <th style="width: 20%" class="text-center">Porcentaje</th>
                    <th style="width: 20%">Progreso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['byStatus'] as $status => $data)
                <tr>
                    <td>
                        <span class="badge
                            @if($status === 'Completada') badge-success
                            @elseif($status === 'En Proceso') badge-info
                            @elseif($status === 'Cancelada') badge-danger
                            @else badge-secondary
                            @endif
                        ">{{ $status }}</span>
                    </td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-center">{{ $data['percentage'] }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $data['percentage'] }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Distribution by Family -->
    <div class="section">
        <div class="section-title">DISTRIBUCIÓN POR FAMILIA DE SERVICIOS</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%">Familia</th>
                    <th style="width: 25%" class="text-center">Cantidad</th>
                    <th style="width: 25%" class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['byFamily'] as $family => $data)
                <tr>
                    <td>{{ $family }}</td>
                    <td class="text-center"><strong>{{ $data['count'] }}</strong></td>
                    <td class="text-center">{{ $data['percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <!-- Requests by Family -->
    @foreach($groupedData as $familyName => $requests)
    <div class="family-section">
        <div class="family-header">
            {{ strtoupper($familyName) }} ({{ $requests->count() }} solicitudes)
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%">ID</th>
                    <th style="width: 20%">Servicio</th>
                    <th style="width: 15%">Solicitante</th>
                    <th style="width: 12%">Fecha</th>
                    <th style="width: 12%">Estado</th>
                    <th style="width: 10%">Criticidad</th>
                    <th style="width: 8%" class="text-center">Evidencias</th>
                    <th style="width: 15%">Responsable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>{{ $request->id }}</td>
                    <td>{{ $request->subService->service->name ?? 'N/A' }}</td>
                    <td>{{ $request->requester->name ?? 'N/A' }}</td>
                    <td>{{ $request->created_at->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge
                            @if($request->status === 'Completada') badge-success
                            @elseif($request->status === 'En Proceso') badge-info
                            @elseif($request->status === 'Cancelada') badge-danger
                            @else badge-secondary
                            @endif
                        ">{{ $request->status }}</span>
                    </td>
                    <td>
                        <span class="badge
                            @if($request->criticality_level === 'Alta') badge-danger
                            @elseif($request->criticality_level === 'Media') badge-warning
                            @else badge-info
                            @endif
                        ">{{ $request->criticality_level }}</span>
                    </td>
                    <td class="text-center">{{ $request->evidences->count() }}</td>
                    <td>{{ $request->assignedTo->name ?? 'Sin asignar' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    @if($evidences->count() > 0)
    <div class="page-break"></div>

    <!-- Evidence List -->
    <div class="section">
        <div class="section-title">REGISTRO DE EVIDENCIAS</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%">ID Sol.</th>
                    <th style="width: 12%">Tipo</th>
                    <th style="width: 35%">Archivo</th>
                    <th style="width: 12%">Tamaño</th>
                    <th style="width: 18%">Subido Por</th>
                    <th style="width: 15%">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evidences as $evidence)
                <tr>
                    <td>{{ $evidence->service_request_id }}</td>
                    <td><span class="badge badge-primary">{{ $evidence->evidence_type }}</span></td>
                    <td>{{ Str::limit($evidence->file_original_name, 40) }}</td>
                    <td>{{ $evidence->formatted_file_size }}</td>
                    <td>{{ $evidence->uploadedBy->name ?? 'N/A' }}</td>
                    <td>{{ $evidence->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size: 8px; color: #6c757d; margin-top: 10px;">
            <strong>Total de evidencias:</strong> {{ $evidences->count() }} archivos
        </p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Reporte generado automáticamente el {{ now()->format('d/m/Y H:i:s') }} | Sistema de Gestión de Solicitudes</p>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
