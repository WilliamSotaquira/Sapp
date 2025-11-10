@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-users text-green-600 mr-3"></i>
            Asignación y Responsables
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Solicitante -->
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ substr($serviceRequest->requester->name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Solicitante</label>
                    <p class="text-gray-900 font-semibold">{{ $serviceRequest->requester->name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-500">{{ $serviceRequest->requester->email ?? '' }}</p>
                </div>
            </div>

            <!-- Asignado a -->
            <div class="flex items-center space-x-3">
                @if($serviceRequest->assigned_to)
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ substr($serviceRequest->assignedTo->name ?? 'T', 0, 1) }}
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Asignado a</label>
                    <p class="text-gray-900 font-semibold">{{ $serviceRequest->assignedTo->name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-500">{{ $serviceRequest->assignedTo->email ?? '' }}</p>
                </div>
                @else
                <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Asignado a</label>
                    <p class="text-gray-900 font-semibold">Sin asignar</p>
                    <p class="text-sm text-gray-500">Pendiente de asignación</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
