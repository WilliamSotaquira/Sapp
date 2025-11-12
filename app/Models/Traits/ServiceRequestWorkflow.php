<?php

namespace App\Models\Traits;

trait ServiceRequestWorkflow
{
    protected function validateWorkflowRules()
    {
        // Validar estado EN_PROCESO sin técnico
        if ($this->status === self::STATUS_IN_PROGRESS && empty($this->assigned_to)) {
            throw new \Exception('No se puede establecer el estado EN_PROCESO sin un técnico asignado.');
        }

        // Validar transiciones de estado
        if ($this->isDirty('status')) {
            $this->validateStateTransition();
        }

        // Auto-asignar al aceptar
        if ($this->isDirty('status') && $this->status === self::STATUS_ACCEPTED && empty($this->assigned_to)) {
            $this->assigned_to = auth()->id() ?? throw new \Exception('No se puede aceptar una solicitud sin asignar un técnico.');
        }

        $this->updateStateTimestamps();
    }

    protected function validateStateTransition()
    {
        $originalStatus = $this->getOriginal('status');
        $newStatus = $this->status;

        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED, self::STATUS_PAUSED],
            self::STATUS_ACCEPTED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_PAUSED],
            self::STATUS_IN_PROGRESS => [self::STATUS_RESOLVED, self::STATUS_CANCELLED, self::STATUS_PAUSED],
            self::STATUS_PAUSED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_RESOLVED, self::STATUS_CLOSED],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED, self::STATUS_REOPENED],
            self::STATUS_CLOSED => [self::STATUS_REOPENED],
            self::STATUS_CANCELLED => [],
            self::STATUS_REOPENED => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED],
        ];

        if ($originalStatus === $newStatus) {
            return;
        }

        if (!in_array($newStatus, $validTransitions[$originalStatus] ?? [])) {
            throw new \Exception("Transición de estado inválida: {$originalStatus} → {$newStatus}");
        }

        $this->validateSpecificTransitions($originalStatus, $newStatus);
    }

    protected function validateSpecificTransitions($from, $to)
    {
        // Validar transición a EN_PROCESO
        if ($from === self::STATUS_ACCEPTED && $to === self::STATUS_IN_PROGRESS && empty($this->assigned_to)) {
            throw new \Exception('No se puede iniciar el proceso sin un técnico asignado.');
        }

        // Validar transición a RESUELTA
        if ($from === self::STATUS_IN_PROGRESS && $to === self::STATUS_RESOLVED) {
            if (empty($this->actual_resolution_time) || empty($this->resolution_notes)) {
                throw new \Exception('Para resolver una solicitud se requiere tiempo de resolución y notas.');
            }
        }

        // Validar transición a PAUSADA
        if ($to === self::STATUS_PAUSED) {
            if (empty($this->pause_reason)) {
                throw new \Exception('Para pausar una solicitud se requiere especificar una razón.');
            }
        }

        // Validar transición desde PAUSADA
        if ($from === self::STATUS_PAUSED && $to === self::STATUS_IN_PROGRESS) {
            $this->resumed_at = now();

            // Calcular tiempo total pausado
            if ($this->paused_at) {
                $pausedMinutes = $this->paused_at->diffInMinutes(now());
                $this->total_paused_minutes = ($this->total_paused_minutes ?? 0) + $pausedMinutes;
            }
        }
    }

    protected function updateStateTimestamps()
    {
        if (!$this->isDirty('status')) return;

        $timestampMap = [
            self::STATUS_ACCEPTED => 'accepted_at',
            self::STATUS_RESOLVED => 'resolved_at',
            self::STATUS_CLOSED => 'closed_at',
            self::STATUS_PAUSED => 'paused_at',
        ];

        if (isset($timestampMap[$this->status]) && !$this->{$timestampMap[$this->status]}) {
            $this->{$timestampMap[$this->status]} = now();
        }
    }

    /**
     * Métodos auxiliares para pausar/reanudar
     */
    public function pause($reason)
    {
        $this->status = self::STATUS_PAUSED;
        $this->pause_reason = $reason;
        $this->is_paused = true;
        $this->save();
    }

    public function resume()
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->is_paused = false;
        $this->resumed_at = now();

        // Calcular tiempo pausado
        if ($this->paused_at) {
            $pausedMinutes = $this->paused_at->diffInMinutes(now());
            $this->total_paused_minutes = ($this->total_paused_minutes ?? 0) + $pausedMinutes;
        }

        $this->save();
    }

    /**
     * Verificar si puede ser pausada
     */
    public function canBePaused()
    {
        $pausableStatuses = [
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS
        ];

        return in_array($this->status, $pausableStatuses);
    }

    /**
     * Verificar si puede ser reanudada
     */
    public function canBeResumed()
    {
        return $this->status === self::STATUS_PAUSED;
    }
}
