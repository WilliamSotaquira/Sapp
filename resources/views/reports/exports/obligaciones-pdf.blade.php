<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Obligaciones</title>
    <style>
        @php
            $pdfPrimaryColor = (isset($primaryColor) && preg_match('/^#([A-Fa-f0-9]{6})$/', $primaryColor))
                ? strtoupper($primaryColor)
                : '#1E3A8A';
            $pdfContrastColor = (isset($contrastColor) && preg_match('/^#([A-Fa-f0-9]{6})$/', $contrastColor))
                ? strtoupper($contrastColor)
                : '#FFFFFF';
        @endphp
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #111; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid {{ $pdfPrimaryColor }}; padding-bottom: 8px; }
        .meta { font-size: 10px; color: #555; margin-top: 0; line-height: 1.15; }
        .table { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .table .section-head-cell {
            background: {{ $pdfPrimaryColor }};
            color: {{ $pdfContrastColor }};
            text-align: left;
            font-weight: bold;
            border-color: {{ $pdfPrimaryColor }};
            padding: 6px 10px;
        }
        .table thead { display: table-header-group; }
        .table tr { page-break-inside: avoid; }
        .muted { color: #666; }
        .link-wrap { word-break: break-all; overflow-wrap: anywhere; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 10px; background: #eef2ff; color: #1e3a8a; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $totalSolicitudes = $serviceRequests->sum(fn($group) => $group->count());
        $rangeStart = $dateRange['start'] ? $dateRange['start']->format('Y-m-d') : '';
        $rangeEnd = $dateRange['end'] ? $dateRange['end']->format('Y-m-d') : '';
        $contractNumber = $cut?->contract?->number;

        if (empty($contractNumber)) {
            $contractNumbers = $serviceRequests
                ->flatten(1)
                ->pluck('subService.service.family.contract.number')
                ->filter()
                ->unique()
                ->values();

            if ($contractNumbers->count() === 1) {
                $contractNumber = $contractNumbers->first();
            } elseif ($contractNumbers->count() > 1) {
                $contractNumber = 'Varios contratos';
            } else {
                $contractNumber = 'Sin contrato';
            }
        }

        $periodLabel = $cut?->name;
        if (empty($periodLabel) && !empty($dateRange['start'])) {
            $periodLabel = ucfirst($dateRange['start']->locale('es')->translatedFormat('F Y'));
        }
        if (empty($periodLabel)) {
            $periodLabel = 'Periodo';
        }

        $headerLabel = $contractNumber . ': ' . $periodLabel;
    @endphp

    <div class="header" style="text-align: left;">
        <h1 style="margin: 0 0 1px 0;">{{ $headerLabel }}</h1>
        <div class="meta" style="margin-top: 0;">
            @if(!empty($generatedByName))
                <div style="margin-top: 1px;"><strong>{{ $generatedByName }}</strong></div>
            @endif
            <div style="margin-top: 1px;"><strong>Rango:</strong> {{ $rangeStart }} - {{ $rangeEnd }} | <span>Generado: {{ now()->format('Y-m-d H:i') }}</span></div>
            <div style="margin-top: 1px;">
                <strong>Total acciones:</strong>
                <span style="background: {{ $pdfPrimaryColor }}22; color: {{ $pdfPrimaryColor }}; padding: 2px 6px; border-radius: 6px; font-weight: bold;">
                    {{ $totalSolicitudes }}
                </span>
            </div>
            <!-- Criterio y total removidos por solicitud -->
        </div>
    </div>

    @if($serviceRequests->count() > 0)
            @foreach($serviceRequests as $serviceName => $obligaciones)
            @php
                $familyDescription = $obligaciones->first()?->subService?->service?->family?->description;
            @endphp
            @php
                $familyTotal = $obligaciones->count();
            @endphp
            <table class="table">
                <thead>
                    <tr>
                        <th colspan="3" class="section-head-cell">
                            <div style="font-weight: bold;">{{ $serviceName }}</div>
                            @if($familyDescription)
                                <div style="font-weight: normal; color: {{ $pdfContrastColor }}; opacity: 0.9; font-size: 10px; margin-top: 2px;">
                                    {{ $familyDescription }}
                                </div>
                            @endif
                            <div style="font-weight: normal; color: {{ $pdfContrastColor }}; opacity: 0.9; font-size: 10px; margin-top: 2px;">
                                Total acciones: {{ $familyTotal }}
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th style="width: 33.33%;">Obligaciones</th>
                        <th style="width: 33.33%;">Actividades Ejecutadas</th>
                        <th style="width: 33.33%;">Productos Presentados</th>
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
                                @php
                                    $fileEvidences = $sr->evidences->where('file_path');
                                    $linkEvidences = $sr->evidences->where('evidence_type', 'ENLACE');
                                    $hasProducts = $fileEvidences->count() > 0 || $linkEvidences->count() > 0;
                                @endphp
                                @if($hasProducts)
                                    @foreach($fileEvidences as $evidence)
                                        @php
                                            $evidenceLabel = $evidence->file_original_name
                                                ?? $evidence->file_name
                                                ?? $evidence->title
                                                ?? 'Evidencia';
                                        @endphp
                                        @if(!empty($evidence->file_url))
                                            <div>
                                                <a href="{{ $evidence->file_url }}" class="link-wrap" style="color:#2563eb; text-decoration: underline;">
                                                    {{ $evidenceLabel }}
                                                </a>
                                            </div>
                                        @else
                                            <div class="muted">{{ $evidenceLabel }}</div>
                                        @endif
                                    @endforeach
                                    @foreach($linkEvidences as $evidence)
                                        @php
                                            $linkUrl = $evidence->evidence_data['url'] ?? $evidence->description;
                                        @endphp
                                        @if($linkUrl)
                                            <div>
                                                <a href="{{ $linkUrl }}" class="link-wrap" style="color:#2563eb; text-decoration: underline;">
                                                    {{ $linkUrl }}
                                                </a>
                                            </div>
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
        <p>Reporte de Obligaciones</p>
    </div>
</body>
</html>
