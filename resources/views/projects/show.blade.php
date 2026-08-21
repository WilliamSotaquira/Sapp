@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('projects.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
            </div>
            <div class="flex items-center gap-3 ml-8">
                <span class="text-xs text-gray-400 font-mono">{{ $project->code }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-{{ $project->status_color }}-100 text-{{ $project->status_color }}-700">
                    {{ $project->status_label }}
                </span>
            </div>
        </div>
        <a href="{{ route('projects.edit', $project) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-edit" aria-hidden="true"></i> Editar
        </a>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Info del proyecto + progreso --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        {{-- Datos del proyecto --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            @if($project->description)
                <p class="text-sm text-gray-600 mb-4">{{ $project->description }}</p>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <p class="text-gray-400 mb-0.5">Inicio</p>
                    <p class="font-semibold text-gray-700">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-0.5">Fin estimado</p>
                    <p class="font-semibold text-gray-700">{{ $project->expected_end_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-0.5">Creado por</p>
                    <p class="font-semibold text-gray-700">{{ $project->creator?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-0.5">Solicitudes</p>
                    <p class="font-semibold text-gray-700">{{ $project->serviceRequests->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Progreso --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col items-center justify-center">
            <div class="relative w-24 h-24 mb-3">
                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-gray-100" stroke="currentColor" stroke-width="3" fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="text-indigo-500" stroke="currentColor" stroke-width="3" fill="none"
                          stroke-dasharray="{{ $project->progress }}, 100" stroke-linecap="round"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">{{ $project->progress }}%</span>
            </div>
            <p class="text-xs text-gray-500">Progreso general</p>
        </div>
    </div>

    {{-- Solicitudes vinculadas --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-indigo-500" aria-hidden="true"></i>
                Solicitudes del proyecto
            </h2>
            <span class="text-xs text-gray-400">{{ $project->serviceRequests->count() }} vinculadas</span>
        </div>

        @if($project->serviceRequests->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach($project->serviceRequests as $sr)
                    <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50 transition">
                        {{-- Status dot --}}
                        @php
                            $srColor = match($sr->status) {
                                'PENDIENTE' => 'gray',
                                'ACEPTADA' => 'blue',
                                'EN_PROCESO' => 'indigo',
                                'RESUELTA' => 'green',
                                'CERRADA' => 'emerald',
                                'PAUSADA' => 'amber',
                                'CANCELADA' => 'red',
                                default => 'gray',
                            };
                        @endphp
                        <span class="w-2.5 h-2.5 rounded-full bg-{{ $srColor }}-500 flex-shrink-0"></span>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('service-requests.show', $sr) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-700 transition line-clamp-1">
                                {{ $sr->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400">
                                <span class="font-mono">{{ $sr->ticket_number }}</span>
                                <span>{{ $sr->subService?->name }}</span>
                            </div>
                        </div>

                        {{-- Status --}}
                        <span class="text-[10px] font-semibold text-{{ $srColor }}-700 bg-{{ $srColor }}-50 px-2 py-0.5 rounded">
                            {{ $sr->status }}
                        </span>

                        {{-- Desvincular --}}
                        <form action="{{ route('projects.unlink-request', [$project, $sr]) }}" method="POST" class="flex-shrink-0"
                              onsubmit="return confirm('¿Desvincular esta solicitud del proyecto?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition" title="Desvincular">
                                <i class="fas fa-unlink text-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-5 py-8 text-center">
                <p class="text-sm text-gray-400">No hay solicitudes vinculadas a este proyecto.</p>
                <p class="text-xs text-gray-300 mt-1">Usa el formulario de abajo para vincular solicitudes existentes.</p>
            </div>
        @endif
    </div>

    {{-- Vincular solicitud --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-link text-indigo-500" aria-hidden="true"></i>
            Vincular solicitud existente
        </h3>

        @if($availableRequests->isNotEmpty())
            <form action="{{ route('projects.link-request', $project) }}" method="POST" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <select name="service_request_id" required
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                        <option value="">Seleccionar solicitud...</option>
                        @foreach($availableRequests as $sr)
                            <option value="{{ $sr->id }}">{{ $sr->ticket_number }} — {{ Str::limit($sr->title, 60) }} [{{ $sr->status }}]</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-link" aria-hidden="true"></i> Vincular
                </button>
            </form>
        @else
            <p class="text-xs text-gray-400">No hay solicitudes disponibles para vincular (todas están cerradas o ya vinculadas a un proyecto).</p>
        @endif
    </div>
</div>
@endsection
