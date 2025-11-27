<?php

namespace App\Http\Controllers\Reports;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Exports\RequestTimelineExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TimelineReportController extends ReportController
{
    /**
     * Obtener el rango de fechas basado en el request
     */
    protected function getDateRange(Request $request)
    {
        $rangeType = $request->get('range', 'this_month');
        $customStart = $request->get('start_date');
        $customEnd = $request->get('end_date');

        if ($customStart && $customEnd) {
            return [
                'start' => Carbon::parse($customStart)->startOfDay(),
                'end' => Carbon::parse($customEnd)->endOfDay()
            ];
        }

        $today = Carbon::today();

        switch ($rangeType) {
            case 'today':
                return [
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->endOfDay()
                ];
            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                return [
                    'start' => $yesterday->startOfDay(),
                    'end' => $yesterday->endOfDay()
                ];
            case 'this_week':
                return [
                    'start' => $today->copy()->startOfWeek(),
                    'end' => $today->copy()->endOfWeek()
                ];
            case 'last_week':
                return [
                    'start' => $today->copy()->subWeek()->startOfWeek(),
                    'end' => $today->copy()->subWeek()->endOfWeek()
                ];
            case 'this_month':
                return [
                    'start' => $today->copy()->startOfMonth(),
                    'end' => $today->copy()->endOfMonth()
                ];
            case 'last_month':
                return [
                    'start' => $today->copy()->subMonth()->startOfMonth(),
                    'end' => $today->copy()->subMonth()->endOfMonth()
                ];
            case 'this_quarter':
                return [
                    'start' => $today->copy()->startOfQuarter(),
                    'end' => $today->copy()->endOfQuarter()
                ];
            case 'last_quarter':
                return [
                    'start' => $today->copy()->subQuarter()->startOfQuarter(),
                    'end' => $today->copy()->subQuarter()->endOfQuarter()
                ];
            case 'this_year':
                return [
                    'start' => $today->copy()->startOfYear(),
                    'end' => $today->copy()->endOfYear()
                ];
            case 'last_year':
                return [
                    'start' => $today->copy()->subYear()->startOfYear(),
                    'end' => $today->copy()->subYear()->endOfYear()
                ];
            case 'all_time':
                return [
                    'start' => Carbon::create(2020, 1, 1)->startOfDay(),
                    'end' => $today->copy()->endOfDay()
                ];
            default:
                return [
                    'start' => $today->copy()->startOfMonth(),
                    'end' => $today->copy()->endOfMonth()
                ];
        }
    }

    /**
     * Reporte de Línea de Tiempo - Listado de solicitudes
     */
    public function requestTimeline(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $requests = ServiceRequest::with(['subService', 'requester', 'assignee', 'sla'])
            ->reportable()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('reports.request-timeline', compact('requests', 'dateRange'));
    }

