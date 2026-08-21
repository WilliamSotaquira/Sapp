@extends('layouts.app')

@section('title', 'Alertas Operativas')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Acciones --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-600">Monitoreo de solicitudes y tareas que requieren atención</p>
        <div class="flex items-center gap-2">
            <form action="{{ route('operational-alerts.mark-all-read') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        title="Marcar todas como leídas">
                    <i class="fas fa-check-double" aria-hidden="true"></i>
                    Marcar todas leídas
                </button>
            </form>
            <a href="{{ route('settings.alerts.edit') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
               title="Configurar umbrales">
                <i class="fas fa-cog" aria-hidden="true"></i>
                Configurar
            </a>
        </div>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Resumen rápido --}}
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</div>
            <div class="text-xs text-gray-500">Total activas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $summary['by_severity']['critica'] }}</div>
            <div class="text-xs text-gray-500">Críticas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $summary['by_severity']['alta'] }}</div>
            <div class="text-xs text-gray-500">Altas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $summary['by_severity']['media'] }}</div>
            <div class="text-xs text-gray-500">Medias</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $summary['by_severity']['baja'] }}</div>
            <div class="text-xs text-gray-500">Bajas</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $summary['unread'] }}</div>
            <div class="text-xs text-gray-500">Sin leer</div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-5">
        <form method="GET" action="{{ route('operational-alerts.index') }}" class="flex flex-wrap gap-3 items-end">
            {{-- Estado --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-200">
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="read" {{ $status === 'read' ? 'selected' : '' }}>Leídas</option>
                    <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resueltas</option>
                    <option value="dismissed" {{ $status === 'dismissed' ? 'selected' : '' }}>Descartadas</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todas</option>
                </select>
            </div>

            {{-- Severidad --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Severidad</label>
                <select name="severity" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-200">
                    <option value="">Todas</option>
                    @foreach(\App\Models\OperationalAlert::$severities as $key => $info)
                        <option value="{{ $key }}" {{ request('severity') === $key ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                <select name="type" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-200">
                    <option value="">Todos</option>
                    @foreach(\App\Models\OperationalAlert::$alertTypes as $key => $info)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-filter" aria-hidden="true"></i> Filtrar
            </button>

            @if(request()->hasAny(['severity', 'type']) || $status !== 'active')
                <a href="{{ route('operational-alerts.index') }}" class="text-xs text-gray-500 hover:text-gray-700 underline">Limpiar filtros</a>
            @endif
        </form>
    </div>

    {{-- Lista de alertas --}}
    <div class="space-y-2">
        @forelse($alerts as $alert)
            @php
                $severityStyles = [
                    'critica' => ['border' => 'border-l-red-600', 'dot' => 'bg-red-600', 'label_text' => 'text-red-600'],
                    'alta' => ['border' => 'border-l-orange-500', 'dot' => 'bg-orange-500', 'label_text' => 'text-orange-600'],
                    'media' => ['border' => 'border-l-yellow-500', 'dot' => 'bg-yellow-500', 'label_text' => 'text-yellow-600'],
                    'baja' => ['border' => 'border-l-blue-400', 'dot' => 'bg-blue-400', 'label_text' => 'text-blue-600'],
                ];
                $style = $severityStyles[$alert->severity] ?? $severityStyles['baja'];
                $typeInfo = $alert->alert_type_info;
            @endphp

            <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $style['border'] }} p-4 {{ !$alert->is_read ? '' : 'opacity-60' }} transition hover:shadow-sm cursor-pointer"
                 id="alert-{{ $alert->id }}"
                 onclick="if(!event.target.closest('form') && !event.target.closest('button')) { @if($alert->alertable && $alert->alertable_type === \App\Models\ServiceRequest::class) window.location='{{ route('service-requests.show', $alert->alertable_id) }}' @endif }">
                <div class="flex items-start gap-3">
                    {{-- Contenido --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $alert->title }}</span>
                            <span class="text-[10px] font-bold {{ $style['label_text'] }}">{{ strtoupper(mb_substr($alert->severity_info['label'], 0, 1)) }}</span>
                            @if(!$alert->is_read)
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $alert->message }}</p>
                        <div class="flex items-center gap-3 mt-2 text-[11px] text-gray-400">
                            <span>{{ $alert->alert_at->format('d/m H:i') }}</span>
                            <span>{{ $typeInfo['label'] }}</span>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        @if(!$alert->is_read)
                            <form action="{{ route('operational-alerts.mark-read', $alert) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 text-gray-300 hover:text-gray-600 rounded transition" title="Marcar como leída">
                                    <i class="fas fa-eye text-xs" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif

                        @if(!$alert->is_resolved)
                            <form action="{{ route('operational-alerts.resolve', $alert) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 text-gray-300 hover:text-green-600 rounded transition" title="Resolver">
                                    <i class="fas fa-check text-xs" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif

                        @if(!$alert->is_dismissed)
                            <form action="{{ route('operational-alerts.dismiss', $alert) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 text-gray-300 hover:text-red-500 rounded transition" title="Descartar">
                                    <i class="fas fa-times text-xs" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                <i class="fas fa-check-circle text-4xl text-green-400 mb-3" aria-hidden="true"></i>
                <p class="text-gray-600 font-medium">No hay alertas para mostrar</p>
                <p class="text-xs text-gray-400 mt-1">
                    @if($status === 'active')
                        Todas las solicitudes están dentro de los parámetros configurados.
                    @else
                        No se encontraron alertas con los filtros seleccionados.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($alerts->hasPages())
        <div class="mt-5">
            {{ $alerts->links() }}
        </div>
    @endif
</div>
@endsection
