<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe Analitico Corte {{ $cut->id }}</title>
    <style>
        @page {
            margin: 18px 22px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .subtitle {
            font-size: 11px;
            color: #475569;
            margin: 0 0 2px 0;
        }

        .meta {
            font-size: 9px;
            color: #64748b;
            margin: 0 0 12px 0;
        }

        .section {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #0f766e;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 8px;
            margin: 0 0 8px 0;
            border-radius: 4px;
        }

        .summary-table,
        .data-table,
        .detail-table,
        .pair-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .summary-note {
            font-size: 9px;
            color: #475569;
            margin-top: 4px;
        }

        .pair-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .list-box {
            border: 1px solid #dbe2ea;
            padding: 8px 10px;
            min-height: 92px;
        }

        ul {
            margin: 0;
            padding-left: 16px;
        }

        li {
            margin-bottom: 5px;
            line-height: 1.35;
        }

        .data-table th,
        .data-table td,
        .detail-table th,
        .detail-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
            word-break: break-word;
        }

        .data-table th,
        .detail-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .data-table td,
        .detail-table td {
            font-size: 9px;
            line-height: 1.3;
        }

        .muted {
            color: #64748b;
        }

        .compact {
            margin-top: 6px;
        }

        .page-break {
            page-break-before: always;
        }

        .w-10 { width: 10%; }
        .w-12 { width: 12%; }
        .w-14 { width: 14%; }
        .w-16 { width: 16%; }
        .w-18 { width: 18%; }
        .w-20 { width: 20%; }
        .w-22 { width: 22%; }
        .w-24 { width: 24%; }
    </style>
</head>
<body>
    <div>
        <p class="title">Informe analitico de gestion y registro</p>
        <p class="subtitle">{{ $cut->name }} | {{ $cut->start_date->format('Y-m-d') }} a {{ $cut->end_date->format('Y-m-d') }}</p>
        @if($cut->contract)
            <p class="subtitle">Contrato: {{ $cut->contract->number }}</p>
        @endif
        <p class="meta">Generado el {{ now()->format('Y-m-d H:i:s') }}</p>

        <div class="section">
            <p class="section-title">Resumen general</p>
            <table class="summary-table">
                <tr>
                    <td>
                        <div class="summary-label">Total solicitudes</div>
                        <div class="summary-value">{{ $analytics['summary']['total'] }}</div>
                        <div class="summary-note">Registros asociados al corte</div>
                    </td>
                    <td>
                        <div class="summary-label">Cerradas / resueltas</div>
                        <div class="summary-value">{{ $analytics['summary']['completed'] }}</div>
                        <div class="summary-note">Solicitudes finalizadas</div>
                    </td>
                    <td>
                        <div class="summary-label">Activas</div>
                        <div class="summary-value">{{ $analytics['summary']['active'] }}</div>
                        <div class="summary-note">Pendientes, aceptadas, en proceso o pausadas</div>
                    </td>
                    <td>
                        <div class="summary-label">Cumplimiento</div>
                        <div class="summary-value">{{ $analytics['summary']['completion_rate'] }}%</div>
                        <div class="summary-note">Tasa de cierre del corte</div>
                    </td>
                </tr>
            </table>
            <table class="data-table compact">
                <thead>
                    <tr>
                        <th>Areas</th>
                        <th>Canales</th>
                        <th>Rutas</th>
                        <th>Canceladas / rechazadas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $analytics['summary']['distinct_areas'] }}</td>
                        <td>{{ $analytics['summary']['distinct_channels'] }}</td>
                        <td>{{ $analytics['summary']['distinct_routes'] }}</td>
                        <td>{{ $analytics['summary']['cancelled'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table class="pair-table">
                <tr>
                    <td>
                        <p class="section-title">Hallazgos</p>
                        <div class="list-box">
                            <ul>
                                @foreach($analytics['findings'] as $finding)
                                    <li>{{ $finding }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                    <td>
                        <p class="section-title">Recomendaciones</p>
                        <div class="list-box">
                            <ul>
                                @foreach($analytics['recommendations'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        @foreach([
            'status' => 'Distribucion por estado',
            'channels' => 'Distribucion por canal de entrada',
            'areas' => 'Distribucion por area solicitante',
            'families' => 'Distribucion por familia',
            'services' => 'Distribucion por servicio',
            'subservices' => 'Distribucion por subservicio',
        ] as $key => $title)
            <div class="section">
                <p class="section-title">{{ $title }}</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-24">Categoria</th>
                            <th class="w-10">Cantidad</th>
                            <th class="w-10">Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analytics['distributions'][$key] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>{{ $row['percentage'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">Sin datos para este corte.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="section page-break">
            <p class="section-title">Detalle operacional del corte</p>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th class="w-12">Ticket</th>
                        <th class="w-20">Titulo</th>
                        <th class="w-14">Area</th>
                        <th class="w-12">Canal</th>
                        <th class="w-16">Servicio</th>
                        <th class="w-16">Subservicio</th>
                        <th class="w-10">Estado</th>
                        <th class="w-18">Ruta</th>
                        <th class="w-12">Creada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analytics['detail_rows']->take(40) as $row)
                        <tr>
                            <td>{{ $row['ticket'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['area'] }}</td>
                            <td>{{ $row['channel'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td>{{ $row['subservice'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['route'] }}</td>
                            <td>{{ $row['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="muted">No hay datos para este corte.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
