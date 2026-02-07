@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-100' }} px-6 py-4 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-align-left {{ $isDead ? 'text-gray-500' : 'text-blue-600' }} mr-3"></i>
            Descripción
        </h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Descripción</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->description }}</p>
                </div>
            </div>

            @if($serviceRequest->additional_notes)
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Notas Adicionales</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->additional_notes }}</p>
                </div>
            </div>
            @endif

            @if($serviceRequest->solution_details)
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Detalles de la Solución</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->solution_details }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
