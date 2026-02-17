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
    <div class="mb-5 sm:mb-7 rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 via-white to-cyan-50/30 p-4 sm:p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Catálogo de servicios</h1>
                <p class="text-slate-600 text-sm sm:text-base mt-1">Gestiona servicios, familias asociadas y sub-servicios configurados.</p>
            </div>
            <a href="{{ route('services.create') }}"
               class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-lg transition duration-200 flex items-center justify-center text-sm font-medium shadow-sm">
                <i class="fas fa-plus mr-2"></i>Nuevo Servicio
            </a>
        </div>
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="rounded-xl border border-slate-200 bg-white p-3.5 sm:p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide font-semibold text-slate-500">Total</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $services->count() }}</p>
            <p class="text-xs text-slate-500 mt-1">Servicios registrados</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 sm:p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide font-semibold text-emerald-700">Activos</p>
            <p class="mt-1 text-xl font-semibold text-emerald-900">{{ $services->where('is_active', true)->count() }}</p>
            <p class="text-xs text-emerald-700/80 mt-1">Disponibles para operación</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-3.5 sm:p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide font-semibold text-rose-700">Inactivos</p>
            <p class="mt-1 text-xl font-semibold text-rose-900">{{ $services->where('is_active', false)->count() }}</p>
            <p class="text-xs text-rose-700/80 mt-1">Pendientes de reactivación</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 mb-4 sm:mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">Buscar</label>
                <input type="text" id="searchInput" placeholder="Nombre, código o descripción..."
                       class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">Familia</label>
                <select id="familyFilter" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todas las familias</option>
                    @foreach($services->pluck('family')->unique()->filter() as $family)
                        @php
                            $familyLabel = $family->contract?->number
                                ? ($family->contract->number . ' - ' . $family->name)
                                : $family->name;
                        @endphp
                        <option value="{{ strtolower($familyLabel) }}">{{ $familyLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">Estado</label>
                <select id="statusFilter" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs sm:text-sm text-slate-600">
                Resultados visibles:
                <span id="visibleCount" class="font-semibold text-slate-900">{{ $services->count() }}</span>
                de
                <span class="font-semibold text-slate-900">{{ $services->count() }}</span>
            </p>
            <button type="button" id="clearFilters"
                class="inline-flex items-center px-3 py-1.5 text-xs sm:text-sm border border-slate-300 text-slate-700 rounded-md hover:bg-slate-50 transition">
                <i class="fas fa-rotate-left mr-1.5"></i>Limpiar filtros
            </button>
        </div>
    </div>

    <!-- Tabla de Servicios -->
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-2/5 px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="name">
                            <div class="flex items-center">
                                <span>Servicio</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="w-2/5 px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="family">
                            <div class="flex items-center">
                                <span>Familia</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="w-1/6 px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="status">
                            <div class="flex items-center">
                                <span>Estado</span>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="w-28 px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="servicesTable">
                    @forelse($services as $service)
                    <tr class="service-row hover:bg-gray-50 transition duration-150"
                        data-name="{{ strtolower($service->name) }}"
                        data-code="{{ strtolower($service->code) }}"
                        data-description="{{ strtolower(strip_tags($service->description ?? '')) }}"
                        @php
                            $familyLabel = $service->family?->contract?->number
                                ? ($service->family->contract->number . ' - ' . $service->family->name)
                                : ($service->family->name ?? '');
                        @endphp
                        data-family="{{ $familyLabel ? strtolower($familyLabel) : '' }}"
                        data-status="{{ $service->is_active ? 'active' : 'inactive' }}">
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-9 w-9 bg-cyan-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cog text-cyan-700 text-sm"></i>
                                </div>
                                <div class="ml-3 min-w-0">
                                    <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            <i class="fas fa-hashtag mr-1"></i>{{ $service->code }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700 border border-cyan-100">
                                            <i class="fas fa-list-ul mr-1"></i>{{ $service->sub_services_count ?? 0 }} sub-servicios
                                        </span>
                                    </div>
                                    @if($service->description)
                                    <div class="mt-1 text-xs text-gray-500 leading-5">{{ Str::limit($service->description, 90) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if($service->family)
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex w-fit items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                                    {{ $service->family->code }}
                                </span>
                                <span class="text-sm text-gray-700 break-words">{{ $familyLabel }}</span>
                            </div>
                            @else
                            <span class="text-sm text-gray-400 italic">Sin familia</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                <i class="fas fa-circle mr-1 text-{{ $service->is_active ? 'green' : 'red' }}-500" style="font-size: 6px;"></i>
                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('services.show', $service) }}"
                                   class="text-cyan-600 hover:text-cyan-700 p-1.5 rounded-md hover:bg-cyan-50 transition duration-200"
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
                        <td colspan="4" class="px-4 py-10 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fas fa-cogs text-4xl mb-3"></i>
                                <p class="text-lg font-medium mb-1">No hay servicios registrados</p>
                                <p class="text-sm">Comienza creando tu primer servicio</p>
                                <a href="{{ route('services.create') }}"
                                   class="mt-4 bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg inline-flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer Servicio
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    @if($services->count() > 0)
                    <tr id="noFilterResultsRow" class="hidden">
                        <td colspan="4" class="px-4 py-10 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fas fa-filter text-3xl mb-2"></i>
                                <p class="text-base font-medium text-slate-600">Sin resultados con los filtros actuales</p>
                                <p class="text-sm">Ajusta o limpia los filtros para ver más servicios.</p>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Información de resultados -->
        @if($services->count() > 0)
        <div class="bg-slate-50 px-4 py-2.5 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm text-gray-600 gap-3 flex-wrap">
                <div>
                    Mostrando <span id="footerVisibleCount" class="font-medium">{{ $services->count() }}</span> de <span class="font-medium">{{ $services->count() }}</span> servicios
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
    const clearFilters = document.getElementById('clearFilters');
    const visibleCount = document.getElementById('visibleCount');
    const footerVisibleCount = document.getElementById('footerVisibleCount');
    const noFilterResultsRow = document.getElementById('noFilterResultsRow');
    const serviceRows = document.querySelectorAll('.service-row');

    function normalizeText(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filterServices() {
        const searchTerm = normalizeText(searchInput.value);
        const familyValue = normalizeText(familyFilter.value);
        const statusValue = statusFilter.value;
        let visibleRows = 0;

        serviceRows.forEach(row => {
            const name = normalizeText(row.getAttribute('data-name'));
            const code = normalizeText(row.getAttribute('data-code'));
            const description = normalizeText(row.getAttribute('data-description'));
            const family = normalizeText(row.getAttribute('data-family'));
            const status = row.getAttribute('data-status');

            const matchesSearch = !searchTerm
                || name.includes(searchTerm)
                || code.includes(searchTerm)
                || description.includes(searchTerm)
                || family.includes(searchTerm);
            const matchesFamily = !familyValue || family.includes(familyValue);
            const matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesFamily && matchesStatus) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount) visibleCount.textContent = visibleRows;
        if (footerVisibleCount) footerVisibleCount.textContent = visibleRows;
        if (noFilterResultsRow) {
            noFilterResultsRow.classList.toggle('hidden', visibleRows > 0);
        }
    }

    searchInput.addEventListener('input', filterServices);
    familyFilter.addEventListener('change', filterServices);
    statusFilter.addEventListener('change', filterServices);
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            searchInput.value = '';
            familyFilter.value = '';
            statusFilter.value = '';
            filterServices();
            searchInput.focus();
        });
    }

    filterServices();

    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            this.classList.toggle('bg-slate-100');
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
