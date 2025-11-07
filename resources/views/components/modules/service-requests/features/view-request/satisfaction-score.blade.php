@props(['serviceRequest'])

@if($serviceRequest->satisfaction_score)
<!-- Calificación de Satisfacción -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Calificación de Satisfacción</h3>
    <div class="flex items-center">
        <div class="text-2xl font-bold text-{{ $serviceRequest->satisfaction_score >= 4 ? 'green' : ($serviceRequest->satisfaction_score >= 3 ? 'yellow' : 'red') }}-600 mr-4">
            {{ $serviceRequest->satisfaction_score }}/5
        </div>
        <div class="flex">
            @for($i = 1; $i <= 5; $i++)
                <i class="fas fa-star {{ $i <= $serviceRequest->satisfaction_score ? 'text-yellow-400' : 'text-gray-300' }} mr-1"></i>
                @endfor
        </div>
    </div>
</div>
@endif
