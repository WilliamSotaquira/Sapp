<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ServiceRequestEvidence extends Model
{
    use HasFactory;

    // ESPECIFICAR EL NOMBRE DE LA TABLA EXPLÍCITAMENTE
    protected $table = 'service_request_evidences';

    // En App\Models\ServiceRequestEvidence.php
    protected $fillable = [
        'service_request_id',
        'title',
        'description',
        'evidence_type',
        'step_number',
        'evidence_data',
        'user_id',
        'file_original_name', // ← Esta SÍ existe
        'file_path', // ← Esta SÍ existe
        'file_mime_type', // ← Esta SÍ existe
        'file_size', // ← Esta SÍ existe
        // NO incluir: 'uploaded_by', 'file_name', 'mime_type'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'evidence_data' => 'array',
    ];

    /**
     * MÉTODO CORREGIDO: Verificar si la evidencia puede ser eliminada
     * Sin depender de hasRole()
     */
    public function canBeDeleted()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Verificar si es el usuario que subió la evidencia
        $isUploader = ($this->uploaded_by === $user->id) || ($this->user_id === $user->id);

        // Verificar si está asignado a la solicitud de servicio
        $isAssigned = $this->serviceRequest && $this->serviceRequest->assigned_to === $user->id;

        // Verificar si es el que creó la solicitud
        $isRequester = $this->serviceRequest && $this->serviceRequest->requested_by === $user->id;

        // Verificar roles de manera simple (sin hasRole())
        $isAdmin = $this->isAdminUser($user); // Método alternativo

        // Verificar si la solicitud está en un estado que permite eliminar evidencias
        $allowedStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'];
        $isEditableStatus = $this->serviceRequest && in_array($this->serviceRequest->status, $allowedStatuses);

        return ($isUploader || $isAssigned || $isRequester || $isAdmin) && $isEditableStatus;
    }

    /**
     * Método alternativo para verificar si es admin
     * Sin depender de hasRole()
     */
    private function isAdminUser($user)
    {
        // Opción 1: Verificar por email (si tienes emails de admin)
        $adminEmails = ['admin@example.com', 'superadmin@example.com'];
        if (in_array($user->email, $adminEmails)) {
            return true;
        }

        // Opción 2: Verificar por un campo específico en la tabla users
        // Si tienes un campo 'role' o 'is_admin' en la tabla users
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        // Opción 3: Verificar por ID específico (para desarrollo)
        $adminIds = [1]; // ID del usuario administrador principal
        if (in_array($user->id, $adminIds)) {
            return true;
        }

        return false;
    }

    /**
     * Versión simplificada de canBeDeleted
     */
    public function canBeDeletedSimple()
    {
        $user = Auth::user();

        if (!$user || !$this->serviceRequest) {
            return false;
        }

        // Solo el que subió la evidencia puede eliminarla
        // y solo si la solicitud no está resuelta o cerrada
        $isUploader = ($this->uploaded_by === $user->id) || ($this->user_id === $user->id);
        $isNotFinalized = !in_array($this->serviceRequest->status, ['RESUELTA', 'CERRADA', 'CANCELADA']);

        return $isUploader && $isNotFinalized;
    }

    /**
     * MÉTODO FALTANTE: Obtener tamaño formateado del archivo
     */
    public function getFormattedFileSize()
    {
        // Si ya tenemos un file_size formateado en la BD, usarlo
        if (!empty($this->file_size) && is_string($this->file_size)) {
            return $this->file_size;
        }

        // Si file_size es numérico (bytes), formatearlo
        if (!empty($this->file_size) && is_numeric($this->file_size)) {
            $bytes = (int) $this->file_size;
            return $this->formatBytes($bytes);
        }

        // Si no hay file_size, intentar obtenerlo del archivo físico
        try {
            if ($this->hasFile()) {
                $size = Storage::size($this->file_path);
                return $this->formatBytes($size);
            }
        } catch (\Exception $e) {
            // Log error si es necesario
        }

        return 'N/A';
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Accessor para file_size formateado
     */
    public function getFormattedFileSizeAttribute()
    {
        return $this->getFormattedFileSize();
    }

    /**
     * Accessor para compatibilidad - usar file_original_name como file_name
     */
    public function getFileNameAttribute($value)
    {
        // Si alguien accede a file_name, devolver file_original_name
        return $this->file_original_name;
    }


    /**
     * Accessor para compatibilidad - usar file_mime_type como mime_type
     */
    public function getMimeTypeAttribute($value)
    {
        // Si alguien accede a mime_type, devolver file_mime_type
        return $this->file_mime_type;
    }

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
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relación alternativa con user_id
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
     * Verificar si tiene archivo
     */
    public function hasFile()
    {
        return !empty($this->file_path) && Storage::exists($this->file_path);
    }

    /**
     * Obtener URL del archivo
     */
    public function getFileUrl()
    {
        if ($this->hasFile()) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    /**
     * Obtener ruta completa del archivo
     */
    public function getFilePath()
    {
        if ($this->hasFile()) {
            return Storage::path($this->file_path);
        }
        return null;
    }

    /**
     * Obtener extensión del archivo
     */
    public function getFileExtension()
    {
        $fileName = $this->file_original_name;
        if ($fileName) {
            return pathinfo($fileName, PATHINFO_EXTENSION);
        }
        return null;
    }

    /**
     * Verificar si es imagen
     */
    public function isImage()
    {
        $mime = $this->file_mime_type;
        return $mime && str_starts_with($mime, 'image/');
    }

    /**
     * Verificar si es PDF
     */
    public function isPdf()
    {
        $mime = $this->mime_type ?: $this->file_mime_type;
        return $mime === 'application/pdf';
    }

    /**
     * Verificar si es documento
     */
    public function isDocument()
    {
        $mime = $this->mime_type ?: $this->file_mime_type;
        $documentMimes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];

        return in_array($mime, $documentMimes);
    }

    /**
     * Scope para evidencias de tipo imagen
     */
    public function scopeImages($query)
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'like', 'image/%')
                ->orWhere('file_mime_type', 'like', 'image/%');
        });
    }

    /**
     * Obtener la URL de la evidencia (para compatibilidad)
     */
    public function getUrlAttribute()
    {
        return $this->getFileUrl();
    }

    /**
     * Verificar si la evidencia es una imagen (para compatibilidad)
     */
    public function getIsImageAttribute()
    {
        return $this->isImage();
    }

    /**
     * Obtener el tipo de archivo amigable
     */
    public function getFileTypeAttribute()
    {
        if ($this->isImage()) {
            return 'Imagen';
        } elseif ($this->isPdf()) {
            return 'PDF';
        } elseif ($this->isDocument()) {
            return 'Documento';
        } else {
            return 'Archivo';
        }
    }

    /**
     * Obtener icono según tipo de archivo
     */
    public function getFileIconAttribute()
    {
        if ($this->isImage()) {
            return 'fa-image';
        } elseif ($this->isPdf()) {
            return 'fa-file-pdf';
        } elseif ($this->isDocument()) {
            return 'fa-file-word';
        } else {
            return 'fa-file';
        }
    }
}
