@props(['serviceRequest'])

<div id="evidences-section" class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4 border-b border-amber-100">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-images text-amber-600 mr-3"></i>
                Evidencias y Archivos Adjuntos
            </h3>
            <span class="text-sm font-normal text-amber-600 bg-amber-100 px-3 py-1 rounded-full">
                {{ $serviceRequest->evidences->count() }} archivos
            </span>
        </div>
    </div>
    <div class="p-6">
        @if($serviceRequest->evidences->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($serviceRequest->evidences as $evidence)
            <x-service-requests.show.evidences.evidence-card :evidence="$evidence" />
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <div class="text-gray-400 mb-3">
                <i class="fas fa-images text-4xl"></i>
            </div>
            <p class="text-gray-500 mb-4">No hay evidencias adjuntas a esta solicitud</p>
            <x-service-requests.show.evidences.evidence-uploader :serviceRequest="$serviceRequest" />
        </div>
        @endif

        <!-- Uploader siempre visible si hay evidencias -->
        @if($serviceRequest->evidences->count() > 0)
        <div class="mt-6 border-t pt-6">
            <x-service-requests.show.evidences.evidence-uploader :serviceRequest="$serviceRequest" />
        </div>
        @endif
    </div>
</div>
