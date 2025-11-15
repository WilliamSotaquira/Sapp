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

    // ==================== TIMELINE METHODS ====================

    /**
     * Obtener los eventos del timeline de la solicitud
     */
    public function getTimelineEvents()
    {
        $events = [];

        // Evento de creación
        $events[] = [
            'type' => 'creation',
            'title' => 'Solicitud Creada',
            'description' => 'La solicitud fue creada por ' . (($this->requestedBy->name ?? $this->requester->name) ?? 'Usuario'),
            'timestamp' => $this->created_at,
            'user' => ($this->requestedBy->name ?? $this->requester->name) ?? 'Sistema',
            'icon' => 'fa-plus-circle',
            'color' => 'blue'
        ];

        // Evento de aceptación
        if ($this->accepted_at) {
            $events[] = [
                'type' => 'acceptance',
                'title' => 'Solicitud Aceptada',
                'description' => 'La solicitud fue aceptada y asignada a ' . ($this->assignee ? $this->assignee->name : 'Sin asignar'),
                'timestamp' => $this->accepted_at,
                'user' => ($this->assignee ? $this->assignee->name : 'Sistema'),
                'icon' => 'fa-check-circle',
                'color' => 'green'
            ];
        }

        // Evento de respuesta
        if ($this->responded_at) {
            $events[] = [
                'type' => 'response',
                'title' => 'Primera Respuesta',
                'description' => 'Se proporcionó la primera respuesta a la solicitud',
                'timestamp' => $this->responded_at,
                'user' => ($this->assignee ? $this->assignee->name : 'Sistema'),
                'icon' => 'fa-reply',
                'color' => 'yellow'
            ];
        }

        // Eventos de evidencias
        foreach ($this->evidences as $evidence) {
            $userName = 'Sistema';
            if ($evidence->user) {
                $userName = $evidence->user->name;
            } elseif ($evidence->uploadedBy) {
                $userName = $evidence->uploadedBy->name;
            }

            $events[] = [
                'type' => 'evidence',
                'title' => 'Evidencia Añadida',
                'description' => $evidence->title ?: 'Nueva evidencia agregada',
                'timestamp' => $evidence->created_at,
                'user' => $userName,
                'icon' => 'fa-paperclip',
                'color' => 'purple',
                'evidence_id' => $evidence->id,
                'evidence_type' => $evidence->evidence_type
            ];
        }

        // Evento de resolución
        if ($this->resolved_at) {
            $events[] = [
                'type' => 'resolution',
                'title' => 'Solicitud Resuelta',
                'description' => $this->resolution_notes ?: 'La solicitud ha sido resuelta',
                'timestamp' => $this->resolved_at,
                'user' => ($this->assignee ? $this->assignee->name : 'Sistema'),
                'icon' => 'fa-check-double',
                'color' => 'green'
            ];
        }

        // Evento de cierre
        if ($this->closed_at) {
            $events[] = [
                'type' => 'closure',
                'title' => 'Solicitud Cerrada',
                'description' => 'La solicitud ha sido cerrada',
                'timestamp' => $this->closed_at,
                'user' => 'Sistema',
                'icon' => 'fa-lock',
                'color' => 'gray'
            ];
        }

        // Eventos de pausas
        if ($this->paused_at && $this->is_paused) {
            $events[] = [
                'type' => 'pause',
                'title' => 'Solicitud Pausada',
                'description' => $this->pause_reason ?: 'La solicitud ha sido pausada',
                'timestamp' => $this->paused_at,
                'user' => 'Sistema',
                'icon' => 'fa-pause-circle',
                'color' => 'orange'
            ];
        }

        if ($this->resumed_at) {
            $events[] = [
                'type' => 'resume',
                'title' => 'Solicitud Reanudada',
                'description' => 'La solicitud ha sido reanudada',
                'timestamp' => $this->resumed_at,
                'user' => 'Sistema',
                'icon' => 'fa-play-circle',
                'color' => 'green'
            ];
        }

        // Ordenar eventos por fecha
        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return collect($events);
    }

    /**
     * Obtener el tiempo en cada estado
     */
    public function getTimeInEachStatus()
    {
        $statusTimes = [];
        $events = $this->getTimelineEvents();

        if ($events->isEmpty()) {
            return collect([]);
        }

        $currentStatus = 'PENDIENTE';
        $lastTimestamp = $this->created_at;

        foreach ($events as $event) {
            if ($lastTimestamp && $event['timestamp']) {
                $duration = $lastTimestamp->diffInMinutes($event['timestamp']);

                if (!isset($statusTimes[$currentStatus])) {
                    $statusTimes[$currentStatus] = 0;
                }
                $statusTimes[$currentStatus] += $duration;
            }

            // Cambiar estado basado en el tipo de evento
            switch ($event['type']) {
                case 'acceptance':
                    $currentStatus = 'ACEPTADA';
                    break;
                case 'response':
                    $currentStatus = 'EN_PROCESO';
                    break;
                case 'resolution':
                    $currentStatus = 'RESUELTA';
                    break;
                case 'closure':
                    $currentStatus = 'CERRADA';
                    break;
                case 'pause':
                    $currentStatus = 'PAUSADA';
                    break;
                case 'resume':
                    $currentStatus = 'EN_PROCESO';
                    break;
            }

            $lastTimestamp = $event['timestamp'];
        }

        // Tiempo en estado actual si no está cerrada
        if (!$this->closed_at && $lastTimestamp) {
            $duration = $lastTimestamp->diffInMinutes(now());
            if (!isset($statusTimes[$currentStatus])) {
                $statusTimes[$currentStatus] = 0;
            }
            $statusTimes[$currentStatus] += $duration;
        }

        // Convertir a formato amigable
        $formattedTimes = [];
        foreach ($statusTimes as $status => $minutes) {
            $formattedTimes[$status] = [
                'minutes' => $minutes,
                'formatted' => $this->formatMinutes($minutes),
                'percentage' => 0 // Se calculará después
            ];
        }

        // Calcular porcentajes
        $totalMinutes = array_sum(array_column($formattedTimes, 'minutes'));
        if ($totalMinutes > 0) {
            foreach ($formattedTimes as $status => $data) {
                $formattedTimes[$status]['percentage'] = round(($data['minutes'] / $totalMinutes) * 100, 1);
            }
        }

        return collect($formattedTimes);
    }

    /**
     * Obtener el tiempo total de resolución
     */
    public function getTotalResolutionTime()
    {
        if (!$this->resolved_at || !$this->created_at) {
            return null;
        }

        $totalMinutes = $this->created_at->diffInMinutes($this->resolved_at);

        // Restar tiempo pausado si existe
        if ($this->total_paused_minutes) {
            $totalMinutes -= $this->total_paused_minutes;
        }

        return [
            'minutes' => $totalMinutes,
            'formatted' => $this->formatMinutes($totalMinutes),
            'days' => round($totalMinutes / 1440, 1),
            'hours' => round($totalMinutes / 60, 1)
        ];
    }

    /**
     * Obtener estadísticas de tiempo
     */
    public function getTimeStatistics()
    {
        $stats = [
            'creation_to_acceptance' => null,
            'acceptance_to_response' => null,
            'response_to_resolution' => null,
            'total_resolution_time' => $this->getTotalResolutionTime(),
            'sla_compliance' => null
        ];

        if ($this->created_at && $this->accepted_at) {
            $minutes = $this->created_at->diffInMinutes($this->accepted_at);
            $stats['creation_to_acceptance'] = [
                'minutes' => $minutes,
                'formatted' => $this->formatMinutes($minutes)
            ];
        }

        if ($this->accepted_at && $this->responded_at) {
            $minutes = $this->accepted_at->diffInMinutes($this->responded_at);
            $stats['acceptance_to_response'] = [
                'minutes' => $minutes,
                'formatted' => $this->formatMinutes($minutes)
            ];
        }

        if ($this->responded_at && $this->resolved_at) {
            $minutes = $this->responded_at->diffInMinutes($this->resolved_at);
            $stats['response_to_resolution'] = [
                'minutes' => $minutes,
                'formatted' => $this->formatMinutes($minutes)
            ];
        }

        // Verificar cumplimiento SLA
        if ($this->sla && $this->resolved_at) {
            $slaMinutes = $this->sla->resolution_time * 60; // Convertir horas a minutos
            $actualMinutes = $stats['total_resolution_time']['minutes'] ?? 0;

            $stats['sla_compliance'] = [
                'sla_time' => $slaMinutes,
                'actual_time' => $actualMinutes,
                'compliant' => $actualMinutes <= $slaMinutes,
                'difference' => $actualMinutes - $slaMinutes,
                'difference_formatted' => $this->formatMinutes(abs($actualMinutes - $slaMinutes))
            ];
        }

        return $stats;
    }

    /**
     * Obtener resumen de tiempo por tipo de evento
     */
    public function getTimeSummaryByEventType()
    {
        $events = $this->getTimelineEvents();
        $summary = [
            'total_events' => $events->count(),
            'evidence_events' => $events->where('type', 'evidence')->count(),
            'status_changes' => $events->whereIn('type', ['acceptance', 'response', 'resolution', 'closure'])->count(),
            'system_events' => $events->whereIn('type', ['pause', 'resume'])->count(),
            'first_event' => $events->first(),
            'last_event' => $events->last(),
            'timeline_duration' => null
        ];

        if ($summary['first_event'] && $summary['last_event']) {
            $minutes = $summary['first_event']['timestamp']->diffInMinutes($summary['last_event']['timestamp']);
            $summary['timeline_duration'] = [
                'minutes' => $minutes,
                'formatted' => $this->formatMinutes($minutes)
            ];
        }

        return $summary;
    }

    /**
     * Formatear minutos en un formato legible
     */
    private function formatMinutes($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $hours . 'h ' . ($remainingMinutes > 0 ? $remainingMinutes . 'min' : '');
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $days . 'd ' . ($remainingHours > 0 ? $remainingHours . 'h' : '');
    }
}
