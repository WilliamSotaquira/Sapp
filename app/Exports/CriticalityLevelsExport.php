<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CriticalityLevelsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ?: Carbon::now()->subDays(30);
        $this->endDate = $endDate ?: Carbon::now();
    }

    public function collection()
    {
        return ServiceRequest::reportable()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('criticality_level, COUNT(*) as count')
            ->groupBy('criticality_level')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nivel de Criticidad',
            'Cantidad',
            'Porcentaje'
        ];
    }

    public function map($data): array
    {
        $total = ServiceRequest::reportable()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
        $percentage = $total > 0 ? round(($data->count / $total) * 100, 2) : 0;

        return [
            $data->criticality_level,
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
