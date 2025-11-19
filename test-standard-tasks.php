<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TAREAS PREDEFINIDAS POR SUBSERVICIO ===\n\n";

$subServices = App\Models\SubService::whereHas('standardTasks')
    ->with(['standardTasks.standardSubtasks', 'service'])
    ->get();

foreach ($subServices as $subService) {
    echo "📋 {$subService->name} ({$subService->code})\n";
    echo "   Servicio: {$subService->service->name}\n";
    echo "   Tareas predefinidas: {$subService->standardTasks->count()}\n\n";

    foreach ($subService->standardTasks as $task) {
        echo "   ✅ {$task->title}\n";
        echo "      Prioridad: {$task->priority} | Horas estimadas: {$task->estimated_hours}\n";
        if ($task->standardSubtasks->count() > 0) {
            echo "      Subtareas: {$task->standardSubtasks->count()}\n";
            foreach ($task->standardSubtasks as $subtask) {
                echo "         - {$subtask->title}\n";
            }
        }
        echo "\n";
    }
    echo str_repeat('-', 80) . "\n\n";
}

echo "\nTOTAL: {$subServices->count()} subservicios con tareas predefinidas\n";
