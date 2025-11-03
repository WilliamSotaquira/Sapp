@extends('layouts.app')

@section('title', 'Acuerdos de Nivel de Servicio (SLA)')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">SLAs</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Lista de Acuerdos de Nivel de Servicio</h2>
        <a href="{{ route('slas.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nuevo SLA
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Familia de Servicio</label>
                <select id="familyFilter" class="w-full border border-gray-300 rounded-md p-2">
                    <option value="">Todas las familias</option>
                    @foreach(\App\Models\ServiceFamily::all() as $family)
                        <option value="{{ $family->id }}">{{ $family->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de Criticidad</label>
                <select id="criticalityFilter" class="w-full border border-gray-300 rounded-md p-2">
                    <option value="">Todos los niveles</option>
                    <option value="BAJA">Baja</option>
                    <option value="MEDIA">Media</option>
                    <option value="ALTA">Alta</option>
                    <option value="CRITICA">Crítica</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="statusFilter" class="w-full border border-gray-300 rounded-md p-2">
                    <option value="">Todos los estados</option>
                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Familia</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criticidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempos (min)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitudes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($slas as $sla)
                    <tr class="hover:bg-gray-50 sla-row"
                        data-family="{{ $sla->service_family_id }}"
                        data-criticality="{{ $sla->criticality_level }}"
                        data-status="{{ $sla->is_active ? 'active' : 'inactive' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ $sla->name }}</div>
                            @if($sla->conditions)
                                <div class="text-sm text-gray-500">{{ Str::limit($sla->conditions, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sla->serviceFamily->name }}</div>
                            <div class="text-xs text-gray-500">{{ $sla->serviceFamily->code }}</div>
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
                            <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $criticalityColors[$sla->criticality_level] }}">
                                {{ $sla->criticality_level }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">Aceptación:</span>
                                    <span class="font-medium">{{ $sla->acceptance_time_minutes }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">Respuesta:</span>
                                    <span class="font-medium">{{ $sla->response_time_minutes }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">Resolución:</span>
                                    <span class="font-medium">{{ $sla->resolution_time_minutes }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-700 bg-blue-100 rounded-full">
                                {{ $sla->serviceRequests->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sla->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $sla->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('slas.show', $sla) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('slas.edit', $sla) }}" class="text-green-600 hover:text-green-900 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('slas.destroy', $sla) }}" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de que desea eliminar este SLA?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center py-8">
                                <i class="fas fa-handshake text-gray-300 text-4xl mb-4"></i>
                                <p class="text-lg font-medium text-gray-600">No se encontraron SLAs</p>
                                <p class="text-gray-500">Comienza creando tu primer acuerdo de nivel de servicio.</p>
                                <a href="{{ route('slas.create') }}" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer SLA
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($slas->hasPages())
    <div class="mt-4">
        {{ $slas->links() }}
    </div>
    @endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const familyFilter = document.getElementById('familyFilter');
        const criticalityFilter = document.getElementById('criticalityFilter');
        const statusFilter = document.getElementById('statusFilter');
        const slaRows = document.querySelectorAll('.sla-row');

        function filterSLAs() {
            const familyValue = familyFilter.value;
            const criticalityValue = criticalityFilter.value;
            const statusValue = statusFilter.value;

            slaRows.forEach(row => {
                const rowFamily = row.getAttribute('data-family');
                const rowCriticality = row.getAttribute('data-criticality');
                const rowStatus = row.getAttribute('data-status');

                const familyMatch = !familyValue || rowFamily === familyValue;
                const criticalityMatch = !criticalityValue || rowCriticality === criticalityValue;
                const statusMatch = !statusValue || rowStatus === statusValue;

                if (familyMatch && criticalityMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        familyFilter.addEventListener('change', filterSLAs);
        criticalityFilter.addEventListener('change', filterSLAs);
        statusFilter.addEventListener('change', filterSLAs);

        // Aplicar filtros desde URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const familyParam = urlParams.get('family');
        const criticalityParam = urlParams.get('criticality');
        const statusParam = urlParams.get('status');

        if (familyParam) {
            familyFilter.value = familyParam;
        }
        if (criticalityParam) {
            criticalityFilter.value = criticalityParam;
        }
        if (statusParam) {
            statusFilter.value = statusParam;
        }

        filterSLAs();
    });
</script>
@endsection
