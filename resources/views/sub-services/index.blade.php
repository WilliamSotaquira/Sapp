@extends('layouts.app')

@section('title', 'Sub-Servicios')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Sub-Servicios</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Lista de Sub-Servicios</h2>
        <a href="{{ route('sub-services.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
            <i class="fas fa-plus mr-2"></i>Nuevo Sub-Servicio
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-list-alt text-purple-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Total Sub-Servicios</p>
                    <p class="text-xl font-semibold text-gray-900">{{ $subServices->total() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Activos</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\SubService::where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Inactivos</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\SubService::where('is_active', false)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <i class="fas fa-tasks text-orange-600 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">Solicitudes</p>
                    <p class="text-xl font-semibold text-gray-900">{{ \App\Models\ServiceRequest::count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Sub-Servicios -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Familia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Costo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($subServices as $subService)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-list-alt text-purple-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('sub-services.show', $subService) }}" class="hover:text-blue-600">
                                                {{ $subService->name }}
                                            </a>
                                        </div>
                                        @if($subService->description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($subService->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $subService->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-medium">{{ $subService->service->name }}</span>
                                <div class="text-xs text-gray-500">{{ $subService->service->code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $subService->service->family->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($subService->cost)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-dollar-sign mr-1"></i>{{ number_format($subService->cost, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">Sin costo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $subService->order }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $subService->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    @if($subService->is_active)
                                        <i class="fas fa-check-circle mr-1"></i>Activo
                                    @else
                                        <i class="fas fa-times-circle mr-1"></i>Inactivo
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('sub-services.show', $subService) }}"
                                       class="text-blue-600 hover:text-blue-900 p-1 rounded transition duration-150"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sub-services.edit', $subService) }}"
                                       class="text-green-600 hover:text-green-900 p-1 rounded transition duration-150"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('sub-services.destroy', $subService) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Está seguro de que desea eliminar el sub-servicio \"{{ $subService->name }}\"?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900 p-1 rounded transition duration-150"
                                                title="Eliminar"
                                                {{ $subService->serviceRequests()->count() > 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-trash {{ $subService->serviceRequests()->count() > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"></i>
                                        </button>
                                    </form>
                                </div>
                                @if($subService->serviceRequests()->count() > 0)
                                    <div class="text-xs text-red-500 mt-1">{{ $subService->serviceRequests()->count() }} solicitudes</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <i class="fas fa-list-alt text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg font-medium mb-2">No se encontraron sub-servicios</p>
                                    <p class="text-sm mb-4">Comienza creando tu primer sub-servicio</p>
                                    <a href="{{ route('sub-services.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
                                        <i class="fas fa-plus mr-2"></i>Crear Primer Sub-Servicio
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
    @if($subServices->hasPages())
        <div class="mt-6 bg-white px-4 py-3 rounded-lg shadow">
            {{ $subServices->links() }}
        </div>
    @endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