    /**
     * Mostrar detalle de timeline de una solicitud específica
     */
    public function showTimeline($id)
    {
        $request = ServiceRequest::with([
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

        $timelineEvents = $request->getTimelineEvents();
        $timeInStatus = $request->getTimeInEachStatus();
        $totalResolutionTime = $request->getTotalResolutionTime();
        $timeStatistics = $request->getTimeStatistics();
        $timeSummary = $request->getTimeSummaryByEventType();

        return view('reports.timeline-detail', compact(
            'request',
            'timelineEvents',
            'timeInStatus',
            'totalResolutionTime',
            'timeStatistics',
            'timeSummary'
        ));
    }

    /**
     * Mostrar formulario para descargar timeline por número de ticket
     */
    public function timelineByTicket()
    {
        // Obtener algunos tickets de ejemplo para mostrar en la ayuda
        $sampleTickets = ServiceRequest::select('ticket_number', 'title')
            ->reportable()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return view('reports.timeline-by-ticket', compact('sampleTickets'));
    }

    /**
     * Exportar timeline a PDF o Excel - VERSIÓN CORREGIDA
     */
    /**
     * Exportar timeline a PDF o Excel - VERSIÓN MEJORADA
     */
    public function exportTimeline($id, $format)
    {
        try {
            $request = ServiceRequest::with([
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

            // Obtener datos del timeline
            $timelineEvents = $request->getTimelineEvents();
            $timeInStatus = $request->getTimeInEachStatus();
            $totalResolutionTime = $request->getTotalResolutionTime();
            $timeStatistics = $request->getTimeStatistics();
            $timeSummary = $request->getTimeSummaryByEventType();

            // PREPARAR EVIDENCIAS CON IMÁGENES PARA PDF
            $evidencesWithImages = $this->prepareEvidencesForPdf($request->evidences);

            $timestamp = now()->format('Y-m-d_His');
            $filename = "timeline-{$request->ticket_number}-{$timestamp}";

            if ($format === 'pdf') {
                // PREPARAR DATOS DE MANERA SEGURA PARA PDF
                $data = [
                    'request' => $request,
                    'timelineEvents' => $this->prepareEventsForPdf($timelineEvents, $request),
                    'timeInStatus' => $timeInStatus ?? collect([]),
                    'totalResolutionTime' => $totalResolutionTime ?? null,
                    'timeStatistics' => $timeStatistics ?? [],
                    'timeSummary' => $timeSummary ?? [],
                    'evidencesWithImages' => $evidencesWithImages // NUEVO: Evidencias preparadas
                ];

                $pdf = PDF::loadView('reports.exports.timeline-pdf', $data)
                    ->setPaper('a4', 'portrait')
                    ->setOption('enable-local-file-access', true)
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true)
                    ->setOption('chroot', storage_path('app')); // IMPORTANTE: Dar acceso a storage

                return $pdf->download("{$filename}.pdf");
            }

            if ($format === 'excel') {
                try {
                    return Excel::download(new RequestTimelineExport($request), "{$filename}.xlsx");
                } catch (\Exception $excelError) {
                    \Log::error('Error específico con Excel: ' . $excelError->getMessage());

                    // Si hay un error con Excel (probablemente ZIP), ofrecer CSV como alternativa
                    if (str_contains($excelError->getMessage(), 'zip')) {
                        \Log::info('Intentando exportación como CSV debido a problema con ZIP');
                        return $this->exportTimelineAsCSV($request, $filename);
                    }

                    throw $excelError;
                }
            }

            return redirect()->back()->with('error', 'Formato no válido');
        } catch (\Exception $e) {
            \Log::error('Error al exportar timeline: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Preparar evidencias para PDF con imágenes
     */
    private function prepareEvidencesForPdf($evidences)
    {
        try {
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
                    'image_data' => null // Inicialmente null
                ];

                // Si es una imagen, cargar los datos base64
                if ($evidence->mime_type && str_starts_with($evidence->mime_type, 'image/')) {
                    $imageData = $this->getImageBase64Data($evidence);
                    if ($imageData) {
                        $preparedEvidence['image_data'] = $imageData;
                    }
                }

                $preparedEvidences->push($preparedEvidence);
            }

            return $preparedEvidences;
        } catch (\Exception $e) {
            \Log::error('Error en prepareEvidencesForPdf: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtener datos base64 de imagen de manera segura
     */
    private function getImageBase64Data($evidence)
    {
        try {
            if (!$evidence->file_path) {
                return null;
            }

            // MÚLTIPLES INTENTOS PARA ENCONTRAR LA IMAGEN
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
                            $base64 = base64_encode($imageContent);
                            return 'data:' . $evidence->mime_type . ';base64,' . $base64;
                        }
                    } catch (\Exception $e) {
                        continue; // Intentar con la siguiente ruta
                    }
                }
            }

            // Si no se encuentra, intentar con Storage de Laravel
            try {
                if (\Storage::exists($evidence->file_path)) {
                    $imageContent = \Storage::get($evidence->file_path);
                    $base64 = base64_encode($imageContent);
                    return 'data:' . $evidence->mime_type . ';base64,' . $base64;
                }
            } catch (\Exception $e) {
                \Log::warning("No se pudo cargar imagen con Storage: " . $e->getMessage());
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error en getImageBase64Data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Método auxiliar para debug de imágenes
     */
    private function debugImagePaths($evidence)
    {
        $debugInfo = [
            'evidence_id' => $evidence->id,
            'file_path' => $evidence->file_path,
            'mime_type' => $evidence->mime_type,
            'file_name' => $evidence->file_name,
        ];

        // Verificar rutas posibles
        $possiblePaths = [
            'storage_app' => storage_path('app/' . $evidence->file_path),
            'storage_app_public' => storage_path('app/public/' . $evidence->file_path),
            'public_storage' => public_path('storage/' . $evidence->file_path),
            'storage_direct' => storage_path($evidence->file_path),
        ];

        foreach ($possiblePaths as $key => $path) {
            $debugInfo[$key] = [
                'path' => $path,
                'exists' => file_exists($path) ? 'YES' : 'NO',
                'is_file' => is_file($path) ? 'YES' : 'NO',
                'readable' => is_readable($path) ? 'YES' : 'NO'
            ];
        }

        // Verificar con Storage
        try {
            $debugInfo['storage_exists'] = \Storage::exists($evidence->file_path) ? 'YES' : 'NO';
        } catch (\Exception $e) {
            $debugInfo['storage_exists'] = 'ERROR: ' . $e->getMessage();
        }

        \Log::info('Debug Image Paths: ', $debugInfo);
        return $debugInfo;
    }

    /**
     * Preparar eventos para PDF de manera segura
     */
    private function prepareEventsForPdf($timelineEvents, $request)
    {
        try {
            if (empty($timelineEvents)) {
                return $this->createBasicTimelineEvents($request);
            }

            $preparedEvents = [];

            foreach ($timelineEvents as $event) {
                $preparedEvent = [
                    'type' => $this->cleanValueForPdf($event['type'] ?? 'system'),
                    'title' => $this->cleanValueForPdf($event['title'] ?? 'Evento del sistema'),
                    'description' => $this->cleanValueForPdf($event['description'] ?? ''),
                    'user' => $this->cleanValueForPdf($event['user'] ?? 'Sistema'),
                    'timestamp' => $this->cleanTimestampForPdf($event['timestamp'] ?? now()),
                    'created_at' => $this->cleanTimestampForPdf($event['timestamp'] ?? now()),
                    'event' => $this->cleanValueForPdf($event['title'] ?? 'Evento del sistema') // Para compatibilidad
                ];

                $preparedEvents[] = $preparedEvent;
            }

            return $preparedEvents;
        } catch (\Exception $e) {
            \Log::error('Error en prepareEventsForPdf: ' . $e->getMessage());
            return $this->createBasicTimelineEvents($request);
        }
    }

    /**
     * Limpiar valor para asegurar que sea string (PDF)
     */
    private function cleanValueForPdf($value)
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

        return (string)$value;
    }

    /**
     * Limpiar timestamp para PDF
     */
    private function cleanTimestampForPdf($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
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
     * Descargar línea de tiempo por ticket
     */
    public function downloadTimelineByTicket(Request $request)
    {
        try {
            // Validar entrada
            $request->validate([
                'ticket_number' => 'required|string',
                'format' => 'required|in:pdf,excel'
            ]);

            $ticketNumber = trim($request->input('ticket_number'));
            $format = $request->input('format', 'pdf');

            \Log::info("Buscando ticket: {$ticketNumber} con formato: {$format}");

            // Buscar el ServiceRequest
            $serviceRequest = ServiceRequest::where('ticket_number', $ticketNumber)
                ->reportable()
                ->with([
                    'subService.service.family',
                    'requester',
                    'requestedBy',
                    'assignee',
                    'sla',
                    'evidences.uploadedBy',
                    'breachLogs'
                ])
                ->first(); // Usar first() en lugar de firstOrFail() para mejor control

            if (!$serviceRequest) {
                // Buscar con variaciones comunes del ticket number
                $serviceRequest = ServiceRequest::where('ticket_number', 'LIKE', "%{$ticketNumber}%")
                    ->reportable()
                    ->orWhere('id', $ticketNumber)
                    ->with([
                        'subService.service.family',
                        'requester',
                        'requestedBy',
                        'assignee',
                        'sla',
                        'evidences.uploadedBy',
                        'breachLogs'
                    ])
                    ->first();
            }

            if (!$serviceRequest) {
                \Log::warning("Ticket no encontrado: {$ticketNumber}");

                // Sugerir algunos tickets disponibles
                $suggestedTickets = ServiceRequest::select('ticket_number', 'title')
                    ->reportable()
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get()
                    ->pluck('ticket_number')
                    ->toArray();

                $suggestion = '';
                if (!empty($suggestedTickets)) {
                    $suggestion = ' Algunos tickets disponibles: ' . implode(', ', $suggestedTickets);
                }

                return redirect()->route('reports.timeline.by-ticket')
                    ->with('error', "No se encontró ninguna solicitud con el número de ticket: {$ticketNumber}. Verifica que el número esté correcto.{$suggestion}");
            }

            \Log::info("Ticket encontrado: ID {$serviceRequest->id}, Número: {$serviceRequest->ticket_number}");

            // Llamar al método de exportación existente con el ID y formato
            return $this->exportTimeline($serviceRequest->id, $format);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('reports.timeline.by-ticket')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error en downloadTimelineByTicket: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('reports.timeline.by-ticket')
                ->with('error', 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Determinar tipo de evento
     */
    private function determineEventType($event, $request)
    {
        $eventType = strtolower($event['type'] ?? $event['event'] ?? '');

        if (empty($eventType)) {
            return 'system';
        }

        if (str_contains($eventType, 'creada') || str_contains($eventType, 'created') || str_contains($eventType, 'creation')) {
            return 'creation';
        } elseif (str_contains($eventType, 'aceptada') || str_contains($eventType, 'accepted') || str_contains($eventType, 'acceptance')) {
            return 'acceptance';
        } elseif (str_contains($eventType, 'asignada') || str_contains($eventType, 'assigned') || str_contains($eventType, 'assignment')) {
            return 'assignment';
        } elseif (str_contains($eventType, 'iniciada') || str_contains($eventType, 'started') || str_contains($eventType, 'in_progress')) {
            return 'progress';
        } elseif (str_contains($eventType, 'resuelta') || str_contains($eventType, 'resolved') || str_contains($eventType, 'resolution')) {
            return 'resolution';
        } elseif (str_contains($eventType, 'cerrada') || str_contains($eventType, 'closed') || str_contains($eventType, 'closure')) {
            return 'closure';
        } elseif (str_contains($eventType, 'evidencia') || str_contains($eventType, 'evidence')) {
            return 'evidence';
        } else {
            return 'system';
        }
    }

    /**
     * Obtener nombre de usuario de manera segura
     */
    private function getUserNameFromEvent($event, $request)
    {
        // Si el evento ya tiene información de usuario
        if (!empty($event['user'])) {
            return $event['user'];
        }

        if (!empty($event['user_name'])) {
            return $event['user_name'];
        }

        if (!empty($event['created_by'])) {
            return $event['created_by'];
        }

        // Para eventos de evidencia, intentar obtener del modelo
        if (isset($event['evidence_id'])) {
            try {
                $evidence = ServiceRequestEvidence::with(['uploadedBy'])->find($event['evidence_id']);
                if ($evidence && $evidence->uploadedBy) {
                    return $evidence->uploadedBy->name ?? 'Usuario';
                }
            } catch (\Exception $e) {
                \Log::warning("No se pudo obtener usuario de evidencia: " . $e->getMessage());
            }
        }

        // Por defecto
        return 'Sistema';
    }

    /**
     * Crear eventos básicos del timeline
     */
    private function createBasicTimelineEvents($request)
    {
        $events = [];

        // Evento de creación
        $events[] = [
            'type' => 'creation',
            'title' => 'Solicitud creada - Ticket #' . $request->ticket_number,
            'description' => 'La solicitud fue creada en el sistema por ' . ($request->requester->name ?? 'Solicitante'),
            'timestamp' => $request->created_at,
            'user' => $request->requester->name ?? 'Solicitante',
            'status' => $request->status
        ];

        // Evento de asignación si existe
        if ($request->assignee) {
            $events[] = [
                'type' => 'assignment',
                'title' => 'Solicitud asignada',
                'description' => 'La solicitud fue asignada a ' . $request->assignee->name,
                'timestamp' => $request->accepted_at ?? $request->created_at,
                'user' => $request->assignee->name ?? 'Sistema',
                'status' => $request->status
            ];
        }

        // Evento de resolución si existe
        if ($request->resolved_at) {
            $events[] = [
                'type' => 'resolution',
                'title' => 'Solicitud marcada como resuelta',
                'description' => $request->resolution_notes ?? 'Solicitud completada y marcada como resuelta',
                'timestamp' => $request->resolved_at,
                'user' => $request->assignee->name ?? 'Técnico',
                'status' => 'RESUELTA'
            ];
        }

        return $events;
    }

    /**
     * Método auxiliar para preparar datos simples del timeline
     */
    private function prepareTimelineSimple($request, $timelineEvents = null)
    {
        try {
            // Si no se pasan eventos, obtenerlos del request
            if (is_null($timelineEvents)) {
                $timelineEvents = $request->getTimelineEvents();
            }

            // Si aún está vacío, crear eventos básicos
            if (empty($timelineEvents)) {
                $timelineEvents = $this->createBasicTimelineEvents($request);
            }

            $processedEvents = [];

            foreach ($timelineEvents as $event) {
                // Determinar el tipo de evento
                $type = $this->determineEventType($event, $request);

                // Obtener información del usuario de manera segura
                $userName = $this->getUserNameFromEvent($event, $request);

                // Crear evento procesado
                $processedEvent = [
                    'type' => $type,
                    'title' => $event['title'] ?? $event['event'] ?? 'Evento del sistema',
                    'description' => $event['description'] ?? $event['notes'] ?? '',
                    'user' => $userName,
                    'timestamp' => $event['timestamp'] ?? $event['created_at'] ?? $event['date'] ?? now(),
                    'status' => $event['status'] ?? $request->status
                ];

                $processedEvents[] = $processedEvent;
            }

            return $processedEvents;
        } catch (\Exception $e) {
            \Log::error('Error en prepareTimelineSimple: ' . $e->getMessage());
            return $this->createBasicTimelineEvents($request);
        }
    }

    /**
     * Obtener imágenes de evidencias
     */
    private function getEvidenceImages($evidenceId)
    {
        try {
            $evidence = ServiceRequestEvidence::with(['uploadedBy'])->find($evidenceId);

            if (!$evidence) {
                return [];
            }

            // Verificar si es imagen
            $isImage = $evidence->mime_type && str_starts_with($evidence->mime_type, 'image/');

            if (!$isImage) {
                return [];
            }

            // Cargar la imagen si existe
            if (!empty($evidence->file_path) && file_exists(storage_path('app/' . $evidence->file_path))) {
                $imageData = base64_encode(file_get_contents(storage_path('app/' . $evidence->file_path)));

                return [[
                    'data' => $imageData,
                    'mime_type' => $evidence->mime_type,
                    'file_name' => $evidence->file_name
                ]];
            }

            return [];
        } catch (\Exception $e) {
            \Log::error('Error en getEvidenceImages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Método de respaldo para exportar como CSV cuando Excel falla
     */
    private function exportTimelineAsCSV($request, $filename)
    {
        try {
            $timelineEvents = $request->getTimelineEvents();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ];

            $callback = function() use ($timelineEvents, $request) {
                $file = fopen('php://output', 'w');

                // UTF-8 BOM para Excel
                fwrite($file, "\xEF\xBB\xBF");

                // Encabezados
                fputcsv($file, [
                    'Evento',
                    'Fecha y Hora',
                    'Usuario Responsable',
                    'Descripción',
                    'Tipo de Evento',
                    'Estado'
                ]);

                // Datos
                foreach ($timelineEvents as $event) {
                    // Obtener el status/type del evento de manera segura
                    $eventStatus = $event['status'] ?? $event['type'] ?? 'unknown';

                    // Obtener el nombre del evento
                    $eventName = $event['event'] ?? $event['title'] ?? 'Evento';

                    // Obtener el usuario de manera segura
                    $userName = 'Sistema';
                    if (isset($event['user']) && $event['user']) {
                        $userName = is_object($event['user']) ? $event['user']->name : $event['user'];
                    }

                    fputcsv($file, [
                        $eventName,
                        $event['timestamp']->format('d/m/Y H:i:s'),
                        $userName,
                        $event['description'] ?? 'Sin descripción',
                        $this->getEventTypeLabel($eventStatus),
                        $eventStatus
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('Error en exportTimelineAsCSV: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el reporte CSV: ' . $e->getMessage());
        }
    }

    private function getEventTypeLabel($status)
    {
        $labels = [
            'created' => 'Creación',
            'assigned' => 'Asignación',
            'accepted' => 'Aceptación',
            'responded' => 'Respuesta Inicial',
            'paused' => 'Pausa',
            'resumed' => 'Reanudación',
            'resolved' => 'Resolución',
            'closed' => 'Cierre',
            'evidence' => 'Evidencia',
            'breach' => 'Incumplimiento SLA'
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}
