@props(['serviceRequest'])

<!-- Notas de Resolución -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Notas de Resolución</h3>
    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->resolution_notes }}</p>
    @if($serviceRequest->actual_resolution_time)
    <p class="text-sm text-gray-500 mt-2">
        <strong>Tiempo real de resolución:</strong> {{ $serviceRequest->actual_resolution_time }} minutos
    </p>
    @endif
</div>
