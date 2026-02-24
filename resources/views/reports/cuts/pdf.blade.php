<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte Corte {{ $cut->id }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        .page { padding: 14px 16px; }
        .main-title { font-size: 26px; font-weight: 700; color: #111827; letter-spacing: 0.1px; margin: 0 0 4px 0; }
        .header-row { font-size: 12px; margin: 4px 0 0 0; line-height: 1.35; }
        .subtitle { color: #4b5563; }
        .subtitle-meta { color: #6b7280; }
        .summary { color: #374151; }
        .summary strong { color: #111827; }
        .line { display: none; }
        .family { margin-top: 10px; overflow: hidden; }
        .family-head { background: #4b3f99; color: #fff; padding: 10px 12px; }
        .family-head h2 { font-size: 18px; margin: 0 0 4px 0; font-weight: 700; letter-spacing: 0.1px; }
        .family-head p { margin: 0; font-size: 11px; line-height: 1.45; color: #e9e7ff; }
        .family-actions { margin-top: 4px; font-size: 11px; color: #f0efff; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        th, td { border: 0; padding: 8px; vertical-align: top; }
        thead tr > th,
        tbody tr > td { border-bottom: 1px solid #d1d5db; }
        thead tr:first-child > th,
        tbody tr:first-child > td { border-top: 1px solid #d1d5db; }
        thead tr > th:first-child,
        tbody tr > td:first-child { border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db; }
        thead tr > th:last-child,
        tbody tr > td:last-child { border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db; }
        th { background: #f3f4f6; text-align: center; font-size: 12px; font-weight: 700; color: #111827; letter-spacing: 0.1px; }
        td { font-size: 11px; line-height: 1.45; color: #374151; }
        .col-detail { width: 100%; }
        .ticket { color: #6b7280; font-size: 10px; margin: 3px 0 0 0; }
        .title { color: #111827; font-size: 14px; font-weight: 700; margin: 0 0 3px 0; line-height: 1.35; }
        .section-title { margin: 10px 0 4px 0; font-size: 12px; font-weight: 700; color: #111827; }
        .list { margin: 4px 0 0 0; padding: 0; list-style: none; }
        .list li { margin: 0 0 2px 0; }
        .products-row-title { font-weight: 700; color: #111827; margin: 0 0 4px 0; }
        .group-start-spaced td { padding-top: 12px !important; border-top: 1px solid #d1d5db !important; }
        .products-list { margin: 4px 0 0 0; padding: 0; list-style: none; }
        .product-entry { margin: 0; padding: 6px 0; border-bottom: 1px solid #e5e7eb; }
        .product-entry:last-child { border-bottom: 0; }
        .product-name { display: block; word-break: break-word; font-size: 11px; color: #374151; }
        .product-preview { margin-top: 6px; text-align: center; }
        .product-image { display: inline-block; width: 390px; max-width: 390px; height: auto; max-height: 270px; border-radius: 2px; }
        .group-spacer td { border: 0 !important; padding: 0 !important; height: 12px; line-height: 0; }
        .muted { color: #6b7280; }
        .empty { color: #9ca3af; }
    </style>
</head>
<body>
@php
    \Carbon\Carbon::setLocale('es');
    $contractNumber = $cut->contract?->number ?? 'Contrato';
    $monthLabel = $cut->start_date->translatedFormat('F Y');
    $titleMonth = mb_convert_case($monthLabel, MB_CASE_TITLE, 'UTF-8');
    $rangeStart = $cut->start_date->format('Y-m-d');
    $rangeEnd = $cut->end_date->format('Y-m-d');
    $generatedAtLabel = $generatedAt->format('Y-m-d H:i');
    $coordinator = $generatedBy ?? 'Sistema';
    $cleanPdfText = function ($value) {
        $text = (string) ($value ?? '');
        // Remove replacement char and common unsupported symbols (e.g., emojis) for DomPDF.
        $text = str_replace("\u{FFFD}", '', $text);
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);
        $text = preg_replace('/\s{2,}/u', ' ', $text);
        return trim((string) $text);
    };
@endphp

<div class="page">
    <p class="main-title">{{ $contractNumber }}: {{ $titleMonth }}</p>
    <p class="subtitle header-row">{{ $coordinator }}</p>
    @if(!empty($generatedByEmail) || !empty($generatedByDependency))
        <p class="subtitle-meta header-row">
            @if(!empty($generatedByEmail))
                Correo: {{ $generatedByEmail }}
            @endif
            @if(!empty($generatedByEmail) && !empty($generatedByDependency))
                |
            @endif
            @if(!empty($generatedByDependency))
                Cargo: {{ $generatedByDependency }}
            @endif
        </p>
    @endif
    <p class="summary header-row">
        <strong>Rango:</strong> {{ $rangeStart }} - {{ $rangeEnd }}
        | <strong>Generado:</strong> {{ $generatedAtLabel }}
    </p>
    <p class="summary header-row"><strong>Total acciones:</strong> {{ $serviceRequests->count() }}</p>
    <div class="line"></div>

    @forelse($groupedData as $family => $requests)
        @php
            $familyModel = $requests->first()?->subService?->service?->family;
            $familyDescription = $familyModel?->description ?: 'Sin descripción registrada para esta familia.';
        @endphp
        <div class="family">
            <div class="family-head">
                <h2>{{ $cleanPdfText($family) }}</h2>
                <p>{{ $cleanPdfText($familyDescription) }}</p>
                <p class="family-actions">Total acciones: {{ $requests->count() }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="col-detail">Detalle de la solicitud</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $sr)
                        @php
                            $tasks = $sr->tasks ?? collect();
                            $evidencesPerRequest = ($evidences ?? collect())->where('service_request_id', $sr->id)->values();
                            $presentedProducts = $evidencesPerRequest
                                ->map(function ($evidence) {
                                    $fileName = trim((string) ($evidence->file_original_name ?? ''));
                                    $filePath = trim((string) ($evidence->file_path ?? ''));

                                    $name = $fileName !== '' ? $fileName : ($filePath !== '' ? basename($filePath) : null);
                                    if (!$name) {
                                        return null;
                                    }

                                    $isImage = (bool) ($evidence->is_image ?? false);
                                    $imageSrc = null;
                                    if ($isImage && $filePath !== '' && !preg_match('#^https?://#i', $filePath)) {
                                        $normalized = ltrim($filePath, '/');
                                        if (str_starts_with($normalized, 'public/')) {
                                            $normalized = substr($normalized, 7);
                                        }
                                        if (str_starts_with($normalized, 'storage/')) {
                                            $normalized = substr($normalized, 8);
                                        }

                                        $candidates = array_filter(array_unique([
                                            $filePath,
                                            $normalized,
                                            $normalized ? ('evidences/' . basename($normalized)) : null,
                                            basename($filePath) ? ('evidences/' . basename($filePath)) : null,
                                        ]));

                                        foreach ($candidates as $candidate) {
                                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($candidate)) {
                                                try {
                                                    $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($candidate);
                                                    $mime = $evidence->file_mime_type ?: \Illuminate\Support\Facades\Storage::disk('public')->mimeType($candidate);
                                                    if ($binary !== null && $mime) {
                                                        $imageSrc = 'data:' . $mime . ';base64,' . base64_encode($binary);
                                                    }
                                                } catch (\Throwable $e) {
                                                    $imageSrc = null;
                                                }
                                                break;
                                            }
                                        }
                                    }

                                    return [
                                        'name' => $name,
                                        'is_image' => $isImage,
                                        'image_src' => $imageSrc,
                                    ];
                                })
                                ->filter()
                                ->values();
                            $cleanResolutionNotes = trim((string) ($sr->resolution_notes ?? ''));
                            if ($cleanResolutionNotes !== '') {
                                $cleanResolutionNotes = preg_replace('/\s*===\s*CIERRE(?:\s+POR\s+VENCIMIENTO|\s+NORMAL)\s*===.*$/is', '', $cleanResolutionNotes);
                                $cleanResolutionNotes = preg_replace('/^\s*Fecha\/Hora:.*$/im', '', $cleanResolutionNotes);
                                $cleanResolutionNotes = preg_replace('/^\s*Usuario:\s*ID\s*\d+.*$/im', '', $cleanResolutionNotes);
                                $cleanResolutionNotes = preg_replace('/\n{3,}/', "\n\n", trim((string) $cleanResolutionNotes));
                            }
                        @endphp
                        <tr class="{{ !$loop->first ? 'group-start-spaced' : '' }}">
                            <td class="col-detail">
                                <p class="title">{{ $cleanPdfText($sr->title) }}</p>
                                <p class="ticket">Ticket: {{ $sr->ticket_number }}</p>
                                @if(!empty($sr->description))
                                    <p>{{ $cleanPdfText($sr->description) }}</p>
                                @else
                                    <p class="empty">Sin descripción registrada.</p>
                                @endif

                                @if(!empty($cleanResolutionNotes))
                                    <p class="section-title">Actividades ejecutadas</p>
                                    <p>{!! nl2br(e($cleanPdfText($cleanResolutionNotes))) !!}</p>
                                @endif

                                @if($presentedProducts->count() > 0)
                                    <p class="section-title">Productos presentados</p>
                                    <ul class="products-list">
                                        @foreach($presentedProducts as $product)
                                            <li class="product-entry">
                                                <span class="product-name">- {{ $cleanPdfText($product['name']) }}</span>
                                                @if(!empty($product['is_image']) && !empty($product['image_src']))
                                                    <div class="product-preview">
                                                        <img src="{{ $product['image_src'] }}" alt="{{ $cleanPdfText($product['name']) }}" class="product-image">
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @if(!$loop->last)
                            <tr class="group-spacer">
                                <td></td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="muted">No hay solicitudes para el filtro seleccionado.</p>
    @endforelse
</div>
</body>
</html>
