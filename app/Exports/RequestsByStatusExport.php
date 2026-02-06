<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class RequestsByStatusExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return ServiceRequest::reportable()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Estado',
            'Cantidad',
            'Porcentaje'
        ];
    }

    public function map($data): array
    {
        $companyId = $this->companyId ?? (int) session('current_company_id');
        $total = ServiceRequest::reportable()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
        $percentage = $total > 0 ? round(($data->count / $total) * 100, 2) : 0;

        return [
            $data->status,
            $data->count,
            $percentage . '%'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
