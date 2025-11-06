@props(['request'])

<!-- Detalles del Servicio -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Detalles del Servicio</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="font-medium text-gray-700">Familia de Servicio:</label>
            <p class="text-gray-600">{{ $request->subService->service->family->name }}</p>
        </div>

        <div>
            <label class="font-medium text-gray-700">Servicio:</label>
            <p class="text-gray-600">{{ $request->subService->service->name }}</p>
        </div>

        <div>
            <label class="font-medium text-gray-700">Sub-Servicio:</label>
            <p class="text-gray-600">{{ $request->subService->name }}</p>
        </div>

        <div>
            <label class="font-medium text-gray-700">SLA:</label>
            <p class="text-gray-600">{{ $request->sla->name }}</p>
        </div>
    </div>
</div>
