@props(['serviceRequest'])

<div class="space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Detalles del Servicio</h2>

    <div>
        <p class="text-sm text-gray-500">Servicio</p>
        <p class="font-medium">{{ $serviceRequest->subService->service->name ?? 'N/A' }}</p>
    </div>

    <div>
        <p class="text-sm text-gray-500">Subservicio</p>
        <p class="font-medium">{{ $serviceRequest->subService->name ?? 'N/A' }}</p>
    </div>

    @if($serviceRequest->internal_code)
    <div>
        <p class="text-sm text-gray-500">Código interno</p>
        <p class="font-medium">{{ $serviceRequest->internal_code }}</p>
    </div>
    @endif

    <div>
        <p class="text-sm text-gray-500 mb-2">Descripción</p>
        <textarea
            readonly
            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 resize-none"
            rows="6"
            style="cursor: default;"
        >{{ $serviceRequest->description ?? 'Sin descripción' }}</textarea>
    </div>

    @if($serviceRequest->observation)
    <div>
        <p class="text-sm text-gray-500 mb-2">Observaciones</p>
        <textarea
            readonly
            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 resize-none"
            rows="6"
            style="cursor: default;"
        >{{ $serviceRequest->observation }}</textarea>
    </div>
    @endif
</div>
