@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-history text-purple-600 mr-3"></i>
            Historial de la Solicitud
        </h3>
    </div>

    <div class="p-6">
        <!-- Timeline visual -->
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <!-- Timeline line -->
                <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 z-0"></div>

                <!-- Timeline steps -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-600 mt-2 text-center">Creada</span>
                </div>

                <div class="flex flex-col items-center relative z-10">
                    <div class="w-8 h-8 rounded-full {{ $serviceRequest->updated_at->gt($serviceRequest->created_at) ? 'bg-blue-500' : 'bg-gray-300' }} flex items-center justify-center">
                        @if($serviceRequest->updated_at->gt($serviceRequest->created_at))
                        <i class="fas fa-sync text-white text-xs"></i>
                        @endif
                    </div>
                    <span class="text-xs text-gray-600 mt-2 text-center">Actualizada</span>
                </div>

                <div class="flex flex-col items-center relative z-10">
                    <div class="w-8 h-8 rounded-full {{ $serviceRequest->resolved_at ? 'bg-purple-600' : 'bg-gray-300' }} flex items-center justify-center">
                        @if($serviceRequest->resolved_at)
                        <i class="fas fa-flag-checkered text-white text-xs"></i>
                        @endif
                    </div>
                    <span class="text-xs text-gray-600 mt-2 text-center">Completada</span>
                </div>
            </div>
        </div>

        <!-- Timeline details -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Creation -->
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-100">
                <div class="text-green-600 mb-2">
                    <i class="fas fa-plus-circle text-xl"></i>
                </div>
                <div class="text-lg font-semibold text-gray-900 mb-1">
                    {{ $serviceRequest->created_at->locale('es')->diffForHumans() }}
                </div>
                <p class="text-sm text-gray-700 mb-1">Solicitud creada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->created_at->format('d/m/Y \\a \\l\\a\\s H:i') }}</p>
            </div>

            <!-- Last Update -->
            <div class="text-center p-4 {{ $serviceRequest->updated_at->gt($serviceRequest->created_at) ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50 border border-gray-100' }}">
                <div class="{{ $serviceRequest->updated_at->gt($serviceRequest->created_at) ? 'text-blue-600' : 'text-gray-400' }} mb-2">
                    <i class="fas fa-edit text-xl"></i>
                </div>
                <div class="text-lg font-semibold text-gray-900 mb-1">
                    @if($serviceRequest->updated_at->gt($serviceRequest->created_at))
                    {{ $serviceRequest->updated_at->locale('es')->diffForHumans() }}
                    @else
                    Sin cambios
                    @endif
                </div>
                <p class="text-sm text-gray-700 mb-1">Última modificación</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->updated_at->format('d/m/Y \\a \\l\\a\\s H:i') }}</p>
            </div>

            <!-- Resolution -->
            <div class="text-center p-4 {{ $serviceRequest->resolved_at ? 'bg-purple-50 border border-purple-100' : 'bg-orange-50 border border-orange-100' }}">
                <div class="{{ $serviceRequest->resolved_at ? 'text-purple-600' : 'text-orange-500' }} mb-2">
                    <i class="{{ $serviceRequest->resolved_at ? 'fas fa-check-double' : 'fas fa-clock' }} text-xl"></i>
                </div>
                <div class="text-lg font-semibold {{ $serviceRequest->resolved_at ? 'text-gray-900' : 'text-orange-600' }} mb-1">
                    @if($serviceRequest->resolved_at)
                    {{ $serviceRequest->resolved_at->locale('es')->diffForHumans() }}
                    @else
                    En proceso
                    @endif
                </div>
                <p class="text-sm text-gray-700 mb-1">
                    {{ $serviceRequest->resolved_at ? 'Solicitud completada' : 'Estado actual' }}
                </p>
                <p class="text-xs text-gray-500">
                    @if($serviceRequest->resolved_at)
                    {{ $serviceRequest->resolved_at->format('d/m/Y \\a \\l\\a\\s H:i') }}
                    @else
                    Pendiente de resolución
                    @endif
                </p>
            </div>
        </div>

        <!-- Additional status info -->
        @if(!$serviceRequest->resolved_at)
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
            <div class="flex items-center justify-center text-yellow-700">
                <i class="fas fa-hourglass-half mr-2"></i>
                <span class="text-sm font-medium">Esta solicitud está actualmente en proceso de atención</span>
            </div>
            <div class="mt-3 flex items-center justify-center">
                @if (!$serviceRequest->is_reportable)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200 text-sm font-semibold">
                        <i class="fas fa-ban"></i>
                        Excluida de reportes
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-50 text-green-700 border border-green-100 text-sm font-semibold">
                        <i class="fas fa-check-circle"></i>
                        Incluida en reportes
                    </span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
