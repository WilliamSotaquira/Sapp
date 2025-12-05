@extends('layouts.app')

@section('title', 'Vista Gantt - Equipo')

@section('breadcrumb')
<nav class="text-sm mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2 text-gray-600">
        <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('technician-schedule.index') }}" class="hover:text-blue-600"><i class="fas fa-calendar-alt"></i> Calendario</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium"><i class="fas fa-stream"></i> Vista Gantt</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto px-4">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Vista Gantt del Equipo</h1>
            <p class="text-gray-600">{{ \Carbon\Carbon::parse($date)->format('l, j \d\e F \d\e Y') }}</p>
        </div>
        <div class="flex gap-2">
            <button onclick="navigateDate('prev')" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg"><i class="fas fa-chevron-left"></i></button>
            <input type="date" id="dateSelector" value="{{ $date }}" onchange="changeDate()" class="border rounded-lg px-3 py-2">
            <button onclick="navigateDate('today')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Hoy</button>
            <button onclick="navigateDate('next')" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="bg-white rounded-lg shadow-sm p-3 mb-4 flex flex-wrap gap-4 text-sm">
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-red-200 border-l-4 border-red-500 rounded"></div><span>Impacto</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-blue-200 border-l-4 border-blue-500 rounded"></div><span>Regular</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-green-200 border-l-4 border-green-500 rounded"></div><span>Completada</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-purple-200 rounded"></div><span>Bloqueado</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-yellow-100 rounded"></div><span>No hábil</span></div>
    </div>

    <!-- Gantt Chart -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[1200px]">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-3 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-100 z-20" style="min-width: 180px;">
                            Técnico
                        </th>
                        @foreach($hours as $hour)
                            @php $hourInt = (int)substr($hour, 0, 2); @endphp
                            <th class="border px-2 py-3 text-center text-sm font-medium {{ $hourInt < 8 || $hourInt >= 16 ? 'bg-yellow-50 text-yellow-700' : 'text-gray-700' }}" style="min-width: 80px;">
                                {{ $hour }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($technicians as $technician)
                        @php
                            $techTasks = $tasks->get($technician->id, collect());
                            $techBlocks = $blocks->get($technician->id, collect());
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <!-- Nombre del técnico -->
                            <td class="border px-3 py-2 sticky left-0 bg-white z-10">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold shadow">
                                        {{ strtoupper(substr($technician->user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ $technician->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $techTasks->count() }} tareas</div>
                                    </div>
                                </div>
                            </td>
                            <!-- Celdas de horas -->
                            @foreach($hours as $hour)
                                @php
                                    $hourInt = (int)substr($hour, 0, 2);
                                    $isNonWorking = $hourInt < 8 || $hourInt >= 16;
                                    $hourTasks = $techTasks->filter(fn($t) => (int)substr($t->scheduled_start_time, 0, 2) === $hourInt);
                                    $hourBlocks = $techBlocks->filter(fn($b) => (int)substr($b->start_time, 0, 2) === $hourInt);
                                @endphp
                                <td class="border px-1 py-1 relative {{ $isNonWorking ? 'bg-yellow-50/50' : '' }}" style="height: 70px;">
                                    <!-- Bloqueos -->
                                    @foreach($hourBlocks as $block)
                                        <div class="absolute inset-1 rounded text-xs p-1 flex items-center gap-1 z-5" 
                                             style="background-color: {{ $block->block_color }}30; border-left: 3px solid {{ $block->block_color }};">
                                            <i class="fas {{ $block->block_icon }} text-gray-600"></i>
                                            <span class="truncate">{{ Str::limit($block->title, 10) }}</span>
                                        </div>
                                    @endforeach
                                    <!-- Tareas -->
                                    @foreach($hourTasks as $task)
                                        @php
                                            $taskColor = $task->status === 'completed' ? 'green' : ($task->type === 'impact' ? 'red' : 'blue');
                                        @endphp
                                        <a href="{{ route('tasks.show', $task) }}" 
                                           class="absolute inset-1 rounded text-xs p-1.5 z-10 bg-{{ $taskColor }}-100 border-l-4 border-{{ $taskColor }}-500 hover:shadow-md transition-shadow flex flex-col justify-center overflow-hidden"
                                           title="{{ $task->title }} - {{ $task->formatted_duration }}">
                                            <div class="font-semibold text-gray-800 truncate text-[11px]">{{ Str::limit($task->title, 12) }}</div>
                                            <div class="text-gray-600 text-[10px]"><i class="fas fa-clock"></i> {{ $task->formatted_duration }}</div>
                                        </a>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($hours) + 1 }}" class="text-center py-12 text-gray-500">
                                <i class="fas fa-users text-4xl mb-3 block text-gray-300"></i>
                                No hay técnicos activos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resumen -->
    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $technicians->count() }}</div>
            <div class="text-sm text-gray-600">Técnicos</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-3xl font-bold text-purple-600">{{ $tasks->flatten()->count() }}</div>
            <div class="text-sm text-gray-600">Tareas Totales</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $tasks->flatten()->where('status', 'completed')->count() }}</div>
            <div class="text-sm text-gray-600">Completadas</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-3xl font-bold text-orange-600">{{ $blocks->flatten()->count() }}</div>
            <div class="text-sm text-gray-600">Bloqueos</div>
        </div>
    </div>
</div>

<script>
function changeDate() {
    const date = document.getElementById('dateSelector').value;
    window.location.href = `{{ route('technician-schedule.gantt') }}?date=${date}`;
}

function navigateDate(direction) {
    const dateInput = document.getElementById('dateSelector');
    const currentDate = new Date(dateInput.value);
    
    if (direction === 'prev') currentDate.setDate(currentDate.getDate() - 1);
    else if (direction === 'next') currentDate.setDate(currentDate.getDate() + 1);
    else if (direction === 'today') return window.location.href = '{{ route('technician-schedule.gantt') }}';
    
    dateInput.value = currentDate.toISOString().split('T')[0];
    changeDate();
}
</script>
@endsection
