<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Cut;
use App\Models\Company;
use App\Models\ServiceRequestEvidence;
use App\Exports\ObligacionesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ObligacionesReportController extends Controller
{
    /**
     * Mostrar reporte de obligaciones
     * Obligaciones = ServiceRequests
     * Actividades = Tasks y Subtasks
     * Productos = ServiceRequestEvidence
     */
    public function index(Request $request): View
    {
        $cuts = Cut::query()
            ->orderByDesc('start_date')
            ->when((int) session('current_company_id'), function ($query) {
                $query->whereHas('contract', function ($q) {
                    $q->where('company_id', (int) session('current_company_id'));
                });
            })
            ->get(['id', 'name', 'start_date', 'end_date']);

        // Si no llega cut_id, preselecciona el corte más reciente.
        if (!$request->filled('cut_id')) {
            $latestCutId = $cuts->first()?->id;
            if ($latestCutId) {
                $request->merge(['cut_id' => (string) $latestCutId]);
            }
        }

        $cutId = $request->get('cut_id');

        // Ordenar por fecha descendente
        $allServiceRequests = $this->buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por servicio
        $serviceRequests = $allServiceRequests->groupBy(function ($sr) {
            $family = $sr->subService?->service?->family;
            $familyName = $family?->name ?? 'Sin Familia';
            $contractNumber = $family?->contract?->number;
            return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
        })->sortKeys();

        $statsBaseQuery = $this->applyFilters(ServiceRequest::query(), $request);

        // Calcular estadísticas
        $stats = [
            'total_obligaciones' => (clone $statsBaseQuery)->count(),
            'total_actividades' => Task::whereIn('service_request_id', (clone $statsBaseQuery)->select('id'))->count(),
            'obligaciones_pendientes' => (clone $statsBaseQuery)->where('status', 'PENDIENTE')->count(),
            'obligaciones_en_progreso' => (clone $statsBaseQuery)->where('status', 'EN_PROCESO')->count(),
            'obligaciones_resueltas' => (clone $statsBaseQuery)->where('status', 'RESUELTA')->count(),
        ];

        return view('reports.obligaciones.index', [
            'pageTitle' => 'Reporte de Obligaciones',
            'serviceRequests' => $serviceRequests,
            'stats' => $stats,
            'statuses' => ServiceRequest::getStatusOptions(),
            'cuts' => $cuts,
            'filters' => [
                'cut_id' => $cutId,
            ]
        ]);
    }

    /**
     * Exportar reporte de obligaciones
     */
    public function export(Request $request): Response
    {
        $format = strtolower((string) $request->get('format', 'pdf'));

        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            return response('Formato no válido', 400);
        }

        $serviceRequests = $this->buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedServiceRequests = $serviceRequests->groupBy(function ($sr) {
            $family = $sr->subService?->service?->family;
            $familyName = $family?->name ?? 'Sin Familia';
            $contractNumber = $family?->contract?->number;
            return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
        })->sortKeys();

        $dateRange = $this->getDateRangeFromFilters($request);
        $selectedCut = null;
        $cutId = $request->get('cut_id');
        if ($cutId) {
            $selectedCut = Cut::query()
                ->with('contract')
                ->when((int) session('current_company_id'), function ($query) {
                    $query->whereHas('contract', function ($q) {
                        $q->where('company_id', (int) session('current_company_id'));
                    });
                })
                ->find($cutId);
        }
        $primaryColor = $this->resolvePrimaryColor();
        $contrastColor = $this->resolveContrastColor();
        $exportFilename = $this->buildExportFilename($serviceRequests, $selectedCut, $dateRange, $format);

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.exports.obligaciones-pdf', [
                'serviceRequests' => $groupedServiceRequests,
                'dateRange' => $dateRange,
                'cut' => $selectedCut,
                'filters' => $this->getFiltersFromRequest($request),
                'primaryColor' => $primaryColor,
                'contrastColor' => $contrastColor,
                'generatedByName' => optional($request->user())->name,
            ])->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', true);

            $domPdf = $pdf->getDomPDF();
            $domPdf->render();

            $canvas = $domPdf->getCanvas();
            $font = $domPdf->getFontMetrics()->getFont('Helvetica', 'normal');
            $canvas->page_text(
                470,
                820,
                'Página {PAGE_NUM} de {PAGE_COUNT}',
                $font,
                8,
                [156 / 255, 163 / 255, 175 / 255]
            );

            return response($domPdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $exportFilename . '"',
            ]);
        }

        if ($format === 'xlsx') {
            return Excel::download(new ObligacionesExport($serviceRequests, $selectedCut, $dateRange, $primaryColor, $contrastColor), $exportFilename);
        }

        return response('Formato no válido', 400);
    }

    /**
     * Descargar evidencias (archivos adjuntos) de las obligaciones filtradas.
     */
    public function downloadEvidences(Request $request)
    {
        try {
            if (!class_exists('ZipArchive')) {
                return back()->with('error', 'La extensión ZIP no está habilitada. Contacte al administrador para habilitar php-zip.');
            }

            $serviceRequests = $this->applyFilters(ServiceRequest::query(), $request)
                ->get(['id', 'ticket_number']);

            if ($serviceRequests->isEmpty()) {
                return back()->with('warning', 'No hay solicitudes para el filtro seleccionado.');
            }

            $ticketByRequestId = $serviceRequests
                ->pluck('ticket_number', 'id')
                ->map(fn($ticket) => $this->sanitizeTicketFolder((string) $ticket));

            $evidences = ServiceRequestEvidence::query()
                ->whereIn('service_request_id', $ticketByRequestId->keys())
                ->whereNotNull('file_path')
                ->get(['id', 'service_request_id', 'file_path', 'file_original_name', 'title']);

            if ($evidences->isEmpty()) {
                return back()->with('warning', 'No se encontraron evidencias de archivo para el filtro seleccionado.');
            }

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filename = $this->buildEvidencesZipFilename($request);
            $zipPath = "{$tempDir}/{$filename}.zip";
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return back()->with('error', 'No se pudo crear el archivo ZIP de evidencias.');
            }

            $usedNames = [];
            $addedCount = 0;

            foreach ($evidences as $evidence) {
                if (empty($evidence->file_path)) {
                    continue;
                }

                $storagePath = $this->resolvePublicStoragePath((string) $evidence->file_path);
                if ($storagePath === null) {
                    continue;
                }

                $absolutePath = Storage::disk('public')->path($storagePath);
                if (!is_file($absolutePath)) {
                    continue;
                }

                $ticketFolder = $ticketByRequestId->get($evidence->service_request_id, 'sin-ticket');
                $folderInZip = "evidencias/{$ticketFolder}";
                $originalName = $evidence->file_original_name ?: basename($storagePath);
                $safeName = $this->sanitizeFileNameForZip($originalName);
                $entryName = $this->buildUniqueZipEntryName($folderInZip, $safeName, $usedNames);

                if ($zip->addFile($absolutePath, $entryName)) {
                    $addedCount++;
                }
            }

            if ($addedCount === 0) {
                $zip->close();
                @unlink($zipPath);
                return back()->with('warning', 'No se encontraron archivos físicos para descargar en las evidencias del filtro seleccionado.');
            }

            $zip->addFromString('RESUMEN_EVIDENCIAS.txt', "Archivos incluidos: {$addedCount}\nGenerado: " . now()->format('d/m/Y H:i:s') . "\n");
            $zip->close();

            return response()->download($zipPath, "{$filename}.zip")->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('Error al descargar evidencias de obligaciones: ' . $e->getMessage(), [
                'exception' => $e,
                'filters' => $this->getFiltersFromRequest($request),
            ]);

            return back()->with('error', 'Ocurrió un error al generar el ZIP de evidencias.');
        }
    }

    /**
     * Construir query con filtros y relaciones para obligaciones.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = ServiceRequest::with([
            'subService.service.family.contract',
            'requester',
            'assignedTechnician',
            'tasks.subtasks',
            'evidences'
        ]);

        return $this->applyFilters($query, $request);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        $currentCompanyId = (int) session('current_company_id');
        if ($currentCompanyId) {
            $query->where('company_id', $currentCompanyId);
        }

        $cutId = $request->get('cut_id');
        if ($cutId && $cutId !== 'all') {
            $query->whereHas('cuts', function ($q) use ($cutId) {
                $q->where('cuts.id', $cutId);
            });
        }

        return $query;
    }

    private function getFiltersFromRequest(Request $request): array
    {
        return [
            'cut_id' => $request->get('cut_id'),
        ];
    }

    private function getDateRangeFromFilters(Request $request): array
    {
        $dateFrom = null;
        $dateTo = null;

        $cutId = $request->get('cut_id');
        if ($cutId && $cutId !== 'all') {
            $cut = Cut::query()
                ->when((int) session('current_company_id'), function ($query) {
                    $query->whereHas('contract', function ($q) {
                        $q->where('company_id', (int) session('current_company_id'));
                    });
                })
                ->find($cutId);
            if ($cut) {
                $dateFrom = $cut->start_date;
                $dateTo = $cut->end_date;
            }
        }

        return [
            'start' => $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
            'end' => $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
        ];
    }

    private function buildExportFilename($serviceRequests, ?Cut $selectedCut, array $dateRange, string $format): string
    {
        $contractNumber = $selectedCut?->contract?->number;

        if (empty($contractNumber)) {
            $contractNumbers = $serviceRequests
                ->pluck('subService.service.family.contract.number')
                ->filter()
                ->unique()
                ->values();

            if ($contractNumbers->count() === 1) {
                $contractNumber = $contractNumbers->first();
            } elseif ($contractNumbers->count() > 1) {
                $contractNumber = 'varios-contratos';
            } else {
                $contractNumber = 'sin-contrato';
            }
        }

        $monthNumber = ($dateRange['start'] ?? null)
            ? Carbon::parse($dateRange['start'])->format('m')
            : now()->format('m');

        $rangeStart = ($dateRange['start'] ?? null)
            ? Carbon::parse($dateRange['start'])->format('Ymd')
            : 'sin-fecha-inicio';

        $rangeEnd = ($dateRange['end'] ?? null)
            ? Carbon::parse($dateRange['end'])->format('Ymd')
            : 'sin-fecha-fin';

        $elaborationDate = now()->format('Ymd');

        $baseName = implode('_', [
            $this->sanitizeFilenameSegmentCompact((string) $contractNumber),
            $monthNumber,
            $rangeStart . $rangeEnd,
            $elaborationDate,
        ]);

        return $baseName . '.' . $format;
    }

    private function sanitizeFilenameSegmentCompact(string $value): string
    {
        $ascii = Str::ascii($value);
        $clean = preg_replace('/[^A-Za-z0-9]+/', '', $ascii) ?? '';

        return $clean !== '' ? $clean : 'sinvalor';
    }

    private function buildEvidencesZipFilename(Request $request): string
    {
        $cutId = $request->get('cut_id');
        $cutSegment = 'todosloscortes';

        if ($cutId && $cutId !== 'all') {
            $cut = Cut::query()
                ->when((int) session('current_company_id'), function ($query) {
                    $query->whereHas('contract', function ($q) {
                        $q->where('company_id', (int) session('current_company_id'));
                    });
                })
                ->find($cutId);

            if ($cut) {
                $cutSegment = $this->sanitizeFilenameSegmentCompact($cut->name ?: ('corte-' . $cut->id));
            }
        }

        return 'evidencias_obligaciones_' . $cutSegment . '_' . now()->format('Ymd_His');
    }

    private function sanitizeTicketFolder(string $ticket): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]/', '-', $ticket) ?? '';
        $clean = trim($clean, '-');

        return $clean !== '' ? $clean : 'sin-ticket';
    }

    private function isExternalUrl(string $path): bool
    {
        return (bool) preg_match('#^https?://#i', $path);
    }

    private function normalizeStoragePath(string $path): string
    {
        $normalized = ltrim($path, '/');

        if (strpos($normalized, 'public/') === 0) {
            $normalized = substr($normalized, 7);
        }

        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, 8);
        }

        return $normalized;
    }

    private function resolvePublicStoragePath(string $filePath): ?string
    {
        if ($filePath === '' || $this->isExternalUrl($filePath)) {
            return null;
        }

        $candidates = [];
        $candidates[] = $filePath;
        $candidates[] = $this->normalizeStoragePath($filePath);

        $basename = basename($filePath);
        if ($basename) {
            $candidates[] = 'evidences/' . $basename;
        }

        $candidates = array_filter(array_unique($candidates));

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function sanitizeFileNameForZip(string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'] ?? 'archivo';

        $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '_', $basename) ?? '';
        $sanitized = trim($sanitized, '._-');
        $sanitized = $sanitized !== '' ? $sanitized : 'archivo';

        $maxLength = 140 - strlen($extension);
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }

        return $sanitized . $extension;
    }

    private function buildUniqueZipEntryName(string $folderInZip, string $safeName, array &$usedNames): string
    {
        $key = strtolower($folderInZip . '/' . $safeName);
        $counter = $usedNames[$key] ?? 0;
        $usedNames[$key] = $counter + 1;

        if ($counter === 0) {
            return $folderInZip . '/' . $safeName;
        }

        $pathInfo = pathinfo($safeName);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'] ?? 'archivo';
        $finalName = $basename . '_' . ($counter + 1) . $extension;

        return $folderInZip . '/' . $finalName;
    }

    private function resolvePrimaryColor(): string
    {
        $companyId = (int) session('current_company_id');
        if ($companyId > 0) {
            $color = Company::query()->where('id', $companyId)->value('primary_color');
            if ($this->isValidHexColor($color)) {
                return strtoupper((string) $color);
            }
        }

        return '#1E3A8A';
    }

    private function isValidHexColor(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return (bool) preg_match('/^#([A-Fa-f0-9]{6})$/', $value);
    }

    private function resolveContrastColor(): string
    {
        $companyId = (int) session('current_company_id');
        if ($companyId > 0) {
            $color = Company::query()->where('id', $companyId)->value('contrast_color');
            if ($this->isValidHexColor($color)) {
                return strtoupper((string) $color);
            }
        }

        return '#FFFFFF';
    }

    private function formatActivities(ServiceRequest $serviceRequest): string
    {
        if (!$serviceRequest->relationLoaded('tasks')) {
            return '';
        }

        $lines = [];

        foreach ($serviceRequest->tasks as $task) {
            $taskTitle = $task->title ?? 'Tarea';
            $lines[] = $taskTitle;
            $subtaskTitles = [];

            if ($task->relationLoaded('subtasks')) {
                foreach ($task->subtasks as $subtask) {
                    if (!empty($subtask->title)) {
                        $subtaskTitles[] = $subtask->title;
                    }
                }
            }

            if (!empty($subtaskTitles)) {
                foreach ($subtaskTitles as $subtaskTitle) {
                    $lines[] = '  - ' . $subtaskTitle;
                }
            }
        }

        return implode("\r\n", $lines);
    }

    private function formatProducts(ServiceRequest $serviceRequest): string
    {
        if (!$serviceRequest->relationLoaded('evidences')) {
            return '';
        }

        $names = [];
        foreach ($serviceRequest->evidences as $evidence) {
            if (empty($evidence->file_path)) {
                continue;
            }

            $names[] = $evidence->file_original_name
                ?? $evidence->file_name
                ?? $evidence->title
                ?? 'Evidencia';
        }

        return implode("\r\n", $names);
    }

    private function stripStatusPrefix(string $title): string
    {
        if ($title === '') {
            return $title;
        }

        $statuses = [
            'PENDIENTE',
            'ACEPTADA',
            'EN_PROCESO',
            'RESUELTA',
            'CERRADA',
            'CANCELADA',
            'PAUSADA',
            'REABIERTO',
            'RECHAZADA',
        ];

        $pattern = '/^.+?\s-\s(' . implode('|', $statuses) . ')\s-\s/i';

        return preg_replace($pattern, '', $title) ?? $title;
    }

    // CSV eliminado por solicitud.
}
