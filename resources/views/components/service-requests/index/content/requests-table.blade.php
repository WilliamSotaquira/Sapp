@props(['serviceRequests'])

<div class="bg-white rounded-lg shadow-sm border border-gray-200" role="region" aria-labelledby="requests-table-title">
    <!-- Header Compacto -->
    <div class="bg-gray-50 px-3 sm:px-4 py-2.5 sm:py-3 border-b border-gray-200">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 id="requests-table-title" class="text-sm sm:text-base font-semibold text-gray-800 flex items-center">
                <i class="fas fa-tasks text-blue-500 mr-1.5 sm:mr-2 text-xs sm:text-sm"></i>
                <span class="hidden sm:inline">Solicitudes de Servicio</span>
                <span class="sm:hidden">Solicitudes</span>
                <span class="ml-1.5 sm:ml-2 text-xs sm:text-sm font-medium text-gray-500 bg-gray-200 px-1.5 sm:px-2 py-0.5 rounded-full">
                    {{ $serviceRequests->total() }}
                </span>
            </h3>

            <!-- Estado Resumido -->
            <div class="flex items-center gap-2 sm:gap-3 text-xs">
                <span class="flex items-center text-yellow-600">
                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full mr-1"></span>
                    {{ $pendingCount ?? 0 }}
                </span>
                <span class="flex items-center text-red-600">
                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></span>
                    {{ $criticalCount ?? 0 }}
                </span>
                <span class="flex items-center text-green-600">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
                    {{ $resolvedCount ?? 0 }}
                </span>
            </div>
        </div>
    </div>

    <!-- Contenido Compacto -->
    <div class="p-3 sm:p-4">
        @if ($serviceRequests->count() > 0)
            <div class="overflow-x-auto -mx-3 sm:mx-0">
                <!-- Tabla Compacta -->
                <table class="min-w-full text-xs sm:text-sm" aria-describedby="table-instructions">
                    <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
                        <tr>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Ticket</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium w-1/5 hidden md:table-cell">Solicitud</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium w-1/5 hidden lg:table-cell">Servicio</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Prioridad</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Estado</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium hidden sm:table-cell">Solicitante</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium hidden xl:table-cell">Fecha</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach ($serviceRequests as $request)
                            <x-service-requests.index.content.table-row :request="$request" />
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Estado Vacío Compacto -->
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-3xl mb-3"></i>
                <p class="text-gray-500 text-sm">No se encontraron solicitudes</p>
            </div>
        @endif
    </div>

    <!-- Footer Compacto -->
    @if ($serviceRequests->hasPages())
        <div class="bg-gray-50 px-4 py-2 border-t border-gray-200">
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Página {{ $serviceRequests->currentPage() }} de {{ $serviceRequests->lastPage() }}</span>
                <span>Actualizado: {{ now()->format('H:i') }}</span>
            </div>
        </div>
    @endif
</div>

<style>
    /* Mejoras de densidad */
    .min-w-full th,
    .min-w-full td {
        padding: 0.5rem 0.75rem;
    }

    /* Estados de enfoque para accesibilidad */
    tr:focus {
        outline: 2px solid #3b82f6;
        background-color: #eff6ff;
    }

    /* Hover sutil */
    tr:hover {
        background-color: #f9fafb;
    }
</style>
