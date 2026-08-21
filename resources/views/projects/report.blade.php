@extends('layouts.app')

@section('title', 'Informe - ' . $project->name)

@section('content')
<div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.show', $project) }}" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Informe del Proyecto</h1>
                <p class="text-sm text-gray-500">{{ $project->name }} ({{ $project->code }})</p>
            </div>
        </div>
        <a href="{{ route('projects.export-report', $project) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-file-csv" aria-hidden="true"></i> Exportar CSV
        </a>
    </div>

    {{-- Datos del proyecto --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Información General</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div><p class="text-gray-400">Estado</p><p class="font-semibold">{{ $data['project']['status'] }}</p></div>
            <div><p class="text-gray-400">Progreso</p><p class="font-semibold">{{ $data['project']['progress'] }}%</p></div>
            <div><p class="text-gray-400">Inicio</p><p class="font-semibold">{{ $data['project']['start_date'] ?? '—' }}</p></div>
            <div><p class="text-gray-400">Fin estimado</p><p class="font-semibold">{{ $data['project']['expected_end_date'] ?? '—' }}</p></div>
        </div>
        @if($data['project']['description'])
            <p class="text-xs text-gray-600 mt-3 border-t border-gray-100 pt-3">{{ $data['project']['description'] }}</p>
        @endif
    </div>

    {{-- Resumen de métricas --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-xl font-bold text-gray-900">{{ $data['summary']['total_requests'] }}</div>
            <div class="text-[10px] text-gray-500">Solicitudes</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-xl font-bold text-green-700">{{ $data['summary']['resolved_requests'] }}</div>
            <div class="text-[10px] text-gray-500">Resueltas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-xl font-bold text-gray-900">{{ $data['summary']['completed_tasks'] }}/{{ $data['summary']['total_tasks'] }}</div>
            <div class="text-[10px] text-gray-500">Tareas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-xl font-bold text-red-700">{{ $data['summary']['total_hours'] }}h</div>
            <div class="text-[10px] text-gray-500">Invertidas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-xl font-bold text-gray-700">{{ $data['summary']['total_evidences'] }}</div>
            <div class="text-[10px] text-gray-500">Evidencias</div>
        </div>
    </div>

    {{-- Línea de tiempo --}}
    @if($data['summary']['first_activity'] || $data['summary']['last_resolution'])
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-2 mb-5 text-xs text-blue-700 flex items-center gap-3">
        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
        <span>
            @if($data['summary']['first_activity'])
                Primera actividad: <strong>{{ $data['summary']['first_activity'] }}</strong>
            @endif
            @if($data['summary']['last_resolution'])
                — Última resolución: <strong>{{ $data['summary']['last_resolution'] }}</strong>
            @endif
        </span>
    </div>
    @endif

    {{-- Tabla de solicitudes --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Detalle de Solicitudes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Ticket</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Título</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Estado</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Subservicio</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Tareas</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Horas</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Evidencias</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($data['requests'] as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-gray-600">{{ $req['ticket'] }}</td>
                            <td class="px-3 py-2 text-gray-900 max-w-[200px] truncate">{{ $req['title'] }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold
                                    {{ in_array($req['status'], ['RESUELTA', 'CERRADA']) ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $req['status'] === 'EN_PROCESO' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $req['status'] === 'PENDIENTE' ? 'bg-gray-100 text-gray-700' : '' }}
                                    {{ $req['status'] === 'PAUSADA' ? 'bg-amber-100 text-amber-700' : '' }}
                                ">{{ $req['status'] }}</span>
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ Str::limit($req['sub_service'] ?? '', 30) }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $req['tasks_completed'] }}/{{ $req['tasks_count'] }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $req['hours_spent'] }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $req['evidences_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabla de tareas --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Detalle de Tareas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Solicitud</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Tarea</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Estado</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Est.</th>
                        <th class="px-3 py-2 text-center text-gray-500 font-medium">Real</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-medium">Completada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($data['requests'] as $req)
                        @foreach($req['tasks'] as $task)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono text-gray-500">{{ $req['ticket'] }}</td>
                                <td class="px-3 py-2 text-gray-900 max-w-[250px] truncate">{{ $task['title'] }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold
                                        {{ $task['status'] === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $task['status'] === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $task['status'] === 'pending' ? 'bg-gray-100 text-gray-700' : '' }}
                                        {{ $task['status'] === 'blocked' ? 'bg-red-100 text-red-700' : '' }}
                                    ">{{ $task['status'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-600">{{ $task['estimated_hours'] ?? '—' }}h</td>
                                <td class="px-3 py-2 text-center text-gray-600">{{ $task['actual_hours'] ?? '—' }}h</td>
                                <td class="px-3 py-2 text-gray-500">{{ $task['completed_at'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Nota de auditoría --}}
    <div class="mt-5 p-3 bg-gray-50 border border-gray-200 rounded-lg text-[10px] text-gray-400 text-center">
        Informe generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}
    </div>
</div>
@endsection
