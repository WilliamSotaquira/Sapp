@props(['request'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $timeSlots = $viewService->getTimeSlots($request);
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
            <div class="text-lg font-semibold">{{ $slot['minutes'] }} min</div>

            <div class="text-xs text-gray-500 mt-2">
                @if($slot['completed_at'])
                Completado: {{ $slot['completed_at']->format('d/m/Y H:i') }}
                @else
                @php
                $now = now();
                $remaining = 0;
                $isOverdue = false;

                if ($slot['deadline']) {
                    $remaining = $slot['deadline']->diffInMinutes($now);
                    $isOverdue = $slot['deadline']->isPast();
                }
                @endphp

                @if($slot['deadline'])
                    @if($isOverdue)
                    <span class="text-red-600 font-semibold">
                        Vencido hace {{ $remaining }} min
                    </span>
                    @else
                    <span class="text-green-600">
                        Vence en {{ $remaining }} min
                    </span>
                    @endif
                    <br>
                    Límite: {{ $slot['deadline']->format('d/m/Y H:i') }}
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
