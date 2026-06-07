<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestAssignmentHistory extends Model
{
    use HasFactory;

    protected $table = 'service_request_assignment_history';

    protected $fillable = [
        'service_request_id',
        'previous_assignee_id',
        'new_assignee_id',
        'reason',
        'changed_by',
    ];

    /**
     * Relación con la solicitud de servicio.
     */
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Relación con el usuario asignado anteriormente.
     */
    public function previousAssignee()
    {
        return $this->belongsTo(User::class, 'previous_assignee_id');
    }

    /**
     * Relación con el nuevo usuario asignado.
     */
    public function newAssignee()
    {
        return $this->belongsTo(User::class, 'new_assignee_id');
    }

    /**
     * Relación con el usuario que realizó el cambio.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
