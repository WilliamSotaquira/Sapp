@props(['serviceRequest'])

<div class="mt-6 pt-6 border-t border-gray-200">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Evidencias</h2>

    @if($serviceRequest->evidences && $serviceRequest->evidences->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($serviceRequest->evidences as $evidence)
        <div class="border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 truncate">
                    {{ $evidence->file_name ?? 'Evidencia' }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ $evidence->created_at->format('d/m/Y') }}
                </span>
            </div>

            @if($evidence->description)
            <p class="text-sm text-gray-600 mb-3">{{ $evidence->description }}</p>
            @endif

            <div class="flex space-x-2">
                @if($evidence->isImage && $evidence->isImage())
                <a href="{{ Storage::url($evidence->file_path) }}"
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-eye mr-1"></i>Ver
                </a>
                @endif

                @if(method_exists($evidence, 'getDownloadUrl'))
                <a href="{{ $evidence->getDownloadUrl() }}"
                   class="text-green-600 hover:text-green-800 text-sm">
                    <i class="fas fa-download mr-1"></i>Descargar
                </a>
                @else
                <a href="{{ route('service-requests.evidences.download', ['service_request' => $serviceRequest->id, 'evidence' => $evidence->id]) }}"
                   class="text-green-600 hover:text-green-800 text-sm">
                    <i class="fas fa-download mr-1"></i>Descargar
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-8">
        <i class="fas fa-folder-open text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500">No hay evidencias disponibles.</p>
    </div>
    @endif
</div>
