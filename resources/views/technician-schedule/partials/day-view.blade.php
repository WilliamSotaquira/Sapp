<!-- Vista de Día Mejorada -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-800">
                {{ \Carbon\Carbon::parse($date)->format('l, j \d\e F \d\e Y') }}
            </h2>
            <!-- Quick Actions -->
            <div class="flex gap-2">
                <button onclick="openBlockModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition-all">
                    <i class="fas fa-ban"></i> <span class="hidden sm:inline">Bloquear Horario</span>
                </button>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="flex flex-wrap gap-3 text-xs mb-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-blue-100 border rounded"></div><span>Hoy</span></div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-yellow-100 border rounded"></div><span>No hábil</span></div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-red-100 border-l-4 border-red-500 rounded"></div><span>Impacto</span></div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-blue-100 border-l-4 border-blue-500 rounded"></div><span>Regular</span></div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-purple-200 border rounded"></div><span>Bloqueado</span></div>
        </div>
    </div>

    <!-- Cuadrícula de horas -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" id="schedule-table">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left" style="width: 80px;">Hora</th>
                    <th class="border px-4 py-2 text-center {{ \Carbon\Carbon::parse($date)->isToday() ? 'bg-blue-50' : '' }}">
                        <div class="font-bold">Tareas Programadas</div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @php
                    $hours = ['06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
                    $blocks = $scheduleBlocks ?? collect();
                @endphp

                @foreach($hours as $hourLabel)
                    @php
                        $hourInt = (int) substr($hourLabel, 0, 2);
                        $isDayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
                        $isNonWorkingHour = $hourInt < 8 || $hourInt >= 16;
                        $isSunday = $isDayOfWeek === 0;
                        $isToday = \Carbon\Carbon::parse($date)->isToday();
                        $bgClass = $isSunday ? 'bg-red-50' : ($isNonWorkingHour ? 'bg-yellow-50' : ($isToday ? 'bg-blue-50' : ''));

                        $hourTasks = $tasks->filter(fn($t) => $t->scheduled_start_time && substr($t->scheduled_start_time, 0, 2) === substr($hourLabel, 0, 2));
                        $hourBlocks = $blocks->filter(fn($b) => substr($b->start_time, 0, 2) === substr($hourLabel, 0, 2));
                    @endphp
                    <tr class="hour-row" data-hour="{{ $hourInt }}">
                        <td class="border px-3 py-2 bg-gray-50 font-medium text-sm relative" style="height: 60px;">
                            {{ $hourLabel }}
                            @if($isNonWorkingHour || $isSunday)<span class="text-orange-500 ml-1">⚠️</span>@endif
                        </td>
                        <td class="border {{ $bgClass }} relative" style="height: 60px; padding: 0;" data-date="{{ $date }}" data-hour="{{ substr($hourLabel, 0, 2) }}">
                            <!-- Indicador de hora actual -->
                            @if($isToday)
                                <div class="current-time-indicator hidden absolute left-0 right-0 z-30 pointer-events-none" data-hour="{{ $hourInt }}">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-red-500 rounded-full shadow-lg"></div>
                                        <div class="flex-1 h-0.5 bg-red-500 shadow"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Bloqueos de horario -->
                            @foreach($hourBlocks as $block)
                                <div class="absolute inset-x-1 rounded text-xs p-2 z-5 opacity-90" style="background-color: {{ $block->block_color }}20; border-left: 3px solid {{ $block->block_color }}; top: 2px; bottom: 2px;">
                                    <div class="flex items-center gap-1 text-gray-700 font-medium">
                                        <i class="fas {{ $block->block_icon }}"></i>
                                        <span>{{ $block->title ?? $block->block_label }}</span>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Tareas -->
                            @foreach($hourTasks as $index => $task)
                                @php
                                    $taskHeight = max(50, $task->estimated_hours * 60);
                                    $startMinutes = (int)substr($task->scheduled_start_time, 3, 2);
                                    $topOffset = ($startMinutes / 60) * 60;
                                    $taskCount = $hourTasks->count();
                                    $widthPercent = $taskCount > 1 ? (100 / $taskCount) - 1 : 98;
                                    $leftPercent = $taskCount > 1 ? ($index * (100 / $taskCount)) + 1 : 1;
                                @endphp
                                <div class="task-card absolute rounded text-sm cursor-move z-10 overflow-hidden transition-all hover:shadow-lg hover:z-20
                                            {{ $task->type === 'impact' ? 'bg-red-100 border-l-4 border-red-500' : 'bg-blue-100 border-l-4 border-blue-500' }}"
                                     data-task-id="{{ $task->id }}"
                                     data-task-url="{{ route('tasks.show', $task) }}"
                                     data-duration="{{ $task->estimated_hours }}"
                                     style="height: {{ $taskHeight }}px; top: {{ $topOffset }}px; left: {{ $leftPercent }}%; width: {{ $widthPercent }}%; min-height: 50px; padding: 6px;"
                                     onclick="if (!isDragging && !isResizing) window.location=this.dataset.taskUrl"
                                     title="{{ $task->title }}">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="font-mono text-xs bg-gray-800 text-white px-1.5 py-0.5 rounded">{{ $task->task_code }}</span>
                                        <span class="px-1.5 py-0.5 text-xs rounded-full bg-{{ $task->status_color }}-200 text-{{ $task->status_color }}-800">{{ $task->status }}</span>
                                    </div>
                                    <div class="font-semibold text-gray-800 text-xs truncate"><i class="fas fa-grip-vertical text-gray-400 mr-1"></i>{{ Str::limit($task->title, 30) }}</div>
                                    <div class="text-gray-600 text-xs mt-1"><i class="fas fa-clock"></i> {{ $task->formatted_duration }}</div>
                                    <div class="resize-handle absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 hover:opacity-50 bg-gray-400"></div>
                                </div>
                            @endforeach

                            @if($hourTasks->isEmpty() && $hourBlocks->isEmpty())
                                <div class="text-center text-gray-300 text-xs py-4"><i class="fas fa-plus-circle"></i> Disponible</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Resumen del día -->
    <div class="p-4 bg-gray-50 border-t">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            @php
                $totalTasks = $tasks->count();
                $impactTasks = $tasks->where('type', 'impact')->count();
                $regularTasks = $tasks->where('type', 'regular')->count();
                $completed = $tasks->where('status', 'completed')->count();
            @endphp
            <div class="bg-white p-3 rounded-lg shadow-sm"><div class="text-2xl font-bold text-blue-600">{{ $totalTasks }}</div><div class="text-xs text-gray-600">Total</div></div>
            <div class="bg-white p-3 rounded-lg shadow-sm"><div class="text-2xl font-bold text-red-600">{{ $impactTasks }}</div><div class="text-xs text-gray-600">Impacto</div></div>
            <div class="bg-white p-3 rounded-lg shadow-sm"><div class="text-2xl font-bold text-blue-600">{{ $regularTasks }}</div><div class="text-xs text-gray-600">Regulares</div></div>
            <div class="bg-white p-3 rounded-lg shadow-sm"><div class="text-2xl font-bold text-green-600">{{ $completed }}</div><div class="text-xs text-gray-600">Completadas</div></div>
        </div>
    </div>
</div>

<!-- Modal para bloquear horario -->
<div id="blockModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md animate-scale-in">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-ban text-purple-600 mr-2"></i>Bloquear Horario</h3>
            <button onclick="closeBlockModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('technician-schedule.store-block') }}" method="POST" class="p-4 space-y-4">
            @csrf
            <input type="hidden" name="block_date" value="{{ $date }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Bloqueo</label>
                <select name="block_type" class="w-full border rounded-lg px-3 py-2" required>
                    <option value="meeting">🗣️ Reunión</option>
                    <option value="lunch">🍽️ Almuerzo</option>
                    <option value="break">☕ Descanso</option>
                    <option value="unavailable">🚫 No Disponible</option>
                    <option value="vacation">🏖️ Vacaciones</option>
                    <option value="training">📚 Capacitación</option>
                    <option value="other">📅 Otro</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Título</label><input type="text" name="title" class="w-full border rounded-lg px-3 py-2" placeholder="Ej: Reunión de equipo" required></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hora Inicio</label><input type="time" name="start_time" class="w-full border rounded-lg px-3 py-2" value="09:00" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hora Fin</label><input type="time" name="end_time" class="w-full border rounded-lg px-3 py-2" value="10:00" required></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label><textarea name="description" class="w-full border rounded-lg px-3 py-2" rows="2" placeholder="Notas adicionales..."></textarea></div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg font-medium transition-colors">Guardar</button>
                <button type="button" onclick="closeBlockModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg font-medium transition-colors">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<style>
.hour-row { height: 60px !important; }
.hour-row td { height: 60px !important; vertical-align: top; }
.task-card { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.task-card:hover { transform: translateY(-1px); }
.animate-scale-in { animation: scaleIn 0.2s ease-out; }
@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.current-time-indicator { transition: top 0.5s ease; }
</style>

<script>
// Indicador de hora actual
function updateCurrentTimeIndicator() {
    const now = new Date();
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    
    document.querySelectorAll('.current-time-indicator').forEach(el => {
        const hour = parseInt(el.dataset.hour);
        if (hour === currentHour) {
            el.classList.remove('hidden');
            const topPercent = (currentMinute / 60) * 100;
            el.style.top = topPercent + '%';
        } else {
            el.classList.add('hidden');
        }
    });
}

// Actualizar cada minuto
setInterval(updateCurrentTimeIndicator, 60000);
updateCurrentTimeIndicator();

// Modal functions
function openBlockModal() { document.getElementById('blockModal').classList.remove('hidden'); }
function closeBlockModal() { document.getElementById('blockModal').classList.add('hidden'); }

// Close modal on escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeBlockModal(); });
</script>
