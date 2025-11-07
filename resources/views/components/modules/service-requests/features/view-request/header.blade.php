@props(['serviceRequest'])

@php
$viewService = app(\App\Services\ServiceRequestViewService::class);
@endphp

<!-- Header -->
<div class="bg-blue-600 text-white px-6 py-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ $serviceRequest->title }}</h2>
            <p class="text-blue-100">{{ $serviceRequest->ticket_number }}</p>
        </div>

        <!-- Botones de Estado -->
        <div class="flex space-x-2">
            <!-- Reporte -->
            <div class="relative group">
                <button onclick="serviceRequestModals.open('report')"
                    class="bg-teal-500 hover:bg-teal-400 px-4 py-2 rounded flex items-center transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>Generar Reporte
                </button>
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block">
                    <div class="bg-black text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                        Descargar línea de tiempo
                    </div>
                    <div class="w-3 h-3 bg-black transform rotate-45 absolute -bottom-1 left-1/2 -translate-x-1/2"></div>
                </div>
            </div>

            <!-- ACEPTAR -->
            @if($serviceRequest->status === 'PENDIENTE')
            <button onclick="serviceRequestModals.open('accept')" class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded">
                <i class="fas fa-check mr-2"></i>Aceptar
            </button>
            @endif

            <!-- INICIAR -->
            @if($serviceRequest->status === 'ACEPTADA')
            <form action="{{ route('service-requests.start', $serviceRequest) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-purple-500 hover:bg-purple-400 px-4 py-2 rounded">
                    <i class="fas fa-play mr-2"></i>Iniciar
                </button>
            </form>
            @endif

            <!-- PAUSAR -->
            @if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']) && !$serviceRequest->is_paused)
            <button onclick="serviceRequestModals.open('pause')" class="bg-orange-500 hover:bg-orange-400 px-4 py-2 rounded">
                <i class="fas fa-pause mr-2"></i>Pausar
            </button>
            @endif

            <!-- REANUDAR -->
            @if($serviceRequest->isPaused())
            <form action="{{ route('service-requests.resume', $serviceRequest) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded">
                    <i class="fas fa-play mr-2"></i>Reanudar
                </button>
            </form>
            @endif

            <!-- RESOLVER -->
            @if($viewService->canShowResolveButton($serviceRequest))
            @php $resolveData = $viewService->getResolveButtonData($serviceRequest); @endphp
            @if($resolveData['can_resolve'])
            <a href="{{ route('service-requests.resolve-form', $serviceRequest) }}"
                class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded">
                <i class="fas fa-check-circle mr-2"></i>Resolver
            </a>
            @else
            <button class="bg-green-300 px-4 py-2 rounded cursor-not-allowed"
                title="Se requieren evidencias paso a paso o archivos adjuntos para resolver">
                <i class="fas fa-check-circle mr-2"></i>Resolver
            </button>
            @endif
            @elseif($serviceRequest->status === 'RESUELTA')
            <span class="bg-gray-300 px-4 py-2 rounded text-gray-600">
                <i class="fas fa-check-circle mr-2"></i>Resuelta
            </span>
            @else
            <button class="bg-gray-300 px-4 py-2 rounded cursor-not-allowed"
                title="La solicitud debe estar en proceso para resolver (Estado actual: {{ $serviceRequest->status }})">
                <i class="fas fa-check-circle mr-2"></i>Resolver
            </button>
            @endif

            <!-- CERRAR -->
            @if(strtoupper(trim($serviceRequest->status)) === 'RESUELTA')
            <button onclick="serviceRequestModals.open('close')" class="bg-gray-500 hover:bg-gray-400 px-4 py-2 rounded">
                <i class="fas fa-lock mr-2"></i>Cerrar
            </button>
            @endif

            <!-- CANCELAR -->
            @if(in_array(strtoupper(trim($serviceRequest->status)), ['PENDIENTE', 'ACEPTADA']))
            <button onclick="serviceRequestModals.open('cancel')" class="bg-red-500 hover:bg-red-400 px-4 py-2 rounded">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
            @endif
        </div>
    </div>
</div>
