<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tendencias Mensuales</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 6px 0; }
        .meta { color: #4b5563; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de Tendencias Mensuales</h1>
    <div class="meta">Rango analizado: últimos {{ $months }} meses</div>

    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th class="right">Total Solicitudes</th>
                <th class="right">Solicitudes Completadas</th>
                <th class="right">Tasa de Finalización (%)</th>
                <th class="right">Horas Promedio Resolución</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthlyTrends as $item)
                <tr>
                    <td>{{ $item['month_name'] }}</td>
                    <td class="right">{{ $item['total_requests'] }}</td>
                    <td class="right">{{ $item['resolved_requests'] }}</td>
                    <td class="right">{{ $item['completion_rate'] }}</td>
                    <td class="right">{{ $item['avg_resolution_hours'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay datos disponibles.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
