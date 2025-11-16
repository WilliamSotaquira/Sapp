<!-- Vista de Mes -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            {{ $startOfMonth->format('F Y') }}
        </h2>
    </div>

    <!-- Cuadrícula de mes -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2">Lunes</th>
                    <th class="border px-4 py-2">Martes</th>
                    <th class="border px-4 py-2">Miércoles</th>
                    <th class="border px-4 py-2">Jueves</th>
                    <th class="border px-4 py-2">Viernes</th>
                    <th class="border px-4 py-2">Sábado</th>
                    <th class="border px-4 py-2">Domingo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weeks as $week)
                    <tr>
                        @foreach($week as $day)
                            <td class="border p-2 align-top h-32 {{ !$day['is_current_month'] ? 'bg-gray-100' : '' }} {{ $day['date']->isToday() ? 'bg-blue-50 ring-2 ring-blue-500' : '' }}">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-semibold {{ !$day['is_current_month'] ? 'text-gray-400' : 'text-gray-800' }}">
                                        {{ $day['date']->format('j') }}
                                    </span>
                                    @if($day['task_count'] > 0)
                                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full">
                                            {{ $day['task_count'] }}
                                        </span>
                                    @endif
                                </div>

                                @if($day['task_count'] > 0)
                                    <div class="space-y-1">
                                        @foreach($day['tasks']->take(3) as $task)
                                            <div class="text-xs p-1 rounded bg-{{ $task->type === 'impact' ? 'red' : 'blue' }}-100 truncate">
                                                {{ $task->title }}
                                            </div>
                                        @endforeach
                                        @if($day['task_count'] > 3)
                                            <div class="text-xs text-gray-500 text-center">
                                                +{{ $day['task_count'] - 3 }} más
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
