<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Exports\RequestTimelineExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class UnifiedTimelineController extends Controller
{
    /**
     * Display paginated list of service requests with date range filter and ticket search.
     * Default: current month when no date range is specified.
     *
     * Requirements: 2.1, 2.2, 2.3
     */
    public function index(Request $request): View
    {
        $dateRange = $this->getDateRange($request);

        $query = ServiceRequest::with(['subService.service.family', 'requester', 'assignee', 'sla'])
            ->reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('created_at', 'desc');

        // Apply ticket search filter if provided (partial match)
        if ($request->filled('ticket')) {
            $ticket = trim($request->input('ticket'));
            $query->where('ticket_number', 'LIKE', "%{$ticket}%");
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('reports.timeline.index', compact('requests', 'dateRange'));
    }

    /**
     * Display full timeline detail for a service request.
     *
     * Requirements: 2.4
     */
    public function show(int $id): View
    {
        $serviceRequest = ServiceRequest::with([
            'subService.service.family',
            'requester',
            'requestedBy',
            'assignee',
            'sla',
            'evidences.uploadedBy',
            'breachLogs'
        ])
        ->reportable()
        ->findOrFail($id);

        $timelineEvents = $serviceRequest->getTimelineEvents();
        $timeInStatus = $serviceRequest->getTimeInEachStatus();
        $totalResolutionTime = $serviceRequest->getTotalResolutionTime();
        $timeStatistics = $serviceRequest->getTimeStatistics();
        $timeSummary = $serviceRequest->getTimeSummaryByEventType();

        return view('reports.timeline.show', compact(
            'serviceRequest',
            'timelineEvents',
            'timeInStatus',
            'totalResolutionTime',
            'timeStatistics',
            'timeSummary'
        ));
    }

    /**
     * Search by ticket number (partial or full match).
     * If single result found, redirect to detail. Otherwise show filtered list.
     *
     * Requirements: 2.2, 2.7
     */
    public function searchByTicket(Request $request): RedirectResponse
    {
        $ticket = trim($request->input('ticket', ''));

        if (empty($ticket)) {
            return redirect()->route('reports.timeline.index');
        }

        // ServiceRequest ya scopea por contrato activo (global scope 'workspace').
        $matches = ServiceRequest::query()
            ->reportable()
            ->where('ticket_number', 'LIKE', "%{$ticket}%")
            ->get();

        if ($matches->isEmpty()) {
            return redirect()->route('reports.timeline.index')
                ->with('warning', "No se encontró ninguna solicitud con el número de ticket: {$ticket}. Verifique el número de ticket e intente nuevamente.")
                ->withInput();
        }

        if ($matches->count() === 1) {
            return redirect()->route('reports.timeline.show', $matches->first()->id);
        }

        // Multiple results: redirect to index with ticket filter applied
        return redirect()->route('reports.timeline.index', ['ticket' => $ticket]);
    }

    /**
     * Export timeline for a specific service request in PDF or Excel format.
     *
     * Requirements: 2.5
     */
    public function export(int $id, string $format)
    {
        try {
            $serviceRequest = ServiceRequest::with([
                'subService.service.family',
                'requester',
                'requestedBy',
                'assignee',
                'sla',
                'evidences.uploadedBy',
                'breachLogs'
            ])
            ->reportable()
            ->findOrFail($id);

            $timelineEvents = $serviceRequest->getTimelineEvents();
            $timeInStatus = $serviceRequest->getTimeInEachStatus();
            $totalResolutionTime = $serviceRequest->getTotalResolutionTime();
            $timeStatistics = $serviceRequest->getTimeStatistics();
            $timeSummary = $serviceRequest->getTimeSummaryByEventType();

            $timestamp = now()->format('Y-m-d_His');
            $filename = "timeline-{$serviceRequest->ticket_number}-{$timestamp}";

            if ($format === 'pdf') {
                $data = [
                    'request' => $serviceRequest,
                    'timelineEvents' => $this->prepareEventsForPdf($timelineEvents, $serviceRequest),
                    'timeInStatus' => $timeInStatus ?? collect([]),
                    'totalResolutionTime' => $totalResolutionTime ?? null,
                    'timeStatistics' => $timeStatistics ?? [],
                    'timeSummary' => $timeSummary ?? [],
                    'evidencesWithImages' => $this->prepareEvidencesForPdf($serviceRequest->evidences),
                ];

                $pdf = PDF::loadView('reports.exports.timeline-pdf', $data)
                    ->setPaper('a4', 'portrait')
                    ->setOption('enable-local-file-access', true)
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true)
                    ->setOption('chroot', storage_path('app'));

                return $pdf->download("{$filename}.pdf");
            }

            if ($format === 'excel') {
                try {
                    return Excel::download(new RequestTimelineExport($serviceRequest), "{$filename}.xlsx");
                } catch (\Exception $excelError) {
                    \Log::error('Error con Excel export: ' . $excelError->getMessage());

                    if (str_contains($excelError->getMessage(), 'zip')) {
                        return $this->exportTimelineAsCsv($serviceRequest, $filename);
                    }

                    throw $excelError;
                }
            }

            return redirect()->back()->with('error', 'Formato no válido. Use pdf o excel.');
        } catch (\Exception $e) {
            \Log::error('Error al exportar timeline: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Get date range from request. Defaults to current month.
     */
    protected function getDateRange(Request $request): array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            // Swap if start > end
            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            return ['start' => $start, 'end' => $end];
        }

        // Default: current month
        return [
            'start' => Carbon::today()->startOfMonth(),
            'end' => Carbon::today()->endOfMonth(),
        ];
    }

    /**
     * Prepare timeline events for PDF export.
     */
    private function prepareEventsForPdf($timelineEvents, $serviceRequest): array
    {
        if (empty($timelineEvents)) {
            return $this->createBasicTimelineEvents($serviceRequest);
        }

        $preparedEvents = [];

        foreach ($timelineEvents as $event) {
            $preparedEvents[] = [
                'type' => $this->cleanValueForPdf($event['type'] ?? 'system'),
                'title' => $this->cleanValueForPdf($event['title'] ?? 'Evento del sistema'),
                'description' => $this->cleanValueForPdf($event['description'] ?? ''),
                'user' => $this->cleanValueForPdf($event['user'] ?? 'Sistema'),
                'timestamp' => $this->cleanTimestampForPdf($event['timestamp'] ?? now()),
                'created_at' => $this->cleanTimestampForPdf($event['timestamp'] ?? now()),
                'event' => $this->cleanValueForPdf($event['title'] ?? 'Evento del sistema'),
            ];
        }

        return $preparedEvents;
    }

    /**
     * Prepare evidences for PDF with base64 images.
     */
    private function prepareEvidencesForPdf($evidences)
    {
        if (!$evidences || $evidences->isEmpty()) {
            return collect();
        }

        $preparedEvidences = collect();

        foreach ($evidences as $evidence) {
            $preparedEvidence = [
                'id' => $evidence->id,
                'title' => $this->cleanValueForPdf($evidence->title),
                'description' => $this->cleanValueForPdf($evidence->description),
                'file_name' => $this->cleanValueForPdf($evidence->file_name),
                'file_path' => $evidence->file_path,
                'mime_type' => $evidence->mime_type,
                'file_size' => $evidence->file_size,
                'created_at' => $evidence->created_at,
                'uploaded_by' => $evidence->uploadedBy ? $this->cleanValueForPdf($evidence->uploadedBy->name) : 'Sistema',
                'evidence_type' => $evidence->evidence_type,
                'step_number' => $evidence->step_number,
                'image_data' => null,
            ];

            if ($evidence->mime_type && str_starts_with($evidence->mime_type, 'image/')) {
                $imageData = $this->getImageBase64Data($evidence);
                if ($imageData) {
                    $preparedEvidence['image_data'] = $imageData;
                }
            }

            $preparedEvidences->push($preparedEvidence);
        }

        return $preparedEvidences;
    }

    /**
     * Get base64 encoded image data for an evidence file.
     */
    private function getImageBase64Data($evidence): ?string
    {
        if (!$evidence->file_path) {
            return null;
        }

        $possiblePaths = [
            storage_path('app/' . $evidence->file_path),
            storage_path('app/public/' . $evidence->file_path),
            public_path('storage/' . $evidence->file_path),
            storage_path($evidence->file_path),
        ];

        foreach ($possiblePaths as $imagePath) {
            if (file_exists($imagePath) && is_file($imagePath)) {
                try {
                    $imageContent = file_get_contents($imagePath);
                    if ($imageContent !== false) {
                        return 'data:' . $evidence->mime_type . ';base64,' . base64_encode($imageContent);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        try {
            if (\Storage::exists($evidence->file_path)) {
                $imageContent = \Storage::get($evidence->file_path);
                return 'data:' . $evidence->mime_type . ';base64,' . base64_encode($imageContent);
            }
        } catch (\Exception $e) {
            \Log::warning("No se pudo cargar imagen: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Create basic timeline events when none exist.
     */
    private function createBasicTimelineEvents($serviceRequest): array
    {
        $events = [];

        $events[] = [
            'type' => 'creation',
            'title' => 'Solicitud creada - Ticket #' . $serviceRequest->ticket_number,
            'description' => 'La solicitud fue creada en el sistema por ' . ($serviceRequest->requester->name ?? 'Solicitante'),
            'timestamp' => $serviceRequest->created_at,
            'user' => $serviceRequest->requester->name ?? 'Solicitante',
            'event' => 'Solicitud creada - Ticket #' . $serviceRequest->ticket_number,
            'created_at' => $serviceRequest->created_at,
        ];

        if ($serviceRequest->assignee) {
            $events[] = [
                'type' => 'assignment',
                'title' => 'Solicitud asignada',
                'description' => 'La solicitud fue asignada a ' . $serviceRequest->assignee->name,
                'timestamp' => $serviceRequest->accepted_at ?? $serviceRequest->created_at,
                'user' => $serviceRequest->assignee->name ?? 'Sistema',
                'event' => 'Solicitud asignada',
                'created_at' => $serviceRequest->accepted_at ?? $serviceRequest->created_at,
            ];
        }

        if ($serviceRequest->resolved_at) {
            $events[] = [
                'type' => 'resolution',
                'title' => 'Solicitud marcada como resuelta',
                'description' => $serviceRequest->resolution_notes ?? 'Solicitud completada y marcada como resuelta',
                'timestamp' => $serviceRequest->resolved_at,
                'user' => $serviceRequest->assignee->name ?? 'Técnico',
                'event' => 'Solicitud marcada como resuelta',
                'created_at' => $serviceRequest->resolved_at,
            ];
        }

        return $events;
    }

    /**
     * Clean a value for safe PDF rendering.
     */
    private function cleanValueForPdf($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return 'Array[' . count($value) . ']';
        }

        if (is_object($value)) {
            return 'Objeto';
        }

        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        return (string) $value;
    }

    /**
     * Clean a timestamp for PDF rendering.
     */
    private function cleanTimestampForPdf($timestamp)
    {
        if ($timestamp instanceof \DateTime || $timestamp instanceof Carbon) {
            return $timestamp;
        }

        if (is_string($timestamp)) {
            try {
                return Carbon::parse($timestamp);
            } catch (\Exception $e) {
                return now();
            }
        }

        return now();
    }

    /**
     * Fallback CSV export when Excel/ZIP fails.
     */
    private function exportTimelineAsCsv($serviceRequest, $filename)
    {
        $timelineEvents = $serviceRequest->getTimelineEvents();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($timelineEvents, $serviceRequest) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Evento',
                'Fecha y Hora',
                'Usuario Responsable',
                'Descripción',
                'Tipo de Evento',
                'Estado'
            ]);

            foreach ($timelineEvents as $event) {
                $eventName = $event['event'] ?? $event['title'] ?? 'Evento';
                $userName = 'Sistema';
                if (isset($event['user']) && $event['user']) {
                    $userName = is_object($event['user']) ? $event['user']->name : $event['user'];
                }

                fputcsv($file, [
                    $eventName,
                    isset($event['timestamp']) ? $event['timestamp']->format('d/m/Y H:i:s') : '',
                    $userName,
                    $event['description'] ?? 'Sin descripción',
                    $event['type'] ?? 'system',
                    $event['status'] ?? $serviceRequest->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
