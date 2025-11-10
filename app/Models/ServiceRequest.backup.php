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

    // ✅ CORREGIDO: Solo campos que existen en BD
    protected $fillable = [
        'service_request_id',
        'title',
        'description',
        'evidence_type',
        'step_number',
        'evidence_data',
        'user_id', // ✅ Usuario que SUBE la evidencia
        'file_original_name',
        'file_path',
        'file_mime_type',
        'file_size',
        // ❌ NO incluir: 'uploaded_by', 'file_name', 'mime_type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'evidence_data' => 'array',
    ];

    /**
     * MÉTODO CORREGIDO: Verificar si la evidencia puede ser eliminada
     * Usando SOLO user_id (no uploaded_by)
     */
    public function canBeDeleted()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // ✅ CORRECCIÓN: Usar SOLO user_id (usuario que subió la evidencia)
        $isUploader = $this->user_id === $user->id;

        // ✅ CORRECCIÓN: Usar constantes de ServiceRequest
        $allowedStatuses = [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            // Si necesitas estado PAUSADA, agregar la constante en ServiceRequest
        ];

        // Verificar si está asignado a la solicitud de servicio
        $isAssigned = $this->serviceRequest && $this->serviceRequest->assigned_to === $user->id;

        // Verificar si es el que creó la solicitud
        $isRequester = $this->serviceRequest && $this->serviceRequest->requested_by === $user->id;

        // Verificar roles de manera simple (sin hasRole())
        $isAdmin = $this->isAdminUser($user);

        // Verificar si la solicitud está en un estado que permite eliminar evidencias
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
     * Versión simplificada de canBeDeleted - CORREGIDA
     */
    public function canBeDeletedSimple()
    {
        $user = Auth::user();

        if (!$user || !$this->serviceRequest) {
            return false;
        }

        // ✅ CORRECCIÓN: Solo user_id (quien subió la evidencia)
        $isUploader = $this->user_id === $user->id;

        // ✅ CORRECCIÓN: Usar constantes de ServiceRequest
        $isNotFinalized = !in_array($this->serviceRequest->status, [
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
            ServiceRequest::STATUS_CANCELLED
        ]);

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
     * ✅ RELACIÓN CORREGIDA - Usuario que SUBIÓ la evidencia
     * Usando user_id (campo que existe en BD)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ❌ ELIMINAR relación uploadedBy() - campo uploaded_by no existe en BD
     */
    // public function uploadedBy()
    // {
    //     return $this->belongsTo(User::class, 'uploaded_by');
    // }

    /**
     * Obtener URL del archivo - VERSIÓN CORREGIDA Y SIMPLIFICADA
     */
    public function getFileUrl()
    {
        if (!$this->file_path) {
            \Log::warning("Evidence {$this->id}: file_path está vacío");
            return null;
        }

        \Log::info("Evidence {$this->id}: Procesando file_path = '{$this->file_path}'");

        // Si el file_path ya es una URL completa, retornarla
        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            \Log::info("Evidence {$this->id}: Es URL completa - {$this->file_path}");
            return $this->file_path;
        }

        // PRIMERO: Intentar con Storage::url (para archivos en storage)
        try {
            // Si file_path empieza con 'evidences/', asumimos que está en storage
            if (str_starts_with($this->file_path, 'evidences/')) {
                $url = Storage::url($this->file_path);
                \Log::info("Evidence {$this->id}: Storage::url generado - {$url}");
                return $url;
            }

            // Si no tiene prefijo, agregar 'evidences/' y probar
            $storagePath = 'evidences/' . $this->file_path;
            if (Storage::exists($storagePath)) {
                $url = Storage::url($storagePath);
                \Log::info("Evidence {$this->id}: Storage::url con prefijo - {$url}");
                return $url;
            }

            // Intentar con la ruta directa
            if (Storage::exists($this->file_path)) {
                $url = Storage::url($this->file_path);
                \Log::info("Evidence {$this->id}: Storage::url directo - {$url}");
                return $url;
            }
        } catch (\Exception $e) {
            \Log::error("Evidence {$this->id}: Error con Storage::url - " . $e->getMessage());
        }

        // SEGUNDO: Si Storage::url no funciona, construir URL manualmente
        try {
            // Construir URL manual basada en la estructura común
            $manualUrl = asset('storage/' . $this->file_path);
            \Log::info("Evidence {$this->id}: URL manual - {$manualUrl}");
            return $manualUrl;
        } catch (\Exception $e) {
            \Log::error("Evidence {$this->id}: Error construyendo URL manual - " . $e->getMessage());
        }

        \Log::warning("Evidence {$this->id}: No se pudo generar URL para '{$this->file_path}'");
        return null;
    }

    /**
     * Verificar si tiene archivo - VERSIÓN MEJORADA
     */
    public function hasFile()
    {
        if (!$this->file_path) {
            return false;
        }

        try {
            // Verificar si el archivo existe en storage
            if (Storage::exists($this->file_path)) {
                return true;
            }

            // Verificar rutas alternativas comunes
            $possiblePaths = [
                $this->file_path,
                'public/' . $this->file_path,
                'evidences/' . $this->file_path,
                'public/evidences/' . $this->file_path,
            ];

            foreach ($possiblePaths as $path) {
                if (Storage::exists($path)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Error en hasFile() para evidence {$this->id}: " . $e->getMessage());
            return false;
        }
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
