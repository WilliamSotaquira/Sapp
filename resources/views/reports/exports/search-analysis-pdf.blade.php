<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Búsqueda y Análisis</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid #999; padding-bottom: 5px; }
        .summary-box { background-color: #f9f9f9; border: 1px solid #ddd; padding: 12px; margin-bottom: 20px; }
        .summary-box p { margin: 4px 0; }
        .summary-inline { display: inline; margin-right: 15px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Búsqueda y Análisis</h1>
        <p><strong>Términos de búsqueda:</strong> {{ implode(', ', $searchTerms) }}</p>
        <p><strong>Generado:</strong> {{ $generatedAt }}</p>
    </div>

    <div class="section-title">Resumen</div>

    <div class="summary-box">
        <p><strong>Total de coincidencias:</strong> {{ $summary['total_matches'] }}</p>

        @if(!empty($summary['by_status']))
            <p><strong>Por estado:</strong>
                @foreach($summary['by_status'] as $status => $count)
                    {{ $status }} ({{ $count }})@if(!$loop->last), @endif
                @endforeach
            </p>
        @endif

        @if(!empty($summary['by_family']))
            <p><strong>Por familia:</strong>
                @foreach($summary['by_family'] as $family => $count)
                    {{ $family }} ({{ $count }})@if(!$loop->last), @endif
                @endforeach
            </p>
        @endif

        @if(!empty($summary['by_criticality']))
            <p><strong>Por criticidad:</strong>
                @foreach($summary['by_criticality'] as $level => $count)
                    {{ $level }} ({{ $count }})@if(!$loop->last), @endif
                @endforeach
            </p>
        @endif
    </div>

    <div class="section-title">Resultados</div>

    @if(count($results) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Servicio</th>
                    <th>Criticidad</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Resolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $item)
                <tr>
                    <td>{{ $item->ticket_number ?? 'N/A' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item->title ?? '', 50) }}</td>
                    <td>{{ $item->status ?? '' }}</td>
                    <td>{{ $item->subService?->service?->name ?? 'N/A' }}</td>
                    <td>{{ $item->criticality_level ?? '' }}</td>
                    <td>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '' }}</td>
                    <td>{{ $item->resolved_at ? \Carbon\Carbon::parse($item->resolved_at)->format('d/m/Y') : '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No se encontraron resultados para los términos de búsqueda proporcionados.</p>
    @endif

    <div class="footer">
        <p>Sistema SAP - Módulo de Servicios</p>
    </div>
</body>
</html>
