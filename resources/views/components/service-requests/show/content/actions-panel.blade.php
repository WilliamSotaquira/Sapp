@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gray-50 border-gray-200' }} px-6 py-4 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-cog {{ $isDead ? 'text-gray-500' : 'text-purple-600' }} mr-3"></i>
            Acciones y Compartir
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">


            <!-- Editar -->
            @if (in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA']))
                <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                    class="flex flex-col items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition duration-150 text-center">
                    <i class="fas fa-edit text-green-600 text-lg mb-1"></i>
                    <span class="font-medium text-gray-900 text-sm">Editar</span>
                    <span class="text-xs text-gray-500">Modificar</span>
                </a>
            @else
                <div class="flex flex-col items-center p-3 bg-gray-100 rounded-lg text-center opacity-50">
                    <i class="fas fa-edit text-gray-400 text-lg mb-1"></i>
                    <span class="font-medium text-gray-500 text-sm">Editar</span>
                    <span class="text-xs text-gray-400">No disponible</span>
                </div>
            @endif

            <!-- Eliminar -->
            @if (in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA']))
                <form action="{{ route('service-requests.destroy', $serviceRequest) }}" method="POST"
                    class="flex flex-col items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition duration-150 text-center"
                    onsubmit="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex flex-col items-center">
                        <i class="fas fa-trash text-red-600 text-lg mb-1"></i>
                        <span class="font-medium text-gray-900 text-sm">Eliminar</span>
                        <span class="text-xs text-gray-500">Remover</span>
                    </button>
                </form>
            @else
                <div class="flex flex-col items-center p-3 bg-gray-100 rounded-lg text-center opacity-50">
                    <i class="fas fa-trash text-gray-400 text-lg mb-1"></i>
                    <span class="font-medium text-gray-500 text-sm">Eliminar</span>
                    <span class="text-xs text-gray-400">No disponible</span>
                </div>
            @endif

            <!-- Descargar Reporte -->
            <a href="{{ route('service-requests.download-report', $serviceRequest) }}"
                class="flex flex-col items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition duration-150 text-center">
                <i class="fas fa-download text-indigo-600 text-lg mb-1"></i>
                <span class="font-medium text-gray-900 text-sm">Descargar</span>
                <span class="text-xs text-gray-500">PDF</span>
            </a>

            <!-- Compartir por WhatsApp -->
            @php
                // URL pública para consulta sin autenticación
                $publicUrl = route('public.tracking.show', $serviceRequest->ticket_number);

                $statusLabels = [
                    'NUEVA' => 'Nueva',
                    'EN_REVISION' => 'En Revisión',
                    'ACEPTADA' => 'Aceptada',
                    'EN_PROGRESO' => 'En Progreso',
                    'PAUSADA' => 'Pausada',
                    'RESUELTA' => 'Resuelta',
                    'CERRADA' => 'Cerrada',
                    'RECHAZADA' => 'Rechazada',
                    'PENDIENTE' => 'Pendiente',
                    'EN_PROCESO' => 'En Proceso',
                    'CANCELADA' => 'Cancelada'
                ];
                $statusText = $statusLabels[$serviceRequest->status] ?? str_replace('_', ' ', $serviceRequest->status);

                // Mensaje optimizado para WhatsApp con mejor formato
                $shareMessage = "🎫 *Solicitud de Servicio*\n\n" .
                                "📋 *Ticket:* " . $serviceRequest->ticket_number . "\n" .
                                "📊 *Estado:* " . $statusText . "\n" .
                                "🔧 *Servicio:* " . ($serviceRequest->subService->service->name ?? 'No especificado') . "\n" .
                                "📅 *Creada:* " . $serviceRequest->created_at->format('d/m/Y') . "\n\n" .
                                "🔗 *Consulta el estado aquí:*\n" . $publicUrl . "\n\n" .
                                "✅ _Sin necesidad de iniciar sesión_\n" .
                                "👤 _Acceso directo para cualquier persona_";

                $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareMessage);

                // URL para email
                $emailSubject = "Consulta de Solicitud - " . $serviceRequest->ticket_number . " (Sin Login)";
                $emailBody = "Hola,\n\n" .
                            "Te comparto el enlace de consulta de esta solicitud de servicio:\n\n" .
                            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
                            "📋 Ticket: " . $serviceRequest->ticket_number . "\n" .
                            "📊 Estado Actual: " . $statusText . "\n" .
                            "🔧 Servicio: " . ($serviceRequest->subService->service->name ?? 'No especificado') . "\n" .
                            "📅 Fecha de Creación: " . $serviceRequest->created_at->format('d/m/Y H:i') . "\n" .
                            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                            "🔗 Enlace de consulta pública:\n" . $publicUrl . "\n\n" .
                            "✅ Este enlace NO requiere iniciar sesión\n" .
                            "👤 Cualquier persona puede consultar el estado\n" .
                            "📱 Funciona en móvil, tablet y computadora\n" .
                            "🔄 El estado se actualiza en tiempo real\n\n" .
                            "Saludos cordiales";
                $emailUrl = "mailto:?subject=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody);
            @endphp

            <!-- WhatsApp -->
            <a href="{{ $whatsappUrl }}" target="_blank"
                class="flex flex-col items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition duration-150 text-center">
                <i class="fab fa-whatsapp text-green-600 text-lg mb-1"></i>
                <span class="font-medium text-gray-900 text-sm">WhatsApp</span>
                <span class="text-xs text-gray-500">Link público</span>
            </a>

            <!-- Email -->
            <a href="{{ $emailUrl }}" target="_blank"
                class="flex flex-col items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-150 text-center">
                <i class="fas fa-envelope text-blue-600 text-lg mb-1"></i>
                <span class="font-medium text-gray-900 text-sm">Email</span>
                <span class="text-xs text-gray-500">Link público</span>
            </a>

            <!-- Copiar Enlace Público -->
            <button onclick="copyPublicLink()" type="button"
                class="flex flex-col items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition duration-150 text-center">
                <i class="fas fa-copy text-purple-600 text-lg mb-1"></i>
                <span class="font-medium text-gray-900 text-sm">Copiar Link</span>
                <span class="text-xs text-gray-500">Link público</span>
            </button>

        </div>

    </div>
</div>
