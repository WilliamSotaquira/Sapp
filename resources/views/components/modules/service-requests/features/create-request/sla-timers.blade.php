@props(['serviceRequest'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $timeSlots = $viewService->getTimeSlots($serviceRequest);
@endphp

<!-- Tiempos del SLA -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Tiempos del SLA</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($timeSlots as $slot)
        <div class="text-center p-4 border rounded-lg
        {{ $slot['completed_at'] ? 'bg-green-50 border-green-200' :
           ($slot['deadline'] && $slot['deadline']->isPast() ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200') }}">

            <i class="fas {{ $slot['icon'] }} text-gray-500 text-lg mb-2"></i>

            <div class="text-sm font-medium text-gray-600">{{ $slot['label'] }}</div>
            <div class="text-lg font-semibold">
                {{ $slot['minutes'] ? \App\Services\TimeFormatService::formatMinutes($slot['minutes']) : 'N/A' }}
            </div>

            <div class="text-xs text-gray-500 mt-2">
                @if($slot['completed_at'])
                <div class="text-green-600 font-semibold">
                    <i class="fas fa-check-circle mr-1"></i>
                    Completado
                </div>
                <div class="mt-1">
                    {{ $slot['completed_at']->format('d/m/Y H:i') }}
                </div>
                @else
                @php
                $now = now();
                $remaining = 0;
                $isOverdue = false;

                if ($slot['deadline']) {
                    $remaining = $now->diffInMinutes($slot['deadline'], false); // false para permitir negativos
                    $isOverdue = $slot['deadline']->isPast();
                }
                @endphp

                @if($slot['deadline'])
                    @if($isOverdue)
                    <div class="text-red-600 font-semibold">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Vencido hace {{ \App\Services\TimeFormatService::formatOverdueTime($remaining) }}
                    </div>
                    @else
                    <div class="text-green-600">
                        <i class="fas fa-clock mr-1"></i>
                        Vence en {{ \App\Services\TimeFormatService::formatRemainingTime($remaining) }}
                    </div>
                    @endif
                    <div class="mt-1 text-gray-600">
                        Límite: {{ $slot['deadline']->format('d/m/Y H:i') }}
                    </div>
                @else
                    <span class="text-gray-500">
                        Sin plazo definido
                    </span>
                @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
