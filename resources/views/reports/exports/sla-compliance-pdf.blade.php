<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Cumplimiento SLA</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Cumplimiento SLA</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(count($slaCompliance) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Familia de Servicio</th>
                    <th>Total Solicitudes</th>
                    <th>Cumplidas</th>
                    <th>Incumplidas</th>
                    <th>Tasa de Cumplimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slaCompliance as $compliance)
                <tr>
                    <td>{{ $compliance['family'] }}</td>
                    <td>{{ $compliance['total_requests'] }}</td>
                    <td class="positive">{{ $compliance['compliant'] }}</td>
                    <td class="negative">{{ $compliance['non_compliant'] }}</td>
                    <td><strong>{{ $compliance['compliance_rate'] }}%</strong></td>
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
