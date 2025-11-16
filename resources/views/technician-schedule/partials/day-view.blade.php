<!-- Vista de Día -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            {{ \Carbon\Carbon::parse($date)->format('l, j \d\e F \d\e Y') }}
        </h2>

        <!-- Leyenda de horarios -->
        <div class="flex gap-4 text-xs mb-4">
            <div class="flex items-center gap-1">
                <div class="w-4 h-4 bg-blue-50 border"></div>
                <span>Día actual</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="w-4 h-4 bg-yellow-50 border"></div>
                <span>⚠️ Horario no hábil (6-8am, 4-6pm)</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="w-4 h-4 bg-red-50 border"></div>
                <span>🗓️ Domingo</span>
            </div>
        </div>
    </div>

    <!-- Cuadrícula de horas -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left" style="width: 100px;">Hora</th>
                    <th class="border px-4 py-2 text-center {{ \Carbon\Carbon::parse($date)->isToday() ? 'bg-blue-50' : '' }}">
                        <div class="font-bold">Tareas Programadas</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @php
                    $hours = [
                        ['time' => '06:00', 'label' => '06:00'],
                        ['time' => '07:00', 'label' => '07:00'],
                        ['time' => '08:00', 'label' => '08:00'],
                        ['time' => '09:00', 'label' => '09:00'],
                        ['time' => '10:00', 'label' => '10:00'],
                        ['time' => '11:00', 'label' => '11:00'],
                        ['time' => '12:00', 'label' => '12:00'],
                        ['time' => '13:00', 'label' => '13:00'],
                        ['time' => '14:00', 'label' => '14:00'],
                        ['time' => '15:00', 'label' => '15:00'],
                        ['time' => '16:00', 'label' => '16:00'],
                        ['time' => '17:00', 'label' => '17:00'],
                    ];
                @endphp

                @foreach($hours as $hour)
                    @php
                        $hourInt = (int) substr($hour['time'], 0, 2);
                        $isDayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
                        $isNonWorkingHour = $hourInt < 8 || $hourInt >= 16;
                        $isSunday = $isDayOfWeek === 0;
                        $bgClass = '';

                        if ($isSunday) {
                            $bgClass = 'bg-red-50';
                        } elseif ($isNonWorkingHour) {
                            $bgClass = 'bg-yellow-50';
                        } elseif (\Carbon\Carbon::parse($date)->isToday()) {
                            $bgClass = 'bg-blue-50';
                        }
                    @endphp
                    <tr>
                        <td class="border px-4 py-2 bg-gray-50 font-medium text-sm" style="height: 60px;">
                            {{ $hour['label'] }}
                            @if($isNonWorkingHour || $isSunday)
                                <span class="text-xs text-orange-600">⚠️</span>
                            @endif
                        </td>
                        <td class="border {{ $bgClass }}" style="height: 60px; padding: 0; position: relative;">
                            <div class="relative" style="height: 60px; overflow: visible;"
                                 data-date="{{ $date }}"
                                 data-hour="{{ substr($hour['time'], 0, 2) }}">
                            @php
                                $hourTasks = $tasks->filter(function($task) use ($hour) {
                                    if (!$task->scheduled_start_time) return false;
                                    $taskHour = substr($task->scheduled_start_time, 0, 2);
                                    $slotHour = substr($hour['time'], 0, 2);
                                    return $taskHour === $slotHour;
                                });

                                $taskCount = $hourTasks->count();
                            @endphp

                            @foreach($hourTasks as $index => $task)
                                @php
                                    // Calcular altura basada en duración (60px por hora)
                                    $taskHeight = $task->estimated_hours * 60;
                                    // Calcular minutos de inicio para posición vertical
                                    $startMinutes = (int)substr($task->scheduled_start_time, 3, 2);
                                    $topOffset = ($startMinutes / 60) * 60;

                                    // Calcular ancho y posición horizontal para múltiples tareas
                                    $widthPercent = $taskCount > 1 ? (100 / $taskCount) : 100;
                                    $leftPercent = $taskCount > 1 ? ($index * $widthPercent) : 0;

                                    // Detectar horario no hábil
                                    $taskHourInt = (int)substr($task->scheduled_start_time, 0, 2);
                                    $taskDate = \Carbon\Carbon::parse($task->scheduled_date);
                                    $isNonWorking = $taskDate->dayOfWeek === 0 || $taskHourInt < 8 || $taskHourInt >= 16;
                                @endphp
                                <div class="task-card p-2 rounded text-sm bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-100 border-l-4 border-{{ $task->type === 'impact' ? 'red' : 'blue' }}-500 hover:shadow-md transition-shadow cursor-move absolute overflow-hidden z-10 {{ $isNonWorking ? 'ring-2 ring-orange-400' : '' }}"
                                     data-task-id="{{ $task->id }}"
                                     data-task-url="{{ route('tasks.show', $task) }}"
                                     data-duration="{{ $task->estimated_hours }}"
                                     style="height: {{ $taskHeight }}px; top: {{ $topOffset }}px; left: {{ $leftPercent }}%; width: {{ $widthPercent }}%; min-height: 60px; padding: 8px;"
                                     onclick="if (!isDragging && !isResizing && !justResized && !justDragged) window.location=this.dataset.taskUrl"
                                     title="{{ $task->title }} - Arrastra para mover | Redimensiona desde abajo{{ $isNonWorking ? ' | ⚠️ HORARIO NO HÁBIL' : '' }}">
                                    @if($isNonWorking)
                                        <div class="absolute top-0 right-0 bg-orange-500 text-white text-xs px-1 rounded-bl" title="Horario no hábil">⚠️</div>
                                    @endif
                                    @if($taskHeight >= 80)
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-mono text-xs bg-gray-800 text-white px-2 py-1 rounded">
                                            {{ $task->task_code }}
                                        </span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-800">
                                            {{ $task->status }}
                                        </span>
                                    </div>
                                    @endif
                                    <div class="font-semibold text-gray-800 truncate" title="{{ $task->title }}" style="font-size: 0.85rem; line-height: 1.2;">
                                        <i class="fas fa-grip-vertical text-gray-400 mr-1" style="font-size: 0.75rem;"></i>{{ Str::limit($task->title, $taskCount > 1 ? 25 : 40) }}
                                    </div>
                                    @if($taskHeight >= 60)
                                    <div class="text-gray-600 text-xs truncate" style="margin-top: 4px;">
                                        <i class="fas fa-user"></i> {{ Str::limit($task->technician->user->name ?? 'Sin asignar', 20) }}
                                    </div>
                                    @endif
                                    <div class="duration-display font-bold text-center" style="margin-top: 4px; font-size: 0.75rem; color: #1f2937; background: rgba(255,255,255,0.8); padding: 3px 6px; border-radius: 4px;">
                                        <i class="fas fa-clock"></i> {{ $task->formatted_duration }}
                                    </div>
                                    @if($taskHeight >= 80 && ($task->priority === 'critical' || $task->priority === 'high'))
                                    <div class="text-red-600 font-semibold text-xs" style="margin-top: 4px;">⚠️ {{ strtoupper($task->priority) }}</div>
                                    @endif
                                    <!-- Handle para redimensionar -->
                                    <div class="resize-handle absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize hover:bg-gray-400 transition-colors"
                                         style="opacity: 0;"
                                         onmouseenter="this.style.opacity='0.5'"
                                         onmouseleave="if(!isResizing) this.style.opacity='0'"
                                         title="Arrastra para cambiar duración">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-grip-lines text-xs text-gray-600"></i>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if($hourTasks->count() === 0)
                                <div class="text-center text-gray-300 text-xs py-3 empty-slot" style="position: absolute; top: 0; left: 0; right: 0;">
                                    <i class="fas fa-plus-circle"></i> Disponible
                                </div>
                            @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Resumen del día -->
    <div class="p-6 bg-gray-50 border-t">
        <div class="grid grid-cols-4 gap-4 text-center">
            @php
                $totalTasks = $tasks->count();
                $impactTasks = $tasks->where('type', 'impact')->count();
                $regularTasks = $tasks->where('type', 'regular')->count();
                $completed = $tasks->where('status', 'completed')->count();
            @endphp
            <div>
                <div class="text-2xl font-bold text-blue-600">{{ $totalTasks }}</div>
                <div class="text-sm text-gray-600">Total Tareas</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-red-600">{{ $impactTasks }}</div>
                <div class="text-sm text-gray-600">Tareas Impacto</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-blue-600">{{ $regularTasks }}</div>
                <div class="text-sm text-gray-600">Tareas Regulares</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-green-600">{{ $completed }}</div>
                <div class="text-sm text-gray-600">Completadas</div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para drag & drop en vista día */
    .drop-target {
        transition: all 0.2s ease;
    }

    .empty-slot {
        opacity: 0.3;
        transition: all 0.2s ease;
    }

    /* Estilos para tareas */
    [data-task-id] {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Prevenir desbordamiento */
    .task-card {
        box-sizing: border-box;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .task-card > * {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Forzar altura fija de celdas y filas */
    tbody tr {
        height: 60px !important;
        max-height: 60px !important;
    }

    tbody td {
        height: 60px !important;
        max-height: 60px !important;
        overflow: visible !important;
        vertical-align: top;
        box-sizing: border-box;
    }

    td[data-date], div[data-date] {
        position: relative;
        height: 60px !important;
        max-height: 60px !important;
    }

    /* Asegurar que las tareas se vean encima de los bordes */
    .task-card {
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }

    .task-card:hover {
        z-index: 20 !important;
    }

    [data-task-id]:hover {
        transform: translateY(-2px);
    }

    [data-task-id]:active {
        cursor: grabbing !important;
    }

    /* Estilos para resize */
    .task-card {
        position: relative;
        min-height: 60px;
        transition: height 0.1s ease-out;
    }

    /* Desactivar transición durante resize activo */
    .task-card[style*="border"] {
        transition: none;
    }

    .resize-handle {
        z-index: 5;
        border-radius: 0 0 4px 4px;
        background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.05));
    }

    .resize-handle:hover {
        opacity: 0.7 !important;
        background: linear-gradient(to bottom, transparent, rgba(59, 130, 246, 0.3)) !important;
    }

    .task-card:hover .resize-handle {
        opacity: 0.3 !important;
    }

    /* Indicador visual durante resize */
    .task-card[style*="border"] {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* Asegurar que la duración siempre sea visible */
    .duration-display {
        font-weight: 600;
        white-space: nowrap;
    }
</style>
