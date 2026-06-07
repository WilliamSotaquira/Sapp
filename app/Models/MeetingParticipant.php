<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_detail_id',
        'name',
        'email',
        'role',
        'user_id',
        'attended',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'role' => 'string',
    ];

    /**
     * Scope: only participants with role "organizador".
     */
    public function scopeOrganizers($query)
    {
        return $query->where('role', 'organizador');
    }

    /**
     * Scope: only participants who attended.
     */
    public function scopeAttended($query)
    {
        return $query->where('attended', true);
    }

    /**
     * Relationship: the meeting detail this participant belongs to.
     */
    public function meetingDetail()
    {
        return $this->belongsTo(MeetingDetail::class);
    }

    /**
     * Relationship: the linked user (nullable).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
