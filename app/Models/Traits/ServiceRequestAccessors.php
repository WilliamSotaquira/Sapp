<?php

namespace App\Models\Traits;

trait ServiceRequestAccessors
{
    public function getStepByStepEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_STEP_BY_STEP)->get();
    }

    public function getFileEvidencesAttribute()
    {
        return $this->evidences()->where('evidence_type', self::TYPE_FILE)->get();
    }

    public function getIsOverdueAttribute()
    {
        return $this->isOverdue();
    }

    public function getTimeRemainingAttribute()
    {
        return $this->getTimeRemaining();
    }

    public function getCriticalityLevelColorAttribute()
    {
        $colors = [
            self::CRITICALITY_LOW => 'success',
            self::CRITICALITY_MEDIUM => 'warning',
            self::CRITICALITY_HIGH => 'orange',
            self::CRITICALITY_CRITICAL => 'danger',
        ];

        return $colors[$this->criticality_level] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'info',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'secondary',
            self::STATUS_CANCELLED => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getEntryChannelLabelAttribute(): ?string
    {
        $options = self::getEntryChannelOptions();

        return $options[$this->entry_channel]['label'] ?? null;
    }
}
