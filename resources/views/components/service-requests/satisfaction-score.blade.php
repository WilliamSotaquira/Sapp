@props(['request'])

@if($request->satisfaction_score)
<!-- Calificación de Satisfacción -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Calificación de Satisfacción</h3>
    <div class="flex items-center">
        <div class="text-2xl font-bold text-{{ $request->satisfaction_score >= 4 ? 'green' : ($request->satisfaction_score >= 3 ? 'yellow' : 'red') }}-600 mr-4">
            {{ $request->satisfaction_score }}/5
        </div>
        <div class="flex">
            @for($i = 1; $i <= 5; $i++)
                <i class="fas fa-star {{ $i <= $request->satisfaction_score ? 'text-yellow-400' : 'text-gray-300' }} mr-1"></i>
                @endfor
        </div>
    </div>
</div>
@endif
