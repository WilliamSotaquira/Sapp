@props(['serviceRequest'])

@php
    // Filtrar solo notas del sistema y comentarios
    $systemNotes = $serviceRequest->evidences->whereIn('evidence_type', ['SISTEMA', 'COMENTARIO']);
@endphp

@if($systemNotes->count() > 0)
<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mt-6">
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-indigo-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-comments text-indigo-600 mr-3 text-xl"></i>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Notas y Comentarios del Sistema</h3>
                    <p class="text-sm text-indigo-700 mt-1">Registro de actividades y observaciones automáticas</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                    <i class="fas fa-clipboard-list mr-2"></i>
                    {{ $systemNotes->count() }} nota{{ $systemNotes->count() !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="space-y-3">
            @foreach($systemNotes->sortByDesc('created_at') as $note)
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start">
                    <!-- Icono según tipo -->
                    <div class="flex-shrink-0 mr-3">
                        @if($note->evidence_type === 'SISTEMA')
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-cog text-blue-600"></i>
                        </div>
                        @else
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-comment text-purple-600"></i>
                        </div>
                        @endif
                    </div>

                    <!-- Contenido -->
                    <div class="flex-1 min-w-0">
                        <!-- Header con tipo y fecha -->
                        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                @if($note->evidence_type === 'SISTEMA')
                                    bg-blue-50 text-blue-700 border border-blue-200
                                @else
                                    bg-purple-50 text-purple-700 border border-purple-200
                                @endif">
                                {{ $note->evidence_type }}
                            </span>
                            <span class="text-xs text-gray-500 flex items-center">
                                <i class="far fa-clock mr-1"></i>
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
                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200">
                            <i class="fas fa-user text-gray-400 mr-2 text-xs"></i>
                            <span class="text-xs text-gray-600">{{ $note->user->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Información adicional -->
        <div class="mt-4 text-center">
            <p class="text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Las notas del sistema registran automáticamente acciones importantes en la solicitud
            </p>
        </div>
    </div>
</div>
@endif
