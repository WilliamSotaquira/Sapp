<?php

namespace App\Models\Traits;

trait ServiceRequestUtilities
{
    public function isOverdue()
    {
        if ($this->closed_at || $this->status === self::STATUS_CLOSED) {
            return false;
        }

        return $this->resolution_deadline && now()->greaterThan($this->resolution_deadline);
    }

    public function getTimeRemaining()
    {
        if ($this->closed_at || !$this->resolution_deadline) {
            return null;
        }

        $now = now();
        if ($now->greaterThan($this->resolution_deadline)) {
            return 'Vencido';
        }

        return $this->formatDuration($now->diff($this->resolution_deadline));
    }

    public function formatDuration($duration)
    {
        if (!$duration) return '0 minutos';

        $parts = [];
        if ($duration->days > 0) $parts[] = $duration->days . ' día' . ($duration->days > 1 ? 's' : '');
        if ($duration->h > 0) $parts[] = $duration->h . ' hora' . ($duration->h > 1 ? 's' : '');
        if ($duration->i > 0) $parts[] = $duration->i . ' minuto' . ($duration->i > 1 ? 's' : '');

        return implode(', ', $parts) ?: '0 minutos';
    }

    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function canBeClosed()
    {
        return $this->status === self::STATUS_RESOLVED && $this->status !== self::STATUS_CLOSED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaused()
    {
        return $this->is_paused === true;
    }

    public function hasAnyEvidenceForResolution()
    {
        return $this->evidences()
            ->whereIn('evidence_type', [self::TYPE_STEP_BY_STEP, self::TYPE_FILE])
            ->exists();
    }

    public function hasStepByStepEvidences()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_STEP_BY_STEP)->exists();
    }

    public function hasFileEvidences()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_FILE)->exists();
    }
}
