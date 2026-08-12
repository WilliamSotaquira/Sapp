@extends('layouts.app')

@section('title', 'Indicadores de Rendimiento')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-chart-line text-indigo-600" aria-hidden="true"></i>
                Indicadores de Rendimiento
            </h1>
            <p class="text-sm text-gray-600 mt-1">Análisis operativo de los últimos {{ $days }} días</p>
        </div>
        <div class="flex items-center gap-2">
            @foreach([7 => '7d', 14 => '14d', 30 => '30d', 60 => '60d', 90 => '90d'] as $d => $label)
                <a href="{{ route('performance-metrics.index', ['days' => $d]) }}"
                   class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $days === $d ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Métricas de volumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $metrics['volume']['created'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Creadas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-700">{{ $metrics['volume']['resolved'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Resueltas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-blue-700">{{ $metrics['volume']['active_now'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Activas ahora</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold {{ $metrics['volume']['resolution_rate'] >= 70 ? 'text-green-700' : 'text-orange-700' }}">
                {{ $metrics['volume']['resolution_rate'] }}%
            </div>
            <div class="text-xs text-gray-500 mt-1">Tasa resolución</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold {{ $metrics['sla_compliance']['rate'] >= 80 ? 'text-green-700' : 'text-red-700' }}">
                {{ $metrics['sla_compliance']['rate'] }}%
            </div>
            <div class="text-xs text-gray-500 mt-1">Cumplimiento SLA</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-700">{{ $metrics['alert_summary']['total'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Alertas activas</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        {{-- Cuello de botella --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-hourglass-half text-orange-500" aria-hidden="true"></i>
                Tiempos Promedio por Fase
            </h2>

            @php
                $phases = [
                    'acceptance' => ['label' => 'Aceptación', 'icon' => 'fa-handshake', 'color' => 'emerald'],
                    'response' => ['label' => 'Inicio de trabajo', 'icon' => 'fa-play', 'color' => 'blue'],
                    'resolution' => ['label' => 'Resolución', 'icon' => 'fa-check-circle', 'color' => 'green'],
                    'total' => ['label' => 'Total (creación → resolución)', 'icon' => 'fa-flag-checkered', 'color' => 'indigo'],
                ];
                $maxAvg = max(array_column($metrics['phase_times'], 'avg')) ?: 1;
            @endphp

            <div class="space-y-3">
                @foreach($phases as $key => $phase)
                    @php
                        $data = $metrics['phase_times'][$key];
                        $barWidth = $maxAvg > 0 ? min(100, round(($data['avg'] / $maxAvg) * 100)) : 0;
                        $isBottleneck = $key === $metrics['bottleneck']['phase'];
                        $hours = round($data['avg'] / 60, 1);
                    @endphp
                    <div class="{{ $isBottleneck ? 'bg-orange-50 border border-orange-200 rounded-lg p-2.5' : 'p-1' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-700 flex items-center gap-1.5">
                                <i class="fas {{ $phase['icon'] }} text-{{ $phase['color'] }}-500" aria-hidden="true"></i>
                                {{ $phase['label'] }}
                                @if($isBottleneck && $key !== 'total')
                                    <span class="text-[10px] bg-orange-200 text-orange-800 px-1.5 py-0.5 rounded font-semibold">CUELLO DE BOTELLA</span>
                                @endif
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $hours >= 24 ? round($hours / 24, 1) . 'd' : $hours . 'h' }}
                                <span class="text-gray-400">({{ $data['count'] }} SR)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-{{ $phase['color'] }}-500 h-2 rounded-full transition-all" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Brechas SLA --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-500" aria-hidden="true"></i>
                Brechas de SLA (últimos {{ $days }} días)
            </h2>

            @php
                $breach = $metrics['breach_summary'];
            @endphp

            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-xl font-bold text-red-700">{{ $breach['by_type']['ACEPTACION'] }}</div>
                    <div class="text-xs text-red-600">Aceptación</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">~{{ round($breach['avg_breach_minutes']['ACEPTACION'] / 60, 1) }}h exceso</div>
                </div>
                <div class="text-center p-3 bg-orange-50 rounded-lg">
                    <div class="text-xl font-bold text-orange-700">{{ $breach['by_type']['RESPUESTA'] }}</div>
                    <div class="text-xs text-orange-600">Respuesta</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">~{{ round($breach['avg_breach_minutes']['RESPUESTA'] / 60, 1) }}h exceso</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <div class="text-xl font-bold text-yellow-700">{{ $breach['by_type']['RESOLUCION'] }}</div>
                    <div class="text-xs text-yellow-600">Resolución</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">~{{ round($breach['avg_breach_minutes']['RESOLUCION'] / 60, 1) }}h exceso</div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500">Total de brechas en el período:</span>
                    <span class="font-semibold text-gray-900">{{ $breach['total'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        {{-- Efectividad de priorización --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-sort-amount-up text-purple-500" aria-hidden="true"></i>
                Efectividad de Priorización
                @if($metrics['priority_effectiveness']['is_effective'])
                    <span class="text-[10px] bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-semibold">EFECTIVA</span>
                @else
                    <span class="text-[10px] bg-red-100 text-red-800 px-2 py-0.5 rounded-full font-semibold">REVISAR</span>
                @endif
            </h2>

            <p class="text-xs text-gray-500 mb-3">¿Las solicitudes de mayor prioridad se resuelven más rápido?</p>

            <div class="space-y-2">
                @foreach($metrics['priority_effectiveness']['by_priority'] as $priority => $data)
                    @if($data['count'] > 0)
                        @php
                            $priorityColors = ['P0' => 'red', 'P1' => 'orange', 'P2' => 'yellow', 'P3' => 'blue', 'P4' => 'gray'];
                            $color = $priorityColors[$priority] ?? 'gray';
                        @endphp
                        <div class="flex items-center justify-between py-1.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                            <span class="text-xs font-medium">
                                <span class="inline-block w-6 text-center px-1 py-0.5 rounded bg-{{ $color }}-100 text-{{ $color }}-800 font-bold text-[10px]">{{ $priority }}</span>
                            </span>
                            <span class="text-xs text-gray-600">{{ $data['count'] }} solicitudes</span>
                            <span class="text-xs font-semibold text-gray-900">
                                {{ $data['avg_hours'] >= 24 ? round($data['avg_hours'] / 24, 1) . ' días' : $data['avg_hours'] . 'h' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Impacto de complejidad --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-layer-group text-teal-500" aria-hidden="true"></i>
                Impacto de Complejidad
                @if($metrics['complexity_impact']['impact_factor'])
                    <span class="text-[10px] bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full font-semibold">
                        Alta = {{ $metrics['complexity_impact']['impact_factor'] }}x más lenta
                    </span>
                @endif
            </h2>

            <p class="text-xs text-gray-500 mb-3">¿Cuánto más tardan las solicitudes complejas?</p>

            <div class="space-y-3">
                @foreach($metrics['complexity_impact']['by_complexity'] as $level => $data)
                    @if($data['count'] > 0)
                        @php
                            $levelLabels = ['BAJA' => 'Baja', 'MEDIA' => 'Media', 'ALTA' => 'Alta'];
                            $levelColors = ['BAJA' => 'green', 'MEDIA' => 'yellow', 'ALTA' => 'red'];
                        @endphp
                        <div class="flex items-center justify-between py-1.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                                <span class="w-2.5 h-2.5 rounded-full bg-{{ $levelColors[$level] }}-500"></span>
                                {{ $levelLabels[$level] }}
                            </span>
                            <span class="text-xs text-gray-600">{{ $data['count'] }} SR</span>
                            <span class="text-xs font-semibold text-gray-900">
                                {{ $data['avg_days'] > 1 ? $data['avg_days'] . ' días' : $data['avg_hours'] . 'h' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tendencias semanales --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar text-blue-500" aria-hidden="true"></i>
            Tendencia Semanal (Creadas vs Resueltas)
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 text-gray-500 font-medium">Semana</th>
                        <th class="text-center py-2 text-gray-500 font-medium">Creadas</th>
                        <th class="text-center py-2 text-gray-500 font-medium">Resueltas</th>
                        <th class="text-center py-2 text-gray-500 font-medium">Balance</th>
                        <th class="text-left py-2 text-gray-500 font-medium pl-3">Proporción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics['trends'] as $week)
                        @php
                            $maxWeekly = max(collect($metrics['trends'])->max('created'), collect($metrics['trends'])->max('resolved'), 1);
                            $createdWidth = round(($week['created'] / $maxWeekly) * 100);
                            $resolvedWidth = round(($week['resolved'] / $maxWeekly) * 100);
                        @endphp
                        <tr class="border-b border-gray-50">
                            <td class="py-2 font-medium text-gray-700">{{ $week['week_label'] }}</td>
                            <td class="py-2 text-center text-gray-900">{{ $week['created'] }}</td>
                            <td class="py-2 text-center text-green-700">{{ $week['resolved'] }}</td>
                            <td class="py-2 text-center {{ $week['net'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ $week['net'] >= 0 ? '+' : '' }}{{ $week['net'] }}
                            </td>
                            <td class="py-2 pl-3">
                                <div class="flex items-center gap-1 h-4">
                                    <div class="bg-blue-300 h-3 rounded" style="width: {{ $createdWidth }}%"></div>
                                    <div class="bg-green-400 h-3 rounded" style="width: {{ $resolvedWidth }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
            <span class="flex items-center gap-1"><span class="w-3 h-2 bg-blue-300 rounded"></span> Creadas</span>
            <span class="flex items-center gap-1"><span class="w-3 h-2 bg-green-400 rounded"></span> Resueltas</span>
        </div>
    </div>

    {{-- Links --}}
    <div class="flex items-center justify-between text-sm">
        <a href="{{ route('operational-alerts.index') }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <i class="fas fa-bell" aria-hidden="true"></i> Ver alertas activas
        </a>
        <a href="{{ route('settings.alerts.edit') }}" class="text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fas fa-cog" aria-hidden="true"></i> Configurar umbrales
        </a>
    </div>
</div>
@endsection
