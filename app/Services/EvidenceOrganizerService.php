<?php

namespace App\Services;

use App\DTOs\OrganizationResult;
use App\DTOs\ValidationResult;
use App\Models\Cut;
use App\Models\EvidenceOrganizationLog;
use App\Models\ServiceRequestEvidence;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EvidenceOrganizerService
{
    /**
     * Default relative path for evidence storage when no custom path is configured.
     */
    private const DEFAULT_RELATIVE_PATH = 'app/public/evidences/cortes';

    /**
     * Resolve the base storage path for evidence organization.
     *
     * Uses the configured system setting value, falling back to the default
     * path when the setting is null, empty, or contains only whitespace.
     */
    public function resolveBasePath(): string
    {
        $configured = SystemSetting::get('evidence_base_path');

        if ($configured === null || trim($configured) === '') {
            return storage_path(self::DEFAULT_RELATIVE_PATH);
        }

        return $configured;
    }

    /**
     * Validate a custom folder name for a cut.
     *
     * Checks:
     * - Only alphanumeric characters, hyphens, and underscores allowed
     * - Length between 1 and 128 characters
     * - No duplicate folder name within the base path
     */
    public function validateFolderName(string $name, string $basePath): ValidationResult
    {
        $errors = [];

        // Check regex pattern: alphanumeric, hyphens, underscores only
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            $errors[] = 'El nombre de carpeta solo puede contener caracteres alfanuméricos, guiones y guiones bajos.';
        }

        // Check length constraints
        if (strlen($name) < 1 || strlen($name) > 128) {
            $errors[] = 'El nombre de carpeta debe tener entre 1 y 128 caracteres.';
        }

        // Check uniqueness within the base path (filesystem or database)
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (is_dir($fullPath) || Cut::where('folder_path', $fullPath)->exists()) {
            $errors[] = 'Ya existe un corte con el mismo nombre de carpeta en la ruta base.';
        }

        if (!empty($errors)) {
            return ValidationResult::fail($errors);
        }

        return ValidationResult::pass();
    }

    /**
     * Generate the suggested folder path for a new cut.
     *
     * Returns a path following the pattern: {basePath}/{contract_number}/{MM}
     * Where contract_number comes from the cut's associated contract and MM is the month.
     */
    public function suggestFolderPath(int $cutId, Carbon $startDate, ?string $contractNumber = null): string
    {
        $basePath = $this->resolveBasePath();

        if ($contractNumber) {
            $sanitizedContract = $this->sanitizeForFilesystem($contractNumber);
            return rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR
                . $sanitizedContract . DIRECTORY_SEPARATOR
                . $startDate->format('m') . '-' . $startDate->format('Y');
        }

        // Fallback for when no contract number is available
        return rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'corte-' . $cutId . '-' . $startDate->format('Y-m-d');
    }

    /**
     * Create the cut folder on the filesystem.
     *
     * If the directory already exists, returns true (reuse without overwriting).
     * Otherwise, creates the directory recursively with 0755 permissions.
     *
     * @param string $folderPath The full path to create
     * @return bool True on success or if directory already exists, false on failure
     */
    public function createCutFolder(string $folderPath): bool
    {
        // If directory already exists, reuse without overwriting (Req 2.5)
        if (is_dir($folderPath)) {
            return true;
        }

        try {
            return mkdir($folderPath, 0755, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Organize a batch of evidence files into the cut's folder structure.
     *
     * Each file is processed independently: failure in one does not block others.
     * The method uses a copy-verify-delete pattern with backup for integrity.
     *
     * @param Cut $cut The target cut with a defined folder_path
     * @param array<int> $evidenceIds IDs of evidences to organize (1-50)
     * @return OrganizationResult Result with succeeded/failed arrays
     */
    public function organizeEvidences(Cut $cut, array $evidenceIds): OrganizationResult
    {
        $succeeded = [];
        $failed = [];
        $userId = Auth::id();
        $basePath = $this->resolveBasePath();
        $backupDir = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . '_backups';

        // Ensure backup directory exists
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        foreach ($evidenceIds as $evidenceId) {
            try {
                $this->processEvidence($evidenceId, $cut, $backupDir, $userId, $succeeded, $failed);
            } catch (\Throwable $e) {
                $failed[] = ['evidence_id' => $evidenceId, 'reason' => $e->getMessage()];
                $this->logOperation(
                    $evidenceId,
                    $cut->id,
                    $userId,
                    '',
                    '',
                    'failed',
                    $e->getMessage()
                );
            }
        }

        return OrganizationResult::fromArrays($succeeded, $failed);
    }

    /**
     * Process a single evidence file for organization.
     */
    private function processEvidence(
        int $evidenceId,
        Cut $cut,
        string $backupDir,
        ?int $userId,
        array &$succeeded,
        array &$failed
    ): void {
        // Acquire row-level lock with 5-second timeout
        $evidence = null;
        try {
            $evidence = DB::transaction(function () use ($evidenceId) {
                return ServiceRequestEvidence::where('id', $evidenceId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();
            }, 5);
        } catch (\Throwable $e) {
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'No se pudo adquirir el bloqueo de fila: conflicto de modificación concurrente.'];
            $this->logOperation($evidenceId, $cut->id, $userId, '', '', 'failed', 'Lock timeout: ' . $e->getMessage());
            return;
        }

        // Validate: record exists and is not soft-deleted
        if (!$evidence) {
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'Registro de evidencia no encontrado o eliminado.'];
            $this->logOperation($evidenceId, $cut->id, $userId, '', '', 'skipped', 'Record not found or soft-deleted');
            return;
        }

        $sourcePath = $evidence->file_path;

        // Handle ENLACE-type evidence: skip filesystem, register URL only (Req 3.4)
        if ($evidence->evidence_type === 'ENLACE') {
            $this->handleEnlaceEvidence($evidence, $cut, $userId, $succeeded);
            return;
        }

        // Validate source file exists on disk
        $absoluteSourcePath = $this->resolveAbsoluteFilePath($sourcePath);
        if (!$absoluteSourcePath || !file_exists($absoluteSourcePath)) {
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'Archivo fuente no encontrado en disco.'];
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath ?? '', '', 'skipped', 'Source file not found on disk');
            return;
        }

        // Determine ticket number from associated service request
        $serviceRequest = $evidence->serviceRequest;
        $ticketNumber = $serviceRequest ? $this->sanitizeForFilesystem($serviceRequest->ticket_number ?? 'sin-ticket') : 'sin-ticket';

        // Destination is directly in the cut folder (flat structure, no subdirectories)
        $destinationDir = rtrim($cut->folder_path, '/\\');
        if (!is_dir($destinationDir)) {
            @mkdir($destinationDir, 0755, true);
        }

        // Determine destination filename with unique identifier: {ticket_number}_{original_filename}
        $originalFilename = basename($absoluteSourcePath);
        $prefixedFilename = $ticketNumber . '_' . $originalFilename;
        $destinationFilename = $this->resolveUniqueFilename($destinationDir, $prefixedFilename);
        $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $destinationFilename;

        // Step 1: Create backup
        $backupFilename = $evidenceId . '_' . time() . '_' . $originalFilename;
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFilename;

        if (!@copy($absoluteSourcePath, $backupPath)) {
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'No se pudo crear la copia de respaldo.'];
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath, '', 'failed', 'Backup copy failed');
            return;
        }

        // Step 2: Copy file to destination
        if (!@copy($absoluteSourcePath, $destinationPath)) {
            // Cleanup backup
            @unlink($backupPath);
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'No se pudo copiar el archivo al destino.'];
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath, $destinationPath, 'failed', 'Copy to destination failed');
            return;
        }

        // Step 3: Verify file size matches (Req 4.2)
        $sourceSize = filesize($absoluteSourcePath);
        $destSize = filesize($destinationPath);

        if ($sourceSize !== $destSize) {
            // Verification failed: delete corrupt destination, restore is not needed (source still exists)
            @unlink($destinationPath);
            @unlink($backupPath);
            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'Verificación de tamaño falló: el archivo destino no coincide con el origen.'];
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath, $destinationPath, 'failed', "Size mismatch: source={$sourceSize}, dest={$destSize}");
            return;
        }

        // Step 4: Update DB in transaction, then cleanup
        $newRelativePath = $this->buildRelativePath($destinationPath);

        try {
            DB::transaction(function () use ($evidence, $newRelativePath) {
                // Re-lock and update file_path
                $lockedEvidence = ServiceRequestEvidence::where('id', $evidence->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedEvidence) {
                    $lockedEvidence->file_path = $newRelativePath;
                    $lockedEvidence->save();
                }
            }, 5);

            // Success: delete backup and delete source
            @unlink($backupPath);

            // Delete the source file from its original location
            if (file_exists($absoluteSourcePath)) {
                $deleted = unlink($absoluteSourcePath);
                if (!$deleted) {
                    Log::warning("EvidenceOrganizer: Could not delete source file after successful move", [
                        'evidence_id' => $evidenceId,
                        'source_path' => $absoluteSourcePath,
                    ]);
                }
            }

            $succeeded[] = $evidenceId;
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath, $newRelativePath, 'success', null);
        } catch (\Throwable $e) {
            // DB transaction failed: restore from backup, remove destination
            @unlink($destinationPath);
            if (file_exists($backupPath) && !file_exists($absoluteSourcePath)) {
                @copy($backupPath, $absoluteSourcePath);
            }
            @unlink($backupPath);

            $failed[] = ['evidence_id' => $evidenceId, 'reason' => 'Error en la transacción de base de datos: ' . $e->getMessage()];
            $this->logOperation($evidenceId, $cut->id, $userId, $sourcePath, $destinationPath, 'failed', 'DB transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle ENLACE-type evidence: only register in DB, no filesystem move.
     */
    private function handleEnlaceEvidence(
        ServiceRequestEvidence $evidence,
        Cut $cut,
        ?int $userId,
        array &$succeeded
    ): void {
        $url = $evidence->file_path ?? $evidence->evidence_data['url'] ?? $evidence->description ?? '';

        $succeeded[] = $evidence->id;
        $this->logOperation(
            $evidence->id,
            $cut->id,
            $userId,
            $url,
            $url,
            'success',
            null
        );
    }

    /**
     * Resolve the absolute filesystem path for a stored file_path.
     */
    private function resolveAbsoluteFilePath(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        // If it's already an absolute path that exists
        if (file_exists($filePath)) {
            return $filePath;
        }

        // Try relative to storage/app/public
        $storagePath = storage_path('app/public/' . ltrim($filePath, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Try stripping common prefixes
        $normalized = $filePath;
        if (strpos($normalized, 'public/') === 0) {
            $normalized = substr($normalized, 7);
        }
        if (strpos($normalized, 'storage/') === 0) {
            $normalized = substr($normalized, 8);
        }

        $storagePath = storage_path('app/public/' . ltrim($normalized, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    /**
     * Sanitize a string for use as a filesystem directory name.
     * Replaces invalid characters with underscores.
     */
    private function sanitizeForFilesystem(string $value): string
    {
        // Replace characters that are invalid in filesystem paths
        $sanitized = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '_', $value);

        // Remove leading/trailing dots and spaces
        $sanitized = trim($sanitized, '. ');

        // Ensure non-empty result
        return $sanitized ?: 'unknown';
    }

    /**
     * Resolve a unique filename at the destination, appending numeric suffix if needed.
     *
     * If "file.pdf" exists, tries "file_1.pdf", "file_2.pdf", etc.
     */
    private function resolveUniqueFilename(string $directory, string $filename): string
    {
        $destinationPath = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($destinationPath)) {
            return $filename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        $counter = 1;
        do {
            $candidateFilename = $basename . '_' . $counter . ($extension ? '.' . $extension : '');
            $candidatePath = $directory . DIRECTORY_SEPARATOR . $candidateFilename;
            $counter++;
        } while (file_exists($candidatePath));

        return $candidateFilename;
    }

    /**
     * Build a relative storage path from an absolute path.
     * This stores the path relative to storage/app/public for compatibility.
     */
    private function buildRelativePath(string $absolutePath): string
    {
        $storagePublicPath = storage_path('app/public');

        // If the path is inside storage/app/public, make it relative
        if (strpos($absolutePath, $storagePublicPath) === 0) {
            return ltrim(substr($absolutePath, strlen($storagePublicPath)), '/\\');
        }

        // Otherwise, store the absolute path
        return $absolutePath;
    }

    /**
     * Clean orphaned backup files older than 24 hours.
     *
     * Scans the _backups directory and deletes any files whose modification
    /**
     * Relocate evidence files from one cut's folder to another.
     *
     * Used when a service request moves between cuts. Moves all evidence files
     * for the given service request IDs from their current location to the
     * destination cut's folder. Verifies each move before deleting the original.
     *
     * @param Cut $destinationCut The cut receiving the service requests
     * @param array<int> $serviceRequestIds IDs of service requests being moved
     * @return void
     */
    public function relocateEvidences(Cut $destinationCut, array $serviceRequestIds): void
    {
        if (empty($destinationCut->folder_path) || empty($serviceRequestIds)) {
            return;
        }

        $destinationDir = rtrim($destinationCut->folder_path, '/\\');
        if (!is_dir($destinationDir)) {
            @mkdir($destinationDir, 0755, true);
        }

        $evidences = \App\Models\ServiceRequestEvidence::query()
            ->whereIn('service_request_id', $serviceRequestIds)
            ->whereNull('deleted_at')
            ->where('evidence_type', '!=', 'ENLACE')
            ->with('serviceRequest:id,ticket_number')
            ->get();

        foreach ($evidences as $evidence) {
            $currentPath = $this->resolveAbsoluteFilePath($evidence->file_path);

            // Skip if file doesn't exist or is already in the destination folder
            if (!$currentPath || !file_exists($currentPath)) {
                continue;
            }

            // Skip if file is already in the destination directory
            if (str_starts_with(str_replace('/', '\\', $currentPath), str_replace('/', '\\', $destinationDir))) {
                continue;
            }

            $ticketNumber = $this->sanitizeForFilesystem($evidence->serviceRequest?->ticket_number ?? 'sin-ticket');
            $originalFilename = basename($currentPath);
            $prefixedFilename = $ticketNumber . '_' . $originalFilename;

            // Remove old ticket prefix if it already has one
            if (preg_match('/^[A-Z]+-\d+_/', $originalFilename)) {
                $prefixedFilename = $ticketNumber . '_' . preg_replace('/^[A-Z]+-\d+_/', '', $originalFilename);
            }

            $destinationFilename = $this->resolveUniqueFilename($destinationDir, $prefixedFilename);
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $destinationFilename;

            // Copy to new location
            if (!@copy($currentPath, $destinationPath)) {
                Log::warning("Failed to relocate evidence #{$evidence->id} to {$destinationPath}");
                continue;
            }

            // Verify size matches
            if (filesize($currentPath) !== filesize($destinationPath)) {
                @unlink($destinationPath);
                Log::warning("Size mismatch relocating evidence #{$evidence->id}");
                continue;
            }

            // Update DB record
            try {
                $newRelativePath = $this->buildRelativePath($destinationPath);
                $evidence->file_path = $newRelativePath;
                $evidence->save();

                // Delete original file
                @unlink($currentPath);
            } catch (\Throwable $e) {
                // Rollback: delete destination, keep original
                @unlink($destinationPath);
                Log::warning("DB update failed relocating evidence #{$evidence->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clean orphaned backup files older than 24 hours.
     *
     * Scans the _backups directory and deletes any files whose modification
     * time is older than 24 hours. Returns the count of successfully deleted files.
     * Returns 0 if the _backups directory does not exist.
     *
     * @return int Number of cleaned (deleted) backup files
     */
    public function cleanOrphanedBackups(): int
    {
        $basePath = $this->resolveBasePath();
        $backupDir = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . '_backups';

        if (!is_dir($backupDir)) {
            return 0;
        }

        $cleanedCount = 0;
        $threshold = time() - (24 * 60 * 60);

        $files = @scandir($backupDir);
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            // Skip directory entries
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $backupDir . DIRECTORY_SEPARATOR . $file;

            // Only process regular files (skip subdirectories)
            if (!is_file($filePath)) {
                continue;
            }

            $modTime = @filemtime($filePath);
            if ($modTime === false) {
                continue;
            }

            if ($modTime < $threshold) {
                if (@unlink($filePath)) {
                    $cleanedCount++;
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Log an organization operation to the audit log.
     * Failures in logging are non-blocking (logged to app log instead).
     */
    private function logOperation(
        int $evidenceId,
        int $cutId,
        ?int $userId,
        string $sourcePath,
        string $destinationPath,
        string $result,
        ?string $errorMessage
    ): void {
        try {
            EvidenceOrganizationLog::create([
                'evidence_id' => $evidenceId,
                'cut_id' => $cutId,
                'user_id' => $userId,
                'source_path' => $sourcePath ?: '(empty)',
                'destination_path' => $destinationPath ?: '(empty)',
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking: log to application log if audit log write fails (per design spec)
            Log::warning("Failed to write evidence organization audit log for evidence {$evidenceId}: " . $e->getMessage());
        }
    }
}
