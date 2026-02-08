{{-- resources/views/services/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Servicios')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li>
            <span class="text-gray-500">Catálogos</span>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-cog"></i>
            <span class="ml-1">Servicios</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3">
        <div>
            <p class="text-gray-600 text-sm sm:text-base">Gestión de servicios y sus configuraciones</p>
        </div>
        <a href="{{ route('services.create') }}"
           class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center text-sm sm:text-base">
            <i class="fas fa-plus mr-2"></i>Nuevo Servicio
        </a>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 sm:p-4 mb-4 sm:mb-6 rounded text-sm sm:text-base">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 mb-4 sm:mb-6 rounded text-sm sm:text-base">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Filtros y Búsqueda -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 mb-4 sm:mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Buscar</label>
                <input type="text" id="searchInput" placeholder="Buscar servicios..."
                       class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Familia</label>
                <select id="familyFilter" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todas las familias</option>
                    @foreach($services->pluck('family')->unique()->filter() as $family)
                        @php
                            $familyLabel = $family->contract?->number
                                ? ($family->contract->number . ' - ' . $family->name)
                                : $family->name;
                        @endphp
                        <option value="{{ $family->id }}">{{ $familyLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Estado</label>
                <select id="statusFilter" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de Servicios -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="name">
                            <div class="flex items-center">
                                <span>Nombre</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="code">
                            <div class="flex items-center">
                                <span>Código</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="family">
                            <div class="flex items-center">
                                <span>Familia</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Sub-Servicios
                        </th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="status">
                            <div class="flex items-center">
                                <span>Estado</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="servicesTable">
                    @forelse($services as $service)
                    <tr class="service-row hover:bg-gray-50 transition duration-150"
                        data-name="{{ strtolower($service->name) }}"
                        data-code="{{ strtolower($service->code) }}"
                        @php
                            $familyLabel = $service->family?->contract?->number
                                ? ($service->family->contract->number . ' - ' . $service->family->name)
                                : ($service->family->name ?? '');
                        @endphp
                        data-family="{{ $familyLabel ? strtolower($familyLabel) : '' }}"
                        data-status="{{ $service->is_active ? 'active' : 'inactive' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-9 w-9 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cog text-blue-600 text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                                    @if($service->description)
                                    <div class="text-xs text-gray-500 leading-5">{{ Str::limit($service->description, 60) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                <i class="fas fa-hashtag mr-1"></i>
                                {{ $service->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($service->family)
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-2">
                                    {{ $service->family->code }}
                                </span>
                                <span class="text-sm text-gray-700">{{ $familyLabel }}</span>
                            </div>
                            @else
                            <span class="text-sm text-gray-400 italic">Sin familia</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-700 font-medium mr-2">
                                    {{ $service->sub_services_count ?? 0 }}
                                </span>
                                <span class="text-sm text-gray-500">sub-servicios</span>
                                @if(($service->sub_services_count ?? 0) > 0)
                                <a href="{{ route('services.show', $service) }}"
                                   class="ml-2 text-blue-500 hover:text-blue-700 text-xs"
                                   title="Ver sub-servicios">
                                    <i class="fas fa-list"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                <i class="fas fa-circle mr-1 text-{{ $service->is_active ? 'green' : 'red' }}-500" style="font-size: 6px;"></i>
                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('services.show', $service) }}"
                                   class="text-blue-500 hover:text-blue-700 p-1.5 rounded-md hover:bg-blue-50 transition duration-200"
                                   title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('services.edit', $service) }}"
                                   class="text-green-500 hover:text-green-700 p-1.5 rounded-md hover:bg-green-50 transition duration-200"
                                   title="Editar servicio">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('services.destroy', $service) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 p-1.5 rounded-md hover:bg-red-50 transition duration-200"
                                            onclick="return confirm('¿Está seguro de eliminar el servicio \"{{ $service->name }}\"? Esta acción no se puede deshacer.')"
                                            title="Eliminar servicio">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fas fa-cogs text-4xl mb-3"></i>
                                <p class="text-lg font-medium mb-1">No hay servicios registrados</p>
                                <p class="text-sm">Comienza creando tu primer servicio</p>
                                <a href="{{ route('services.create') }}"
                                   class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer Servicio
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Información de resultados -->
        @if($services->count() > 0)
        <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm text-gray-600 gap-3 flex-wrap">
                <div>
                    Mostrando <span class="font-medium">{{ $services->count() }}</span> servicios
                </div>
                <div class="flex items-center space-x-3">
                    <span id="activeCount" class="flex items-center">
                        <i class="fas fa-circle text-green-500 mr-1" style="font-size: 6px;"></i>
                        <span class="font-medium">{{ $services->where('is_active', true)->count() }}</span> activos
                    </span>
                    <span id="inactiveCount" class="flex items-center">
                        <i class="fas fa-circle text-red-500 mr-1" style="font-size: 6px;"></i>
                        <span class="font-medium">{{ $services->where('is_active', false)->count() }}</span> inactivos
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const familyFilter = document.getElementById('familyFilter');
    const statusFilter = document.getElementById('statusFilter');
    const servicesTable = document.getElementById('servicesTable');
    const serviceRows = document.querySelectorAll('.service-row');

    function filterServices() {
        const searchTerm = searchInput.value.toLowerCase();
        const familyValue = familyFilter.value;
        const statusValue = statusFilter.value;

        serviceRows.forEach(row => {
            const name = row.getAttribute('data-name');
            const code = row.getAttribute('data-code');
            const family = row.getAttribute('data-family');
            const status = row.getAttribute('data-status');

            const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);
            const matchesFamily = !familyValue || family.includes(familyValue.toLowerCase());
            const matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesFamily && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Event listeners para filtros
    searchInput.addEventListener('input', filterServices);
    familyFilter.addEventListener('change', filterServices);
    statusFilter.addEventListener('change', filterServices);

    // Ordenamiento simple
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            // Implementar lógica de ordenamiento aquí si es necesario
            console.log('Ordenar por:', sortBy);
        });
    });
});
</script>
@endpush

<style>
.sortable:hover {
    background-color: #f9fafb;
}

.service-row {
    transition: all 0.2s ease-in-out;
}
</style>
@endsection
