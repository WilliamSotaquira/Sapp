@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-clock text-purple-600 mr-3"></i>
            Líneas de Tiempo
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 mb-1">
                    {{ $serviceRequest->created_at->locale('es')->diffForHumans() }}
                </div>
                <p class="text-sm text-gray-600">Creada</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 mb-1">
                    @if($serviceRequest->updated_at->gt($serviceRequest->created_at))
                    {{ $serviceRequest->updated_at->locale('es')->diffForHumans() }}
                    @else
                    -
                    @endif
                </div>
                <p class="text-sm text-gray-600">Última actualización</p>
                <p class="text-xs text-gray-500">{{ $serviceRequest->updated_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 mb-1">
                    @if($serviceRequest->resolved_at)
                    {{ $serviceRequest->resolved_at->locale('es')->diffForHumans() }}
                    @else
                    -
                    @endif
                </div>
                <p class="text-sm text-gray-600">Resuelta</p>
                <p class="text-xs text-gray-500">
                    @if($serviceRequest->resolved_at)
                    {{ $serviceRequest->resolved_at->format('d/m/Y H:i') }}
                    @else
                    Pendiente
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
