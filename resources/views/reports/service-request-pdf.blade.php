<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
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
            grid-template-columns: 1fr 1fr;
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

        .files-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .file-container {
            text-align: center;
            border: 1px solid #ccc;
            padding: 8px;
        }

        .evidence-image {
            max-width: 100%;
            max-height: 500px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .file-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .file-title {
            font-size: 10px;
            margin-top: 5px;
            font-weight: bold;
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

        .file-error {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            border: 1px dashed #ccc;
            font-size: 10px;
        }

        .debug-info {
            background: #fffacd;
            padding: 5px;
            margin: 5px 0;
            font-size: 9px;
            border-left: 3px solid #ffd700;
        }

        .system-evidence {
            background-color: #f0f8ff;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>REPORTE DE SOLICITUD DE SERVICIO</h1>
        <div><strong>Ticket:</strong> {{ $serviceRequest->ticket_number }}</div>
        <div><strong>Generado el:</strong> {{ $generated_at }}</div>
    </div>

    <!-- Información Básica -->
    <div class="section">
        <div class="section-title">INFORMACIÓN BÁSICA</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Ticket:</span> {{ $serviceRequest->ticket_number }}
            </div>
            <div class="info-item">
                <span class="info-label">Estado:</span>
                <span class="status">{{ $serviceRequest->status }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Solicitante:</span> {{ $serviceRequest->requester->name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Técnico:</span> {{ $serviceRequest->assignedTechnician->name ?? 'No asignado' }}
            </div>
            <div class="info-item">
                <span class="info-label">Título:</span> {{ $serviceRequest->title ?? 'Sin título' }}
            </div>
            <div class="info-item">
                <span class="info-label">Sub-servicio:</span> {{ $serviceRequest->subService->name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Fecha Creación:</span> {{ $serviceRequest->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <!-- Tareas -->
    <div class="section">
        <div class="section-title">TAREAS ({{ $serviceRequest->tasks->count() }})</div>

        @if($serviceRequest->tasks && $serviceRequest->tasks->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Subtareas</th>
                        <th>Completadas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceRequest->tasks as $task)
                    <tr>
                        <td>{{ $task->task_code }}</td>
                        <td>{{ $task->title }}</td>
                        <td>{{ $task->status }}</td>
                        <td>{{ $task->priority }}</td>
                        <td>{{ $task->subtasks->count() }}</td>
                        <td>{{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }}</td>
                    </tr>
                    @if($task->subtasks->count() > 0)
                        @foreach($task->subtasks as $subtask)
                        <tr style="background-color: #f9f9f9;">
                            <td style="padding-left: 20px;">--</td>
                            <td colspan="2">{{ $subtask->title }}</td>
                            <td>{{ $subtask->priority ?? 'N/A' }}</td>
                            <td colspan="2">
                                {{ $subtask->is_completed ? '[X] Completada' : '[ ] Pendiente' }}
                                @if($subtask->completed_at)
                                    ({{ $subtask->completed_at->format('d/m/Y H:i') }})
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">No hay tareas registradas para esta solicitud</div>
        @endif
    </div>

    <!-- Evidencias -->
    <div class="section">
        <div class="section-title">EVIDENCIAS ({{ $serviceRequest->evidences->count() }})</div>

        @if($serviceRequest->evidences && $serviceRequest->evidences->count() > 0)
            <!-- Lista de todas las evidencias -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceRequest->evidences as $evidence)
                    <tr class="{{ $evidence->evidence_type === 'SISTEMA' ? 'system-evidence' : '' }}">
                        <td>{{ $evidence->evidence_type ?? 'N/A' }}</td>
                        <td>{{ $evidence->title ?? 'Sin título' }}</td>
                        <td>{{ $evidence->description ?? 'Sin descripción' }}</td>
                        <td>{{ $evidence->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Archivos adjuntos (imágenes y otros) -->
            @if($serviceRequest->evidences->where('file_path', '!=', null)->count() > 0)
            <div style="margin-top: 15px;">
                <div style="font-weight: bold; margin-bottom: 10px;">ARCHIVOS ADJUNTOS:</div>
                <div class="files-grid">
                    @foreach($serviceRequest->evidences->where('file_path', '!=', null) as $evidence)
                        <div class="file-container">
                            @if(isset($evidence->is_image) && $evidence->is_image && $evidence->base64_content)
                                <!-- Es una imagen -->
                                <img src="{{ $evidence->base64_content }}"
                                     alt="{{ $evidence->title ?? 'Archivo ' . $loop->iteration }}"
                                     class="evidence-image">
                                <div class="file-title" style="color: green;">
                                    📷 {{ $evidence->title ?? 'Imagen ' . $loop->iteration }}
                                </div>
                            @elseif(isset($evidence->file_found) && $evidence->file_found)
                                <!-- Es otro tipo de archivo -->
                                <div class="file-icon">📄</div>
                                <div class="file-title" style="color: blue;">
                                    📄 {{ $evidence->title ?? 'Archivo ' . $loop->iteration }}
                                </div>
                                <div style="font-size: 9px; color: #666;">
                                    {{ $evidence->file_path }}
                                </div>
                            @else
                                <!-- Archivo no encontrado -->
                                <div class="file-error">
                                    <strong>✗ ARCHIVO NO ENCONTRADO</strong><br>
                                    <small>{{ $evidence->file_path ?? 'No especificado' }}</small>
                                </div>
                                <div class="file-title" style="color: red;">
                                    ✗ {{ $evidence->title ?? 'Archivo ' . $loop->iteration }}
                                </div>
                            @endif

                            @if($evidence->description)
                            <div style="font-size: 9px; margin-top: 3px;">
                                {{ Str::limit($evidence->description, 40) }}
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @else
            <div class="no-data">No hay evidencias registradas para esta solicitud</div>
        @endif
    </div>

    <!-- Pie de página -->
    <div class="footer">
        Reporte generado automáticamente por el Sistema de Gestión de Servicios
    </div>
</body>
</html>
