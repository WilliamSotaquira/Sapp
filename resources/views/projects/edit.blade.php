@extends('layouts.app')

@section('title', 'Editar Proyecto')

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-gray-400 hover:text-gray-600 transition">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Editar: {{ $project->name }}</h1>
    </div>

    <form action="{{ route('projects.update', $project) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Nombre --}}
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre del proyecto <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        {{-- Descripción --}}
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 resize-none">{{ old('description', $project->description) }}</textarea>
        </div>

        {{-- Estado y fechas --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200">
                    @foreach(\App\Models\Project::getStatusOptions() as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $project->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label for="expected_end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha estimada fin</label>
                <input type="date" id="expected_end_date" name="expected_end_date" value="{{ old('expected_end_date', $project->expected_end_date?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200">
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-save" aria-hidden="true"></i> Guardar cambios
            </button>
        </div>
    </form>

    {{-- Zona de peligro --}}
    @if(!$project->serviceRequests()->exists())
    <div class="mt-6 p-4 border border-red-200 rounded-lg bg-red-50">
        <p class="text-xs text-red-700 mb-2 font-medium">Zona de peligro</p>
        <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline"
              onsubmit="return confirm('¿Eliminar este proyecto? Esta acción no se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-red-600 hover:text-red-800 underline">Eliminar proyecto</button>
        </form>
    </div>
    @endif
</div>
@endsection
