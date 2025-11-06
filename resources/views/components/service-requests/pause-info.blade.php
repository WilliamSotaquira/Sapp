@props(['request'])

<!-- Información de Pausa -->
<div class="p-6 border-b bg-orange-50">
    <h3 class="text-lg font-semibold mb-4 text-orange-800">
        <i class="fas fa-pause-circle mr-2"></i>Información de Pausa
    </h3>

    @if($request->isPaused())
    <div class="bg-orange-100 border border-orange-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-orange-500 text-xl mr-3"></i>
            <div>
                <p class="font-semibold text-orange-800">SOLICITUD PAUSADA</p>
                <p class="text-orange-700">Pausada desde: {{ $request->paused_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($request->pause_reason)
        <div>
            <label class="font-medium text-gray-700">Motivo de pausa:</label>
            <p class="text-gray-700 mt-1">{{ $request->pause_reason }}</p>
        </div>
        @endif

        <div>
            <label class="font-medium text-gray-700">Tiempo total pausado:</label>
            <p class="text-gray-700 mt-1 font-semibold">
                {{ $request->getTotalPausedTimeFormatted() }}
            </p>
        </div>

        @if($request->paused_at)
        <div>
            <label class="font-medium text-gray-700">Inicio de pausa:</label>
            <p class="text-gray-700 mt-1">{{ $request->paused_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif

        @if($request->resumed_at)
        <div>
            <label class="font-medium text-gray-700">Última reanudación:</label>
            <p class="text-gray-700 mt-1">{{ $request->resumed_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif
    </div>
</div>
