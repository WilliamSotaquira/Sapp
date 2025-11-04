<?php

namespace App\Models\Managers;

use App\Models\ServiceRequestEvidence; // Agregar este import

trait ServiceRequestEvidenceManager
{
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
}
