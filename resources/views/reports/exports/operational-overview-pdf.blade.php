<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Panorama Operativo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid #999; padding-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Panorama Operativo</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Tendencias:</strong> últimos {{ $months }} meses</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">Distribución por Estado</div>

    @if(count($statusData) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusData as $item)
                <tr>
                    <td>{{ $item->status }}</td>
                    <td class="right">{{ $item->count }}</td>
                    <td class="right">{{ $item->percentage }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos de distribución por estado disponibles para el período seleccionado.</p>
    @endif

    <div class="section-title">Distribución por Criticidad</div>

    @if(count($criticalityData) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Nivel de Criticidad</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Promedio Horas Resolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach($criticalityData as $item)
                <tr>
                    <td>{{ $item->criticality_level }}</td>
                    <td class="right">{{ $item->count }}</td>
                    <td class="right">{{ $item->avg_resolution_hours ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos de criticidad disponibles para el período seleccionado.</p>
    @endif

    <div class="section-title">Tendencias Mensuales</div>

    @if(count($trendsData) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="right">Total Solicitudes</th>
                    <th class="right">Resueltas</th>
                    <th class="right">Tasa de Completitud (%)</th>
                    <th class="right">Promedio Horas Resolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trendsData as $item)
                <tr>
                    <td>{{ $item['month_name'] }}</td>
                    <td class="right">{{ $item['total_requests'] }}</td>
                    <td class="right">{{ $item['resolved_requests'] }}</td>
                    <td class="right">{{ $item['completion_rate'] }}%</td>
                    <td class="right">{{ $item['avg_resolution_hours'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos de tendencias disponibles para el período seleccionado.</p>
    @endif

    <div class="footer">
        <p>Sistema SAP - Módulo de Servicios</p>
    </div>
</body>
</html>
