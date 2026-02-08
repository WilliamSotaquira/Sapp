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
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Nombre
                                <i class="fas {{ $sortIcon('name') }}"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('department') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Departamento
                                <i class="fas {{ $sortIcon('department') }}"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('position') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Cargo
                                <i class="fas {{ $sortIcon('position') }}"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('service_requests_count') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Solicitudes
                                <i class="fas {{ $sortIcon('service_requests_count') }}"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortLink('is_active') }}" class="inline-flex items-center gap-1 hover:text-gray-800">
                                Estado
                                <i class="fas {{ $sortIcon('is_active') }}"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requesters as $requester)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $requester->name }}</div>
                                @if($requester->email)
                                    <div class="text-sm text-gray-500">{{ $requester->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($requester->phone)
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                        {{ $requester->phone }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $requester->department ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $requester->position ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ $requester->service_requests_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $requester->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $requester->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('requester-management.requesters.show', $requester) }}"
                                       class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                       title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('requester-management.requesters.edit', $requester) }}"
                                       class="text-yellow-600 hover:text-yellow-900 transition-colors duration-200"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('requester-management.requesters.toggle-status', $requester) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="text-{{ $requester->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $requester->is_active ? 'yellow' : 'green' }}-900 transition-colors duration-200"
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
                                                    class="text-red-600 hover:text-red-900 transition-colors duration-200"
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
                            <td colspan="7" class="px-6 py-12 text-center">
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
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $requesters->links() }}
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
