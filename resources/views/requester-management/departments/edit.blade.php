@extends('layouts.app')

@section('title', 'Editar Departamento')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h1 class="text-lg font-semibold text-slate-900 mb-1">Editar departamento</h1>
        <p class="text-sm text-slate-600 mb-6">Actualiza la información del departamento.</p>

        <form method="POST" action="{{ route('requester-management.departments.update', $department) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $department->name) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $department->sort_order) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('sort_order') border-red-500 @enderror">
                @error('sort_order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <label for="is_active" class="ml-2 text-sm text-slate-700">Activo</label>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4">
                <a href="{{ route('requester-management.departments.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-save mr-2"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
