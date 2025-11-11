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

    const CRITICALITY_LOW = 'BAJA';
    const CRITICALITY_MEDIUM = 'MEDIA';
    const CRITICALITY_HIGH = 'ALTA';
    const CRITICALITY_CRITICAL = 'CRITICA';
}
