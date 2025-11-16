<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'specialization',
        'years_experience',
        'skill_level',
        'max_daily_capacity_hours',
        'status',
        'availability_status',
    ];

    protected $casts = [
        'years_experience' => 'decimal:1',
        'max_daily_capacity_hours' => 'decimal:1',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function scheduleBlocks()
    {
        return $this->hasMany(ScheduleBlock::class);
    }

    public function skills()
    {
        return $this->hasMany(TechnicianSkill::class);
    }

    public function capacityRules()
    {
        return $this->hasMany(CapacityRule::class);
    }

    public function environmentAccess()
    {
        return $this->hasMany(EnvironmentAccess::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['active']);
    }

    public function scopeWithSkill($query, $skillName)
    {
        return $query->whereHas('skills', function ($q) use ($skillName) {
            $q->where('skill_name', 'like', "%{$skillName}%");
        });
    }

    // Métodos de utilidad
    public function getAvailableCapacityForDate($date)
    {
        $totalCapacity = $this->daily_capacity_minutes;

        $usedCapacity = $this->scheduleBlocks()
            ->where('block_date', $date)
            ->where('status', 'occupied')
            ->sum('duration_minutes');

        return $totalCapacity - $usedCapacity;
    }

    public function hasSkill($skillName, $minLevel = 'intermediate')
    {
        return $this->skills()
            ->where('skill_name', 'like', "%{$skillName}%")
            ->where('proficiency_level', '>=', $minLevel)
            ->exists();
    }

    public function getTasksForDate($date)
    {
        return $this->tasks()
            ->whereDate('scheduled_date', $date)
            ->orderBy('scheduled_start_time')
            ->get();
    }

    public function isAvailableAt($date, $time)
    {
        return !$this->scheduleBlocks()
            ->where('block_date', $date)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->where('status', '!=', 'available')
            ->exists();
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->user->name ?? 'N/A';
    }

    public function getEmailAttribute()
    {
        return $this->user->email ?? 'N/A';
    }
}
