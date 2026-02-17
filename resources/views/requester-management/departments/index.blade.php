@extends('layouts.app')

@section('title', 'Departamentos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Departamentos</h1>
            <p class="text-sm text-slate-600">Catálogo de departamentos por organización.</p>
        </div>
        <a href="{{ route('requester-management.departments.create') }}"
           class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
            <i class="fas fa-plus mr-2"></i>Nuevo departamento
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ route('requester-management.departments.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por nombre..."
                   class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Activos</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 text-sm">
                    <i class="fas fa-search mr-1"></i>Aplicar
                </button>
                <a href="{{ route('requester-management.departments.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Orden</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($departments as $department)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $department->name }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $department->sort_order }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $department->is_active ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100' }}">
                                    {{ $department->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('requester-management.departments.edit', $department) }}"
                                       class="text-amber-600 hover:text-amber-900 p-1.5 rounded-md hover:bg-amber-50" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('requester-management.departments.toggle-status', $department) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-{{ $department->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $department->is_active ? 'yellow' : 'green' }}-900 p-1.5 rounded-md hover:bg-{{ $department->is_active ? 'yellow' : 'green' }}-50"
                                                title="{{ $department->is_active ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $department->is_active ? 'times' : 'check' }}"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('requester-management.departments.destroy', $department) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900 p-1.5 rounded-md hover:bg-red-50"
                                                onclick="return confirm('¿Eliminar este departamento?')"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                No hay departamentos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $departments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
