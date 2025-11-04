<?php

namespace App\Models\Managers;

use Carbon\Carbon;
use App\Models\User;

trait ServiceRequestTimelineManager
{
    // =============================================
    // MÉTODOS PARA LÍNEA DE TIEMPO
    // =============================================

    /**
     * Obtener todos los eventos de la línea de tiempo
     */
    public function getTimelineEvents()
    {
        $events = [];

        // Eventos básicos del ciclo de vida
        $this->addBasicTimelineEvents($events);

        // Agregar eventos de evidencias
        $this->loadEvidencesForTimeline($events);

        // Agregar eventos de rutas web
        $this->loadWebRoutesForTimeline($events);

        // Agregar eventos de incumplimiento SLA
        $this->addSlaBreachEvents($events);

        // Ordenar eventos por fecha
        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $events;
    }

    /**
     * Agregar eventos básicos del ciclo de vida
     */
    private function addBasicTimelineEvents(&$events)
    {
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
    }

    /**
     * Método auxiliar para cargar evidencias de forma segura
     */
    private function loadEvidencesForTimeline(&$events)
    {
        try {
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
            \Log::warning('Error loading evidences for timeline in request ' . $this->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Método auxiliar para cargar rutas web en timeline (CORREGIDO)
     */
    private function loadWebRoutesForTimeline(&$events)
    {
        try {
            $routes = $this->web_routes;

            // Verificar si es un array válido
            if (is_array($routes) && !empty($routes)) {
                foreach ($routes as $route) {
                    // Verificar si es un array complejo o un string simple
                    if (is_array($route) && isset($route['route'])) {
                        $events[] = [
                            'event' => 'RUTA WEB AGREGADA',
                            'timestamp' => Carbon::parse($route['added_at']),
                            'user' => User::find($route['added_by']),
                            'description' => $route['description'] ?
                                "Ruta: {$route['route']} - {$route['description']}" :
                                "Ruta web agregada: {$route['route']}",
                            'status' => 'web_route',
                            'icon' => 'link',
                            'color' => 'info',
                            'route' => $route['route']
                        ];
                    } elseif (is_string($route)) {
                        // Si es un string simple (fallback)
                        $events[] = [
                            'event' => 'RUTA WEB AGREGADA',
                            'timestamp' => $this->created_at,
                            'user' => $this->requester,
                            'description' => "Ruta web agregada: {$route}",
                            'status' => 'web_route',
                            'icon' => 'link',
                            'color' => 'info',
                            'route' => $route
                        ];
                    }
                }
            }

            // Evento para ruta principal
            if ($this->main_web_route) {
                $events[] = [
                    'event' => 'RUTA PRINCIPAL ESTABLECIDA',
                    'timestamp' => $this->updated_at,
                    'user' => $this->assignee,
                    'description' => "Ruta principal establecida: {$this->main_web_route}",
                    'status' => 'main_route',
                    'icon' => 'star',
                    'color' => 'warning'
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Error loading web routes for timeline in request ' . $this->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Agregar eventos de incumplimiento SLA
     */
    private function addSlaBreachEvents(&$events)
    {
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
     * Obtener tiempo total de resolución
     */
    public function getTotalResolutionTime()
    {
        if ($this->created_at && $this->closed_at) {
            return $this->created_at->diff($this->closed_at);
        }

        if ($this->created_at) {
            return $this->created_at->diff(now());
        }

        return null;
    }
}
