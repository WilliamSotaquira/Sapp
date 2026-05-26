<?php

namespace App\Models\Traits;

trait ServiceRequestConstants
{
    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_ACCEPTED = 'ACEPTADA';
    const STATUS_IN_PROGRESS = 'EN_PROCESO';
    const STATUS_RESOLVED = 'RESUELTA';
    const STATUS_CLOSED = 'CERRADA';
    const STATUS_CANCELLED = 'CANCELADA';
    const STATUS_PAUSED = 'PAUSADA';
    const STATUS_REOPENED = 'REABIERTO';

    const TYPE_SYSTEM = 'SISTEMA';
    const TYPE_STEP_BY_STEP = 'PASO_A_PASO';
    const TYPE_FILE = 'ARCHIVO';

    // Niveles de criticidad
    const CRITICALITY_LOW = 'BAJA';
    const CRITICALITY_MEDIUM = 'MEDIA';
    const CRITICALITY_HIGH = 'ALTA';
    const CRITICALITY_CRITICAL = 'CRITICA';

    // Niveles de complejidad
    const COMPLEXITY_LOW = 'BAJA';
    const COMPLEXITY_MEDIUM = 'MEDIA';
    const COMPLEXITY_HIGH = 'ALTA';

    // Niveles de prioridad (P0-P4)
    const PRIORITY_P0 = 'P0'; // Atender hoy
    const PRIORITY_P1 = 'P1'; // Atender 24-48h
    const PRIORITY_P2 = 'P2'; // Programar esta semana
    const PRIORITY_P3 = 'P3'; // Cola operativa
    const PRIORITY_P4 = 'P4'; // Archivar o validar si aplica

    // Clasificación de antigüedad
    const ANTIQUITY_VERY_RECENT = 'MUY_RECIENTE';   // 0-6 días
    const ANTIQUITY_RECENT = 'RECIENTE';             // 7-13 días
    const ANTIQUITY_MEDIUM = 'MEDIA';                // 14-29 días
    const ANTIQUITY_OLD = 'ANTIGUA';                 // 30-59 días
    const ANTIQUITY_VERY_OLD = 'MUY_ANTIGUA';        // 60+ días
}
