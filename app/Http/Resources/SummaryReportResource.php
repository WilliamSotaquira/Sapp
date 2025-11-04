<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SummaryReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'total_requests' => $this->total_requests,
            'closed_requests' => $this->closed_requests,
            'in_progress_requests' => $this->in_progress_requests,
            'overdue_requests' => $this->overdue_requests,
            'closure_rate' => $this->total_requests > 0
                ? round(($this->closed_requests / $this->total_requests) * 100, 2)
                : 0,
            'sla_compliance_rate' => $this->sla_compliance_rate,
            'period_days' => $this->period_days,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
