@extends('layouts.app')

@section('content')
<div class="py-6">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Nuevo corte</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Crear corte</h2>
            <p class="text-sm text-gray-600">Define el rango; el sistema asociará solicitudes creadas dentro de esas fechas.</p>
        </div>

        @if(!$hasActiveContract)
            <div class="p-6">
                <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800">Sin contrato activo</h3>
                            <p class="mt-1 text-sm text-yellow-700">No es posible crear un corte sin un contrato activo en el espacio de trabajo actual. Configura un contrato activo antes de continuar.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Volver a cortes</a>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('reports.cuts.store') }}" class="p-6 space-y-6" id="cut-create-form">
                @csrf

                {{-- Inline overlap warning (shown when server returns overlap error via session) --}}
                @if(session('error') && str_contains(session('error'), 'solapa'))
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4" role="alert">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Solapamiento de fechas detectado</h3>
                                <p class="mt-1 text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @elseif(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4" role="alert">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contrato activo</label>
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
                        {{ $activeContract->number }}{{ $activeContract->name ? ' - ' . $activeContract->name : '' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror" required>
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora inicio <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date', $dateSuggestion ? $dateSuggestion->startDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('start_date') border-red-500 @enderror" required>
                        @if($dateSuggestion)
                            <p class="mt-1 text-xs text-gray-500">Sugerido: <span class="font-mono">{{ $dateSuggestion->formattedStartDate() }}</span> <span class="text-gray-400">(YYYY-MM-DD HH:mm)</span></p>
                        @endif
                        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora fin <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date', $dateSuggestion ? $dateSuggestion->endDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('end_date') border-red-500 @enderror" required>
                        @if($dateSuggestion)
                            <p class="mt-1 text-xs text-gray-500">Sugerido: <span class="font-mono">{{ $dateSuggestion->formattedEndDate() }}</span> <span class="text-gray-400">(YYYY-MM-DD HH:mm)</span></p>
                        @endif
                        @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" {{ session('error') && str_contains(session('error'), 'solapa') ? 'disabled' : '' }}>
                        Crear y asociar por fecha
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
