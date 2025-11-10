@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-cogs text-blue-600 mr-3"></i>
            Información del Servicio
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500">Familia de Servicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->service->family->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Servicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->service->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Subservicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Categoría</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->category ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>
