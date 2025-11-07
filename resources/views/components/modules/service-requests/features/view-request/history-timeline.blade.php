@props(['serviceRequest'])

<!-- Historial de Estados -->
<div class="p-6">
    <h3 class="text-lg font-semibold mb-4">Historial</h3>
    <div class="space-y-3">
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Creada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        @if($serviceRequest->accepted_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Aceptada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->accepted_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->responded_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">En Proceso</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->responded_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->paused_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Pausada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->resumed_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Reanudada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->resumed_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->resolved_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Resuelta</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->resolved_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->closed_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Cerrada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->closed_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
