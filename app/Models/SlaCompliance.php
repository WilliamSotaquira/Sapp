<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaCompliance extends Model
{
    use HasFactory;

    protected $table = 'sla_compliance';

    protected $fillable = [
        'task_id',
        'service_request_id',
        'sla_id',
        'sla_response_time_minutes',
        'sla_resolution_time_minutes',
        'actual_response_time_minutes',
        'actual_resolution_time_minutes',
        'compliance_status',
        'compliance_percentage',
        'sla_deadline',
        'breach_reason',
    ];

    protected $casts = [
        'sla_response_time_minutes' => 'integer',
        'sla_resolution_time_minutes' => 'integer',
        'actual_response_time_minutes' => 'integer',
        'actual_resolution_time_minutes' => 'integer',
        'compliance_percentage' => 'decimal:2',
        'sla_deadline' => 'datetime',
    ];

    // Relaciones
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function sla()
    {
        return $this->belongsTo(ServiceLevelAgreement::class);
    }

    // Scopes
    public function scopeWithinSla($query)
    {
        return $query->where('compliance_status', 'within_sla');
    }

    public function scopeAtRisk($query)
    {
        return $query->where('compliance_status', 'at_risk');
    }

    public function scopeBreached($query)
    {
        return $query->where('compliance_status', 'breached');
    }

    // Métodos
    public function calculateCompliance()
    {
        if (!$this->sla_resolution_time_minutes || !$this->actual_resolution_time_minutes) {
            return;
        }

        $percentage = ($this->sla_resolution_time_minutes / $this->actual_resolution_time_minutes) * 100;

        $status = 'within_sla';
        if ($percentage < 100) {
            $status = 'breached';
        } elseif ($percentage < 125) {
            $status = 'at_risk';
        }

        $this->update([
            'compliance_percentage' => min($percentage, 100),
            'compliance_status' => $status,
        ]);
    }

    public function isBreached()
    {
        return $this->compliance_status === 'breached';
    }

    public function isAtRisk()
    {
        return $this->compliance_status === 'at_risk';
    }
}
