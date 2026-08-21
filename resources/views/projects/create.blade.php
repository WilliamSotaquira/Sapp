@extends('layouts.app')

@section('title', 'Nuevo Proyecto')

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.index') }}" class="text-gray-400 hover:text-gray-600 transition">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nuevo Proyecto</h1>
    </div>

    <form action="{{ route('projects.store') }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        @csrf

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
            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400"
                   placeholder="Ej: Micrositio Congreso de Investigación Artística 2026">
        </div>

        {{-- Descripción --}}
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 resize-none"
                      placeholder="Objetivo del proyecto, alcance general...">{{ old('description') }}</textarea>
        </div>

        {{-- Estado y fechas --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>En progreso</option>
                    <option value="on_hold" {{ old('status') === 'on_hold' ? 'selected' : '' }}>En pausa</option>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200">
            </div>
            <div>
                <label for="expected_end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha estimada fin</label>
                <input type="date" id="expected_end_date" name="expected_end_date" value="{{ old('expected_end_date') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-200">
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-save" aria-hidden="true"></i> Crear proyecto
            </button>
        </div>
    </form>
</div>
@endsection
