@props(['serviceRequest'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $statusColors = $viewService->getStatusColors();
    $criticalityColors = $viewService->getCriticalityColors();
@endphp

<!-- Información General -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Información General</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="font-medium text-gray-700">Estado:</label>
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$serviceRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $serviceRequest->status }}
                @if($serviceRequest->is_paused && $serviceRequest->status === 'PAUSADA')
                <i class="fas fa-pause ml-1"></i>
                @endif
            </span>
        </div>

        <div>
            <label class="font-medium text-gray-700">Criticidad:</label>
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$serviceRequest->criticality_level] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $serviceRequest->criticality_level }}
            </span>
        </div>

        <div>
            <label class="font-medium text-gray-700">Solicitante:</label>
            <p class="text-gray-600">{{ $serviceRequest->requester->name }}</p>
        </div>

        <div>
            <label class="font-medium text-gray-700">Asignado a:</label>
            <p class="text-gray-600">{{ $serviceRequest->assignee ? $serviceRequest->assignee->name : 'Sin asignar' }}</p>
        </div>
    </div>
</div>
