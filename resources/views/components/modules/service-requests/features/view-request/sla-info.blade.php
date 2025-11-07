@props(['serviceRequest'])

<div class="space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Información SLA</h2>

    <div>
        <p class="text-sm text-gray-500">Tiempo transcurrido</p>
        <p class="font-medium">{{ $serviceRequest->created_at->diffForHumans() }}</p>
    </div>

    @if($serviceRequest->due_date)
    <div>
        <p class="text-sm text-gray-500">Tiempo restante</p>
        <p class="font-medium {{ $serviceRequest->due_date->isPast() ? 'text-red-600' : 'text-green-600' }}">
            {{ now()->diffForHumans($serviceRequest->due_date, true) }}
        </p>
    </div>
    @endif

    @if($serviceRequest->sla)
    <div>
        <p class="text-sm text-gray-500">SLA Aplicado</p>
        <p class="font-medium">{{ $serviceRequest->sla->name ?? 'N/A' }}</p>
    </div>

    <div>
        <p class="text-sm text-gray-500">Tiempo de respuesta</p>
        <p class="font-medium">{{ $serviceRequest->sla->response_time ?? 'N/A' }} horas</p>
    </div>

    <div>
        <p class="text-sm text-gray-500">Tiempo de resolución</p>
        <p class="font-medium">{{ $serviceRequest->sla->resolution_time ?? 'N/A' }} horas</p>
    </div>
    @endif
</div>
