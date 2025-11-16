@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-play-circle text-purple-600 mr-3"></i>
            Acciones y Operaciones
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">


            <!-- Editar -->
            @if (in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA']))
                <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                    class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition duration-150 text-center">
                    <i class="fas fa-edit text-green-600 text-xl mb-2"></i>
                    <span class="font-medium text-gray-900">Editar</span>
                    <span class="text-sm text-gray-500 mt-1">Modificar solicitud</span>
                </a>
            @else
                <div class="flex flex-col items-center p-4 bg-gray-100 rounded-lg text-center opacity-50">
                    <i class="fas fa-edit text-gray-400 text-xl mb-2"></i>
                    <span class="font-medium text-gray-500">Editar</span>
                    <span class="text-sm text-gray-400 mt-1">No disponible</span>
                </div>
            @endif

            <!-- Eliminar -->
            @if (in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA']))
                <form action="{{ route('service-requests.destroy', $serviceRequest) }}" method="POST"
                    class="flex flex-col items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition duration-150 text-center"
                    onsubmit="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex flex-col items-center">
                        <i class="fas fa-trash text-red-600 text-xl mb-2"></i>
                        <span class="font-medium text-gray-900">Eliminar</span>
                        <span class="text-sm text-gray-500 mt-1">Remover solicitud</span>
                    </button>
                </form>
            @else
                <div class="flex flex-col items-center p-4 bg-gray-100 rounded-lg text-center opacity-50">
                    <i class="fas fa-trash text-gray-400 text-xl mb-2"></i>
                    <span class="font-medium text-gray-500">Eliminar</span>
                    <span class="text-sm text-gray-400 mt-1">No disponible</span>
                </div>
            @endif

            <!-- Descargar Reporte -->
            <a href="{{ route('service-requests.download-report', $serviceRequest) }}"
                class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition duration-150 text-center">
                <i class="fas fa-download text-indigo-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Descargar</span>
                <span class="text-sm text-gray-500 mt-1">Reporte PDF</span>
            </a>

            <!-- Compartir por WhatsApp -->
            @php
                $shareUrl = route('service-requests.show', $serviceRequest);
                $statusLabels = [
                    'PENDIENTE' => 'Pendiente',
                    'ACEPTADA' => 'Aceptada',
                    'EN_PROCESO' => 'En Proceso',
                    'PAUSADA' => 'Pausada',
                    'RESUELTA' => 'Resuelta',
                    'CERRADA' => 'Cerrada',
                    'CANCELADA' => 'Cancelada'
                ];
                $statusText = $statusLabels[$serviceRequest->status] ?? $serviceRequest->status;

                // Mensaje sin emojis para evitar problemas de codificación
                $shareMessage = "Hola!\n\n" .
                                "Te comparto los detalles de esta solicitud de servicio:\n\n" .
                                "*Ticket:* " . $serviceRequest->ticket_number . "\n" .
                                "*Estado:* " . $statusText . "\n" .
                                "*Servicio:* " . ($serviceRequest->service->name ?? 'No especificado') . "\n\n" .
                                "Ver mas informacion aqui: " . $shareUrl;

                $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareMessage);
            @endphp
            <a href="{{ $whatsappUrl }}" target="_blank"
                class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition duration-150 text-center">
                <i class="fab fa-whatsapp text-green-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Compartir</span>
                <span class="text-sm text-gray-500 mt-1">Enviar por WhatsApp</span>
            </a>

        </div>
    </div>
</div>
