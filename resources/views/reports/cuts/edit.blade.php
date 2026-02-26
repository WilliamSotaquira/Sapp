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
            <li class="text-gray-900 font-medium">Editar</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-3xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
            <h2 class="text-xl font-bold text-gray-900">Editar corte</h2>
            <p class="text-sm text-gray-600">
                Contrato: {{ $cut->contract?->number ?? 'N/A' }}
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">
                <ul class="list-disc ml-5 space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reports.cuts.update', $cut) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre del corte</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $cut->name) }}"
                    required
                    maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Fecha inicio</label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ old('start_date', optional($cut->start_date)->format('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Fecha fin</label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date', optional($cut->end_date)->format('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                >{{ old('notes', $cut->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('reports.cuts.show', $cut) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

