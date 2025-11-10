@props(['serviceRequest'])

<div id="history-section" class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-history text-purple-600 mr-3"></i>
                Historial y Timeline
            </h3>
            <span class="text-sm font-normal text-purple-600 bg-purple-100 px-3 py-1 rounded-full">
                {{ ($serviceRequest->history ? $serviceRequest->history->count() : 0) }} eventos
            </span>
        </div>
    </div>
    <div class="p-6">
        <!-- ✅ CORREGIDO: Verificar si history existe y tiene elementos -->
        @if($serviceRequest->history && $serviceRequest->history->count() > 0)
        <div class="space-y-6">
            @foreach($serviceRequest->history->sortByDesc('created_at') as $historyItem)
            <x-service-requests.show.history.history-item :historyItem="$historyItem" />
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <div class="text-gray-400 mb-3">
                <i class="fas fa-history text-4xl"></i>
            </div>
            <p class="text-gray-500">No hay historial registrado para esta solicitud</p>
        </div>
        @endif

        <!-- Cambios de Estado -->
        <!-- ✅ CORREGIDO: Verificar si statusChanges existe -->
        @if($serviceRequest->statusChanges && $serviceRequest->statusChanges->count() > 0)
        <div class="mt-8 pt-6 border-t">
            <x-service-requests.show.history.status-changes :serviceRequest="$serviceRequest" />
        </div>
        @endif
    </div>
</div>
