@extends('layouts.app')

@section('title', 'Proyectos')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-project-diagram text-indigo-600" aria-hidden="true"></i>
                Proyectos
            </h1>
            <p class="text-sm text-gray-600 mt-1">Gestión de proyectos de desarrollo y esfuerzos agrupados</p>
        </div>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-plus" aria-hidden="true"></i>
            Nuevo proyecto
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

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                       placeholder="Buscar por nombre, código o descripción...">
            </div>
            <div>
                <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-200">
                    <option value="">Todos los estados</option>
                    @foreach(\App\Models\Project::getStatusOptions() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-search" aria-hidden="true"></i> Filtrar
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('projects.index') }}" class="text-xs text-gray-500 hover:text-gray-700 underline">Limpiar</a>
            @endif
        </form>
    </div>

    {{-- Listado --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($projects as $project)
            <a href="{{ route('projects.show', $project) }}"
               class="block bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all p-5 group">
                {{-- Header --}}
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 group-hover:text-indigo-700 transition line-clamp-2">{{ $project->name }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $project->code }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-{{ $project->status_color }}-100 text-{{ $project->status_color }}-700">
                        {{ $project->status_label }}
                    </span>
                </div>

                {{-- Descripción --}}
                @if($project->description)
                    <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ Str::limit($project->description, 100) }}</p>
                @endif

                {{-- Barra de progreso --}}
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Progreso</span>
                        <span class="font-semibold">{{ $project->progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width: {{ $project->progress }}%"></div>
                    </div>
                </div>

                {{-- Métricas --}}
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-clipboard-list mr-1" aria-hidden="true"></i> {{ $project->service_requests_count }} solicitudes</span>
                    @if($project->expected_end_date)
                        <span><i class="fas fa-calendar mr-1" aria-hidden="true"></i> {{ $project->expected_end_date->format('d/m/Y') }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 p-12 text-center">
                <i class="fas fa-project-diagram text-4xl text-gray-300 mb-3" aria-hidden="true"></i>
                <p class="text-gray-600 font-medium">No hay proyectos</p>
                <p class="text-xs text-gray-400 mt-1">Crea tu primer proyecto para agrupar solicitudes relacionadas.</p>
                <a href="{{ route('projects.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus" aria-hidden="true"></i> Crear proyecto
                </a>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($projects->hasPages())
        <div class="mt-5">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
