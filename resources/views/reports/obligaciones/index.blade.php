@extends('layouts.app')

@section('content')
<div class="py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Reporte de Obligaciones</h1>
        <p class="text-gray-600 mt-2">Gestión de obligaciones, actividades y productos ejecutados</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="{{ route('reports.obligaciones.index') }}" class="space-y-4" id="cutFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Corte</label>
                    <select name="cut_id" id="cutFilterSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Todos los cortes</option>
                        @foreach($cuts as $cut)
                            <option value="{{ $cut->id }}" {{ (string)($filters['cut_id'] ?? '') === (string)$cut->id ? 'selected' : '' }}>
                                {{ $cut->name }} ({{ $cut->start_date?->format('d/m/Y') }} - {{ $cut->end_date?->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @php
                $exportParams = array_filter([
                    'cut_id' => $filters['cut_id'] ?? null,
                ], function ($value) {
                    return $value !== null && $value !== '' && $value !== 'all';
                });
            @endphp
            <div class="flex gap-2">
                <a href="{{ route('reports.obligaciones.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-semibold py-2 px-6 rounded-lg">
                    Limpiar
                </a>
                <a href="{{ route('reports.obligaciones.export', array_merge($exportParams, ['format' => 'pdf'])) }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg ml-auto">
                    Descargar PDF
                </a>
                <a href="{{ route('reports.obligaciones.export', array_merge($exportParams, ['format' => 'xlsx'])) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-lg">
                    Descargar Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Obligaciones Agrupadas por Familia -->
    @if($serviceRequests->count() > 0)
        <div class="space-y-6">
            @foreach($serviceRequests as $serviceName => $obligaciones)
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Encabezado de la Familia -->
                    <div class="bg-blue-600 px-6 py-3">
                        @php
                            $familyDescription = $obligaciones->first()?->subService?->service?->family?->description;
                        @endphp
                        <h2 class="text-lg font-bold text-white">{{ $serviceName }}</h2>
                        @if($familyDescription)
                            <p class="text-blue-100 text-sm mt-1">{{ $familyDescription }}</p>
                        @endif
                    </div>

                    <!-- Tabla de Obligaciones de la Familia -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <colgroup>
                            <col class="w-[35%]">
                            <col class="w-[35%]">
                            <col class="w-[30%]">
                        </colgroup>
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Obligaciones</th>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Actividades Ejecutadas</th>
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-700">Productos Presentados</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($obligaciones as $sr)
                                <tr class="hover:bg-gray-50">
                                    <!-- OBLIGACIONES -->
                                    <td class="px-6 py-3 text-sm">
                                        <p class="font-semibold text-gray-900">{{ $sr->title }}</p>
                                        @if($sr->description)
                                            <p class="text-xs text-gray-600 mt-1">{{ Str::limit($sr->description, 80) }}</p>
                                        @endif
                                    </td>

                                    <!-- ACTIVIDADES EJECUTADAS -->
                                    <td class="px-6 py-3 text-sm">
                                        @if($sr->tasks->count() > 0)
                                            <div class="space-y-2">
                                                @foreach($sr->tasks as $task)
                                                    <div>
                                                        <p class="font-semibold text-gray-900 text-xs">{{ $task->title }}</p>
                                                        @if($task->subtasks->count() > 0)
                                                            <ul class="mt-1 space-y-1">
                                                                @foreach($task->subtasks as $subtask)
                                                                    <li class="text-xs text-gray-700">- {{ $subtask->title }}
                                                                        @if($subtask->evidence_completed)
                                                                            <span class="text-green-600 ml-1">✓</span>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-500">—</p>
                                        @endif
                                    </td>

                                    <!-- PRODUCTOS PRESENTADOS -->
                                    <td class="px-6 py-3 text-sm">
                                        @if($sr->evidences->where('file_path')->count() > 0)
                                            <ul class="space-y-1">
                                                @foreach($sr->evidences->where('file_path') as $evidence)
                                                    <li>
                                                        <a href="{{ $evidence->file_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-semibold underline">
                                                            {{ $evidence->file_original_name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-xs text-gray-500">—</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No se encontraron obligaciones con los filtros especificados</p>
        </div>
    @endif
</div>

<style>
    .bg-gradient-to-r {
        background-image: linear-gradient(to right, var(--tw-gradient-stops));
    }
</style>
<script>
    (function () {
        const select = document.getElementById('cutFilterSelect');
        const form = document.getElementById('cutFilterForm');

        if (!select || !form) {
            return;
        }

        select.addEventListener('change', function () {
            form.submit();
        });
    })();
</script>
@endsection
