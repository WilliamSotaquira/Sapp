<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MeetingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'scheduled_date',
        'start_time',
        'expected_duration_minutes',
        'location',
        'virtual_meeting_url',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_time' => 'string',
    ];

    // ==================== ACCESSORS ====================

    /**
     * Get the computed end time based on start_time + expected_duration_minutes.
     */
    public function getEndTimeAttribute(): ?string
    {
        if (empty($this->start_time) || empty($this->expected_duration_minutes)) {
            return null;
        }

        return Carbon::parse($this->start_time)
            ->addMinutes($this->expected_duration_minutes)
            ->format('H:i:s');
    }

    // ==================== RELATIONSHIPS ====================

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
