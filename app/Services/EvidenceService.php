<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EvidenceService
{
    private const EVIDENCE_DISK = 'public';
    private const EVIDENCE_DIRECTORY = 'evidences';
    private const MAX_FILE_SIZE = 10240; // 10MB en KB

    /**
     * Subir múltiples archivos como evidencias
     */
    public function uploadEvidences(ServiceRequest $serviceRequest, array $files): array
    {
        Log::info('=== INICIANDO SUBIDA DE EVIDENCIAS ===');

        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $evidence = $this->uploadSingleEvidence($serviceRequest, $file);
                if ($evidence) {
                    $uploadedFiles[] = $evidence;
                }
            } catch (\Exception $e) {
                $errors[] = "Error al subir {$file->getClientOriginalName()}: {$e->getMessage()}";
                Log::error('Error al subir evidencia: ' . $e->getMessage());
            }
        }

        Log::info('=== SUBIDA COMPLETADA ===', [
            'total_successful' => count($uploadedFiles),
            'total_errors' => count($errors),
            'service_request_id' => $serviceRequest->id,
        ]);

        return [
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
            'success_count' => count($uploadedFiles),
            'error_count' => count($errors)
        ];
    }

    /**
     * Subir un archivo individual
     */
    private function uploadSingleEvidence(ServiceRequest $serviceRequest, UploadedFile $file): ?ServiceRequestEvidence
    {
        Log::info('Procesando archivo:', [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        if (!$file->isValid()) {
            throw new \Exception('Archivo no válido');
        }

        if ($file->getSize() > (self::MAX_FILE_SIZE * 1024)) {
            throw new \Exception('El archivo excede el tamaño máximo permitido');
        }

        // Generar nombre único
        $fileName = $this->generateUniqueFileName($serviceRequest, $file);

        // Crear directorio si no existe
        $this->ensureDirectoryExists();

        // Guardar archivo
        $filePath = $file->storeAs(self::EVIDENCE_DIRECTORY, $fileName, self::EVIDENCE_DISK);

        if (!$filePath) {
            throw new \Exception('No se pudo guardar el archivo');
        }

        // Verificar que se guardó correctamente
        $this->verifyStoredFile($filePath, $file->getSize());

        // Crear registro en base de datos
        return $this->createEvidenceRecord($serviceRequest, $file, $filePath, $fileName);
    }

    /**
     * Generar nombre único para el archivo
     */
    private function generateUniqueFileName(ServiceRequest $serviceRequest, UploadedFile $file): string
    {
        $serviceCode = $serviceRequest->code ?? 'SR' . $serviceRequest->id;
        $timestamp = now()->format('Ymd-His');
        $microtime = substr(str_replace('.', '', microtime(true)), -6);
        $extension = $file->getClientOriginalExtension();

        // Limpiar el código de servicio
        $cleanServiceCode = preg_replace('/[^a-zA-Z0-9]/', '-', $serviceCode);
        $cleanServiceCode = substr($cleanServiceCode, 0, 20);

        return "{$cleanServiceCode}-{$timestamp}-{$microtime}.{$extension}";
    }

    /**
     * Asegurar que existe el directorio
     */
    private function ensureDirectoryExists(): void
    {
        if (!Storage::disk(self::EVIDENCE_DISK)->exists(self::EVIDENCE_DIRECTORY)) {
            Storage::disk(self::EVIDENCE_DISK)->makeDirectory(self::EVIDENCE_DIRECTORY);
            Log::info('Directorio creado: ' . self::EVIDENCE_DIRECTORY);
        }
    }

    /**
     * Verificar que el archivo se guardó correctamente
     */
    private function verifyStoredFile(string $filePath, int $originalSize): void
    {
        if (!Storage::disk(self::EVIDENCE_DISK)->exists($filePath)) {
            throw new \Exception('El archivo no se guardó correctamente');
        }

        $storedSize = Storage::disk(self::EVIDENCE_DISK)->size($filePath);

        if ($storedSize === 0) {
            Storage::disk(self::EVIDENCE_DISK)->delete($filePath);
            throw new \Exception('El archivo se guardó con tamaño 0');
        }

        Log::info('Archivo verificado - Tamaño original: ' . $originalSize . ', Tamaño guardado: ' . $storedSize);
    }

    /**
     * Crear registro de evidencia en base de datos
     */
    private function createEvidenceRecord(
        ServiceRequest $serviceRequest,
        UploadedFile $file,
        string $filePath,
        string $fileName
    ): ServiceRequestEvidence {
        $evidenceData = [
            'service_request_id' => $serviceRequest->id,
            'title' => $fileName,
            'description' => 'Archivo subido: ' . $file->getClientOriginalName(),
            'evidence_type' => 'ARCHIVO',
            'file_path' => $filePath,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getMimeType(),
            'file_size' => Storage::disk(self::EVIDENCE_DISK)->size($filePath),
            'user_id' => auth()->id(),
        ];

        Log::info('Creando evidencia en BD:', $evidenceData);

        try {
            $evidence = ServiceRequestEvidence::create($evidenceData);
            $evidence->load('user');
            Log::info('✅ Evidencia creada con ID: ' . $evidence->id);
            return $evidence;
        } catch (\Exception $e) {
            // Eliminar archivo si falla la BD
            Storage::disk(self::EVIDENCE_DISK)->delete($filePath);
            throw new \Exception('Error al crear registro en BD: ' . $e->getMessage());
        }
    }

    /**
     * Obtener evidencias con archivos procesados para PDF
     */
    public function prepareEvidencesForPdf(ServiceRequest $serviceRequest): \Illuminate\Database\Eloquent\Collection
    {
        $evidences = $serviceRequest->evidences;

        return $evidences->map(function ($evidence) {
            if ($evidence->file_path && $evidence->evidence_type !== 'SISTEMA') {
                try {
                    $filePath = $this->findEvidenceFile($evidence->file_path);

                    if ($filePath && file_exists($filePath)) {
                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                        if (in_array($extension, $imageExtensions)) {
                            $evidence->is_image = true;
                            $evidence->base64_content = $this->fileToBase64($filePath, $extension);
                            $evidence->file_found = true;
                        } else {
                            $evidence->is_image = false;
                            $evidence->file_found = true;
                            $evidence->base64_content = null;
                        }
                    } else {
                        $evidence->file_found = false;
                        $evidence->is_image = false;
                        $evidence->base64_content = null;
                        Log::warning("Archivo no encontrado: {$evidence->file_path}");
                    }
                } catch (\Exception $e) {
                    $evidence->file_found = false;
                    $evidence->is_image = false;
                    $evidence->base64_content = null;
                    Log::error("Error procesando archivo {$evidence->file_path}: " . $e->getMessage());
                }
            } else {
                $evidence->file_found = false;
                $evidence->is_image = false;
                $evidence->base64_content = null;
            }

            return $evidence;
        });
    }

    /**
     * Buscar archivo de evidencia en ubicaciones posibles
     */
    private function findEvidenceFile(string $filePath): ?string
    {
        $possiblePaths = [
            storage_path('app/public/' . $filePath),
            storage_path('app/public/evidences/' . basename($filePath)),
            storage_path('app/' . $filePath),
            public_path('storage/' . $filePath),
            public_path($filePath),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Convertir archivo a Base64
     */
    private function fileToBase64(string $filePath, string $extension): string
    {
        $imageData = base64_encode(file_get_contents($filePath));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';

        return "data:$mimeType;base64,$imageData";
    }
}
