@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-cog text-purple-600 mr-3"></i>
            Acciones y Compartir
        </h3>
        <p class="text-sm text-gray-600 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            Los links compartidos permiten consultar sin autenticación
        </p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">


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
                class="flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition-all duration-150 text-center group shadow-sm hover:shadow-md">
                <i class="fab fa-whatsapp text-green-600 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-gray-900">WhatsApp</span>
                <span class="text-xs text-gray-600 mt-1">Link público sin login</span>
            </a>

            <!-- Email -->
            <a href="{{ $emailUrl }}" target="_blank"
                class="flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all duration-150 text-center group shadow-sm hover:shadow-md">
                <i class="fas fa-envelope text-blue-600 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-gray-900">Email</span>
                <span class="text-xs text-gray-600 mt-1">Enviar sin login</span>
            </a>

            <!-- Copiar Enlace Público -->
            <button onclick="copyPublicLink()" type="button"
                class="flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:from-purple-100 hover:to-purple-200 transition-all duration-150 text-center group shadow-sm hover:shadow-md">
                <i class="fas fa-copy text-purple-600 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-gray-900">Copiar Link</span>
                <span class="text-xs text-gray-600 mt-1">Acceso sin login</span>
            </button>

        </div>

        <!-- Nota Informativa -->
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                <div class="text-sm text-blue-800">
                    <span class="font-semibold">Enlaces públicos:</span> Las personas con quienes compartas estos enlaces podrán consultar el estado de la solicitud sin necesidad de crear cuenta o iniciar sesión.
                </div>
            </div>
        </div>
    </div>
</div>
