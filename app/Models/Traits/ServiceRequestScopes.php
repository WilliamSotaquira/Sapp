<?php

namespace App\Models\Traits;

trait ServiceRequestScopes
{
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeOverdue($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('closed_at')
                ->where('resolution_deadline', '<', now())
                ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
        });
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeOfCriticality($query, $criticalityLevel)
    {
        return $query->where('criticality_level', $criticalityLevel);
    }

    public function scopeWithEvidences($query)
    {
        return $query->whereHas('evidences');
    }

    public function scopeWithoutEvidences($query)
    {
        return $query->whereDoesntHave('evidences');
    }

    public function scopeWithInconsistencies($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS)->whereNull('assigned_to');
    }
}
