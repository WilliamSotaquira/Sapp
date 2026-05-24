@extends('layouts.app')

@section('title', 'Línea de Tiempo')

@section('content')
@php
    $selectedStartDate = request('start_date');
    $selectedEndDate = request('end_date');
    $selectedTicket = request('ticket');
    $hasDateFilter = ($selectedStartDate !== null && $selectedStartDate !== '') || ($selectedEndDate !== null && $selectedEndDate !== '');
    $hasTicketFilter = $selectedTicket !== null && $selectedTicket !== '';
    $activeFilterCount = ($hasDateFilter ? 1 : 0) + ($hasTicketFilter ? 1 : 0);
@endphp
<div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-history text-xl"></i>
                <h1 class="text-xl font-bold">Línea de Tiempo</h1>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="openFiltersSidebar"
                        class="bg-white/10 border border-white/30 text-white px-3 py-1.5 rounded-full text-sm font-medium hover:bg-white/20 transition-colors inline-flex items-center">
                    <i class="fas fa-sliders-h mr-2"></i>Filtros
                    @if($activeFilterCount > 0)
                        <span class="ml-2 inline-flex items-center justify-center min-w-5 h-5 px-1 text-xs font-semibold bg-white text-blue-700 rounded-full">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>
                <div class="bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-medium">
                    Total: {{ $requests->total() }} solicitudes
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Búsqueda por Ticket -->
        <div class="mb-6">
            <form action="{{ route('reports.timeline.search') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <div class="flex-1">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" name="ticket" value="{{ $selectedTicket }}"
                               placeholder="Buscar por número de ticket (parcial o completo)..."
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
                <button type="submit"
                        class="bg-blue-600 text-white hover:bg-blue-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i>Buscar Ticket
                </button>
            </form>
        </div>

        <!-- Flash Messages -->
        @if(session('warning'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
            <div>
                <p class="text-yellow-800 text-sm font-medium">{{ session('warning') }}</p>
            </div>
        </div>
        @endif

        <!-- Active Filters -->
        @if($hasDateFilter || $hasTicketFilter)
        <div class="mb-6 flex flex-wrap gap-2">
            @if($hasDateFilter)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}
            </span>
            @endif
            @if($hasTicketFilter)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                <i class="fas fa-ticket-alt mr-1"></i>
                Ticket: {{ $selectedTicket }}
            </span>
            @endif
            <a href="{{ route('reports.timeline.index') }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
                <i class="fas fa-times mr-1"></i>Limpiar filtros
            </a>
        </div>
        @endif

        <!-- Date Range Info -->
        @if(!$hasDateFilter)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                <div>
                    <span class="text-blue-800">Mostrando solicitudes del mes actual: </span>
                    <strong class="text-blue-900">{{ $dateRange['start']->format('d/m/Y') }}</strong>
                    <span class="text-blue-800"> al </span>
                    <strong class="text-blue-900">{{ $dateRange['end']->format('d/m/Y') }}</strong>
                </div>
            </div>
        </div>
        @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                <div>
                    <span class="text-blue-800">Mostrando solicitudes del </span>
                    <strong class="text-blue-900">{{ $dateRange['start']->format('d/m/Y') }}</strong>
                    <span class="text-blue-800"> al </span>
                    <strong class="text-blue-900">{{ $dateRange['end']->format('d/m/Y') }}</strong>
                </div>
            </div>
        </div>
        @endif

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Título</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Solicitante</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($requests as $request)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                    {{ $request->ticket_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 text-sm">{{ Str::limit($request->title, 50) }}</div>
                                <div class="text-xs text-gray-500">{{ $request->subService->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $request->requester->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                        'ASIGNADA' => 'bg-blue-100 text-blue-800',
                                        'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                        'PAUSADA' => 'bg-gray-100 text-gray-800',
                                        'RESUELTA' => 'bg-green-100 text-green-800',
                                        'CERRADA' => 'bg-gray-200 text-gray-800',
                                        'CANCELADA' => 'bg-red-100 text-red-800',
                                        'RECHAZADA' => 'bg-gray-200 text-gray-800',
                                    ];
                                    $statusColor = $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="{{ $statusColor }} px-2 py-1 rounded text-xs font-medium">
                                    {{ $request->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('reports.timeline.show', $request->id) }}"
                                   class="bg-blue-600 text-white hover:bg-blue-700 px-2 py-1 rounded text-xs font-medium transition-colors inline-flex items-center"
                                   title="Ver Línea de Tiempo">
                                    <i class="fas fa-history mr-1"></i>Timeline
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                                <p class="font-medium">No hay solicitudes para mostrar</p>
                                <p class="text-sm mt-1">Intente ajustar los filtros de fecha o buscar por número de ticket.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    </div>
</div>

<!-- Filters Sidebar -->
<div id="filtersSidebar"
     class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-sliders-h mr-2"></i>Filtros Avanzados
                </h3>
                <button type="button" id="closeFiltersSidebar" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="text-blue-100 text-xs mt-1">Personaliza tu búsqueda</p>
        </div>

        <div class="flex-1 px-6 py-4 space-y-6">
            <form id="advancedFiltersForm" class="space-y-4">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Rango de Fechas</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="startDateFilterAdv" class="block text-xs text-gray-600 mb-1">Desde</label>
                            <input id="startDateFilterAdv" name="start_date" value="{{ $selectedStartDate }}" type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label for="endDateFilterAdv" class="block text-xs text-gray-600 mb-1">Hasta</label>
                            <input id="endDateFilterAdv" name="end_date" value="{{ $selectedEndDate }}" type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex gap-3">
            <button type="button" id="clearSidebarFiltersBtn"
                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="fas fa-redo mr-2"></i>Limpiar
            </button>
            <button type="button" id="applySidebarFiltersBtn"
                    class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm">
                <i class="fas fa-check mr-2"></i>Aplicar
            </button>
        </div>
    </div>
</div>
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>
@endsection

@push('scripts')
<script>
    (function () {
        const sidebar = document.getElementById('filtersSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('openFiltersSidebar');
        const closeBtn = document.getElementById('closeFiltersSidebar');
        const applyBtn = document.getElementById('applySidebarFiltersBtn');
        const clearBtn = document.getElementById('clearSidebarFiltersBtn');
        const form = document.getElementById('advancedFiltersForm');
        const baseUrl = @json(route('reports.timeline.index'));

        if (!sidebar || !overlay || !openBtn || !closeBtn || !applyBtn || !clearBtn || !form) {
            return;
        }

        const openSidebar = () => {
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeSidebar = () => {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const applyFilters = () => {
            const params = new URLSearchParams();
            const startDate = form.querySelector('[name="start_date"]')?.value?.trim() || '';
            const endDate = form.querySelector('[name="end_date"]')?.value?.trim() || '';

            if (startDate) params.set('start_date', startDate);
            if (endDate) params.set('end_date', endDate);

            window.location.href = params.toString() ? `${baseUrl}?${params}` : baseUrl;
        };

        const clearFilters = () => {
            window.location.href = baseUrl;
        };

        openBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
        applyBtn.addEventListener('click', applyFilters);
        clearBtn.addEventListener('click', clearFilters);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            applyFilters();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !sidebar.classList.contains('translate-x-full')) {
                closeSidebar();
            }
        });
    })();
</script>
@endpush
