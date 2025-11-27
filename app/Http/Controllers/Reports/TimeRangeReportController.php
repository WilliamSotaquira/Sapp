<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceFamily;
use App\Models\ServiceRequestEvidence;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Exports\TimeRangeReportExport;

class TimeRangeReportController extends Controller
{
    /**
     * Mostrar el formulario del reporte por rango de tiempo
     */
    public function index()
    {
        $families = ServiceFamily::active()
            ->withCount('services')
            ->ordered()
            ->get();

        return view('reports.time-range.index', compact('families'));
    }

    /**
     * Generar reporte por rango de tiempo
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel,zip',
            'families' => 'nullable|array',
            'families.*' => 'exists:service_families,id'
        ]);

        try {
            $dateRange = [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end' => Carbon::parse($request->end_date)->endOfDay()
            ];

            $serviceFamilyIds = $request->input('families', []);
            $reportData = $this->getReportData($dateRange, $serviceFamilyIds);

            $timestamp = now()->format('Y-m-d_His');
            $filename = "reporte-tiempo-{$timestamp}";

            switch ($request->format) {
                case 'pdf':
                    return $this->generatePdf($reportData, $filename);

                case 'excel':
                    return $this->generateExcel($reportData, $filename);

                case 'zip':
                    // Verificar si ZipArchive está disponible
                    if (!class_exists('ZipArchive')) {
                        return back()->with('error', 'La extensión ZIP no está habilitada. Intente con PDF o Excel, o contacte al administrador para habilitar la extensión php-zip.');
                    }
                    return $this->generateZipWithEvidences($reportData, $filename);

                default:
                    return back()->with('error', 'Formato no válido');
            }

        } catch (\Exception $e) {
            Log::error('Error generando reporte por tiempo: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Obtener datos del reporte
     */
    private function getReportData($dateRange, $serviceFamilyIds = [])
    {
        $query = ServiceRequest::with([
            'subService.service.family',
            'requester',
            'assignee',
            'evidences.uploadedBy',
            'tasks',
            'sla'
        ])
        ->reportable()
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        // Filtrar por familias de servicios si se especifican
        if (!empty($serviceFamilyIds)) {
            $query->whereHas('subService.service.family', function ($q) use ($serviceFamilyIds) {
                $q->whereIn('id', $serviceFamilyIds);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        // Agrupar por familia de servicios
        $groupedData = $requests->groupBy(function($request) {
            return $request->subService->service->family->name ?? 'Sin Familia';
        });

        // Calcular estadísticas
        $statistics = $this->calculateStatistics($requests);

        // Obtener evidencias
        $evidences = $this->getEvidences($requests);

        return [
            'requests' => $requests,
            'groupedData' => $groupedData,
            'statistics' => $statistics,
            'evidences' => $evidences,
            'dateRange' => $dateRange,
            'serviceFamilyIds' => $serviceFamilyIds
        ];
    }

    /**
     * Calcular estadísticas del reporte
     */
    private function calculateStatistics($requests)
    {
        $total = $requests->count();

        $byStatus = $requests->groupBy('status')->map(function ($group) use ($total) {
            return [
                'count' => $group->count(),
                'percentage' => $total > 0 ? round(($group->count() / $total) * 100, 2) : 0
            ];
        });

        $byCriticality = $requests->groupBy('criticality_level')->map(function ($group) use ($total) {
            return [
                'count' => $group->count(),
                'percentage' => $total > 0 ? round(($group->count() / $total) * 100, 2) : 0
            ];
        });

        $byFamily = $requests->groupBy(function($request) {
            return $request->subService->service->family->name ?? 'Sin Familia';
        })->map(function ($group) use ($total) {
            return [
                'count' => $group->count(),
                'percentage' => $total > 0 ? round(($group->count() / $total) * 100, 2) : 0,
                'avgResolutionTime' => $this->calculateAverageResolutionTime($group)
            ];
        });

        // Métricas de tiempo
        $resolvedRequests = $requests->where('status', 'RESUELTA');
        $avgResolutionTime = $this->calculateAverageResolutionTime($resolvedRequests);
        $overdueRequests = $requests->filter(function ($request) {
            return $request->resolution_deadline &&
                   $request->resolution_deadline < now() &&
                   !in_array($request->status, ['RESUELTA', 'CERRADA']);
        });

        return [
            'total' => $total,
            'byStatus' => $byStatus,
            'byCriticality' => $byCriticality,
            'byFamily' => $byFamily,
            'resolvedCount' => $resolvedRequests->count(),
            'overdueCount' => $overdueRequests->count(),
            'avgResolutionTime' => $avgResolutionTime,
            'satisfactionAvg' => $requests->where('satisfaction_score', '>', 0)->avg('satisfaction_score') ?: 0
        ];
    }

    /**
     * Calcular tiempo promedio de resolución
     */
    private function calculateAverageResolutionTime($requests)
    {
        $resolvedRequests = $requests->filter(function ($request) {
            return $request->resolved_at && $request->created_at;
        });

        if ($resolvedRequests->isEmpty()) {
            return 0;
        }

        $totalDays = $resolvedRequests->sum(function ($request) {
            return $request->created_at->diffInDays($request->resolved_at);
        });

        return round($totalDays / $resolvedRequests->count(), 1);
    }

    /**
     * Obtener evidencias de las solicitudes
     */
    private function getEvidences($requests)
    {
        $requestIds = $requests->pluck('id');

        return ServiceRequestEvidence::whereIn('service_request_id', $requestIds)
            ->with(['uploadedBy', 'serviceRequest'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Generar PDF
     */
    private function generatePdf($reportData, $filename)
    {
        try {
            // Validar que existan las claves necesarias
            if (!isset($reportData['statistics'])) {
                throw new \Exception('Faltan datos de estadísticas');
            }

            $pdf = Pdf::loadView('reports.time-range.pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false);

            return $pdf->download("{$filename}.pdf");

        } catch (\Exception $e) {
            Log::error('Error generando PDF: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw new \Exception('Error al generar el archivo PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar Excel
     */
    private function generateExcel($reportData, $filename)
    {
        try {
            return Excel::download(
                new TimeRangeReportExport($reportData),
                "{$filename}.xlsx"
            );

        } catch (\Exception $e) {
            Log::error('Error generando Excel: ' . $e->getMessage());
            throw new \Exception('Error al generar el archivo Excel');
        }
    }

    /**
     * Generar ZIP con evidencias
     */
    private function generateZipWithEvidences($reportData, $filename)
    {
        try {
            // Verificar si ZipArchive está disponible
            if (!class_exists('ZipArchive')) {
                throw new \Exception('La extensión ZIP no está habilitada en PHP. Por favor, genere el reporte en PDF o Excel por separado.');
            }

            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipFilePath = storage_path("app/temp/{$filename}.zip");
            $zip = new ZipArchive();

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }

            // 1. Agregar reporte PDF
            $pdfContent = Pdf::loadView('reports.time-range.pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false)
                ->output();
            $zip->addFromString("reporte-principal.pdf", $pdfContent);

            // 2. Agregar reporte Excel
            $excelPath = storage_path("app/temp/excel_{$filename}.xlsx");
            Excel::store(new TimeRangeReportExport($reportData), "temp/excel_{$filename}.xlsx");

            if (file_exists($excelPath)) {
                $excelContent = file_get_contents($excelPath);
                $zip->addFromString("reporte-datos.xlsx", $excelContent);
            }

            // 3. Crear carpeta de evidencias en el ZIP
            $evidencesAdded = 0;
            foreach ($reportData['evidences'] as $evidence) {
                if ($evidence->file_path && Storage::exists($evidence->file_path)) {
                    try {
                        $filePath = Storage::path($evidence->file_path);
                        $storedName = basename($evidence->file_path);
                        $safeFileName = $this->sanitizeFileName($storedName ?: $evidence->file_original_name);

                        $ticketFolder = $evidence->serviceRequest->ticket_number ?? ('SR-' . $evidence->service_request_id);
                        $ticketFolder = preg_replace('/[^A-Za-z0-9_-]/', '-', $ticketFolder);

                        $evidenceFolderName = "evidencias/{$ticketFolder}";

                        // Leer el contenido del archivo y agregarlo al ZIP
                        $fileContent = file_get_contents($filePath);
                        $zip->addFromString("{$evidenceFolderName}/{$safeFileName}", $fileContent);
                        $evidencesAdded++;

                    } catch (\Exception $e) {
                        Log::warning("No se pudo agregar evidencia {$evidence->id}: " . $e->getMessage());
                    }
                }
            }

            // 4. Crear archivo de resumen
            $summaryContent = $this->generateSummaryText($reportData, $evidencesAdded);
            $zip->addFromString("RESUMEN_REPORTE.txt", $summaryContent);

            $zip->close();

            // Limpiar archivo temporal de Excel
            if (file_exists($excelPath)) {
                unlink($excelPath);
            }

            return response()->download($zipFilePath, "{$filename}.zip")->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('Error generando ZIP: ' . $e->getMessage());
            throw new \Exception('Error al generar el archivo ZIP con evidencias');
        }
    }

    /**
     * Sanitizar nombre de archivo para ZIP
     */
    private function sanitizeFileName($filename)
    {
        // Separar nombre y extensión
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'];

        // Limpiar caracteres problemáticos del nombre
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $basename);

        // Eliminar guiones bajos múltiples
        $sanitized = preg_replace('/_+/', '_', $sanitized);

        // Limitar longitud del nombre (dejando espacio para la extensión)
        $maxLength = 100 - strlen($extension);
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }

        return $sanitized . $extension;
    }

    /**
     * Generar texto de resumen
     */
    private function generateSummaryText($reportData, $evidencesAdded)
    {
        $stats = $reportData['statistics'];
        $dateRange = $reportData['dateRange'];

        $summary = "RESUMEN DEL REPORTE POR RANGO DE TIEMPO\n";
        $summary .= "==========================================\n\n";
        $summary .= "Periodo: " . $dateRange['start']->format('d/m/Y') . " - " . $dateRange['end']->format('d/m/Y') . "\n";
        $summary .= "Generado: " . now()->format('d/m/Y H:i:s') . "\n\n";

        $summary .= "ESTADÍSTICAS GENERALES:\n";
        $summary .= "- Total de solicitudes: " . $stats['total'] . "\n";
        $summary .= "- Solicitudes resueltas: " . $stats['resolvedCount'] . "\n";
        $summary .= "- Solicitudes vencidas: " . $stats['overdueCount'] . "\n";
        $summary .= "- Tiempo promedio de resolución: " . round($stats['avgResolutionTime'], 1) . " días\n";
        $summary .= "- Satisfacción promedio: " . round($stats['satisfactionAvg'], 2) . "/5\n\n";

        $summary .= "POR ESTADO:\n";
        foreach ($stats['byStatus'] as $status => $data) {
            $summary .= "- {$status}: {$data['count']} ({$data['percentage']}%)\n";
        }

        $summary .= "\nPOR FAMILIA DE SERVICIO:\n";
        foreach ($stats['byFamily'] as $family => $data) {
            $summary .= "- {$family}: {$data['count']} ({$data['percentage']}%)\n";
        }

        $summary .= "\nARCHIVOS INCLUIDOS:\n";
        $summary .= "- reporte-principal.pdf: Reporte detallado en formato PDF\n";
        $summary .= "- reporte-datos.xlsx: Datos en formato Excel para análisis\n";
        $summary .= "- evidencias/: Carpeta con {$evidencesAdded} archivos de evidencia\n";

        return $summary;
    }
}
