<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ServiceRequestEvidence extends Model
{
    use HasFactory;

    // ✅ CORREGIDO: Nombre exacto de la tabla
    protected $table = 'service_request_evidences';

    protected $fillable = ['service_request_id', 'title', 'description', 'evidence_type', 'step_number', 'evidence_data', 'user_id', 'file_original_name', 'file_path', 'file_mime_type', 'file_size'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'evidence_data' => 'array',
    ];

    protected $appends = ['file_url', 'is_image', 'file_icon', 'formatted_file_size', 'has_file', 'file_type'];

    /**
     * Relación con ServiceRequest
     */
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Relación con el usuario que subió la evidencia
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias para user() - Para compatibilidad hacia atrás
     */
    public function uploadedBy()
    {
        return $this->user();
    }
    /**
     * Accessor para file_url - VERSIÓN MEJORADA SIN WARNINGS
     */
    public function getFileUrlAttribute()
    {
        if (empty($this->file_path)) {
            return null;
        }
        if ($this->isExternalUrl($this->file_path)) {
            return $this->file_path;
        }
        try {
            $resolvedPath = $this->resolveStoragePath();
            if ($resolvedPath) {
                return Storage::disk('public')->url($resolvedPath);
            }

            $normalized = $this->normalizeStoragePath($this->file_path);
            return $normalized ? Storage::disk('public')->url($normalized) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Accessor para verificar si es imagen
     */
    public function getIsImageAttribute()
    {
        if (!$this->file_mime_type) {
            return false;
        }

        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($this->file_mime_type, $imageMimes);
    }

    /**
     * Accessor para el icono del archivo
     */
    public function getFileIconAttribute()
    {
        if ($this->is_image) {
            return 'fa-file-image';
        }

        $mimeIcons = [
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/zip' => 'fa-file-archive',
            'application/x-rar-compressed' => 'fa-file-archive',
            'text/plain' => 'fa-file-alt',
        ];

        return $mimeIcons[$this->file_mime_type] ?? 'fa-file';
    }

    /**
     * Accessor para tamaño formateado del archivo
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Accessor para verificar si tiene archivo - VERSIÓN MEJORADA
     */
    public function getHasFileAttribute()
    {
        if (empty($this->file_path)) {
            return false;
        }
        if ($this->isExternalUrl($this->file_path)) {
            return true;
        }
        try {
            return $this->resolveStoragePath() !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Accessor para tipo de archivo amigable
     */
    public function getFileTypeAttribute()
    {
        if ($this->is_image) {
            return 'Imagen';
        }

        $mimeTypes = [
            'application/pdf' => 'PDF',
            'application/msword' => 'Documento Word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Documento Word',
            'application/vnd.ms-excel' => 'Excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'application/zip' => 'Archivo comprimido',
            'application/x-rar-compressed' => 'Archivo comprimido',
            'text/plain' => 'Texto',
        ];

        return $mimeTypes[$this->file_mime_type] ?? 'Archivo';
    }

    /**
     * Método helper para compatibilidad con controladores/vistas
     */
    public function hasFile(): bool
    {
        return (bool) $this->has_file;
    }

    /**
     * Determina si el path es una URL externa
     */
    private function isExternalUrl(string $path): bool
    {
        return (bool) preg_match('#^https?://#i', $path);
    }

    /**
     * Normaliza paths con prefijos conocidos
     */
    private function normalizeStoragePath(string $path): string
    {
        $normalized = ltrim($path, '/');

        if (strpos($normalized, 'public/') === 0) {
            $normalized = substr($normalized, 7);
        }

        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, 8);
        }

        return $normalized;
    }

    /**
     * Resuelve el path real del archivo en el disco público
     */
    private function resolveStoragePath(): ?string
    {
        $candidates = [];
        $candidates[] = $this->file_path;
        $candidates[] = $this->normalizeStoragePath($this->file_path);

        $basename = basename($this->file_path);
        if ($basename) {
            $candidates[] = 'evidences/' . $basename;
        }

        $candidates = array_filter(array_unique($candidates));

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Verificar si la evidencia puede ser eliminada
     */
    public function canBeDeleted()
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Usuario que subió la evidencia
        $isUploader = $this->user_id === $user->id;

        // Estados que permiten eliminar evidencias
        $allowedStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];

        $isEditableStatus = $this->serviceRequest && in_array($this->serviceRequest->status, $allowedStatuses);

        return $isUploader && $isEditableStatus;
    }
}
