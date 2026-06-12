<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check SLAs directly
$slas = \App\Models\ServiceLevelAgreement::where('is_active', true)->get(['id', 'service_subservice_id', 'name', 'is_active']);

echo "=== SLAs ACTIVOS ===" . PHP_EOL;
foreach ($slas as $sla) {
    echo "  SLA #{$sla->id}: {$sla->name} → service_subservice_id: {$sla->service_subservice_id}" . PHP_EOL;
}

echo PHP_EOL . "=== SERVICE_SUBSERVICES ===" . PHP_EOL;
$serviceSubservices = \App\Models\ServiceSubservice::with(['subService', 'service.family'])->get();
foreach ($serviceSubservices as $ss) {
    $hasSla = $slas->contains('service_subservice_id', $ss->id);
    $familyName = $ss->service?->family?->name ?? '?';
    $subName = $ss->subService?->name ?? $ss->name ?? '?';
    $status = $hasSla ? 'CON SLA' : 'SIN SLA';
    echo "  [{$ss->id}] {$familyName} > {$subName} → {$status}" . PHP_EOL;
}

