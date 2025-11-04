@extends('layouts.app')

@section('title', 'Solicitudes de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitudes de Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <!-- Header con estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-tasks text-blue-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $serviceRequests->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pendientes</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $pendingCount ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Críticas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $criticalCount ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Resueltas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $resolvedCount ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Lista de Solicitudes de Servicio</h2>

            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Filtro de estado -->
                <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="ACEPTADA">Aceptada</option>
                    <option value="EN_PROCESO">En Proceso</option>
                    <option value="PAUSADA">Pausada</option>
                    <option value="RESUELTA">Resuelta</option>
                    <option value="CERRADA">Cerrada</option>
                    <option value="CANCELADA">Cancelada</option>
                </select>

                <!-- Filtro de criticidad -->
                <select id="criticalityFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas las criticidades</option>
                    <option value="BAJA">Baja</option>
                    <option value="MEDIA">Media</option>
                    <option value="ALTA">Alta</option>
                    <option value="CRITICA">Crítica</option>
                </select>

                <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Nueva Solicitud
                </a>
            </div>
        </div>
    </div>

    <!-- Tabla de solicitudes -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criticidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($serviceRequests as $request)
                        <tr class="hover:bg-gray-50 transition duration-150" data-status="{{ $request->status }}" data-criticality="{{ $request->criticality_level }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-blue-600 hover:text-blue-800">
                                    <a href="{{ route('service-requests.show', $request) }}" class="flex items-center">
                                        #{{ $request->ticket_number }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $request->title }}</div>
                                <div class="text-sm text-gray-500 truncate max-w-xs">{{ $request->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $request->subService->service->family->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $criticalityColors = [
                                        'BAJA' => 'bg-green-100 text-green-800 border-green-200',
                                        'MEDIA' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'ALTA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        'CRITICA' => 'bg-red-100 text-red-800 border-red-200'
                                    ];
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $criticalityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    {{ $request->criticality_level }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'PENDIENTE' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'ACEPTADA' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'EN_PROCESO' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'PAUSADA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        'RESUELTA' => 'bg-green-100 text-green-800 border-green-200',
                                        'CERRADA' => 'bg-gray-100 text-gray-800 border-gray-200',
                                        'CANCELADA' => 'bg-red-100 text-red-800 border-red-200'
                                    ];
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    {{ $request->status }}
                                    @if($request->is_paused && $request->status === 'PAUSADA')
                                        <i class="fas fa-pause ml-1"></i>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->requester->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $request->requester->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $request->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('service-requests.show', $request) }}"
                                       class="text-blue-600 hover:text-blue-900 transition duration-150 p-1 rounded"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if($request->status === 'PENDIENTE')
                                        <a href="{{ route('service-requests.edit', $request) }}"
                                           class="text-green-600 hover:text-green-900 transition duration-150 p-1 rounded"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    @if(in_array($request->status, ['PENDIENTE', 'CANCELADA']))
                                        <form action="{{ route('service-requests.destroy', $request) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900 transition duration-150 p-1 rounded"
                                                    title="Eliminar"
                                                    onclick="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center">
                                <div class="text-gray-400 mb-2">
                                    <i class="fas fa-inbox text-4xl"></i>
                                </div>
                                <p class="text-gray-500 mb-4">No se encontraron solicitudes de servicio.</p>
                                <a href="{{ route('service-requests.create') }}"
                                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 inline-flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Crear la primera solicitud
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($serviceRequests->hasPages())
        <div class="mt-6 bg-white px-4 py-3 rounded-lg shadow-md">
            {{ $serviceRequests->links() }}
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros
    const statusFilter = document.getElementById('statusFilter');
    const criticalityFilter = document.getElementById('criticalityFilter');

    function applyFilters() {
        const status = statusFilter.value;
        const criticality = criticalityFilter.value;
        const rows = document.querySelectorAll('tbody tr[data-status]');

        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const rowCriticality = row.getAttribute('data-criticality');

            const statusMatch = !status || rowStatus === status;
            const criticalityMatch = !criticality || rowCriticality === criticality;

            if (statusMatch && criticalityMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    statusFilter.addEventListener('change', applyFilters);
    criticalityFilter.addEventListener('change', applyFilters);

    // Restaurar valores de filtro si existen
    const savedStatus = sessionStorage.getItem('serviceRequests_statusFilter');
    const savedCriticality = sessionStorage.getItem('serviceRequests_criticalityFilter');

    if (savedStatus) statusFilter.value = savedStatus;
    if (savedCriticality) criticalityFilter.value = savedCriticality;

    // Guardar valores de filtro
    statusFilter.addEventListener('change', () => {
        sessionStorage.setItem('serviceRequests_statusFilter', statusFilter.value);
    });

    criticalityFilter.addEventListener('change', () => {
        sessionStorage.setItem('serviceRequests_criticalityFilter', criticalityFilter.value);
    });

    // Aplicar filtros al cargar la página
    applyFilters();
});
</script>
@endpush
