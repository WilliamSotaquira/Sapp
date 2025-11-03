<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Tendencias Mensuales</title>
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
        <h1>Reporte de Tendencias Mensuales</h1>
        <p><strong>Período:</strong> Últimos 6 meses</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(count($trends) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Total Solicitudes</th>
                    <th>Cerradas</th>
                    <th>Tasa Finalización</th>
                    <th>Satisfacción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trends as $item)
                <tr>
                    <td>{{ $item['month_name'] }}</td>
                    <td>{{ $item['total_requests'] }}</td>
                    <td>{{ $item['closed_requests'] }}</td>
                    <td>{{ $item['completion_rate'] }}%</td>
                    <td>{{ $item['avg_satisfaction'] }}/5</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay datos disponibles para mostrar tendencias.</p>
    @endif

    <div class="footer">
        <p>Sistema SAP - Módulo de Servicios</p>
    </div>
</body>
</html>
