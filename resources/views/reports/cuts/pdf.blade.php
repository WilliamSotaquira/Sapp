<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Corte {{ $cut->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0; }
        .muted { color: #6b7280; }
        .header { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-weight: 700; }
        .section { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Corte: {{ $cut->name }}</h1>
        <div class="muted">Rango: {{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }} | Generado: {{ $generatedAt->format('Y-m-d H:i') }}</div>
        <div class="muted">Criterio: actividad en el rango (created_at/updated_at de solicitud/tareas y created_at de evidencias/historiales).</div>
        <div class="section">Total solicitudes: <span class="badge">{{ $serviceRequests->count() }}</span></div>
        @if($cut->notes)
            <div class="section"><strong>Notas:</strong> {{ $cut->notes }}</div>
        @endif
    </div>

    @foreach($groupedData as $family => $requests)
        <div class="section">
            <h2 style="font-size: 12px; margin: 0 0 6px 0;">{{ $family }} ({{ $requests->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">Ticket</th>
                        <th style="width: 28%">Título</th>
                        <th style="width: 10%">Estado</th>
                        <th style="width: 12%">Criticidad</th>
                        <th style="width: 20%">Solicitante</th>
                        <th style="width: 20%">Fechas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $sr)
                        <tr>
                            <td>{{ $sr->ticket_number }}</td>
                            <td>{{ $sr->title }}</td>
                            <td>{{ $sr->status }}</td>
                            <td>{{ $sr->criticality_level }}</td>
                            <td>{{ $sr->requester?->email ?? '-' }}</td>
                            <td>
                                Creada: {{ $sr->created_at?->format('Y-m-d H:i') }}<br>
                                Actualizada: {{ $sr->updated_at?->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
