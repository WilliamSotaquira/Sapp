<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Línea de Tiempo - Solicitud #{{ $request->ticket_number }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "IBM Plex Sans", Arial, sans-serif;
            margin: 20px;
            line-height: 1;
        }
        .header {
            border-bottom: 1px solid #d5d5d5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #252525;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .timeline-event {
            border-left: 3px solid #666;
            padding: 10px 0 10px 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        .event-time {
            font-weight: bold;
            color: #666;
            font-size: 0.7rem;
            margin-bottom: 5px;
        }
        .event-title {
            font-weight: bold;
            margin: 5px 0;
            font-size: 0.875rem;
            color: #353535;
        }
        .event-description {
            color: #454545;
            margin: 5px 0;
            font-size: 0.8rem;
            line-height: 1;

        }
        .event-type {
            color: #7f8c8d;
            font-style: italic;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        .event-duration {
            color: #454545;
            font-size: 0.7rem;
            margin-top: 3px;
        }
        .info-row {
            margin-bottom: 4px;
            font-size: 0.875rem;
            line-height: 1;
        }
        .info-row-card {
            margin-bottom: 6px;
            font-size: 0.875rem;
            line-height: 1;
        }
        .info-label {
            font-weight: bold;
            color: #252525;
            margin-right: 5px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Línea de Tiempo - Solicitud #{{ $request->ticket_number }}</h1>
        <div class="info-row">
            <span class="info-label">Título:</span> {{ $request->title }}
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span> {{ $request->status }}
        </div>
        <div class="info-row">
            <span class="info-label">Solicitante:</span> {{ $request->requester->name ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Asignado a:</span> {{ $request->assignee->name ?? 'No asignado' }}
        </div>
        <div class="info-row">
            <span class="info-label">Nivel de criticidad:</span> {{ $request->criticality_level }}
        </div>
        <div class="info-row">
            <span class="info-label">Fecha creación:</span> {{ $request->created_at->format('d/m/Y H:i') }}
        </div>
        @if($request->resolved_at)
        <div class="info-row">
            <span class="info-label">Fecha resolución:</span> {{ $request->resolved_at->format('d/m/Y H:i') }}
        </div>
        @endif
    </div>

    <!-- Resumen de tiempos -->
    @if(isset($totalResolutionTime) && $totalResolutionTime)
    <div style="background: #f5f5f5; padding: 16px; border-radius: 5px; margin-bottom: 0px;">
        <h3 style="margin-top: 0.4rem; margin-bottom: 0.4rem; color: #151515; font-size: 1.1rem;">Resumen de Tiempos</h3>
        <div class="info-row-card">
            <span class="info-label">Tiempo total de resolución:</span>
            {{ $totalResolutionTime->format('%d días %h horas %i minutos') }}
        </div>
        @if(isset($timeStatistics['efficiency']))
        <div class="info-row-card">
            <span class="info-label">Eficiencia:</span> {{ $timeStatistics['efficiency'] }}
        </div>
        @endif
    </div>
    @endif

    <!-- Línea de tiempo -->
    <h2 style="color: #252525; font-size: 1rem; border-bottom: 1px solid #bdc3c7; padding-bottom: 8px;">Eventos de la Solicitud</h2>

    <div class="timeline">
        @foreach($timelineEvents as $event)
        <div class="timeline-event">
            <div class="event-time">{{ isset($event['timestamp']) ? $event['timestamp']->format('d/m/Y H:i') : 'Fecha no disponible' }}</div>
            <div class="event-title">{{ $event['title'] ?? 'Evento sin título' }}</div>

            @if(isset($event['description']) && !empty($event['description']))
            <div class="event-description">{{ $event['description'] }}</div>
            @endif

            @if(isset($timeInStatus[$event['status'] ?? '']) && isset($timeInStatus[$event['status']]['formatted']))
            <div class="event-duration">
                <strong>Tiempo en estado:</strong> {{ $timeInStatus[$event['status']]['formatted'] }}
            </div>
            @endif

            @if(isset($event['evidence_type']) && !empty($event['evidence_type']))
            <div class="event-type">
                <strong>Tipo:</strong>
                @php
                    $evidenceType = $event['evidence_type'];
                    $typeLabel = 'Evidencia';
                    if ($evidenceType === 'image') {
                        $typeLabel = 'Imagen';
                    } elseif ($evidenceType === 'document') {
                        $typeLabel = 'Documento';
                    } elseif ($evidenceType === 'log') {
                        $typeLabel = 'Registro';
                    } elseif ($evidenceType === 'system') {
                        $typeLabel = 'Sistema';
                    }
                @endphp
                {{ $typeLabel }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #bdc3c7; text-align: center; color: #7f8c8d; font-size: 11px;">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} | Sistema de Gestión de Servicios</p>
    </div>
</body>
</html>
