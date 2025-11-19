<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Tareas IMP del 2025-11-19 ===" . PHP_EOL . PHP_EOL;

$tasks = DB::table('tasks')
    ->where('task_code', 'like', 'IMP-20251119-%')
    ->orderBy('task_code')
    ->get(['id', 'task_code', 'title', 'created_at']);

if ($tasks->isEmpty()) {
    echo "No hay tareas IMP para esta fecha." . PHP_EOL;
} else {
    echo "Total: " . $tasks->count() . " tareas" . PHP_EOL . PHP_EOL;
    foreach ($tasks as $task) {
        echo "ID: {$task->id} | Código: {$task->task_code} | Título: {$task->title} | Creada: {$task->created_at}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Última tarea IMP ===" . PHP_EOL;
$lastTask = DB::table('tasks')
    ->where('task_code', 'like', 'IMP-20251119-%')
    ->orderBy('task_code', 'desc')
    ->first(['task_code']);

if ($lastTask) {
    echo "Última: {$lastTask->task_code}" . PHP_EOL;
    $parts = explode('-', $lastTask->task_code);
    $nextSeq = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
    echo "Próximo número secuencial: " . sprintf('%03d', $nextSeq) . PHP_EOL;
    echo "Próximo código: IMP-20251119-" . sprintf('%03d', $nextSeq) . PHP_EOL;
} else {
    echo "No hay tareas previas" . PHP_EOL;
}
