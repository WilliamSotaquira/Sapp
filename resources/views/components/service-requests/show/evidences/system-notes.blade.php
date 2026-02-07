@props(['serviceRequest'])

@php
    // Filtrar solo notas del sistema y comentarios
    $systemNotes = $serviceRequest->evidences->whereIn('evidence_type', ['SISTEMA', 'COMENTARIO']);
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

@if($systemNotes->count() > 0)
<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mt-6">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gray-50 border-gray-200' }} px-6 py-4 border-b">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-comments text-indigo-600 mr-3 text-lg"></i>
                <div>
                    <h3 class="sr-card-title text-gray-800">Notas del Sistema</h3>
                </div>
            </div>
            <div class="text-sm text-gray-600">
                {{ $systemNotes->count() }} nota{{ $systemNotes->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="space-y-3">
            @foreach($systemNotes->sortByDesc('created_at') as $note)
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <div class="flex items-start">
                    <!-- Icono según tipo -->
                    <div class="flex-shrink-0 mr-3">
                        @if($note->evidence_type === 'SISTEMA')
                        <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-cog text-blue-600"></i>
                        </div>
                        @else
                        <div class="w-9 h-9 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-comment text-purple-600"></i>
                        </div>
                        @endif
                    </div>

                    <!-- Contenido -->
                    <div class="flex-1 min-w-0">
                        <!-- Header con tipo y fecha -->
                        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                            <span class="text-xs text-gray-500">
                                {{ $note->evidence_type }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $note->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <!-- Título -->
                        @if($note->title)
                        <h4 class="font-semibold text-gray-900 text-sm mb-2">
                            {{ $note->title }}
                        </h4>
                        @endif

                        <!-- Descripción -->
                        @if($note->description)
                        <div class="text-sm text-gray-700 leading-relaxed">
                            <p class="whitespace-pre-line">{{ $note->description }}</p>
                        </div>
                        @endif

                        <!-- Usuario que creó la nota (si existe) -->
                        @if($note->user)
                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200 text-xs text-gray-600">
                            {{ $note->user->name }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
