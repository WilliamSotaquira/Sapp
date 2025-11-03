@extends('layouts.app')

@section('title', 'Familias de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Familias de Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Lista de Familias de Servicio</h2>
        <a href="{{ route('service-families.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
            <i class="fas fa-plus mr-2"></i>Nueva Familia
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <form action="{{ route('service-families.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre o código..."
                       class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="status" id="status" class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                    <i class="fas fa-search mr-2"></i>Buscar
                </button>
                <a href="{{ route('service-families.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 flex items-center">
                    <i class="fas fa-refresh mr-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-layer-group text-blue-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Familias</p>
                    <p class="text-xl font-semibold text-gray-900">{{ $serviceFamilies->total() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Activas</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\ServiceFamily::where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Inactivas</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\ServiceFamily::where('is_active', false)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-cogs text-purple-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Servicios</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\Service::count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Familias -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center space-x-1">
                                <span>Nombre</span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'code', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center space-x-1">
                                <span>Código</span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLAs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($serviceFamilies as $family)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-layer-group text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('service-families.show', $family) }}" class="hover:text-blue-600">
                                                {{ $family->name }}
                                            </a>
                                        </div>
                                        @if($family->description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($family->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $family->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <span class="font-semibold">{{ $family->services_count }}</span>
                                    @if($family->services_count > 0)
                                        <span class="ml-2 text-xs text-gray-500">
                                            ({{ $family->activeServices()->count() }} activos)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-semibold">{{ $family->service_level_agreements_count }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $family->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    @if($family->is_active)
                                        <i class="fas fa-check-circle mr-1"></i>Activa
                                    @else
                                        <i class="fas fa-times-circle mr-1"></i>Inactiva
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('service-families.show', $family) }}"
                                       class="text-blue-600 hover:text-blue-900 p-1 rounded transition duration-150"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('service-families.edit', $family) }}"
                                       class="text-green-600 hover:text-green-900 p-1 rounded transition duration-150"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('services.create') }}?family={{ $family->id }}"
                                       class="text-purple-600 hover:text-purple-900 p-1 rounded transition duration-150"
                                       title="Agregar Servicio">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                    <form action="{{ route('service-families.destroy', $family) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Está seguro de que desea eliminar la familia de servicio \"{{ $family->name }}\"?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900 p-1 rounded transition duration-150"
                                                title="Eliminar"
                                                {{ $family->services_count > 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-trash {{ $family->services_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"></i>
                                        </button>
                                    </form>
                                </div>
                                @if($family->services_count > 0)
                                    <div class="text-xs text-red-500 mt-1">No se puede eliminar</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <i class="fas fa-layer-group text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg font-medium mb-2">No se encontraron familias de servicio</p>
                                    <p class="text-sm mb-4">Comienza creando tu primera familia de servicio</p>
                                    <a href="{{ route('service-families.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                                        <i class="fas fa-plus mr-2"></i>Crear Primera Familia
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($serviceFamilies->hasPages())
        <div class="mt-6 bg-white px-4 py-3 rounded-lg shadow">
            {{ $serviceFamilies->links() }}
        </div>
    @endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-enfocar el campo de búsqueda
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.focus();
        }

        // Confirmación para eliminar
        const deleteForms = document.querySelectorAll('form[onsubmit]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('¿Está seguro de que desea eliminar este registro?')) {
                    e.preventDefault();
                }
            });
        });
    });
</script>
@endsection
