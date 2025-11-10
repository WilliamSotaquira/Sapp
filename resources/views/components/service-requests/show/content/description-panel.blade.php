@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-align-left text-blue-600 mr-3"></i>
            Descripción Detallada
        </h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Título</label>
                <p class="text-gray-900 font-semibold text-lg">{{ $serviceRequest->title }}</p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Descripción</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->description }}</p>
                </div>
            </div>

            @if($serviceRequest->additional_notes)
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Notas Adicionales</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->additional_notes }}</p>
                </div>
            </div>
            @endif

            @if($serviceRequest->solution_details)
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Detalles de la Solución</label>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->solution_details }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
