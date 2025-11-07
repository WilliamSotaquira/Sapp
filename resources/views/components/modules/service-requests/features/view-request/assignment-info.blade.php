@props(['serviceRequest'])

<div class="space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Asignación y Criticidad</h2>

    <div>
        <p class="text-sm text-gray-500">Asignado a</p>
        <p class="font-medium">{{ $serviceRequest->assignedTo->name ?? 'No asignado' }}</p>
    </div>

    <div>
        <p class="text-sm text-gray-500">Nivel de criticidad</p>
        <p class="font-medium">{{ $serviceRequest->criticalityLevel->name ?? 'N/A' }}</p>
    </div>

    <div>
        <p class="text-sm text-gray-500">Fecha límite</p>
        <p class="font-medium {{ $serviceRequest->due_date && $serviceRequest->due_date->isPast() ? 'text-red-600' : '' }}">
            {{ $serviceRequest->due_date ? $serviceRequest->due_date->format('d/m/Y H:i') : 'No definida' }}
        </p>
    </div>

    @if($serviceRequest->priority)
    <div>
        <p class="text-sm text-gray-500">Prioridad</p>
        <p class="font-medium">{{ $serviceRequest->priority }}</p>
    </div>
    @endif
</div>
