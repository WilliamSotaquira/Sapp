<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'sla_id',
        'sub_service_id',
        'requested_by',
        'assigned_to',
        'title',
        'description',
        'criticality_level',
        'status',
        'acceptance_deadline',
        'response_deadline',
        'resolution_deadline',
        'accepted_at',
        'responded_at',
        'resolved_at',
        'closed_at',
        'resolution_notes',
        'satisfaction_score',
        'is_paused',
        'pause_reason',
        'paused_at',
        'resumed_at',
        'total_paused_minutes'
    ];

    protected $casts = [
        'acceptance_deadline' => 'datetime',
        'response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'is_paused' => 'boolean',
    ];

    // =============================================
    // RELACIONES EXISTENTES
    // =============================================

    /**
     * Relación con Sub-Servicio
     */
    public function subService()
    {
        return $this->belongsTo(SubService::class, 'sub_service_id');
    }

    /**
     * Relación con SLA
     */
    public function sla()
    {
        return $this->belongsTo(ServiceLevelAgreement::class, 'sla_id');
    }

    /**
     * Relación con Usuario Solicitante
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Relación con Usuario Asignado
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relación con Logs de Incumplimiento SLA
     */
    public function breachLogs()
    {
        return $this->hasMany(SlaBreachLog::class);
    }

    // =============================================
    // RELACIONES PARA EVIDENCIAS
    // =============================================

    /**
     * Relación con todas las evidencias
     */
    public function evidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id');
    }

    /**
     * Relación con evidencias paso a paso
     */
    public function stepByStepEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
            ->where('evidence_type', 'PASO_A_PASO')
            ->orderBy('step_number');
    }

    /**
     * Relación con evidencias de archivo
     */
    public function fileEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
            ->where('evidence_type', 'ARCHIVO');
    }

    /**
     * Relación con evidencias de comentario
     */
    public function commentEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
            ->where('evidence_type', 'COMENTARIO');
    }

    /**
     * Relación con evidencias del sistema
     */
    public function systemEvidences()
    {
        return $this->hasMany(ServiceRequestEvidence::class, 'service_request_id')
            ->where('evidence_type', 'SISTEMA');
    }

    // =============================================
    // ACCESSORS PARA CONTAR EVIDENCIAS
    // =============================================

    /**
     * Contar todas las evidencias
     */
    public function getEvidencesCountAttribute()
    {
        return $this->evidences()->count();
    }

    /**
     * Contar evidencias paso a paso
     */
    public function getStepByStepEvidencesCountAttribute()
    {
        return $this->stepByStepEvidences()->count();
    }

    /**
     * Contar evidencias de archivo
     */
    public function getFileEvidencesCountAttribute()
    {
        return $this->fileEvidences()->count();
    }

    /**
     * Contar evidencias de comentario
     */
    public function getCommentEvidencesCountAttribute()
    {
        return $this->commentEvidences()->count();
    }

    // =============================================
    // MÉTODOS PARA VALIDACIÓN DE EVIDENCIAS
    // =============================================

    /**
     * Verificar si puede ser resuelta (tiene evidencias paso a paso)
     */
    public function canBeResolved()
    {
        return $this->status === 'EN_PROCESO' && $this->stepByStepEvidences()->exists();
    }

    /**
     * Verificar si tiene evidencias suficientes para resolver
     */
    public function hasRequiredEvidences()
    {
        return $this->stepByStepEvidences()->count() > 0;
    }

    /**
     * Obtener el siguiente número de paso disponible
     */
    public function getNextStepNumber()
    {
        $lastStep = $this->stepByStepEvidences()->max('step_number');
        return $lastStep ? $lastStep + 1 : 1;
    }

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

    // =============================================
    // MÉTODOS PARA LÍNEA DE TIEMPO (NUEVOS)
    // =============================================

    /**
     * Obtener todos los eventos de la línea de tiempo
     */
    public function getTimelineEvents()
    {
        $events = [];

        // Evento de creación
        $events[] = [
            'event' => 'SOLICITUD CREADA',
            'timestamp' => $this->created_at,
            'user' => $this->requester,
            'description' => 'Solicitud registrada en el sistema',
            'status' => 'created',
            'icon' => 'plus-circle',
            'color' => 'primary'
        ];

        // Evento de asignación (si está asignada)
        if ($this->assigned_to) {
            $events[] = [
                'event' => 'ASIGNADA A TÉCNICO',
                'timestamp' => $this->updated_at,
                'user' => $this->assignee,
                'description' => 'Solicitud asignada al técnico responsable',
                'status' => 'assigned',
                'icon' => 'user-check',
                'color' => 'info'
            ];
        }

        // Evento de aceptación
        if ($this->accepted_at) {
            $events[] = [
                'event' => 'SOLICITUD ACEPTADA',
                'timestamp' => $this->accepted_at,
                'user' => $this->assignee,
                'description' => 'Solicitud aceptada por el técnico asignado',
                'status' => 'accepted',
                'icon' => 'check-circle',
                'color' => 'success'
            ];
        }

        // Evento de respuesta
        if ($this->responded_at) {
            $events[] = [
                'event' => 'RESPUESTA INICIAL',
                'timestamp' => $this->responded_at,
                'user' => $this->assignee,
                'description' => 'Primera respuesta proporcionada al usuario',
                'status' => 'responded',
                'icon' => 'reply',
                'color' => 'info'
            ];
        }

        // Evento de pausa
        if ($this->paused_at) {
            $events[] = [
                'event' => 'SOLICITUD PAUSADA',
                'timestamp' => $this->paused_at,
                'user' => $this->assignee,
                'description' => $this->pause_reason ? "Solicitud pausada: {$this->pause_reason}" : 'Solicitud pausada temporalmente',
                'status' => 'paused',
                'icon' => 'pause-circle',
                'color' => 'warning'
            ];
        }

        // Evento de reanudación
        if ($this->resumed_at) {
            $events[] = [
                'event' => 'SOLICITUD REANUDADA',
                'timestamp' => $this->resumed_at,
                'user' => $this->assignee,
                'description' => 'Solicitud reanudada después de pausa',
                'status' => 'resumed',
                'icon' => 'play-circle',
                'color' => 'success'
            ];
        }

        // Evento de resolución
        if ($this->resolved_at) {
            $events[] = [
                'event' => 'SOLICITUD RESUELTA',
                'timestamp' => $this->resolved_at,
                'user' => $this->assignee,
                'description' => $this->resolution_notes ? "Resuelta: {$this->resolution_notes}" : 'Solicitud marcada como resuelta',
                'status' => 'resolved',
                'icon' => 'check-double',
                'color' => 'success'
            ];
        }

        // Evento de cierre
        if ($this->closed_at) {
            $events[] = [
                'event' => 'SOLICITUD CERRADA',
                'timestamp' => $this->closed_at,
                'user' => $this->assignee,
                'description' => 'Solicitud cerrada y finalizada' . ($this->satisfaction_score ? " - Satisfacción: {$this->satisfaction_score}/5" : ''),
                'status' => 'closed',
                'icon' => 'lock',
                'color' => 'dark'
            ];
        }

        // Agregar eventos de evidencias - VERSIÓN SEGURA
        $this->loadEvidencesForTimeline($events);

        // Agregar eventos de incumplimiento SLA
        foreach ($this->breachLogs()->orderBy('created_at')->get() as $breach) {
            $events[] = [
                'event' => 'INCUMPLIMIENTO SLA',
                'timestamp' => $breach->created_at,
                'user' => null,
                'description' => "Incumplimiento en {$breach->breach_type}: {$breach->description}",
                'status' => 'breach',
                'icon' => 'exclamation-triangle',
                'color' => 'danger'
            ];
        }

        // Ordenar eventos por fecha
        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $events;
    }

    /**
     * Método auxiliar para cargar evidencias de forma segura
     */
    private function loadEvidencesForTimeline(&$events)
    {
        try {
            // Verificar si la relación existe y tiene datos
            if (!$this->relationLoaded('evidences')) {
                $this->load('evidences.user');
            }

            if ($this->evidences->isNotEmpty()) {
                foreach ($this->evidences->sortBy('created_at') as $evidence) {
                    $user = $evidence->relationLoaded('user') ? $evidence->user : null;

                    $events[] = [
                        'event' => 'EVIDENCIA AGREGADA',
                        'timestamp' => $evidence->created_at,
                        'user' => $user,
                        'description' => $this->getEvidenceDescription($evidence),
                        'status' => 'evidence',
                        'icon' => $this->getEvidenceIcon($evidence->evidence_type),
                        'color' => $this->getEvidenceColor($evidence->evidence_type),
                        'evidence_type' => $evidence->evidence_type
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log del error pero continuar sin evidencias
            \Log::warning('Error loading evidences for timeline in request ' . $this->id . ': ' . $e->getMessage());
        }
    }
    /**
     * Obtener descripción para evidencias
     */
    private function getEvidenceDescription($evidence)
    {
        switch ($evidence->evidence_type) {
            case 'PASO_A_PASO':
                return "Paso {$evidence->step_number}: {$evidence->description}";
            case 'ARCHIVO':
                return "Archivo adjunto: {$evidence->file_name}";
            case 'COMENTARIO':
                return "Comentario: {$evidence->description}";
            case 'SISTEMA':
                return "Evidencia del sistema: {$evidence->description}";
            default:
                return "Evidencia: {$evidence->description}";
        }
    }

    /**
     * Obtener icono para tipo de evidencia
     */
    private function getEvidenceIcon($evidenceType)
    {
        $icons = [
            'PASO_A_PASO' => 'list-ol',
            'ARCHIVO' => 'paperclip',
            'COMENTARIO' => 'comment',
            'SISTEMA' => 'cog'
        ];

        return $icons[$evidenceType] ?? 'file-alt';
    }

    /**
     * Obtener color para tipo de evidencia
     */
    private function getEvidenceColor($evidenceType)
    {
        $colors = [
            'PASO_A_PASO' => 'primary',
            'ARCHIVO' => 'info',
            'COMENTARIO' => 'secondary',
            'SISTEMA' => 'dark'
        ];

        return $colors[$evidenceType] ?? 'secondary';
    }

    /**
     * Calcular tiempo en cada estado
     */
    public function getTimeInEachStatus()
    {
        $times = [];
        $events = $this->getTimelineEvents();

        for ($i = 0; $i < count($events) - 1; $i++) {
            $currentEvent = $events[$i];
            $nextEvent = $events[$i + 1];

            $duration = $currentEvent['timestamp']->diff($nextEvent['timestamp']);
            $totalMinutes = $duration->i + ($duration->h * 60) + ($duration->days * 24 * 60);

            $times[$currentEvent['status']] = [
                'duration' => $duration,
                'total_minutes' => $totalMinutes,
                'hours' => $duration->h + ($duration->days * 24),
                'minutes' => $duration->i,
                'formatted' => $this->formatDuration($duration)
            ];
        }

        return $times;
    }

    /**
     * Obtener tiempo total de resolución
     */
    public function getTotalResolutionTime()
    {
        if ($this->created_at && $this->closed_at) {
            return $this->created_at->diff($this->closed_at);
        }

        // Si no está cerrada, calcular hasta ahora
        if ($this->created_at) {
            return $this->created_at->diff(now());
        }

        return null;
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

    /**
     * Obtener estadísticas de tiempo detalladas
     */
    public function getTimeStatistics()
    {
        $totalTime = $this->getTotalResolutionTime();
        $timeInStatus = $this->getTimeInEachStatus();

        $totalMinutes = 0;
        if ($totalTime) {
            $totalMinutes = $totalTime->i + ($totalTime->h * 60) + ($totalTime->days * 24 * 60);
        }

        // Calcular tiempo pausado
        $pausedMinutes = $this->total_paused_minutes;
        if ($this->isPaused() && $this->paused_at) {
            $pausedMinutes += now()->diffInMinutes($this->paused_at);
        }

        // Calcular tiempo activo
        $activeMinutes = max(0, $totalMinutes - $pausedMinutes);

        // Calcular eficiencia
        $efficiency = $totalMinutes > 0 ? ($activeMinutes / $totalMinutes) * 100 : 0;

        return [
            'total_time' => $totalTime ? $this->formatDuration($totalTime) : 'En progreso',
            'total_minutes' => $totalMinutes,
            'active_time' => $this->formatMinutesToReadable($activeMinutes),
            'active_minutes' => $activeMinutes,
            'paused_time' => $this->getTotalPausedTimeFormatted(),
            'paused_minutes' => $pausedMinutes,
            'efficiency' => round($efficiency, 1) . '%',
            'efficiency_raw' => $efficiency
        ];
    }

    /**
     * Obtener resumen de tiempos por tipo de evento
     */
    public function getTimeSummaryByEventType()
    {
        $timeInStatus = $this->getTimeInEachStatus();
        $summary = [];

        foreach ($timeInStatus as $status => $time) {
            $summary[] = [
                'event_type' => $this->getEventTypeLabel($status),
                'duration' => $time['formatted'],
                'minutes' => $time['total_minutes'],
                'percentage' => $this->calculateTimePercentage($time['total_minutes'])
            ];
        }

        return $summary;
    }

    /**
     * Obtener etiqueta para tipo de evento
     */
    private function getEventTypeLabel($status)
    {
        $labels = [
            'created' => 'Creación',
            'assigned' => 'Asignación',
            'accepted' => 'Aceptación',
            'responded' => 'Respuesta Inicial',
            'paused' => 'Pausa',
            'resumed' => 'Reanudación',
            'resolved' => 'Resolución',
            'closed' => 'Cierre',
            'evidence' => 'Evidencias',
            'breach' => 'Incumplimientos SLA'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Calcular porcentaje de tiempo
     */
    private function calculateTimePercentage($minutes)
    {
        $stats = $this->getTimeStatistics();
        $totalMinutes = $stats['total_minutes'];

        if ($totalMinutes > 0) {
            return round(($minutes / $totalMinutes) * 100, 1);
        }

        return 0;
    }

    // =============================================
    // MÉTODOS PARA COLORES Y ESTADOS (compatibilidad)
    // =============================================

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
     * Verificar si la solicitud está vencida
     */
    public function isOverdue()
    {
        if ($this->closed_at) {
            return false;
        }

        $deadline = $this->resolution_deadline;
        if (!$deadline) {
            return false;
        }

        return now()->greaterThan($deadline);
    }

    /**
     * Obtener tiempo restante para vencimiento
     */
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
    /**
     * Formatear duración para intervalos de fecha
     */
    public function formatDuration($duration)
    {
        if (!$duration) {
            return '0 minutos';
        }

        $parts = [];

        if ($duration->days > 0) {
            $parts[] = $duration->days . ' día' . ($duration->days > 1 ? 's' : '');
        }

        if ($duration->h > 0) {
            $parts[] = $duration->h . ' hora' . ($duration->h > 1 ? 's' : '');
        }

        if ($duration->i > 0) {
            $parts[] = $duration->i . ' minuto' . ($duration->i > 1 ? 's' : '');
        }

        return implode(', ', $parts) ?: '0 minutos';
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

    /**
     * Obtener tiempo total de resolución
     */
    public function getTotalResolutionTime()
    {
        if ($this->created_at && $this->closed_at) {
            return $this->created_at->diff($this->closed_at);
        }

        // Si no está cerrada, calcular hasta ahora
        if ($this->created_at) {
            return $this->created_at->diff(now());
        }

        return null;
    }

    /**
     * Calcular tiempo en cada estado
     */
    public function getTimeInEachStatus()
    {
        $times = [];
        $events = $this->getTimelineEvents();

        for ($i = 0; $i < count($events) - 1; $i++) {
            $currentEvent = $events[$i];
            $nextEvent = $events[$i + 1];

            $duration = $currentEvent['timestamp']->diff($nextEvent['timestamp']);
            $totalMinutes = $duration->i + ($duration->h * 60) + ($duration->days * 24 * 60);

            $times[$currentEvent['status']] = [
                'duration' => $duration,
                'total_minutes' => $totalMinutes,
                'hours' => $duration->h + ($duration->days * 24),
                'minutes' => $duration->i,
                'formatted' => $this->formatDuration($duration)
            ];
        }

        return $times;
    }

    /**
     * Obtener estadísticas de tiempo detalladas
     */
    public function getTimeStatistics()
    {
        $totalTime = $this->getTotalResolutionTime();
        $timeInStatus = $this->getTimeInEachStatus();

        $totalMinutes = 0;
        if ($totalTime) {
            $totalMinutes = $totalTime->i + ($totalTime->h * 60) + ($totalTime->days * 24 * 60);
        }

        // Calcular tiempo pausado
        $pausedMinutes = $this->total_paused_minutes;
        if ($this->isPaused() && $this->paused_at) {
            $pausedMinutes += now()->diffInMinutes($this->paused_at);
        }

        // Calcular tiempo activo
        $activeMinutes = max(0, $totalMinutes - $pausedMinutes);

        // Calcular eficiencia
        $efficiency = $totalMinutes > 0 ? ($activeMinutes / $totalMinutes) * 100 : 0;

        return [
            'total_time' => $totalTime ? $this->formatDuration($totalTime) : 'En progreso',
            'total_minutes' => $totalMinutes,
            'active_time' => $this->formatMinutesToReadable($activeMinutes),
            'active_minutes' => $activeMinutes,
            'paused_time' => $this->getTotalPausedTimeFormatted(),
            'paused_minutes' => $pausedMinutes,
            'efficiency' => round($efficiency, 1) . '%',
            'efficiency_raw' => $efficiency
        ];
    }

    /**
     * Obtener resumen de tiempos por tipo de evento
     */
    public function getTimeSummaryByEventType()
    {
        $timeInStatus = $this->getTimeInEachStatus();
        $summary = [];

        foreach ($timeInStatus as $status => $time) {
            $summary[] = [
                'event_type' => $this->getEventTypeLabel($status),
                'duration' => $time['formatted'],
                'minutes' => $time['total_minutes'],
                'percentage' => $this->calculateTimePercentage($time['total_minutes'])
            ];
        }

        return $summary;
    }

    /**
     * Obtener etiqueta para tipo de evento
     */
    private function getEventTypeLabel($status)
    {
        $labels = [
            'created' => 'Creación',
            'assigned' => 'Asignación',
            'accepted' => 'Aceptación',
            'responded' => 'Respuesta Inicial',
            'paused' => 'Pausa',
            'resumed' => 'Reanudación',
            'resolved' => 'Resolución',
            'closed' => 'Cierre',
            'evidence' => 'Evidencias',
            'breach' => 'Incumplimientos SLA'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Calcular porcentaje de tiempo
     */
    private function calculateTimePercentage($minutes)
    {
        $stats = $this->getTimeStatistics();
        $totalMinutes = $stats['total_minutes'];

        if ($totalMinutes > 0) {
            return round(($minutes / $totalMinutes) * 100, 1);
        }

        return 0;
    }

    /**
     * Verificar si la solicitud está vencida
     */
    public function isOverdue()
    {
        if ($this->closed_at) {
            return false;
        }

        $deadline = $this->resolution_deadline;
        if (!$deadline) {
            return false;
        }

        return now()->greaterThan($deadline);
    }
}
