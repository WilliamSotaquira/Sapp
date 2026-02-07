<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Cut;
use App\Exports\ObligacionesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

        $cuts = Cut::query()
            ->orderByDesc('start_date')
            ->when((int) session('current_company_id'), function ($query) {
                $query->whereHas('contract', function ($q) {
                    $q->where('company_id', (int) session('current_company_id'));
                });
            })
            ->get(['id', 'name', 'start_date', 'end_date']);

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
        $exportFilename = $this->buildExportFilename($serviceRequests, $selectedCut, $dateRange, $format);

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.exports.obligaciones-pdf', [
                'serviceRequests' => $groupedServiceRequests,
                'dateRange' => $dateRange,
                'cut' => $selectedCut,
                'filters' => $this->getFiltersFromRequest($request),
            ])->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', true);

            return $pdf->download($exportFilename);
        }

        if ($format === 'xlsx') {
            return Excel::download(new ObligacionesExport($serviceRequests, $selectedCut, $dateRange), $exportFilename);
        }

        return response('Formato no válido', 400);
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
