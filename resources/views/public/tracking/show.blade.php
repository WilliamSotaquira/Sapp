<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Solicitud - {{ $serviceRequest->ticket_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header con botón de regreso -->
        <div class="max-w-5xl mx-auto mb-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('public.tracking.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold px-4 py-2 bg-white rounded-lg shadow hover:shadow-md transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Nueva búsqueda
                </a>
                <div class="flex items-center px-4 py-2 bg-green-50 border-2 border-green-200 rounded-lg shadow-sm">
                    <i class="fas fa-unlock text-green-600 mr-2"></i>
                    <span class="text-sm font-semibold text-green-800">Acceso sin login</span>
                </div>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <!-- Header de la solicitud -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold mb-2">
                                <i class="fas fa-ticket-alt"></i>
                                {{ $serviceRequest->ticket_number }}
                            </h1>
                            <p class="text-blue-100">{{ $serviceRequest->title }}</p>
                        </div>
                        <div class="mt-4 sm:mt-0">
                            @php
                                $statusConfig = [
                                    'NUEVA' => ['class' => 'bg-blue-500', 'icon' => 'fa-star'],
                                    'EN_REVISION' => ['class' => 'bg-yellow-500', 'icon' => 'fa-search'],
                                    'ACEPTADA' => ['class' => 'bg-green-500', 'icon' => 'fa-check'],
                                    'EN_PROGRESO' => ['class' => 'bg-purple-500', 'icon' => 'fa-cog fa-spin'],
                                    'RESUELTA' => ['class' => 'bg-teal-500', 'icon' => 'fa-check-circle'],
                                    'CERRADA' => ['class' => 'bg-gray-500', 'icon' => 'fa-lock'],
                                    'RECHAZADA' => ['class' => 'bg-red-500', 'icon' => 'fa-times-circle'],
                                    'PAUSADA' => ['class' => 'bg-orange-500', 'icon' => 'fa-pause-circle'],
                                ];
                                $config = $statusConfig[$serviceRequest->status] ?? ['class' => 'bg-gray-500', 'icon' => 'fa-question'];
                            @endphp
                            <span class="inline-flex items-center px-4 py-2 rounded-full {{ $config['class'] }} text-white font-semibold text-sm sm:text-base">
                                <i class="fas {{ $config['icon'] }} mr-2"></i>
                                {{ str_replace('_', ' ', $serviceRequest->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Información del Servicio -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Información Básica -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                Información General
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Servicio</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">
                                        {{ $serviceRequest->subService->service->name ?? 'N/A' }} -
                                        {{ $serviceRequest->subService->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Criticidad</dt>
                                    <dd class="mt-1">
                                        @php
                                            $criticalityConfig = [
                                                'BAJA' => ['class' => 'bg-green-100 text-green-800', 'icon' => 'fa-arrow-down'],
                                                'MEDIA' => ['class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fa-minus'],
                                                'ALTA' => ['class' => 'bg-orange-100 text-orange-800', 'icon' => 'fa-arrow-up'],
                                                'CRITICA' => ['class' => 'bg-red-100 text-red-800', 'icon' => 'fa-exclamation-triangle'],
                                            ];
                                            $critConfig = $criticalityConfig[$serviceRequest->criticality_level] ?? ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fa-question'];
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $critConfig['class'] }}">
                                            <i class="fas {{ $critConfig['icon'] }} mr-1"></i>
                                            {{ $serviceRequest->criticality_level }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <i class="fas fa-calendar text-blue-500 mr-1"></i>
                                        {{ $serviceRequest->created_at->format('d/m/Y H:i') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Plazos y Tiempos -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-clock text-blue-600 mr-2"></i>
                                Tiempos y Plazos
                            </h3>
                            <dl class="space-y-3">
                                @if($serviceRequest->acceptance_deadline)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Plazo de Aceptación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $serviceRequest->acceptance_deadline->format('d/m/Y H:i') }}
                                    </dd>
                                </div>
                                @endif
                                @if($serviceRequest->response_deadline)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Plazo de Respuesta</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $serviceRequest->response_deadline->format('d/m/Y H:i') }}
                                    </dd>
                                </div>
                                @endif
                                @if($serviceRequest->resolution_deadline)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Plazo de Resolución</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $serviceRequest->resolution_deadline->format('d/m/Y H:i') }}
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                            Descripción
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                            {{ $serviceRequest->description }}
                        </div>
                    </div>

                    <!-- Historial de Estados -->
                    @if($serviceRequest->statusHistories && $serviceRequest->statusHistories->count() > 0)
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-history text-blue-600 mr-2"></i>
                            Historial de Seguimiento
                        </h3>
                        <div class="space-y-3">
                            @foreach($serviceRequest->statusHistories as $history)
                            @php
                                $colorClasses = [
                                    'blue' => 'bg-blue-100 text-blue-600',
                                    'yellow' => 'bg-yellow-100 text-yellow-600',
                                    'green' => 'bg-green-100 text-green-600',
                                    'purple' => 'bg-purple-100 text-purple-600',
                                    'orange' => 'bg-orange-100 text-orange-600',
                                    'teal' => 'bg-teal-100 text-teal-600',
                                    'gray' => 'bg-gray-100 text-gray-600',
                                    'red' => 'bg-red-100 text-red-600',
                                ];
                                $colorClass = $colorClasses[$history->status_color] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full {{ $colorClass }} flex items-center justify-center">
                                        <i class="fas {{ $history->status_icon }} text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                        <span class="font-semibold text-gray-800 text-sm">
                                            {{ $history->status_label }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{ $history->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    @if($history->previous_status)
                                    <div class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-arrow-right mr-1"></i>
                                        Cambio desde: {{ str_replace('_', ' ', $history->previous_status) }}
                                    </div>
                                    @endif
                                    @if($history->comments)
                                    <p class="text-sm text-gray-600 mt-2 bg-white p-2 rounded border border-gray-200">
                                        <i class="fas fa-comment-alt mr-1 text-gray-400"></i>
                                        {{ $history->comments }}
                                    </p>
                                    @endif
                                    @if($history->changedBy)
                                    <div class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-user mr-1"></i>
                                        Por: {{ $history->changedBy->name }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <!-- Estado Actual (cuando no hay historial) -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            Estado Actual
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">Estado:</span>
                                <span class="font-semibold text-gray-900">{{ str_replace('_', ' ', $serviceRequest->status) }}</span>
                            </div>
                            @if($serviceRequest->accepted_at)
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-700">Aceptada:</span>
                                <span class="text-sm text-gray-900">{{ $serviceRequest->accepted_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                            @if($serviceRequest->responded_at)
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-700">Respuesta:</span>
                                <span class="text-sm text-gray-900">{{ $serviceRequest->responded_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                            @if($serviceRequest->resolved_at)
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-700">Resuelta:</span>
                                <span class="text-sm text-gray-900">{{ $serviceRequest->resolved_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                            @if($serviceRequest->closed_at)
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-700">Cerrada:</span>
                                <span class="text-sm text-gray-900">{{ $serviceRequest->closed_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">¿Necesitas más información?</h4>
                        <p class="text-sm text-blue-800">
                            Si tienes preguntas sobre tu solicitud, puedes contactar al equipo de soporte.
                            El estado se actualiza automáticamente conforme avanza el proceso.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
