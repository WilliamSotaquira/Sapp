<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateSummaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $startDate,
        public $endDate
    ) {}

    public function handle()
    {
        // Lógica pesada de generación de reportes
        return ServiceRequest::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN status = "CERRADA" THEN 1 ELSE 0 END) as closed_requests')
            )->first();
    }
}
