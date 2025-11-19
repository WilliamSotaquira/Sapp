<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$task = \App\Models\Task::withTrashed()->where('task_code', 'IMP-20251119-007')->first();

if ($task) {
    echo "Tarea 007 encontrada:\n";
    echo "ID: {$task->id}\n";
    echo "Título: {$task->title}\n";
    echo "Deleted at: " . ($task->deleted_at ?? 'NULL') . "\n";
} else {
    echo "Tarea 007 NO encontrada\n";
}
