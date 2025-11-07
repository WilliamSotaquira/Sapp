@props(['serviceRequest'])

<!-- Información de Pausa -->
<div class="p-6 border-b bg-orange-50">
    <h3 class="text-lg font-semibold mb-4 text-orange-800">
        <i class="fas fa-pause-circle mr-2"></i>Información de Pausa
    </h3>

    @if($serviceRequest->isPaused())
    <div class="bg-orange-100 border border-orange-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-orange-500 text-xl mr-3"></i>
            <div>
                <p class="font-semibold text-orange-800">SOLICITUD PAUSADA</p>
                <p class="text-orange-700">Pausada desde: {{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($serviceRequest->pause_reason)
        <div>
            <label class="font-medium text-gray-700">Motivo de pausa:</label>
            <p class="text-gray-700 mt-1">{{ $serviceRequest->pause_reason }}</p>
        </div>
        @endif

        <div>
            <label class="font-medium text-gray-700">Tiempo total pausado:</label>
            <p class="text-gray-700 mt-1 font-semibold">
                {{ $serviceRequest->getTotalPausedTimeFormatted() }}
            </p>
        </div>

        @if($serviceRequest->paused_at)
        <div>
            <label class="font-medium text-gray-700">Inicio de pausa:</label>
            <p class="text-gray-700 mt-1">{{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif

        @if($serviceRequest->resumed_at)
        <div>
            <label class="font-medium text-gray-700">Última reanudación:</label>
            <p class="text-gray-700 mt-1">{{ $serviceRequest->resumed_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif
    </div>
</div>
