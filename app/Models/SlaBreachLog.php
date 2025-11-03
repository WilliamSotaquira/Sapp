<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaBreachLog extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id', 'breach_type', 'breach_minutes', 'notes'];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
