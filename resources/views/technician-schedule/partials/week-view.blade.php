<!-- Vista de Semana -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            Semana del {{ $startOfWeek->format('j \d\e F') }} al {{ $endOfWeek->format('j \d\e F \d\e Y') }}
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

    <!-- Cuadrícula de semana -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" style="table-layout: fixed;">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left">Hora</th>
                    @foreach($days as $day)
                        <th class="border px-4 py-2 text-center {{ $day['date']->isToday() ? 'bg-blue-50' : '' }}">
                            <div class="font-bold">{{ $day['date']->format('D') }}</div>
                            <div class="text-sm text-gray-600">{{ $day['date']->format('d/m') }}</div>
                        </th>
                    @endforeach
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
                        $isNonWorkingHour = $hourInt < 8 || $hourInt >= 16;
                    @endphp
                    <tr>
                        <td class="border px-4 py-2 bg-gray-50 font-medium text-sm" style="height: 60px;">
                            {{ $hour['label'] }}
                            @if($isNonWorkingHour)
                                <span class="text-xs text-orange-600">⚠️</span>
                            @endif
                        </td>
                        @foreach($days as $day)
                            @php
                                $isSunday = $day['date']->dayOfWeek === 0;
                                $bgClass = '';

                                if ($isSunday) {
                                    $bgClass = 'bg-red-50';
                                } elseif ($isNonWorkingHour) {
                                    $bgClass = 'bg-yellow-50';
                                } elseif ($day['date']->isToday()) {
                                    $bgClass = 'bg-blue-50';
                                }
                            @endphp
                            <td class="border {{ $bgClass }}" style="height: 60px; padding: 0; position: relative;">
                                <div class="relative drop-target" style="height: 60px; overflow: visible;"
                                     data-date="{{ $day['date']->format('Y-m-d') }}"
                                     data-hour="{{ substr($hour['time'], 0, 2) }}">
                                @php
                                    $hourTasks = $day['tasks']->filter(function($task) use ($hour) {
                                        if (!$task->scheduled_start_time) return false;
                                        // Extraer primeros 2 caracteres de la hora (formato HH:MM:SS o HH:MM)
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
                                        $topOffset = ($startMinutes / 60) * 60; // Offset desde el inicio de la hora

                                        // Calcular ancho y posición horizontal para múltiples tareas
                                        $widthPercent = $taskCount > 1 ? (100 / $taskCount) : 100;
                                        $leftPercent = $taskCount > 1 ? ($index * $widthPercent) : 0;

                                        // Detectar horario no hábil
                                        $taskHourInt = (int)substr($task->scheduled_start_time, 0, 2);
                                        $taskDate = \Carbon\Carbon::parse($task->scheduled_date);
                                        $isNonWorking = $taskDate->dayOfWeek === 0 || $taskHourInt < 8 || $taskHourInt >= 16;
                                    @endphp
                                    <div class="task-card p-1 rounded text-xs bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-100 border-l-2 border-{{ $task->type === 'impact' ? 'red' : 'blue' }}-500 hover:shadow-md transition-shadow cursor-move absolute overflow-hidden z-10 {{ $isNonWorking ? 'ring-2 ring-orange-400' : '' }}"
                                         data-task-id="{{ $task->id }}"
                                         data-task-url="{{ route('tasks.show', $task) }}"
                                         data-duration="{{ $task->estimated_hours }}"
                                         style="height: {{ $taskHeight }}px; top: {{ $topOffset }}px; left: {{ $leftPercent }}%; width: {{ $widthPercent }}%; min-height: 40px; padding: 4px;"
                                         onclick="if (!isDragging && !isResizing && !justResized && !justDragged) window.location=this.dataset.taskUrl"
                                         title="{{ $task->title }} - Arrastra para mover | Redimensiona desde abajo{{ $isNonWorking ? ' | ⚠️ HORARIO NO HÁBIL' : '' }}">
                                        @if($isNonWorking)
                                            <div class="absolute top-0 right-0 bg-orange-500 text-white px-1 rounded-bl" style="font-size: 0.6rem;" title="Horario no hábil">⚠️</div>
                                        @endif
                                        <div class="font-semibold truncate text-xs" title="{{ $task->title }}" style="line-height: 1.2;">
                                            <i class="fas fa-grip-vertical text-gray-400 mr-1" style="font-size: 0.65rem;"></i>{{ Str::limit($task->title, 20) }}
                                        </div>
                                        @if($taskHeight >= 60)
                                        <div class="text-gray-600 truncate" style="font-size: 0.65rem; line-height: 1.2; margin-top: 2px;">
                                            <i class="fas fa-user" style="font-size: 0.6rem;"></i> {{ Str::limit($task->technician->user->name ?? 'Sin asignar', 15) }}
                                        </div>
                                        @endif
                                        <div class="flex items-center justify-between" style="margin-top: 2px; font-size: 0.65rem; line-height: 1.2;">
                                            <span class="font-mono" style="font-size: 0.6rem;">{{ $task->task_code }}</span>
                                        </div>
                                        <div class="duration-display font-bold text-center" style="margin-top: 2px; font-size: 0.7rem; color: #1f2937; background: rgba(255,255,255,0.7); padding: 2px 4px; border-radius: 3px;">
                                            <i class="fas fa-clock" style="font-size: 0.65rem;"></i> {{ $task->formatted_duration }}
                                        </div>
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
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Resumen de la semana -->
    <div class="p-3 sm:p-4 md:p-6 bg-gray-50 border-t">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-center">
            @php
                $totalTasks = collect($days)->sum(fn($d) => $d['tasks']->count());
                $impactTasks = collect($days)->sum(fn($d) => $d['tasks']->where('type', 'impact')->count());
                $regularTasks = collect($days)->sum(fn($d) => $d['tasks']->where('type', 'regular')->count());
                $completed = collect($days)->sum(fn($d) => $d['tasks']->where('status', 'completed')->count());
            @endphp
            <div class="bg-white p-2 sm:p-3 rounded-lg shadow-sm">
                <div class="text-xl sm:text-2xl font-bold text-blue-600">{{ $totalTasks }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Total Tareas</div>
            </div>
            <div class="bg-white p-2 sm:p-3 rounded-lg shadow-sm">
                <div class="text-xl sm:text-2xl font-bold text-red-600">{{ $impactTasks }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Impacto</div>
            </div>
            <div class="bg-white p-2 sm:p-3 rounded-lg shadow-sm">
                <div class="text-xl sm:text-2xl font-bold text-blue-600">{{ $regularTasks }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Regulares</div>
            </div>
            <div class="bg-white p-2 sm:p-3 rounded-lg shadow-sm">
                <div class="text-xl sm:text-2xl font-bold text-green-600">{{ $completed }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Completadas</div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para drag & drop */
    .drop-target {
        transition: all 0.2s ease;
    }

    .drop-target:hover .empty-slot {
        opacity: 1;
        transform: scale(1.2);
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

    /* Limitar altura de celdas y permitir posicionamiento absoluto */
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
        border-radius: 0 0 4px 4px;
        background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.05));
    }

    .resize-handle:hover {
        background: linear-gradient(to bottom, transparent, rgba(59, 130, 246, 0.2));
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
        font-weight: 700 !important;
        white-space: nowrap;
        display: block !important;
        text-align: center;
        z-index: 1;
    }

    /* Ajustes para múltiples tareas */
    .task-card {
        border-right: 1px solid rgba(0,0,0,0.1);
        position: relative;
    }

    .task-card:hover {
        z-index: 30 !important;
        transform: scale(1.02);
    }

    /* Asegurar que el resize handle sea accesible */
    .task-card:hover .resize-handle {
        opacity: 0.3 !important;
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
</style>
