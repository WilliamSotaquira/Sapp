<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Resumen del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { margin-bottom: 30px; }
        .section h2 { font-size: 16px; margin-bottom: 15px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .warning { color: #ffc107; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; page-break-before: avoid; }
        .summary-stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 10px; border: 1px solid #ddd; background-color: #f8f9fa; }
        .no-data { text-align: center; color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Resumen del Sistema de Gestión</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Sección: Cumplimiento SLA -->
    <div class="section">
        <h2>1. Cumplimiento de Acuerdos de Nivel de Servicio (SLA)</h2>
        @if(count($slaCompliance) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Familia</th>
                        <th>Total</th>
                        <th>Cumplidas</th>
                        <th>Incumplidas</th>
                        <th>% Cumplimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($slaCompliance as $compliance)
                    <tr>
                        <td>{{ $compliance['service_name'] }}</td>
                        <td>{{ $compliance['family'] }}</td>
                        <td>{{ $compliance['total_requests'] }}</td>
                        <td class="positive">{{ $compliance['compliant'] }}</td>
                        <td class="negative">{{ $compliance['overdue'] }}</td>
                        <td>
                            <strong class="{{ $compliance['compliance_rate'] >= 90 ? 'positive' : ($compliance['compliance_rate'] >= 70 ? 'warning' : 'negative') }}">
                                {{ $compliance['compliance_rate'] }}%
                            </strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">No hay datos de SLA disponibles para el período seleccionado.</p>
        @endif
    </div>

    <!-- Sección: Solicitudes por Estado -->
    <div class="section">
        <h2>2. Distribución de Solicitudes por Estado</h2>
        @if(count($requestsByStatus) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Cantidad</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requestsByStatus as $status)
                    <tr>
                        <td>{{ $status->status }}</td>
                        <td>{{ $status->count }}</td>
                        <td>{{ $status->percentage }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">No hay datos de estados disponibles para el período seleccionado.</p>
        @endif
    </div>

    <!-- Sección: Niveles de Criticidad -->
    <div class="section">
        <h2>3. Análisis por Niveles de Criticidad</h2>
        @if(count($criticalityLevels) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Nivel de Criticidad</th>
                        <th>Cantidad</th>
                        <th>Horas Promedio de Resolución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criticalityLevels as $level => $data)
                    <tr>
                        <td>{{ $level }}</td>
                        <td>{{ $data->count }}</td>
                        <td>{{ number_format($data->avg_resolution_hours ?? 0, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">No hay datos de criticidad disponibles para el período seleccionado.</p>
        @endif
    </div>

    <!-- Sección: Rendimiento por Servicio -->
    <div class="section">
        <h2>4. Rendimiento por Servicio</h2>
        @if(count($servicePerformance) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Familia</th>
                        <th>Total Solicitudes</th>
                        <th>Solicitudes Resueltas</th>
                        <th>Tiempo Promedio (Horas)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicePerformance as $performance)
                    <tr>
                        <td>{{ $performance->service_name }}</td>
                        <td>{{ $performance->family_name }}</td>
                        <td>{{ $performance->total_requests }}</td>
                        <td>{{ $performance->resolved_count }}</td>
                        <td>{{ number_format($performance->avg_resolution_hours ?? 0, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">No hay datos de rendimiento disponibles para el período seleccionado.</p>
        @endif
    </div>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Gestión de Servicios</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
