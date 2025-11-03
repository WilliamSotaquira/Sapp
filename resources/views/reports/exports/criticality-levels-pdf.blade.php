<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte por Niveles de Criticidad</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte por Niveles de Criticidad</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if($criticalityData->count() > 0)
        @php
            $totalRequests = $criticalityData->sum('count');
        @endphp
        <table class="table">
            <thead>
                <tr>
                    <th>Nivel de Criticidad</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($criticalityData as $item)
                <tr>
                    <td>{{ $item->criticality_level }}</td>
                    <td>{{ $item->count }}</td>
                    <td>{{ $totalRequests > 0 ? round(($item->count / $totalRequests) * 100, 2) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos disponibles para el período seleccionado.</p>
    @endif

    <div class="footer">
        <p>Sistema SAP - Módulo de Servicios</p>
    </div>
</body>
</html>
