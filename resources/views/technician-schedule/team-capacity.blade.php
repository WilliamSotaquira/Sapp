@extends('layouts.app')

@section('title', 'Capacidad del Equipo')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li>
            <a href="{{ route('technician-schedule.index') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-calendar-alt"></i>
                <span class="ml-1">Calendario</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-chart-bar"></i>
            <span class="ml-1">Capacidad del Equipo</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto">
    <!-- Info y controles -->
    <div class="mb-4 sm:mb-6">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-3 sm:gap-4">
            <div class="flex-1">
                <p class="text-sm sm:text-base text-gray-600">
                    <i class="fas fa-calendar-day"></i> {{ \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('l, j \\d\\e F \\d\\e Y') }}
                </p>
                <p class="text-xs sm:text-sm text-blue-600 mt-2">
                    <i class="fas fa-info-circle"></i> Mostrando solo las tareas programadas para este día.
                    <span class="hidden sm:inline">Haz clic en el número de tareas para ver el detalle.</span>
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 lg:flex-shrink-0">
                <input type="date" id="dateSelector" value="{{ $date }}"
                       onchange="window.location.href='{{ route('technician-schedule.team-capacity') }}?date=' + this.value"
                       class="w-full sm:w-auto text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <a href="{{ route('technician-schedule.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                    <i class="fas fa-calendar"></i> <span class="hidden sm:inline">Calendario</span><span class="sm:hidden">Cal.</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="bg-white border-l-4 border-blue-500 p-3 sm:p-4 rounded-lg shadow">
            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600">{{ $teamStats['total_technicians'] }}</div>
            <div class="text-xs sm:text-sm text-gray-600 mt-1">Técnicos<span class="hidden sm:inline"> Activos</span></div>
        </div>

        <div class="bg-white border-l-4 border-gray-500 p-3 sm:p-4 rounded-lg shadow">
            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-600">{{ $teamStats['total_tasks_today'] }}</div>
            <div class="text-xs sm:text-sm text-gray-600 mt-1">Total<span class="hidden sm:inline"> Tareas</span></div>
        </div>

        <div class="bg-white border-l-4 border-green-500 p-3 sm:p-4 rounded-lg shadow">
            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600">{{ $teamStats['completed_tasks_today'] }}</div>
            <div class="text-xs sm:text-sm text-gray-600 mt-1">Completadas</div>
        </div>

        <div class="bg-white border-l-4 border-blue-500 p-3 sm:p-4 rounded-lg shadow">
            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-blue-600">{{ $teamStats['in_progress_tasks_today'] }}</div>
            <div class="text-xs sm:text-sm text-gray-600 mt-1">En Progreso</div>
        </div>

        <div class="bg-white border-l-4 border-yellow-500 p-3 sm:p-4 rounded-lg shadow sm:col-span-3 lg:col-span-1">
            <div class="text-xl sm:text-2xl md:text-3xl font-bold text-yellow-600">{{ $teamStats['pending_tasks_today'] }}</div>
            <div class="text-xs sm:text-sm text-gray-600 mt-1">Pendientes</div>
        </div>
    </div>

    <!-- Capacidad por Técnico -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-3 sm:p-4 md:p-6 border-b">
            <h2 class="text-base sm:text-lg md:text-xl font-bold text-gray-800">Utilización de Capacidad por Técnico</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Técnico
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tareas Asignadas
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Capacidad Total
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Capacidad Usada
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Disponible
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Utilización
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($capacityData as $data)
                        @php
                            $statusColors = [
                                'overloaded' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-triangle', 'label' => 'Sobrecargado'],
                                'high' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'fa-arrow-up', 'label' => 'Alta Carga'],
                                'optimal' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle', 'label' => 'Óptimo'],
                                'low' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-arrow-down', 'label' => 'Baja Carga'],
                                'underutilized' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-minus-circle', 'label' => 'Subutilizado'],
                            ];
                            $statusInfo = $statusColors[$data['status']] ?? $statusColors['optimal'];
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($data['technician']->user->name, 0, 2)) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $data['technician']->user->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ ucfirst($data['technician']->experience_level) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button onclick="toggleTaskDetails('tech-{{ $data['technician']->id }}')" class="text-blue-600 hover:text-blue-800">
                                    <span class="font-semibold">{{ $data['tasks_count'] }}</span> tareas
                                    <i class="fas fa-chevron-down ml-1" id="icon-tech-{{ $data['technician']->id }}"></i>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($data['total_capacity'] == 0)
                                    <span class="text-gray-400 text-xs">Sin configurar</span>
                                @else
                                    {{ round($data['total_capacity'] / 60, 1) }} horas
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ round($data['used_capacity'] / 60, 1) }} horas
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($data['total_capacity'] == 0)
                                    <span class="text-gray-400 text-xs">N/A</span>
                                @elseif($data['is_over_allocated'])
                                    <span class="font-semibold text-red-600">
                                        0 horas
                                    </span>
                                    <span class="text-xs text-red-500 block">(Sobrecargado)</span>
                                @else
                                    <span class="font-semibold text-green-600">
                                        {{ round($data['available_capacity'] / 60, 1) }} horas
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <!-- Barra de progreso -->
                                @if($data['total_capacity'] == 0)
                                    <span class="text-xs text-gray-400">Sin capacidad configurada</span>
                                @else
                                    <div class="flex items-center">
                                        <div class="w-32 bg-gray-200 rounded-full h-4 mr-2">
                                            <div class="bg-{{ $data['utilization_percentage'] >= 90 ? 'red' : ($data['utilization_percentage'] >= 75 ? 'orange' : 'green') }}-600 h-4 rounded-full"
                                                 style="width: {{ min($data['utilization_percentage'], 100) }}%">
                                            </div>
                                        </div>
                                        <span class="text-sm font-semibold {{ $data['is_over_allocated'] ? 'text-red-600' : '' }}">
                                            {{ $data['utilization_percentage'] }}%
                                            @if($data['is_over_allocated'])
                                                <i class="fas fa-exclamation-triangle ml-1 text-xs" title="Sobrecargado"></i>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusInfo['bg'] }} {{ $statusInfo['text'] }}">
                                    <i class="fas {{ $statusInfo['icon'] }} mr-1"></i>
                                    {{ $statusInfo['label'] }}
                                </span>
                            </td>
                        </tr>
                        <!-- Fila expandible con detalle de tareas -->
                        <tr id="tasks-tech-{{ $data['technician']->id }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="text-sm">
                                    <h4 class="font-semibold text-gray-700 mb-2">Tareas asignadas para {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}:</h4>
                                    @if($data['technician']->tasks->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($data['technician']->tasks as $task)
                                                <div class="flex items-center justify-between bg-white p-3 rounded border border-gray-200">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono text-xs bg-gray-800 text-white px-2 py-1 rounded">{{ $task->task_code }}</span>
                                                            <span class="font-medium">{{ $task->title }}</span>
                                                            <span class="px-2 py-1 text-xs rounded bg-{{ $task->priority === 'critical' ? 'red' : ($task->priority === 'high' ? 'orange' : 'blue') }}-100">
                                                                {{ ucfirst($task->priority) }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-600 mt-1">
                                                            <i class="fas fa-clock"></i> {{ substr($task->scheduled_start_time, 0, 5) }}
                                                            <span class="mx-2">•</span>
                                                            <i class="fas fa-hourglass-half"></i> {{ $task->estimated_hours }} horas ({{ round($task->estimated_hours * 60) }} minutos)
                                                            <span class="mx-2">•</span>
                                                            <span class="px-2 py-0.5 rounded bg-{{ $task->status === 'completed' ? 'green' : ($task->status === 'in_progress' ? 'blue' : 'gray') }}-100">
                                                                {{ ucfirst($task->status) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('tasks.show', $task) }}" class="text-blue-600 hover:text-blue-800 ml-4">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-gray-300">
                                            <div class="text-sm font-semibold text-gray-700">
                                                Total: {{ $data['technician']->tasks->sum('estimated_hours') }} horas
                                                ({{ $data['technician']->tasks->sum(function($t) { return round($t->estimated_hours * 60); }) }} minutos)
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-gray-500 italic">No hay tareas asignadas para este día</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráfico de utilización (Placeholder - se puede implementar con Chart.js) -->
    <div class="mt-4 sm:mt-6 bg-white rounded-lg shadow-md p-3 sm:p-4 md:p-6">
        <h2 class="text-base sm:text-lg md:text-xl font-bold text-gray-800 mb-3 sm:mb-4">Distribución de Capacidad</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            @php
                $overloaded = $capacityData->where('status', 'overloaded')->count();
                $high = $capacityData->where('status', 'high')->count();
                $optimal = $capacityData->where('status', 'optimal')->count();
                $low = $capacityData->where('status', 'low')->count();
                $underutilized = $capacityData->where('status', 'underutilized')->count();
                $total = $capacityData->count();
            @endphp

            <div class="text-center p-3 sm:p-4 bg-red-50 rounded-lg">
                <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-red-600">{{ $overloaded + $high }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Sobrecargados<span class="hidden sm:inline"> / Alta Carga</span></div>
                @if($total > 0)
                    <div class="text-xs text-gray-500 mt-1">{{ round((($overloaded + $high) / $total) * 100) }}% <span class="hidden sm:inline">del equipo</span></div>
                @endif
            </div>

            <div class="text-center p-3 sm:p-4 bg-green-50 rounded-lg">
                <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-green-600">{{ $optimal }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Utilización Óptima</div>
                @if($total > 0)
                    <div class="text-xs text-gray-500 mt-1">{{ round(($optimal / $total) * 100) }}% <span class="hidden sm:inline">del equipo</span></div>
                @endif
            </div>

            <div class="text-center p-3 sm:p-4 bg-gray-50 rounded-lg">
                <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-600">{{ $low + $underutilized }}</div>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">Baja Utilización</div>
                @if($total > 0)
                    <div class="text-xs text-gray-500 mt-1">{{ round((($low + $underutilized) / $total) * 100) }}% <span class="hidden sm:inline">del equipo</span></div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recomendaciones -->
    @if($overloaded > 0 || ($low + $underutilized) > 0)
        <div class="mt-4 sm:mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-3 sm:p-4 md:p-6 rounded-lg">
            <h3 class="text-base sm:text-lg font-bold text-yellow-800 mb-2 sm:mb-3">
                <i class="fas fa-lightbulb"></i> Recomendaciones
            </h3>
            <ul class="space-y-2 text-xs sm:text-sm text-yellow-700">
                @if($overloaded > 0)
                    <li><i class="fas fa-exclamation-triangle"></i> <strong>{{ $overloaded }}</strong> técnico(s) sobrecargado(s). Considera redistribuir tareas.</li>
                @endif
                @if($low + $underutilized > 1)
                    <li><i class="fas fa-info-circle"></i> <strong>{{ $low + $underutilized }}</strong> técnico(s) con baja utilización. Podrían asumir más tareas.</li>
                @endif
                @if($overloaded > 0 && ($low + $underutilized) > 0)
                    <li><i class="fas fa-balance-scale"></i> Balancea la carga entre técnicos sobrecargados y subutilizados para optimizar el rendimiento del equipo.</li>
                @endif
            </ul>
        </div>
    @endif
</div>

<script>
function toggleTaskDetails(techId) {
    const detailRow = document.getElementById('tasks-' + techId);
    const icon = document.getElementById('icon-' + techId);

    if (detailRow.classList.contains('hidden')) {
        detailRow.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        detailRow.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>
@endsection
