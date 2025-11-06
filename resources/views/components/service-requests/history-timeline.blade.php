@props(['request'])

<!-- Historial de Estados -->
<div class="p-6">
    <h3 class="text-lg font-semibold mb-4">Historial</h3>
    <div class="space-y-3">
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Creada</p>
                <p class="text-xs text-gray-500">{{ $request->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        @if($request->accepted_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Aceptada</p>
                <p class="text-xs text-gray-500">{{ $request->accepted_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($request->responded_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">En Proceso</p>
                <p class="text-xs text-gray-500">{{ $request->responded_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($request->paused_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Pausada</p>
                <p class="text-xs text-gray-500">{{ $request->paused_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($request->resumed_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Reanudada</p>
                <p class="text-xs text-gray-500">{{ $request->resumed_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($request->resolved_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Resuelta</p>
                <p class="text-xs text-gray-500">{{ $request->resolved_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif

        @if($request->closed_at)
        <div class="flex items-center">
            <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
            <div class="ml-3">
                <p class="text-sm font-medium">Cerrada</p>
                <p class="text-xs text-gray-500">{{ $request->closed_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
