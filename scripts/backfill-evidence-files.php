<?php

declare(strict_types=1);

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// CLI options
$args = $argv ?? [];
$dryRun = in_array('--dry-run', $args, true);
$pathArg = null;
$userId = null;

foreach ($args as $arg) {
    if (strpos($arg, '--path=') === 0) {
        $pathArg = substr($arg, 7);
    }
    if (strpos($arg, '--user-id=') === 0) {
        $userId = (int) substr($arg, 10);
    }
}

if ($userId === null || $userId <= 0) {
    $userId = 1;
}

$baseDir = public_path('storage/evidences');
if ($pathArg) {
    $baseDir = rtrim($pathArg, DIRECTORY_SEPARATOR);
}

if (!is_dir($baseDir)) {
    fwrite(STDERR, "Base directory not found: {$baseDir}\n");
    exit(1);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$created = 0;
$skipped = 0;
$missingTickets = 0;

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

foreach ($rii as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $fullPath = $fileInfo->getPathname();
    $relativeToBase = ltrim(str_replace($baseDir, '', $fullPath), DIRECTORY_SEPARATOR);

    // Expecting evidences/<TICKET>/<FILENAME>
    $parts = explode(DIRECTORY_SEPARATOR, $relativeToBase);
    if (count($parts) < 2) {
        $skipped++;
        continue;
    }

    $ticketNumber = $parts[0];
    $fileName = $parts[count($parts) - 1];
    $relativeStoragePath = 'evidences/' . $ticketNumber . '/' . $fileName;

    $serviceRequest = ServiceRequest::where('ticket_number', $ticketNumber)->first();
    if (!$serviceRequest) {
        $missingTickets++;
        continue;
    }

    $exists = ServiceRequestEvidence::where('service_request_id', $serviceRequest->id)
        ->where('file_path', $relativeStoragePath)
        ->exists();

    if ($exists) {
        $skipped++;
        continue;
    }

    $mimeType = $finfo->file($fullPath) ?: 'application/octet-stream';
    $fileSize = filesize($fullPath) ?: 0;

    $payload = [
        'service_request_id' => $serviceRequest->id,
        'title' => $fileName,
        'description' => 'Archivo subido: ' . $fileName,
        'evidence_type' => 'ARCHIVO',
        'file_path' => $relativeStoragePath,
        'file_original_name' => $fileName,
        'file_mime_type' => $mimeType,
        'file_size' => $fileSize,
        'user_id' => $userId,
    ];

    if ($dryRun) {
        echo "[DRY-RUN] Would create evidence for ticket {$ticketNumber}: {$relativeStoragePath}\n";
        $created++;
        continue;
    }

    ServiceRequestEvidence::create($payload);
    echo "Created evidence for ticket {$ticketNumber}: {$relativeStoragePath}\n";
    $created++;
}

echo "\nSummary\n";
echo "Created: {$created}\n";
echo "Skipped: {$skipped}\n";
echo "Missing tickets: {$missingTickets}\n";
