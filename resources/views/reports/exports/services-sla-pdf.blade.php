<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Servicios y SLA</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid #999; padding-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Servicios y SLA</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">Cumplimiento SLA</div>

    @if(count($slaData) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Familia</th>
                    <th class="right">Total Solicitudes</th>
                    <th class="right">Cumplidas</th>
                    <th class="right">Vencidas</th>
                    <th class="right">Tasa de Cumplimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slaData as $item)
                <tr>
                    <td>{{ $item['service_name'] }}</td>
                    <td>{{ $item['family'] }}</td>
                    <td class="right">{{ $item['total_requests'] }}</td>
                    <td class="right positive">{{ $item['compliant'] }}</td>
                    <td class="right negative">{{ $item['overdue'] }}</td>
                    <td class="right"><strong>{{ $item['compliance_rate'] }}%</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos de cumplimiento SLA disponibles para el período seleccionado.</p>
    @endif

    <div class="section-title">Rendimiento de Servicios</div>

    @if(count($performanceData) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Familia</th>
                    <th class="right">Total Solicitudes</th>
                    <th class="right">Promedio Horas Resolución</th>
                    <th class="right">Resueltas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performanceData as $item)
                <tr>
                    <td>{{ $item->service_name }}</td>
                    <td>{{ $item->family_name }}</td>
                    <td class="right">{{ $item->total_requests }}</td>
                    <td class="right">{{ round($item->avg_resolution_hours ?? 0, 1) }}</td>
                    <td class="right">{{ $item->resolved_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos de rendimiento disponibles para el período seleccionado.</p>
    @endif

    <div class="footer">
        <p>Sistema SAP - Módulo de Servicios</p>
    </div>
</body>
</html>
