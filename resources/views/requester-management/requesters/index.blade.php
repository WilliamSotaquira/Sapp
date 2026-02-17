{{-- resources/views/requester-management/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Solicitantes')

@section('breadcrumb')
<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-red-600">
                <i class="fas fa-home mr-2"></i>
                Inicio
            </a>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Gestión de Solicitantes</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    @php
        $currentSortBy = request('sort_by', 'name');
        $currentSortDir = request('sort_dir', 'asc');
        $sortIcon = function (string $column) use ($currentSortBy, $currentSortDir): string {
            if ($currentSortBy !== $column) {
                return 'fa-sort text-gray-400';
            }

            return $currentSortDir === 'asc'
                ? 'fa-sort-up text-red-600'
                : 'fa-sort-down text-red-600';
        };
        $sortLink = function (string $column) use ($currentSortBy, $currentSortDir): string {
            $nextDir = ($currentSortBy === $column && $currentSortDir === 'asc') ? 'desc' : 'asc';
            return route('requester-management.requesters.index', array_merge(request()->query(), [
                'sort_by' => $column,
                'sort_dir' => $nextDir,
                'page' => null,
            ]));
        };
    @endphp

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-red-600 text-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-200">Total</p>
                    <p class="text-2xl font-bold">{{ \App\Models\Requester::count() }}</p>
                    <p class="text-sm text-red-200">Solicitantes</p>
                </div>
            </div>
        </div>

        <div class="bg-green-600 text-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-200">Activos</p>
                    <p class="text-2xl font-bold">{{ \App\Models\Requester::active()->count() }}</p>
                    <p class="text-sm text-green-200">Solicitantes activos</p>
                </div>
            </div>
        </div>

        <div class="bg-blue-600 text-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-tasks text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-200">Con Solicitudes</p>
                    <p class="text-2xl font-bold">{{ \App\Models\Requester::has('serviceRequests')->count() }}</p>
                    <p class="text-sm text-blue-200">Con solicitudes creadas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <form action="{{ route('requester-management.requesters.index') }}" method="GET" class="flex flex-col gap-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2">
                            <input type="text" name="search"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="Buscar por nombre, email, departamento o cargo..."
                                   value="{{ request('search') }}">
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="all">Todos</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                            <input type="text" name="department"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="Filtrar por departamento"
                                   value="{{ request('department') }}">
                            <input type="text" name="position"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   placeholder="Filtrar por cargo"
                                   value="{{ request('position') }}">
                            <select name="has_requests" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Con/Sin solicitudes</option>
                                <option value="yes" {{ request('has_requests') == 'yes' ? 'selected' : '' }}>Con solicitudes</option>
                                <option value="no" {{ request('has_requests') == 'no' ? 'selected' : '' }}>Sin solicitudes</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'name') }}">
                            <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'asc') }}">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-search mr-2"></i>Buscar
                            </button>
                            <a href="{{ route('requester-management.requesters.index') }}"
                               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
                <div>
                    <a href="{{ route('requester-management.requesters.create') }}"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nuevo Solicitante
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-[26%] px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Solicitante
                                <i class="fas {{ $sortIcon('name') }}"></i>
                            </a>
                        </th>
                        <th class="w-[28%] px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contacto</th>
                        <th class="w-[24%] px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('department') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Área / Cargo
                                <i class="fas {{ $sortIcon('department') }}"></i>
                            </a>
                        </th>
                        <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('service_requests_count') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Gestión
                                <i class="fas {{ $sortIcon('service_requests_count') }}"></i>
                            </a>
                        </th>
                        <th class="w-[8%] px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($requesters as $requester)
                        <tr class="hover:bg-slate-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-red-50 text-red-700 flex items-center justify-center font-semibold text-xs">
                                        {{ strtoupper(\Illuminate\Support\Str::substr($requester->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $requester->name }}</div>
                                        <div class="text-xs text-slate-500">ID #{{ $requester->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($requester->email)
                                    <div class="flex items-center text-sm text-slate-700">
                                        <i class="fas fa-envelope text-slate-400 mr-2"></i>
                                        <span class="break-all">{{ $requester->email }}</span>
                                    </div>
                                @else
                                    <div class="text-sm text-slate-400">Sin correo</div>
                                @endif
                                @if($requester->phone)
                                    <div class="flex items-center text-sm text-slate-700 mt-1">
                                        <i class="fas fa-phone text-slate-400 mr-2"></i>
                                        {{ $requester->phone }}
                                    </div>
                                @else
                                    <div class="text-sm text-slate-400 mt-1">Sin teléfono</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($requester->department)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $requester->department }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-400">Sin departamento</span>
                                @endif
                                <div class="mt-1">
                                    @if($requester->position)
                                        <span class="text-sm text-slate-700">{{ $requester->position }}</span>
                                    @else
                                        <span class="text-sm text-slate-400">Sin cargo</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex w-fit items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                        <i class="fas fa-ticket-alt mr-1"></i>
                                        {{ $requester->service_requests_count }}
                                    </span>
                                    <span class="inline-flex w-fit items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $requester->is_active ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100' }}">
                                        <i class="fas fa-circle mr-1 text-[8px]"></i>
                                        {{ $requester->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('requester-management.requesters.show', $requester) }}"
                                       class="text-blue-600 hover:text-blue-900 p-1.5 rounded-md hover:bg-blue-50 transition-colors duration-200"
                                       title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('requester-management.requesters.edit', $requester) }}"
                                       class="text-amber-600 hover:text-amber-900 p-1.5 rounded-md hover:bg-amber-50 transition-colors duration-200"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('requester-management.requesters.toggle-status', $requester) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-{{ $requester->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $requester->is_active ? 'yellow' : 'green' }}-900 p-1.5 rounded-md hover:bg-{{ $requester->is_active ? 'yellow' : 'green' }}-50 transition-colors duration-200"
                                                title="{{ $requester->is_active ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $requester->is_active ? 'times' : 'check' }}"></i>
                                        </button>
                                    </form>
                                    @if($requester->can_be_deleted)
                                        <form action="{{ route('requester-management.requesters.destroy', $requester) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900 p-1.5 rounded-md hover:bg-red-50 transition-colors duration-200"
                                                    onclick="return confirm('¿Está seguro de eliminar este solicitante?')"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    <p class="text-lg font-medium mb-4">No se encontraron solicitantes</p>
                                    <a href="{{ route('requester-management.requesters.create') }}"
                                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                                        <i class="fas fa-plus mr-2"></i>
                                        Crear primer solicitante
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($requesters->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-slate-600">
                        Mostrando
                        <span class="font-medium text-slate-900">{{ $requesters->firstItem() }}</span>
                        a
                        <span class="font-medium text-slate-900">{{ $requesters->lastItem() }}</span>
                        de
                        <span class="font-medium text-slate-900">{{ $requesters->total() }}</span>
                        solicitantes
                    </p>
                    <div>
                        {{ $requesters->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Scripts específicos para esta página
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar funcionalidades adicionales si es necesario
    });
</script>
@endsection
