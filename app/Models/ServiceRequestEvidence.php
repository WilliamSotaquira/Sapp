<?php
// app/Models/ServiceRequestEvidence.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ServiceRequestEvidence extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_request_evidences';

    protected $fillable = [
        'service_request_id',
        'title',
        'description',
        'evidence_data',
        'evidence_type',
        'step_number',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size'
    ];

    protected $casts = [
        'evidence_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'file_size' => 'integer'
    ];

    // Tipos de evidencia
    const TYPE_STEP_BY_STEP = 'PASO_A_PASO';
    const TYPE_FILE = 'ARCHIVO';
    const TYPE_COMMENT = 'COMENTARIO';
    const TYPE_SYSTEM = 'SISTEMA';

    /**
     * Relación con la solicitud de servicio
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Accesor para obtener los datos de evidencia
     */
    public function getEvidenceDataAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    /**
     * Mutador para serializar los datos de evidencia
     */
    public function setEvidenceDataAttribute($value)
    {
        $this->attributes['evidence_data'] = json_encode($value ?? []);
    }

    /**
     * Scope para evidencias paso a paso
     */
    public function scopeStepByStep($query)
    {
        return $query->where('evidence_type', self::TYPE_STEP_BY_STEP)
                    ->orderBy('step_number');
    }

    /**
     * Scope para archivos adjuntos
     */
    public function scopeFiles($query)
    {
        return $query->where('evidence_type', self::TYPE_FILE);
    }

    /**
     * Scope para comentarios
     */
    public function scopeComments($query)
    {
        return $query->where('evidence_type', self::TYPE_COMMENT);
    }

    /**
     * Scope para eventos del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('evidence_type', self::TYPE_SYSTEM);
    }

    /**
     * Verificar si tiene archivo adjunto
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    /**
     * Obtener la ruta completa del archivo
     */
    public function getFilePath(): ?string
    {
        return $this->file_path ? storage_path('app/public/' . $this->file_path) : null;
    }

    /**
     * Obtener tamaño formateado del archivo - MÉTODO FALTANTE
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtener icono según tipo de evidencia
     */
    public function getEvidenceIcon(): string
    {
        return match($this->evidence_type) {
            self::TYPE_STEP_BY_STEP => 'fas fa-list-ol',
            self::TYPE_FILE => 'fas fa-file',
            self::TYPE_COMMENT => 'fas fa-comment',
            self::TYPE_SYSTEM => 'fas fa-cog',
            default => 'fas fa-camera'
        };
    }

    /**
     * Obtener color según tipo de evidencia
     */
    public function getEvidenceColor(): string
    {
        return match($this->evidence_type) {
            self::TYPE_STEP_BY_STEP => 'primary',
            self::TYPE_FILE => 'success',
            self::TYPE_COMMENT => 'info',
            self::TYPE_SYSTEM => 'secondary',
            default => 'dark'
        };
    }

    /**
     * Obtener badge HTML para tipo de evidencia
     */
    public function getEvidenceBadge(): string
    {
        $color = $this->getEvidenceColor();
        $icon = $this->getEvidenceIcon();

        return '<span class="badge bg-' . $color . '"><i class="' . $icon . ' me-1"></i>' . $this->evidence_type . '</span>';
    }

    /**
     * Verificar si la evidencia puede ser eliminada
     */
    public function canBeDeleted(): bool
    {
        return in_array($this->serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']);
    }
}
