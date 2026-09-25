<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consolidado del Equipo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .intro { font-size: 11px; color: #444; margin-bottom: 15px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid #999; padding-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals td { padding: 6px 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Consolidado del Equipo</h1>
        <p><strong>Período:</strong> {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <p class="intro">
        Este informe separa lo <strong>resuelto por el equipo</strong> (ejecución de cada técnico) de lo
        <strong>verificado por el líder</strong> (control y seguimiento), reflejando la autoría dual del proceso.
    </p>

    <div class="section-title">Ejecución por técnico (resuelto por el equipo)</div>

    @if(count($executionByTechnician) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Técnico</th>
                    <th class="right">Asignadas</th>
                    <th class="right">Resueltas</th>
                    <th class="right">Cerradas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executionByTechnician as $row)
                <tr>
                    <td>{{ $row->technician_name }}</td>
                    <td class="right">{{ $row->total_assigned }}</td>
                    <td class="right">{{ $row->total_resolved }}</td>
                    <td class="right">{{ $row->total_closed }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Sin actividad de técnicos en el período seleccionado.</p>
    @endif

    <div class="section-title">Control del líder (verificación / seguimiento)</div>

    @if(count($controlByLeader) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Líder</th>
                    <th class="right">Controles completados</th>
                    <th class="right">Solicitudes verificadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($controlByLeader as $row)
                <tr>
                    <td>{{ $row->leader_name }}</td>
                    <td class="right">{{ $row->control_tasks_completed }}</td>
                    <td class="right">{{ $row->requests_verified }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Sin controles registrados en el período seleccionado.</p>
    @endif

    <div class="section-title">Totales</div>
    <table class="table totals">
        <tr><td>Técnicos con actividad</td><td class="right">{{ $totals['technicians'] }}</td></tr>
        <tr><td>Solicitudes asignadas</td><td class="right">{{ $totals['total_assigned'] }}</td></tr>
        <tr><td>Solicitudes resueltas por el equipo</td><td class="right">{{ $totals['total_resolved'] }}</td></tr>
        <tr><td>Controles completados por el líder</td><td class="right">{{ $totals['control_tasks_completed'] }}</td></tr>
        <tr><td>Solicitudes verificadas por el líder</td><td class="right">{{ $totals['requests_verified'] }}</td></tr>
    </table>

    <div class="footer">
        Documento generado automáticamente por el sistema de gestión de servicios.
    </div>
</body>
</html>
