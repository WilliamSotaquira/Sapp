@props([
    'evidence',
    'typeColors' => [
        'PASO_A_PASO' => 'bg-blue-100 text-blue-800 border-blue-300',
        'ARCHIVO' => 'bg-green-100 text-green-800 border-green-300',
        'IMAGEN' => 'bg-purple-100 text-purple-800 border-purple-300',
        'DOCUMENTO' => 'bg-orange-100 text-orange-800 border-orange-300',
        'AUDIO' => 'bg-pink-100 text-pink-800 border-pink-300',
        'VIDEO' => 'bg-red-100 text-red-800 border-red-300',
        'default' => 'bg-gray-100 text-gray-800 border-gray-300'
    ]
])

<!-- DEBUG TEMPORAL -->
<div class="hidden debug-info">
    <p>Evidence ID: {{ $evidence->id }}</p>
    <p>File Path: {{ $evidence->file_path }}</p>
    <p>File URL: {{ $evidence->file_url }}</p>
    <p>Has File: {{ $evidence->hasFile() ? 'YES' : 'NO' }}</p>
    <p>Is Image: {{ $evidence->is_image ? 'YES' : 'NO' }}</p>
    <p>MIME Type: {{ $evidence->file_mime_type }}</p>
</div>

<div class="evidence-card bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-200">
    <!-- Header de la evidencia -->
    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-gray-800 text-sm truncate">{{ $evidence->title }}</h4>
            <span class="text-xs px-2 py-1 rounded-full border {{ $typeColors[$evidence->evidence_type] ?? $typeColors['default'] }}">
                {{ $evidence->evidence_type }}
            </span>
        </div>
        @if($evidence->description)
        <p class="text-xs text-gray-600 mt-1">{{ $evidence->description }}</p>
        @endif
    </div>

    <!-- Contenido de la evidencia -->
    <div class="p-4">
        @if($evidence->evidence_type === 'ARCHIVO' && $evidence->file_path)
            @if($evidence->is_image)
                <!-- Vista previa de imagen -->
                <div class="evidence-preview cursor-pointer" onclick="openPreview('{{ $evidence->file_url }}', '{{ $evidence->file_original_name }}')">
                    <img
                        src="{{ $evidence->file_url }}"
                        alt="{{ $evidence->title }}"
                        class="evidence-image w-full h-32 object-cover rounded-lg"
                        onerror="console.error('Error loading image:', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';"
                    >
                    <div class="hidden w-full h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-image text-gray-400 text-2xl"></i>
                        <span class="text-xs text-gray-500 ml-2">Error cargando imagen</span>
                    </div>
                </div>
            @else
                <!-- Otros tipos de archivo -->
                <div class="flex items-center justify-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-center">
                        <i class="fas {{ $evidence->file_icon }} text-gray-400 text-3xl mb-2"></i>
                        <p class="text-xs text-gray-600 break-words">{{ $evidence->file_original_name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $evidence->formatted_file_size }}</p>
                    </div>
                </div>
            @endif
        @elseif($evidence->evidence_type === 'PASO_A_PASO')
            <!-- Información de paso a paso -->
            <div class="space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Paso #{{ $evidence->step_number }}</span>
                    @if($evidence->evidence_data['duration'] ?? false)
                    <span class="text-gray-500">{{ $evidence->evidence_data['duration'] }} min</span>
                    @endif
                </div>
                @if($evidence->evidence_data['observations'] ?? false)
                <p class="text-xs text-gray-700 bg-gray-50 p-2 rounded">{{ $evidence->evidence_data['observations'] }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Footer de la evidencia -->
    <div class="bg-gray-50 px-4 py-2 border-t border-gray-200">
        <div class="flex justify-between items-center text-xs text-gray-500">
            <span>{{ $evidence->created_at->format('d/m/Y H:i') }}</span>
            @if($evidence->user)
            <span>Subido por: {{ $evidence->user->name ?? 'N/A' }}</span>
            @endif
        </div>
    </div>
</div>
