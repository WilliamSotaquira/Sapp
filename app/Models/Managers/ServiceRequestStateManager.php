<?php

namespace App\Models\Managers;

trait ServiceRequestStateManager
{
    // =============================================
    // MÉTODOS PARA GESTIÓN DE ESTADOS Y PAUSAS
    // =============================================

    /**
     * Pausar la solicitud
     */
    public function pause($reason = null)
    {
        $this->update([
            'is_paused' => true,
            'status' => 'PAUSADA',
            'pause_reason' => $reason,
            'paused_at' => now(),
        ]);
    }

    /**
     * Reanudar la solicitud
     */
    public function resume()
    {
        $pausedMinutes = 0;
        if ($this->paused_at) {
            $pausedMinutes = now()->diffInMinutes($this->paused_at);
        }

        $this->update([
            'is_paused' => false,
            'status' => 'EN_PROCESO',
            'resumed_at' => now(),
            'total_paused_minutes' => $this->total_paused_minutes + $pausedMinutes,
        ]);
    }

    /**
     * Verificar si está pausada
     */
    public function isPaused()
    {
        return $this->is_paused && $this->status === 'PAUSADA';
    }

    /**
     * Obtener el tiempo total pausado formateado
     */
    public function getTotalPausedTimeFormatted()
    {
        $minutes = $this->total_paused_minutes;

        if ($this->isPaused() && $this->paused_at) {
            $minutes += now()->diffInMinutes($this->paused_at);
        }

        return $this->formatMinutesToReadable($minutes);
    }

    /**
     * Calcular fechas límite considerando pausas
     */
    public function getAdjustedDeadlines()
    {
        $pausedMinutes = $this->total_paused_minutes;

        if ($this->isPaused() && $this->paused_at) {
            $pausedMinutes += now()->diffInMinutes($this->paused_at);
        }

        return [
            'acceptance_deadline' => $this->acceptance_deadline?->addMinutes($pausedMinutes),
            'response_deadline' => $this->response_deadline?->addMinutes($pausedMinutes),
            'resolution_deadline' => $this->resolution_deadline?->addMinutes($pausedMinutes),
        ];
    }

    /**
     * Obtener color del estado
     */
    public function getStatusColor()
    {
        $colors = [
            'PENDIENTE' => 'warning',
            'ASIGNADA' => 'info',
            'EN_PROCESO' => 'primary',
            'PAUSADA' => 'secondary',
            'RESUELTA' => 'success',
            'CERRADA' => 'dark',
            'CANCELADA' => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Obtener texto del estado
     */
    public function getStatusText()
    {
        return $this->status;
    }

    /**
     * Obtener color de la prioridad
     */
    public function getPriorityColor()
    {
        $colors = [
            'BAJA' => 'success',
            'MEDIA' => 'warning',
            'ALTA' => 'danger',
            'CRITICA' => 'dark'
        ];

        return $colors[$this->criticality_level] ?? 'secondary';
    }

    /**
     * Obtener texto de la prioridad
     */
    public function getPriorityText()
    {
        return $this->criticality_level;
    }

    /**
     * Formatear minutos a formato legible
     */
    private function formatMinutesToReadable($minutes)
    {
        if ($minutes < 60) {
            return "{$minutes} minuto" . ($minutes > 1 ? 's' : '');
        } elseif ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            if ($mins > 0) {
                return "{$hours} hora" . ($hours > 1 ? 's' : '') . " {$mins} minuto" . ($mins > 1 ? 's' : '');
            }
            return "{$hours} hora" . ($hours > 1 ? 's' : '');
        } else {
            $days = floor($minutes / 1440);
            $hours = floor(($minutes % 1440) / 60);
            if ($hours > 0) {
                return "{$days} día" . ($days > 1 ? 's' : '') . " {$hours} hora" . ($hours > 1 ? 's' : '');
            }
            return "{$days} día" . ($days > 1 ? 's' : '');
        }
    }
}
