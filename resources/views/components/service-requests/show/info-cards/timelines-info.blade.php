@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<x-service-requests.show.sr-card
    title="Historial de la Solicitud"
    icon="fa-history"
    iconColor="text-purple-500"
    :headerBg="$isDead ? null : 'bg-purple-50/50 border-purple-100'"
    :dead="$isDead">

    <div class="space-y-3 text-sm">
        <div class="flex items-center justify-between">
            <span class="text-gray-500">Fecha de la solicitud</span>
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

</x-service-requests.show.sr-card>
