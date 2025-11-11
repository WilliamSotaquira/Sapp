@props(['evidence'])

<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1 pr-2">
            <h4 class="font-semibold text-gray-900 text-sm mb-1 truncate">
                {{ $evidence->title ?: 'Sin título' }}
            </h4>
            @if($evidence->description)
            <p class="text-xs text-gray-600 line-clamp-2">
                {{ $evidence->description }}
            </p>
            @endif
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-medium">
            {{ $evidence->evidence_type }}
        </span>
    </div>

    <!-- Contenido -->
    @if($evidence->evidence_type === 'ARCHIVO' && $evidence->has_file)
        @if($evidence->is_image)
            <!-- Imagen -->
            <div class="mb-3 rounded-lg overflow-hidden border border-gray-200">
                <img
                    src="{{ $evidence->file_url }}"
                    alt="{{ $evidence->title }}"
                    class="w-full h-32 object-cover hover:scale-105 transition-transform duration-200"
                >
            </div>
        @else
            <!-- Archivo -->
            <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200 mb-3">
                <div class="w-10 h-10 bg-white rounded-lg border border-gray-300 flex items-center justify-center mr-3">
                    <i class="fas fa-file text-gray-500"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $evidence->file_original_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $evidence->formatted_file_size }}</p>
                </div>
            </div>
        @endif

        <!-- Acciones -->
        <div class="flex space-x-4">
            <a href="{{ $evidence->file_url }}" target="_blank"
               class="flex items-center text-blue-600 hover:text-blue-700 text-sm font-medium transition-colors">
                <i class="fas fa-eye mr-2"></i> Ver
            </a>
            <a href="{{ $evidence->file_url }}" download
               class="flex items-center text-green-600 hover:text-green-700 text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i> Descargar
            </a>
        </div>

    @elseif($evidence->evidence_type === 'PASO_A_PASO')
        <!-- Paso a paso -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-medium">
                    <i class="fas fa-footsteps mr-1"></i>
                    Paso {{ $evidence->step_number ?? 'N/A' }}
                </span>
                @if(isset($evidence->evidence_data['duration']))
                <span class="text-xs text-gray-600 font-medium">
                    {{ $evidence->evidence_data['duration'] }} min
                </span>
                @endif
            </div>
            @if(isset($evidence->evidence_data['observations']))
            <p class="text-sm text-gray-700 bg-blue-50 rounded-lg p-3 border border-blue-100">
                {{ $evidence->evidence_data['observations'] }}
            </p>
            @endif
        </div>
    @else
        <!-- Sin archivo -->
        <div class="text-center py-6 text-gray-400">
            <i class="fas fa-file-exclamation text-2xl mb-2"></i>
            <p class="text-sm">Archivo no disponible</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="flex justify-between items-center text-xs text-gray-500 mt-4 pt-3 border-t border-gray-100">
        <div class="flex items-center">
            <i class="far fa-clock mr-1.5"></i>
            <span>{{ $evidence->created_at->format('d/m/Y H:i') }}</span>
        </div>
        @if($evidence->user)
        <div class="flex items-center">
            <i class="fas fa-user mr-1.5"></i>
            <span>{{ $evidence->user->name }}</span>
        </div>
        @endif
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
