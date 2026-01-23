<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Obligaciones</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #111; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .meta { font-size: 10px; color: #555; margin-top: 6px; }
        .section-title { margin-top: 18px; background: #1e3a8a; color: #fff; padding: 6px 10px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .muted { color: #666; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 10px; background: #eef2ff; color: #1e3a8a; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $totalSolicitudes = $serviceRequests->sum(fn($group) => $group->count());
        $rangeStart = $dateRange['start'] ? $dateRange['start']->format('Y-m-d') : '';
        $rangeEnd = $dateRange['end'] ? $dateRange['end']->format('Y-m-d') : '';
        $cutName = $cut?->name ?? 'Sin corte';
    @endphp

    <div class="header" style="text-align: left;">
        <h1 style="margin: 0 0 4px 0;">Corte: {{ $cutName }}</h1>
        <div class="meta" style="margin-top: 4px;">
            <div><strong>Rango:</strong> {{ $rangeStart }} - {{ $rangeEnd }} | <span>Generado: {{ now()->format('Y-m-d H:i') }}</span></div>
            <!-- Criterio y total removidos por solicitud -->
        </div>
    </div>

    @if($serviceRequests->count() > 0)
            @foreach($serviceRequests as $serviceName => $obligaciones)
            @php
                $familyDescription = $obligaciones->first()?->subService?->service?->family?->description;
            @endphp
            <div class="section-title">
                <div style="font-weight: bold;">{{ $serviceName }}</div>
                @if($familyDescription)
                    <div style="font-weight: normal; color: #e0e7ff; font-size: 10px; margin-top: 2px;">
                        {{ $familyDescription }}
                    </div>
                @endif
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Obligaciones</th>
                        <th style="width: 35%;">Actividades Ejecutadas</th>
                        <th style="width: 30%;">Productos Presentados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($obligaciones as $sr)
                        <tr>
                            <td>
                                <div><strong>{{ $sr->title }}</strong></div>
                                @if($sr->ticket_number)
                                    <div class="muted">Ticket: {{ $sr->ticket_number }}</div>
                                @endif
                                @if($sr->description)
                                    <div class="muted">{{ $sr->description }}</div>
                                @endif
                            </td>
                            <td>
                                @if($sr->tasks->count() > 0)
                                    @foreach($sr->tasks as $task)
                                        <div><strong>{{ $task->title }}</strong></div>
                                        @if($task->subtasks->count() > 0)
                                            <div class="muted">
                                                @foreach($task->subtasks as $subtask)
                                                    - {{ $subtask->title }}
                                                    @if($subtask->evidence_completed)
                                                        ✓
                                                    @endif
                                                    <br>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($sr->evidences->where('file_path')->count() > 0)
                                    @foreach($sr->evidences->where('file_path') as $evidence)
                                        @php
                                            $evidenceLabel = $evidence->file_original_name
                                                ?? $evidence->file_name
                                                ?? $evidence->title
                                                ?? 'Evidencia';
                                        @endphp
                                        @if(!empty($evidence->file_url))
                                            <div>
                                                <a href="{{ $evidence->file_url }}" style="color:#2563eb; text-decoration: underline;">
                                                    {{ $evidenceLabel }}
                                                </a>
                                            </div>
                                        @else
                                            <div class="muted">{{ $evidenceLabel }}</div>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <p class="muted">No se encontraron obligaciones con los filtros especificados.</p>
    @endif

    <div class="footer">
        <p>Sistema SDM - Reporte de Obligaciones</p>
    </div>
</body>
</html>
