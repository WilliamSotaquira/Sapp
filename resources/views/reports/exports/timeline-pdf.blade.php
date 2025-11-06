<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Timeline - Ticket #{{ $request->ticket_number ?? 'N/A' }}</title>
    <style>
        /* ESTILOS GENERALES */
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2c5aa0;
        }

        .header h1 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 18px;
            font-weight: bold;
        }

        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 9px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 9px;
        }

        .header-info div {
            text-align: left;
        }

        /* TABLAS */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c5aa0;
        }

        /* SECCIONES */
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section h2 {
            background-color: #2c5aa0;
            color: white;
            padding: 6px 10px;
            margin: 0 0 12px 0;
            font-size: 11px;
            border-radius: 3px;
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 15px;
            font-size: 9px;
        }

        /* EVENTOS TIMELINE */
        .timeline-event {
            margin-bottom: 8px;
            padding: 5px;
            border-left: 3px solid #2c5aa0;
            background-color: #f8f9fa;
        }

        .event-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            margin-right: 5px;
        }

        .event-creation {
            background-color: #d4edda;
            color: #155724;
        }

        .event-assignment {
            background-color: #cce7ff;
            color: #004085;
        }

        .event-resolution {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .event-evidence {
            background-color: #fff3cd;
            color: #856404;
        }

        .event-system {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .event-progress {
            background-color: #e8d7ff;
            color: #4a2a7a;
        }

        .event-closure {
            background-color: #ffd6cc;
            color: #8b4513;
        }

        /* EVIDENCIAS E IMÁGENES */
        .evidences-section {
            margin-top: 15px;
        }

        .evidence-item {
            margin-bottom: 12px;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #fafafa;
        }

        .evidence-header {
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 5px;
            font-size: 9px;
        }

        .evidence-description {
            font-size: 8px;
            color: #666;
            margin-bottom: 8px;
        }

        .images-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .image-container {
            width: calc(50% - 4px);
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .evidence-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 3px;
            max-height: 150px;
            object-fit: contain;
        }

        .image-caption {
            font-size: 7px;
            color: #666;
            text-align: center;
            margin-top: 3px;
        }

        .no-image {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 15px;
            font-size: 8px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 3px;
        }

        /* ESTADÍSTICAS */
        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-card {
            flex: 1;
            min-width: 120px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #2c5aa0;
        }

        .stat-value {
            font-size: 11px;
            font-weight: bold;
            color: #2c5aa0;
        }

        .stat-label {
            font-size: 8px;
            color: #666;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 8px;
        }

        /* UTILIDADES */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mt-10 {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- ENCABEZADO -->
    <div class="header">
        <h1>📋 REPORTE DE TIMELINE DETALLADO</h1>
        <p>Ticket #{{ $request->ticket_number ?? 'N/A' }} - Generado el {{ now()->format('d/m/Y H:i:s') }}</p>

        <div class="header-info">
            <div>
                <strong>Solicitante:</strong> {{ $request->requester->name ?? 'N/A' }}<br>
                <strong>Servicio:</strong> {{ $request->subService->name ?? 'N/A' }}<br>
                <strong>Asignado a:</strong> {{ $request->assignee->name ?? 'No asignado' }}
            </div>
            <div>
                <strong>Estado:</strong> {{ $request->status ?? 'N/A' }}<br>
                <strong>Prioridad:</strong> {{ $request->sla->criticality_level ?? 'N/A' }}<br>
                <strong>SLA:</strong> {{ $request->sla->name ?? 'N/A' }}
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN GENERAL -->
    <div class="section">
        <h2>📊 INFORMACIÓN GENERAL</h2>
        <table class="table">
            <tbody>
                <tr>
                    <td width="25%"><strong>Ticket Number</strong></td>
                    <td width="25%">{{ $request->ticket_number ?? 'N/A' }}</td>
                    <td width="25%"><strong>Fecha Creación</strong></td>
                    <td width="25%">{{ $request->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Solicitante</strong></td>
                    <td>{{ $request->requester->name ?? 'N/A' }}</td>
                    <td><strong>Email</strong></td>
                    <td>{{ $request->requester->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Servicio</strong></td>
                    <td>{{ $request->subService->name ?? 'N/A' }}</td>
                    <td><strong>Familia</strong></td>
                    <td>{{ $request->subService->service->family->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Estado Actual</strong></td>
                    <td>{{ $request->status ?? 'N/A' }}</td>
                    <td><strong>Asignado a</strong></td>
                    <td>{{ $request->assignee->name ?? 'No asignado' }}</td>
                </tr>
                @if($request->resolved_at)
                <tr>
                    <td><strong>Fecha Resolución</strong></td>
                    <td>{{ $request->resolved_at->format('d/m/Y H:i') }}</td>
                    <td><strong>Tiempo Total</strong></td>
                    <td>{{ $totalResolutionTime ?? 'N/A' }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- ESTADÍSTICAS DE TIEMPO -->
    @if($timeInStatus && count($timeInStatus) > 0)
    <div class="section">
        <h2>⏱️ ESTADÍSTICAS DE TIEMPO</h2>

        @if($totalResolutionTime)
        <div class="stat-card">
            <div class="stat-value">{{ $totalResolutionTime }}</div>
            <div class="stat-label">Tiempo Total de Resolución</div>
        </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th width="70%">Estado</th>
                    <th width="30%">Tiempo Transcurrido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeInStatus as $status => $time)
                <tr>
                    <td>{{ $status }}</td>
                    <td>{{ $time }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- TIMELINE DE EVENTOS -->
    <div class="section">
        <h2>🕒 LINEA DE TIEMPO - EVENTOS</h2>

        @if(!empty($timelineEvents) && count($timelineEvents) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th width="15%">Fecha/Hora</th>
                    <th width="12%">Tipo</th>
                    <th width="38%">Evento</th>
                    <th width="20%">Usuario</th>
                    <th width="15%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timelineEvents as $event)
                @php
                // Determinar clase del tipo de evento
                $type = $event['type'] ?? 'system';
                $typeClasses = [
                'creation' => 'event-creation',
                'assignment' => 'event-assignment',
                'acceptance' => 'event-assignment',
                'progress' => 'event-progress',
                'resolution' => 'event-resolution',
                'closure' => 'event-closure',
                'evidence' => 'event-evidence',
                'system' => 'event-system'
                ];
                $typeClass = $typeClasses[$type] ?? 'event-system';
                $typeLabel = match($type) {
                'creation' => 'Creación',
                'assignment' => 'Asignación',
                'acceptance' => 'Aceptación',
                'progress' => 'En Progreso',
                'resolution' => 'Resolución',
                'closure' => 'Cierre',
                'evidence' => 'Evidencia',
                default => 'Sistema'
                };

                // Formatear timestamp
                $timestamp = $event['timestamp'] ?? $event['created_at'] ?? $event['date'] ?? now();
                if ($timestamp instanceof \DateTime) {
                $formattedTime = $timestamp->format('d/m/Y H:i');
                } else {
                $formattedTime = $timestamp;
                }
                @endphp
                <tr>
                    <td>{{ $formattedTime }}</td>
                    <td>
                        <span class="event-type {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td>
                        <strong>{{ $event['title'] ?? $event['event'] ?? 'Evento del sistema' }}</strong>
                        @if(!empty($event['description']))
                        <br><small>{{ $event['description'] }}</small>
                        @endif
                    </td>
                    <td>{{ $event['user'] ?? $event['user_name'] ?? $event['created_by'] ?? 'Sistema' }}</td>
                    <td>{{ $event['status'] ?? $request->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <p>No hay eventos de timeline disponibles para esta solicitud.</p>
        </div>
        @endif
    </div>

    <!-- EVIDENCIAS CON IMÁGENES - VERSIÓN CORREGIDA -->
    @php
    // USAR LAS EVIDENCIAS PREPARADAS POR EL CONTROLADOR
    $evidencesToShow = $evidencesWithImages ?? $request->evidences;

    if($evidencesToShow && (is_countable($evidencesToShow) ? count($evidencesToShow) : 0) > 0) {
    $imageEvidences = collect($evidencesToShow)->filter(function($evidence) {
    $mimeType = is_array($evidence) ? ($evidence['mime_type'] ?? null) : $evidence->mime_type;
    return $mimeType && str_starts_with($mimeType, 'image/');
    });

    $otherEvidences = collect($evidencesToShow)->filter(function($evidence) {
    $mimeType = is_array($evidence) ? ($evidence['mime_type'] ?? null) : $evidence->mime_type;
    return !$mimeType || !str_starts_with($mimeType, 'image/');
    });
    } else {
    $imageEvidences = collect();
    $otherEvidences = collect();
    }
    @endphp

    @if($evidencesToShow && (is_countable($evidencesToShow) ? count($evidencesToShow) : 0) > 0)
    <div class="section">
        <h2>📎 EVIDENCIAS ADJUNTAS</h2>

        <!-- EVIDENCIAS CON IMÁGENES -->
        @if($imageEvidences->count() > 0)
        <div class="evidences-section">
            <h3 style="color: #2c5aa0; font-size: 10px; margin-bottom: 10px;">🖼️ Evidencias con Imágenes ({{ $imageEvidences->count() }})</h3>

            <div class="images-grid">
                @foreach($imageEvidences as $evidence)
                <div class="image-container">
                    <div class="evidence-item">
                        <div class="evidence-header">
                            {{ is_array($evidence) ? ($evidence['title'] ?? $evidence['file_name']) : ($evidence->title ?? $evidence->file_name) }}
                        </div>

                        @php
                        $description = is_array($evidence) ? ($evidence['description'] ?? '') : ($evidence->description ?? '');
                        @endphp

                        @if($description)
                        <div class="evidence-description">
                            {{ Str::limit($description, 100) }}
                        </div>
                        @endif

                        @php
                        // OBTENER IMAGEN - VERSIÓN SIMPLIFICADA Y SEGURA
                        $imageSrc = null;

                        if (is_array($evidence) && isset($evidence['image_data'])) {
                        // Si el controlador ya preparó los datos de la imagen
                        $imageSrc = $evidence['image_data'];
                        } else {
                        // Intentar cargar la imagen directamente
                        try {
                        $filePath = is_array($evidence) ? ($evidence['file_path'] ?? null) : $evidence->file_path;
                        $mimeType = is_array($evidence) ? ($evidence['mime_type'] ?? null) : $evidence->mime_type;

                        if ($filePath) {
                        // Intentar múltiples rutas
                        $possiblePaths = [
                        storage_path('app/' . $filePath),
                        storage_path('app/public/' . $filePath),
                        public_path('storage/' . $filePath),
                        storage_path($filePath),
                        ];

                        foreach ($possiblePaths as $imagePath) {
                        if (file_exists($imagePath) && is_file($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                        break;
                        }
                        }

                        // Si no se encontró en rutas directas, intentar con Storage
                        if (!$imageSrc && class_exists('Illuminate\Support\Facades\Storage')) {
                        if (\Illuminate\Support\Facades\Storage::exists($filePath)) {
                        $imageContent = \Illuminate\Support\Facades\Storage::get($filePath);
                        $imageSrc = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                        }
                        }
                        }
                        } catch (Exception $e) {
                        // Silenciar errores y continuar con placeholder
                        }
                        }

                        // Si no se encuentra la imagen, mostrar placeholder
                        if (!$imageSrc) {
                        $fileName = is_array($evidence) ? ($evidence['file_name'] ?? 'Archivo') : ($evidence->file_name ?? 'Archivo');
                        $placeholderSvg = '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg">
                            <rect width="100%" height="100%" fill="#f8f9fa" />
                            <text x="50%" y="45%" dominant-baseline="middle" text-anchor="middle"
                                font-family="Arial" font-size="10" fill="#666">
                                Imagen no disponible
                            </text>
                            <text x="50%" y="60%" dominant-baseline="middle" text-anchor="middle"
                                font-family="Arial" font-size="8" fill="#999">
                                ' . $fileName . '
                            </text>
                        </svg>';
                        $imageSrc = 'data:image/svg+xml;base64,' . base64_encode($placeholderSvg);
                        }
                        @endphp

                        <img src="{{ $imageSrc }}" class="evidence-image" alt="{{ is_array($evidence) ? ($evidence['file_name'] ?? '') : $evidence->file_name }}">

                        <div class="image-caption">
                            {{ is_array($evidence) ? ($evidence['file_name'] ?? '') : $evidence->file_name }}<br>
                            Subido: {{ (is_array($evidence) ? Carbon\Carbon::parse($evidence['created_at']) : $evidence->created_at)->format('d/m/Y H:i') }}<br>
                            Por: {{ is_array($evidence) ? ($evidence['uploaded_by'] ?? 'Sistema') : ($evidence->uploadedBy->name ?? 'Sistema') }}
                        </div>
                    </div>
                </div>

                <!-- Salto de página cada 4 imágenes para mejor legibilidad -->
                @if(($loop->iteration % 4) == 0)
                <div style="page-break-after: always;"></div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- OTRAS EVIDENCIAS -->
        @if($otherEvidences->count() > 0)
        <div class="evidences-section">
            <h3 style="color: #2c5aa0; font-size: 10px; margin-bottom: 10px;">📄 Otras Evidencias ({{ $otherEvidences->count() }})</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th width="25%">Archivo</th>
                        <th width="15%">Tipo</th>
                        <th width="30%">Descripción</th>
                        <th width="15%">Subido por</th>
                        <th width="15%">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($otherEvidences as $evidence)
                    <tr>
                        <td>{{ is_array($evidence) ? ($evidence['file_name'] ?? '') : $evidence->file_name }}</td>
                        <td>
                            @php
                            $mimeType = is_array($evidence) ? ($evidence['mime_type'] ?? null) : $evidence->mime_type;
                            $fileType = '📦 Archivo';
                            if ($mimeType) {
                            if (str_contains($mimeType, 'pdf')) {
                            $fileType = '📊 PDF';
                            } else if (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
                            $fileType = '📝 Documento';
                            } else if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'sheet')) {
                            $fileType = '📈 Excel';
                            } else if (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar')) {
                            $fileType = '🗜️ Comprimido';
                            }
                            }
                            @endphp
                            {{ $fileType }}
                        </td>
                        <td>
                            @if(is_array($evidence))
                            {{ $evidence['description'] ? Str::limit($evidence['description'], 50) : 'Sin descripción' }}
                            @else
                            {{ $evidence->description ? Str::limit($evidence->description, 50) : 'Sin descripción' }}
                            @endif
                        </td>
                        <td>{{ is_array($evidence) ? ($evidence['uploaded_by'] ?? 'Sistema') : ($evidence->uploadedBy->name ?? 'Sistema') }}</td>
                        <td>{{ (is_array($evidence) ? Carbon\Carbon::parse($evidence['created_at']) : $evidence->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @else
    <div class="section">
        <h2>📎 EVIDENCIAS</h2>
        <div class="no-data">
            <p>No hay evidencias adjuntas para esta solicitud.</p>
        </div>
    </div>
    @endif

    <!-- RESUMEN FINAL -->
    <div class="section">
        <h2>📈 RESUMEN EJECUTIVO</h2>
        <table class="table">
            <tbody>
                <tr>
                    <td width="25%"><strong>Total Eventos</strong></td>
                    <td width="25%">{{ count($timelineEvents ?? []) }}</td>
                    <td width="25%"><strong>Total Evidencias</strong></td>
                    <td width="25%">{{ is_countable($evidencesToShow) ? count($evidencesToShow) : 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Imágenes</strong></td>
                    <td>{{ $imageEvidences->count() ?? 0 }}</td>
                    <td><strong>Documentos</strong></td>
                    <td>{{ $otherEvidences->count() ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Estado Final</strong></td>
                    <td>{{ $request->status }}</td>
                    <td><strong>SLA Cumplido</strong></td>
                    <td>
                        @if($request->resolved_at && $request->sla)
                        @php
                        $resolutionTime = $request->created_at->diffInMinutes($request->resolved_at);
                        $slaTime = $request->sla->resolution_time_minutes;
                        $isSlaMet = $resolutionTime <= $slaTime;
                            @endphp
                            {{ $isSlaMet ? '✅ Sí' : '❌ No' }}
                            @else
                            N/A
                            @endif
                            </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Gestión de Servicios | Página 1 de 1</p>
    </div>
</body>

</html>
