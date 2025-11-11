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
            self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED],
            self::STATUS_ACCEPTED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_RESOLVED, self::STATUS_CANCELLED],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED],
            self::STATUS_CLOSED => [],
            self::STATUS_CANCELLED => [],
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
        if ($from === self::STATUS_ACCEPTED && $to === self::STATUS_IN_PROGRESS && empty($this->assigned_to)) {
            throw new \Exception('No se puede iniciar el proceso sin un técnico asignado.');
        }

        if ($from === self::STATUS_IN_PROGRESS && $to === self::STATUS_RESOLVED) {
            if (empty($this->actual_resolution_time) || empty($this->resolution_notes)) {
                throw new \Exception('Para resolver una solicitud se requiere tiempo de resolución y notas.');
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
        ];

        if (isset($timestampMap[$this->status]) && !$this->{$timestampMap[$this->status]}) {
            $this->{$timestampMap[$this->status]} = now();
        }
    }
}
