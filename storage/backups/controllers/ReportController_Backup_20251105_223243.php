<?php

namespace App\Http\Controllers;

use App\Models\ServiceFamily;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceRequest;
use App\Models\ServiceLevelAgreement;
use App\Models\Evidence; // AÑADIR ESTA IMPORTACIÓN
use App\Exports\RequestTimelineExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Display the main reports dashboard
     */
    public function index()
    {
        return view('reports.index');
    }

    // =============================================
    // NUEVOS MÉTODOS PARA LÍNEA DE TIEMPO
    // =============================================

    /**
     * Reporte de Línea de Tiempo - Listado de solicitudes
     */
    public function requestTimeline(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $requests = ServiceRequest::with(['subService', 'requester', 'assignee', 'sla'])
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
            'subService',
            'requester',
            'assignee',
            'sla',
            'evidences.user',
            'breachLogs'
        ])->findOrFail($id);

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
        return view('reports.timeline-by-ticket');
    }

    /**
     * Exportar timeline a PDF o Excel - CORREGIDO
     */
    public function exportTimeline($id, $format)
    {
        try {
            $request = ServiceRequest::with([
                'subService.service.family',
                'requester',
                'assignee',
                'sla',
                'evidences',
                'breachLogs'
            ])->findOrFail($id);

            // Obtener eventos del timeline usando el método mejorado
            $timelineEvents = $this->prepareTimelineSimple($request);

            $timeInStatus = $request->getTimeInEachStatus();
            $totalResolutionTime = $request->getTotalResolutionTime();
            $timeStatistics = $request->getTimeStatistics();
            $timeSummary = $request->getTimeSummaryByEventType();

            $timestamp = now()->format('Y-m-d_His');

            if ($format === 'pdf') {
                $data = [
                    'request' => $request,
                    'timelineEvents' => $timelineEvents,
                    'timeInStatus' => $timeInStatus,
                    'totalResolutionTime' => $totalResolutionTime,
                    'timeStatistics' => $timeStatistics,
                    'timeSummary' => $timeSummary
                ];

                $pdf = PDF::loadView('reports.exports.timeline-pdf', $data);
                return $pdf->download("timeline-{$request->ticket_number}-{$timestamp}.pdf");
            }

            if ($format === 'excel') {
                return Excel::download(new RequestTimelineExport($request), "timeline-{$request->ticket_number}-{$timestamp}.xlsx");
            }

            return redirect()->back()->with('error', 'Formato no válido');
        } catch (\Exception $e) {
            \Log::error('Error al exportar timeline: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    // =============================================
    // MÉTODOS AUXILIARES PARA TIMELINE - CORREGIDOS
    // =============================================

    /**
     * Versión simplificada CON imágenes para PDF - CORREGIDA
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
                // Determinar el tipo de evento de manera más robusta
                $type = $this->determineEventType($event, $request);

                // Generar título basado en el tipo
                $title = $this->generateEventTitleBasedOnType($type, $event, $request);

                // Obtener imágenes de evidencias si es un evento de evidencia
                $evidenceImages = [];
                if ($type === 'evidence' && isset($event['evidence_id'])) {
                    $evidenceImages = $this->getEvidenceImages($event['evidence_id']);
                }

                // Crear evento procesado
                $processedEvent = [
                    'type' => $type,
                    'type_label' => $this->getEventTypeLabel($type),
                    'title' => $title,
                    'description' => $this->ensureString($event['description'] ?? $event['notes'] ?? ''),
                    'user' => $this->ensureString($event['user'] ?? $event['user_name'] ?? $event['created_by'] ?? 'Sistema'),
                    'timestamp' => $event['timestamp'] ?? $event['created_at'] ?? $event['date'] ?? now(),
                    'status' => $this->translateStatus($event['status'] ?? $request->status),
                    'evidence_images' => $evidenceImages,
                    'evidence_id' => $event['evidence_id'] ?? null
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
     * Obtener imágenes de evidencias - CORREGIDO
     */
    private function getEvidenceImages($evidenceId)
    {
        try {
            \Log::info("Buscando imágenes para evidencia ID: {$evidenceId}");

            // USAR EL MODELO CORRECTO - Evidence
            $evidence = Evidence::find($evidenceId);

            if (!$evidence) {
                \Log::warning("Evidencia no encontrada: {$evidenceId}");
                return [];
            }

            // Verificar si es imagen por mime_type
            $isImage = $evidence->mime_type && str_starts_with($evidence->mime_type, 'image/');

            if (!$isImage) {
                \Log::info("Evidencia no es una imagen (mime_type: {$evidence->mime_type})");
                return [];
            }

            // Cargar la imagen
            if (!empty($evidence->file_path)) {
                $fullPath = storage_path('app/' . $evidence->file_path);
                \Log::info("Intentando cargar imagen desde: {$fullPath}");

                if (file_exists($fullPath)) {
                    $imageData = base64_encode(file_get_contents($fullPath));

                    if ($imageData && strlen($imageData) > 100) {
                        \Log::info("Imagen cargada exitosamente: {$evidence->file_name}");
                        return [[
                            'data' => $imageData,
                            'mime_type' => $evidence->mime_type,
                            'file_name' => $evidence->file_name,
                            'extension' => pathinfo($evidence->file_name, PATHINFO_EXTENSION)
                        ]];
                    }
                } else {
                    \Log::warning("Archivo no existe: {$fullPath}");
                }
            }

            return [];
        } catch (\Exception $e) {
            \Log::error('Error en getEvidenceImages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Determinar el tipo de evento de manera más inteligente
     */
    private function determineEventType($event, $request)
    {
        // Si el evento ya tiene tipo, usarlo
        if (!empty($event['type'])) {
            return $event['type'];
        }

        // Determinar por contenido del evento
        if (isset($event['evidence_id']) || stripos($event['title'] ?? '', 'evidencia') !== false) {
            return 'evidence';
        }

        if (isset($event['breach_type']) || stripos($event['title'] ?? '', 'sla') !== false) {
            return 'sla';
        }

        if (stripos($event['title'] ?? '', 'creada') !== false || stripos($event['description'] ?? '', 'creada') !== false) {
            return 'creation';
        }

        if (stripos($event['title'] ?? '', 'resuelta') !== false || stripos($event['description'] ?? '', 'resuelta') !== false) {
            return 'resolution';
        }

        if (stripos($event['title'] ?? '', 'estado') !== false || isset($event['status'])) {
            return 'status_change';
        }

        // Por defecto
        return 'system';
    }

    /**
     * Generar título basado en el tipo - MEJORADO
     */
    private function generateEventTitleBasedOnType($type, $event, $request)
    {
        $status = $this->translateStatus($event['status'] ?? $request->status ?? 'DESCONOCIDO');
        $user = $event['user'] ?? $event['user_name'] ?? 'Sistema';

        switch ($type) {
            case 'creation':
                return 'Solicitud creada - Ticket #' . $request->ticket_number;

            case 'status_change':
                return 'Cambio de estado a: ' . $status;

            case 'evidence':
                return 'Evidencia registrada: ' . ($event['title'] ?? 'Documentación adjunta');

            case 'resolution':
                return 'Solicitud marcada como RESUELTA';

            case 'sla':
                return 'Evento de SLA: ' . ($event['breach_type'] ?? 'Monitoreo de tiempos');

            case 'user':
                return 'Acción realizada por: ' . $user;

            default:
                return 'Evento del sistema - ' . ($event['title'] ?? 'Actualización');
        }
    }

    /**
     * Traducir estados del inglés al español
     */
    private function translateStatus($status)
    {
        $translations = [
            // Estados básicos
            'created' => 'CREADA',
            'pending' => 'PENDIENTE',
            'accepted' => 'ACEPTADA',
            'assigned' => 'ASIGNADA',
            'in_progress' => 'EN_PROCESO',
            'responded' => 'RESPONDIDA',
            'resolved' => 'RESUELTA',
            'closed' => 'CERRADA',
            'cancelled' => 'CANCELADA',

            // Estados específicos de rutas
            'web_route' => 'RUTA_WEB',
            'main_route' => 'RUTA_PRINCIPAL',
            'route' => 'RUTA',

            // Estados de evidencia
            'evidence' => 'EVIDENCIA',
            'attachment' => 'ARCHIVO_ADJUNTO',

            // Otros estados
            'completed' => 'COMPLETADA',
            'rejected' => 'RECHAZADA',
            'on_hold' => 'EN_ESPERA',
            'reopened' => 'REABIERTA'
        ];

        return $translations[strtolower($status)] ?? $status;
    }

    /**
     * Obtener etiqueta para tipo de evento
     */
    private function getEventTypeLabel($type)
    {
        $labels = [
            'evidence' => 'Evidencia',
            'system' => 'Sistema',
            'status_change' => 'Estado',
            'sla' => 'SLA',
            'user' => 'Usuario',
            'creation' => 'Creación',
            'resolution' => 'Resolución'
        ];

        return $labels[$type] ?? 'Sistema';
    }

    /**
     * Asegurar que el valor sea string
     */
    private function ensureString($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? $value->__toString() : json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Crear eventos básicos del timeline - CORREGIDO
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
            'status' => 'PENDIENTE',
            'evidence_images' => []
        ];

        // Evento de asignación si existe
        if ($request->assignee) {
            $events[] = [
                'type' => 'status_change',
                'title' => 'Solicitud asignada',
                'description' => 'La solicitud fue asignada a ' . $request->assignee->name,
                'timestamp' => $request->accepted_at ?? $request->created_at,
                'user' => $request->assignee->name ?? 'Sistema',
                'status' => 'ASIGNADA',
                'evidence_images' => []
            ];
        }

        // Evento de aceptación si existe
        if ($request->accepted_at) {
            $events[] = [
                'type' => 'status_change',
                'title' => 'Solicitud aceptada para procesamiento',
                'description' => 'La solicitud fue aceptada para procesamiento',
                'timestamp' => $request->accepted_at,
                'user' => $request->assignee->name ?? 'Técnico',
                'status' => 'ACEPTADA',
                'evidence_images' => []
            ];
        }

        // Evento de inicio de proceso si existe
        if ($request->responded_at) {
            $events[] = [
                'type' => 'status_change',
                'title' => 'Proceso de atención iniciado',
                'description' => 'Se comenzó a trabajar en la solicitud',
                'timestamp' => $request->responded_at,
                'user' => $request->assignee->name ?? 'Técnico',
                'status' => 'EN_PROCESO',
                'evidence_images' => []
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
                'status' => 'RESUELTA',
                'evidence_images' => []
            ];
        }

        // Evento de cierre si existe
        if ($request->closed_at) {
            $events[] = [
                'type' => 'status_change',
                'title' => 'Solicitud cerrada definitivamente',
                'description' => 'Solicitud finalizada y cerrada en el sistema',
                'timestamp' => $request->closed_at,
                'user' => $request->assignee->name ?? 'Técnico',
                'status' => 'CERRADA',
                'evidence_images' => []
            ];
        }

        // Agregar eventos de evidencias - ELIMINADO DUPLICADO
        foreach ($request->evidences as $evidence) {
            $evidenceId = $evidence->id;
            $evidenceImages = $this->getEvidenceImages($evidenceId);
            $fileType = $this->getFileTypeLabel($evidence);

            $events[] = [
                'type' => 'evidence',
                'title' => 'Evidencia registrada: ' . $evidence->file_name,
                'description' => $evidence->description ?? 'Archivo adjunto: ' . $evidence->file_name,
                'timestamp' => $evidence->created_at ?? now(),
                'user' => $fileType,
                'status' => $this->translateStatus($request->status),
                'evidence_id' => $evidenceId,
                'evidence_images' => $evidenceImages
            ];
        }

        // Agregar eventos de SLA si existen
        foreach ($request->breachLogs as $breachLog) {
            $events[] = [
                'type' => 'sla',
                'title' => 'Incumplimiento de SLA detectado',
                'description' => 'Se detectó un incumplimiento en el tiempo de respuesta o resolución',
                'timestamp' => $breachLog->created_at,
                'user' => 'Sistema',
                'status' => $this->translateStatus($request->status),
                'evidence_images' => []
            ];
        }

        // Ordenar eventos por fecha
        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $events;
    }

    /**
     * Obtener etiqueta del tipo de archivo
     */
    private function getFileTypeLabel($evidence)
    {
        try {
            // Si es el modelo Evidence (tiene mime_type)
            if (isset($evidence->mime_type)) {
                $mimeType = strtolower($evidence->mime_type);

                if (str_starts_with($mimeType, 'image/')) {
                    return 'IMAGEN';
                } elseif ($mimeType === 'application/pdf') {
                    return 'PDF';
                } elseif (str_starts_with($mimeType, 'application/')) {
                    return 'DOCUMENTO';
                }
            }

            // Por extensión del archivo
            if (isset($evidence->file_name)) {
                $extension = strtolower(pathinfo($evidence->file_name, PATHINFO_EXTENSION));

                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                $documentExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

                if (in_array($extension, $imageExtensions)) {
                    return 'IMAGEN';
                } elseif ($extension === 'pdf') {
                    return 'PDF';
                } elseif (in_array($extension, $documentExtensions)) {
                    return 'DOCUMENTO';
                }
            }

            return 'ARCHIVO';
        } catch (\Exception $e) {
            return 'ARCHIVO';
        }
    }

    // =============================================
    // MÉTODOS EXISTENTES (se mantienen igual)
    // =============================================

    /**
     * SLA Compliance Report
     */
    public function slaCompliance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $slaCompliance = ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('sla.serviceFamily.name')
            ->map(function ($requests, $familyName) {
                $total = $requests->count();
                $compliant = $requests->filter(function ($request) {
                    return $this->isSlaCompliant($request);
                })->count();

                return [
                    'family' => $familyName,
                    'total_requests' => $total,
                    'compliant' => $compliant,
                    'non_compliant' => $total - $compliant,
                    'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();

        return view('reports.sla-compliance', compact('slaCompliance', 'dateRange'));
    }

    /**
     * Service Requests by Status Report
     */
    public function requestsByStatus(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $requestsByStatus = ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalRequests = $requestsByStatus->sum('count');

        return view('reports.requests-by-status', compact('requestsByStatus', 'totalRequests', 'dateRange'));
    }

    /**
     * Criticality Level Report
     */
    public function criticalityLevels(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $criticalityData = ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get()
            ->keyBy('criticality_level');

        return view('reports.criticality-levels', compact('criticalityData', 'dateRange'));
    }

    /**
     * Service Performance Report
     */
    public function servicePerformance(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $servicePerformance = ServiceRequest::with(['subService.service.family'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('subService.service.family.name')
            ->map(function ($requests, $familyName) {
                $totalRequests = $requests->count();
                $avgResolutionTime = $requests->whereNotNull('resolved_at')
                    ->whereNotNull('accepted_at')
                    ->avg(function ($request) {
                        return $request->accepted_at->diffInMinutes($request->resolved_at);
                    });

                $satisfactionRate = $requests->whereNotNull('satisfaction_score')
                    ->avg('satisfaction_score');

                return [
                    'family' => $familyName,
                    'total_requests' => $totalRequests,
                    'avg_resolution_time' => round($avgResolutionTime ?? 0, 2),
                    'avg_satisfaction' => round($satisfactionRate ?? 0, 2),
                    'services_count' => $requests->groupBy('subService.service.name')->count()
                ];
            })
            ->sortByDesc('total_requests')
            ->values();

        return view('reports.service-performance', compact('servicePerformance', 'dateRange'));
    }

    /**
     * Monthly Trends Report
     */
    public function monthlyTrends(Request $request)
    {
        $months = 6;
        $trends = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $monthData = ServiceRequest::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "CERRADA" THEN 1 ELSE 0 END) as closed_requests,
                    AVG(CASE WHEN satisfaction_score IS NOT NULL THEN satisfaction_score ELSE NULL END) as avg_satisfaction
                ')
                ->first();

            $trends->push([
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->translatedFormat('F Y'),
                'total_requests' => $monthData->total_requests,
                'closed_requests' => $monthData->closed_requests,
                'completion_rate' => $monthData->total_requests > 0
                    ? round(($monthData->closed_requests / $monthData->total_requests) * 100, 2)
                    : 0,
                'avg_satisfaction' => round($monthData->avg_satisfaction ?? 0, 2)
            ]);
        }

        return view('reports.monthly-trends', compact('trends'));
    }

    /**
     * Export reports to PDF
     */
    public function exportPdf(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            switch ($reportType) {
                case 'sla-compliance':
                    $slaCompliance = $this->getSlaComplianceData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.sla-compliance-pdf',
                        compact('slaCompliance', 'dateRange'),
                        "reporte-cumplimiento-sla-{$timestamp}.pdf"
                    );

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $totalRequests = $data->sum('count');
                    return $this->downloadPdf(
                        'reports.exports.requests-by-status-pdf',
                        [
                            'requestsByStatus' => $data,
                            'totalRequests' => $totalRequests,
                            'dateRange' => $dateRange
                        ],
                        "reporte-estados-solicitudes-{$timestamp}.pdf"
                    );

                case 'criticality-levels':
                    $criticalityData = $this->getCriticalityLevelsData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.criticality-levels-pdf',
                        compact('criticalityData', 'dateRange'),
                        "reporte-criticidad-{$timestamp}.pdf"
                    );

                case 'service-performance':
                    $servicePerformance = $this->getServicePerformanceData($dateRange);
                    return $this->downloadPdf(
                        'reports.exports.service-performance-pdf',
                        compact('servicePerformance', 'dateRange'),
                        "reporte-rendimiento-servicios-{$timestamp}.pdf"
                    );

                case 'request-timeline':
                    return back()->with('info', 'Use la opción de exportación desde el detalle del timeline');

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export reports to Excel - VERSIÓN CSV
     */
    public function exportExcel(Request $request, $reportType)
    {
        try {
            $dateRange = $this->getDateRange($request);
            $timestamp = now()->format('Y-m-d_His');

            switch ($reportType) {
                case 'sla-compliance':
                    $data = $this->getSlaComplianceData($dateRange);
                    $csv = $this->formatSlaComplianceForCsv($data);
                    return $this->downloadCsv($csv, "reporte-cumplimiento-sla-{$timestamp}.csv");

                case 'requests-by-status':
                    $data = $this->getRequestsByStatusData($dateRange);
                    $csv = $this->formatRequestsByStatusForCsv($data);
                    return $this->downloadCsv($csv, "reporte-estados-solicitudes-{$timestamp}.csv");

                case 'criticality-levels':
                    $data = $this->getCriticalityLevelsData($dateRange);
                    $csv = $this->formatCriticalityLevelsForCsv($data);
                    return $this->downloadCsv($csv, "reporte-criticidad-{$timestamp}.csv");

                case 'service-performance':
                    $data = $this->getServicePerformanceData($dateRange);
                    $csv = $this->formatServicePerformanceForCsv($data);
                    return $this->downloadCsv($csv, "reporte-rendimiento-servicios-{$timestamp}.csv");

                case 'request-timeline':
                    return back()->with('info', 'Use la opción de exportación desde el detalle del timeline');

                default:
                    return back()->with('error', 'Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Método auxiliar para descargar PDF
     */
    private function downloadPdf($view, $data, $filename)
    {
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
            return $pdf->download($filename);
        } else {
            $html = view($view, $data)->render();
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }
    }

    /**
     * Método auxiliar para descargar CSV
     */
    private function downloadCsv($csv, $filename)
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Métodos auxiliares para obtener datos
     */
    private function getSlaComplianceData($dateRange)
    {
        return ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('sla.serviceFamily.name')
            ->map(function ($requests, $familyName) {
                $total = $requests->count();
                $compliant = $requests->filter(function ($request) {
                    return $this->isSlaCompliant($request);
                })->count();

                return [
                    'family' => $familyName,
                    'total_requests' => $total,
                    'compliant' => $compliant,
                    'non_compliant' => $total - $compliant,
                    'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();
    }

    private function getRequestsByStatusData($dateRange)
    {
        return ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }

    private function getCriticalityLevelsData($dateRange)
    {
        return ServiceRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get();
    }

    private function getServicePerformanceData($dateRange)
    {
        return ServiceRequest::with(['subService.service.family'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('subService.service.family.name')
            ->map(function ($requests, $familyName) {
                $totalRequests = $requests->count();
                $avgResolutionTime = $requests->whereNotNull('resolved_at')
                    ->whereNotNull('accepted_at')
                    ->avg(function ($request) {
                        return $request->accepted_at->diffInMinutes($request->resolved_at);
                    });

                $satisfactionRate = $requests->whereNotNull('satisfaction_score')
                    ->avg('satisfaction_score');

                return [
                    'family' => $familyName,
                    'total_requests' => $totalRequests,
                    'avg_resolution_time' => round($avgResolutionTime ?? 0, 2),
                    'avg_satisfaction' => round($satisfactionRate ?? 0, 2),
                    'services_count' => $requests->groupBy('subService.service.name')->count()
                ];
            })
            ->sortByDesc('total_requests')
            ->values();
    }

    /**
     * Métodos para formatear datos para CSV
     */
    private function formatSlaComplianceForCsv($data)
    {
        $csv = "Familia de Servicio,Total Solicitudes,Cumplidas,Incumplidas,Tasa de Cumplimiento(%)\n";
        foreach ($data as $item) {
            $csv .= "\"{$item['family']}\",{$item['total_requests']},{$item['compliant']},{$item['non_compliant']},{$item['compliance_rate']}\n";
        }
        return $csv;
    }

    private function formatRequestsByStatusForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Estado,Cantidad,Porcentaje\n";
        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->status}\",{$item->count},{$percentage}\n";
        }
        return $csv;
    }

    private function formatCriticalityLevelsForCsv($data)
    {
        $total = $data->sum('count');
        $csv = "Nivel de Criticidad,Cantidad,Porcentaje\n";
        foreach ($data as $item) {
            $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
            $csv .= "\"{$item->criticality_level}\",{$item->count},{$percentage}\n";
        }
        return $csv;
    }

    private function formatServicePerformanceForCsv($data)
    {
        $csv = "Familia de Servicio,Cantidad de Servicios,Total Solicitudes,Tiempo Resolución Promedio (min),Satisfacción Promedio\n";
        foreach ($data as $item) {
            $csv .= "\"{$item['family']}\",{$item['services_count']},{$item['total_requests']},{$item['avg_resolution_time']},{$item['avg_satisfaction']}\n";
        }
        return $csv;
    }

    /**
     * Check if a service request is SLA compliant
     */
    private function isSlaCompliant(ServiceRequest $request): bool
    {
        if (!$request->sla) return false;

        $compliant = true;

        if ($request->accepted_at && $request->acceptance_deadline) {
            if ($request->accepted_at->gt($request->acceptance_deadline)) {
                $compliant = false;
            }
        }

        if ($request->responded_at && $request->response_deadline) {
            if ($request->responded_at->gt($request->response_deadline)) {
                $compliant = false;
            }
        }

        if ($request->resolved_at && $request->resolution_deadline) {
            if ($request->resolved_at->gt($request->resolution_deadline)) {
                $compliant = false;
            }
        }

        return $compliant;
    }

    /**
     * Get date range from request or default to last 30 days
     */
    private function getDateRange(Request $request): array
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }
}
