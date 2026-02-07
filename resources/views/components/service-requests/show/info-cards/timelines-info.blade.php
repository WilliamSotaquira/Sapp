@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gradient-to-r from-purple-50 to-violet-50 border-purple-100' }} px-6 py-4 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-history {{ $isDead ? 'text-gray-500' : 'text-purple-600' }} mr-3"></i>
            Historial de la Solicitud
        </h3>
    </div>

    <div class="p-6">
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500">Creada</span>
                <span class="font-medium text-gray-900">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">Última modificación</span>
                <span class="font-medium text-gray-900">
                    @if($serviceRequest->updated_at->gt($serviceRequest->created_at))
                        {{ $serviceRequest->updated_at->format('d/m/Y H:i') }}
                    @else
                        Sin cambios
                    @endif
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">Resolución</span>
                <span class="font-medium {{ $serviceRequest->resolved_at ? 'text-gray-900' : 'text-amber-600' }}">
                    {{ $serviceRequest->resolved_at ? $serviceRequest->resolved_at->format('d/m/Y H:i') : 'Pendiente' }}
                </span>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-500">
            {{ $serviceRequest->created_at->locale('es')->diffForHumans() }}
            @if($serviceRequest->resolved_at)
                · Resuelta {{ $serviceRequest->resolved_at->locale('es')->diffForHumans() }}
            @endif
        </div>
    </div>
</div>
