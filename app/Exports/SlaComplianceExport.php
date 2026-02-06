<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SlaComplianceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $companyId;

    public function __construct($startDate = null, $endDate = null, $companyId = null)
    {
        $this->startDate = $startDate ?: Carbon::now()->subDays(30);
        $this->endDate = $endDate ?: Carbon::now();
        $this->companyId = $companyId;
    }

    public function collection()
    {
        $companyId = $this->companyId ?? (int) session('current_company_id');
        $data = ServiceRequest::with(['sla.serviceFamily', 'subService.service'])
            ->reportable()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->groupBy(function ($request) {
                $family = $request->sla?->serviceFamily;
                $familyName = $family?->name ?? 'N/A';
                $contractNumber = $family?->contract?->number;
                return $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
            })
            ->map(function ($requests, $familyName) {
                $total = $requests->count();
                $compliant = $requests->filter(function ($request) {
                    return $this->isSlaCompliant($request);
                })->count();

                return (object) [
                    'family' => $familyName,
                    'total_requests' => $total,
                    'compliant' => $compliant,
                    'non_compliant' => $total - $compliant,
                    'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();

        return $data;
    }

    public function headings(): array
    {
        return [
            'Familia de Servicio',
            'Total Solicitudes',
            'Cumplidas',
            'Incumplidas',
            'Tasa de Cumplimiento (%)',
            'Estado'
        ];
    }

    public function map($compliance): array
    {
        $status = $compliance->compliance_rate >= 90 ? 'Excelente' :
                 ($compliance->compliance_rate >= 80 ? 'Bueno' :
                 ($compliance->compliance_rate >= 70 ? 'Regular' : 'Deficiente'));

        return [
            $compliance->family,
            $compliance->total_requests,
            $compliance->compliant,
            $compliance->non_compliant,
            $compliance->compliance_rate,
            $status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function isSlaCompliant($request): bool
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
}
