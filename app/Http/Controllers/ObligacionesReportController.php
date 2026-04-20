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
        $filters = $this->getFiltersFromRequest($request);
        $availableStatuses = ServiceRequest::getStatusOptions();

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
        $filters['cut_id'] = $cutId;

        // Ordenar por fecha descendente
        $allServiceRequests = $this->buildFilteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por servicio
        $serviceRequests = $allServiceRequests
            ->groupBy(function ($sr) {
                $family = $sr->subService?->service?->family;

                return $family?->name ?? 'Sin Familia';
            })
            ->sortBy(function ($items) {
                return (int) ($items->first()?->subService?->service?->family?->sort_order ?? PHP_INT_MAX);
            });

        $statsBaseQuery = $this->applyFilters(ServiceRequest::query(), $request);

        // Calcular estadísticas
        $stats = [
            'total_obligaciones' => (clone $statsBaseQuery)->count(),
            'total_actividades' => Task::whereIn('service_request_id', (clone $statsBaseQuery)->select('id'))->count(),
            'obligaciones_pendientes' => (clone $statsBaseQuery)->where('status', 'PENDIENTE')->count(),
            'obligaciones_en_progreso' => (clone $statsBaseQuery)->where('status', 'EN_PROCESO')->count(),
            'obligaciones_cerradas' => (clone $statsBaseQuery)->where('status', ServiceRequest::STATUS_CLOSED)->count(),
            'total_productos' => ServiceRequestEvidence::query()
                ->whereIn('service_request_id', (clone $statsBaseQuery)->select('id'))
                ->count(),
            'familias' => $serviceRequests->count(),
        ];

        $familySummaries = $serviceRequests->map(function ($items, $serviceName) {
            $slugBase = Str::slug($serviceName);
            $first = $items->first();
            $productCount = $items->sum(fn ($sr) => (int) $sr->evidences->count());
            $taskCount = $items->sum(fn ($sr) => (int) $sr->tasks->count());

            return [
                'name' => $serviceName,
                'anchor' => 'family-' . ($slugBase !== '' ? $slugBase : 'sin-familia'),
                'count' => $items->count(),
                'products' => $productCount,
                'tasks' => $taskCount,
                'description' => $first?->subService?->service?->family?->description,
            ];
        })->values();

        $familyExportRequirements = $serviceRequests->map(function ($items, $serviceName) {
            $family = $items->first()?->subService?->service?->family;

            return [
                'id' => (int) ($family?->id ?? 0),
                'name' => $serviceName,
            ];
        })->filter(fn ($family) => $family['id'] > 0)->values();

        return view('reports.obligaciones.index', [
            'pageTitle' => 'Reporte de Obligaciones',
            'serviceRequests' => $serviceRequests,
            'stats' => $stats,
            'familySummaries' => $familySummaries,
            'familyExportRequirements' => $familyExportRequirements,
            'statuses' => $availableStatuses,
            'cuts' => $cuts,
            'filters' => $filters,
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

        $groupedServiceRequests = $serviceRequests
            ->groupBy(function ($sr) {
                $family = $sr->subService?->service?->family;

                return $family?->name ?? 'Sin Familia';
            })
            ->sortBy(function ($items) {
                return (int) ($items->first()?->subService?->service?->family?->sort_order ?? PHP_INT_MAX);
            });

        $linkValidationError = $this->validateFamilyCloudLinks($serviceRequests, $request);
        if ($linkValidationError !== null) {
            return back()->withInput()->with('error', $linkValidationError);
        }

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

            $serviceRequests = $this->applyFilters(
                ServiceRequest::query()->with(['subService.service.family']),
                $request
            )->get();

            if ($serviceRequests->isEmpty()) {
                return back()->with('warning', 'No hay solicitudes para el filtro seleccionado.');
            }

            $foldersByRequestId = $serviceRequests->mapWithKeys(function (ServiceRequest $serviceRequest) {
                $familyFolder = $this->buildEvidenceFamilyFolderName($serviceRequest);
                $requestFolder = $this->buildEvidenceRequestFolderName($serviceRequest);

                return [
                    $serviceRequest->id => [
                        'family' => $familyFolder,
                        'request' => $requestFolder,
                    ],
                ];
            });

            $evidences = ServiceRequestEvidence::query()
                ->whereIn('service_request_id', $foldersByRequestId->keys())
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

                $requestFolders = $foldersByRequestId->get($evidence->service_request_id, [
                    'family' => 'sin-familia',
                    'request' => 'sin-ticket',
                ]);

                $familyFolder = $requestFolders['family'] ?? 'sin-familia';
                $requestFolder = $requestFolders['request'] ?? 'sin-ticket';
                $folderInZip = "evidencias/{$familyFolder}/{$requestFolder}";
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
            'requestedBy',
            'assignedTechnician',
            'tasks.subtasks',
            'evidences'
        ]);

        return $this->applyFilters($query, $request);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        $currentCompanyId = (int) session('current_company_id');
        $availableStatuses = ServiceRequest::getStatusOptions();

        if ($currentCompanyId) {
            $query->where('company_id', $currentCompanyId);
        }

        $cutId = $request->get('cut_id');
        if ($cutId && $cutId !== 'all') {
            $query->whereHas('cuts', function ($q) use ($cutId) {
                $q->where('cuts.id', $cutId);
            });
        }

        $statuses = $this->resolveSelectedStatuses($request);
        if (count($statuses) > 0 && count($statuses) < count($availableStatuses)) {
            $query->whereIn('status', $statuses);
        }

        $search = Str::of((string) $request->get('q', ''))->squish()->limit(120, '');
        if ($search !== '') {
            $searchTerm = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $search) . '%';

            $query->where(function ($inner) use ($searchTerm) {
                $inner->where('ticket_number', 'like', $searchTerm)
                    ->orWhere('title', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                    ->orWhereHas('requester', function ($requesterQuery) use ($searchTerm) {
                        $requesterQuery->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm)
                            ->orWhere('department', 'like', $searchTerm);
                    })
                    ->orWhereHas('requestedBy', function ($requestedByQuery) use ($searchTerm) {
                        $requestedByQuery->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    })
                    ->orWhereHas('subService', function ($subServiceQuery) use ($searchTerm) {
                        $subServiceQuery->where('name', 'like', $searchTerm)
                            ->orWhereHas('service', function ($serviceQuery) use ($searchTerm) {
                                $serviceQuery->where('name', 'like', $searchTerm)
                                    ->orWhereHas('family', function ($familyQuery) use ($searchTerm) {
                                        $familyQuery->where('name', 'like', $searchTerm)
                                            ->orWhere('description', 'like', $searchTerm);
                                    });
                            });
                    });
            });
        }

        return $query;
    }

    private function getFiltersFromRequest(Request $request): array
    {
        return [
            'cut_id' => $request->get('cut_id'),
            'status' => $this->resolveSelectedStatuses($request)[0] ?? ServiceRequest::STATUS_CLOSED,
            'statuses' => $this->resolveSelectedStatuses($request),
            'q' => (string) Str::of((string) $request->get('q', ''))->squish()->limit(120, ''),
        ];
    }

    private function resolveSelectedStatuses(Request $request): array
    {
        $rawStatuses = $request->input('statuses', []);

        if (!is_array($rawStatuses)) {
            $rawStatuses = [$rawStatuses];
        }

        if (count($rawStatuses) === 0) {
            $legacyStatus = $request->get('status');
            if ($legacyStatus !== null && trim((string) $legacyStatus) !== '') {
                $rawStatuses = [$legacyStatus];
            }
        }

        $availableStatuses = ServiceRequest::getStatusOptions();
        $statuses = collect($rawStatuses)
            ->map(fn ($status) => strtoupper(trim((string) $status)))
            ->filter(fn ($status) => $status !== '' && array_key_exists($status, $availableStatuses))
            ->unique()
            ->values()
            ->all();

        if (count($statuses) === 0) {
            return [ServiceRequest::STATUS_CLOSED];
        }

        return $statuses;
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

    private function sanitizeZipFolderSegment(string $value, string $fallback = 'sin-valor', int $maxLength = 80): string
    {
        $ascii = Str::ascii($value);
        $clean = preg_replace('/[^A-Za-z0-9._ -]+/', '-', $ascii) ?? '';
        $clean = preg_replace('/[\s-]+/', '-', trim($clean)) ?? '';
        $clean = trim($clean, '-._ ');

        if ($clean === '') {
            return $fallback;
        }

        if (strlen($clean) > $maxLength) {
            $clean = substr($clean, 0, $maxLength);
            $clean = rtrim($clean, '-._ ');
        }

        return $clean !== '' ? $clean : $fallback;
    }

    private function buildEvidenceFamilyFolderName(ServiceRequest $serviceRequest): string
    {
        $family = $serviceRequest->subService?->service?->family;
        $familyName = $family?->name ?: 'Sin Familia';
        $familyId = (int) ($family?->id ?? 0);
        $familyTitle = Str::ascii($familyName);
        $familyTitle = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $familyTitle) ?? '';
        $familyTitle = preg_replace('/\s+/', ' ', trim($familyTitle)) ?? '';

        if ($familyTitle === '') {
            $familyTitle = 'Sin Familia';
        }

        return max(0, $familyId) . '. ' . $familyTitle;
    }

    private function buildEvidenceRequestFolderName(ServiceRequest $serviceRequest): string
    {
        $ticketSegment = $this->sanitizeTicketFolder((string) ($serviceRequest->ticket_number ?: 'sin-ticket'));
        $titleSegment = $this->sanitizeZipFolderSegment((string) ($serviceRequest->title ?: ''), '', 24);

        if ($titleSegment !== '') {
            return $ticketSegment . '__' . $titleSegment;
        }

        return $ticketSegment;
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

        $maxLength = max(20, 72 - strlen($extension));
        if (strlen($sanitized) > $maxLength) {
            $hash = substr(md5($filename), 0, 6);
            $trimmedLength = max(12, $maxLength - 7);
            $sanitized = rtrim(substr($sanitized, 0, $trimmedLength), '._-') . '_' . $hash;
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
        $suffix = '_' . ($counter + 1);
        $maxBaseLength = max(12, 72 - strlen($extension) - strlen($suffix));
        if (strlen($basename) > $maxBaseLength) {
            $basename = rtrim(substr($basename, 0, $maxBaseLength), '._-');
        }
        $finalName = $basename . $suffix . $extension;

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

    private function validateFamilyCloudLinks($serviceRequests, Request $request): ?string
    {
        $familyLinks = $request->input('family_links', []);
        if (!is_array($familyLinks)) {
            $familyLinks = [];
        }

        $requiredFamilies = $serviceRequests
            ->map(function (ServiceRequest $serviceRequest) {
                $family = $serviceRequest->subService?->service?->family;
                return [
                    'id' => (int) ($family?->id ?? 0),
                    'label' => $family?->name ?? 'Sin Familia',
                ];
            })
            ->filter(fn ($family) => $family['id'] > 0)
            ->unique('id')
            ->values();

        if ($requiredFamilies->isEmpty()) {
            return null;
        }

        $missingFamilies = [];
        $invalidFamilies = [];

        foreach ($requiredFamilies as $family) {
            $rawLink = trim((string) ($familyLinks[$family['id']] ?? ''));

            if ($rawLink === '') {
                $missingFamilies[] = $family['label'];
                continue;
            }

            if (!$this->isValidAbsoluteUrl($rawLink)) {
                $invalidFamilies[] = $family['label'];
            }
        }

        if (count($missingFamilies) === 0 && count($invalidFamilies) === 0) {
            return null;
        }

        $parts = ['Antes de generar el informe debe registrar un enlace absoluto del directorio en la nube por cada familia incluida en el reporte.'];

        if (count($missingFamilies) > 0) {
            $parts[] = 'Falta enlace en: ' . implode(', ', $missingFamilies) . '.';
        }

        if (count($invalidFamilies) > 0) {
            $parts[] = 'El enlace debe ser absoluto y válido en: ' . implode(', ', $invalidFamilies) . '.';
        }

        return implode(' ', $parts);
    }

    private function isValidAbsoluteUrl(string $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return (bool) preg_match('#^https?://#i', $value);
    }

    // CSV eliminado por solicitud.
}
