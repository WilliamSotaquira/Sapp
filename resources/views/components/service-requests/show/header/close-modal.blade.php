{{-- resources/views/components/service-requests/show/header/close-modal.blade.php --}}
@if($serviceRequest->status === 'RESUELTA')
@php
    // Filtrar solo evidencias de tipo ARCHIVO
    $fileEvidences = $serviceRequest->evidences ? $serviceRequest->evidences->where('evidence_type', 'ARCHIVO') : collect();
    $fileEvidencesCount = $fileEvidences->count();
    $hasFileEvidences = $fileEvidencesCount > 0;
    $evidenceText = $fileEvidencesCount == 1 ? 'archivo' : 'archivos';
@endphp

<div class="inline">
    @if($hasFileEvidences)
    <!-- Botón HABILITADO con contador de archivos -->
    <div class="inline" x-data="{ open: false }">
        <button type="button"
                @click="open = true"
                class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 group">

            <i class="fas fa-lock mr-2"></i>
            Cerrar Solicitud
            <span class="ml-2 bg-purple-500 text-white text-xs font-bold px-2 py-1 rounded-full border border-purple-300 group-hover:bg-purple-400">
                {{ $fileEvidencesCount }}
            </span>
        </button>

        <!-- Modal de cierre -->
        <div x-show="open"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Fondo overlay -->
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                     @click="open = false">
                </div>

                <!-- Centrar modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <!-- Contenido del modal -->
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    <!-- Icono y título -->
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-lock text-purple-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Cerrar Solicitud
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Ticket: <span class="font-mono text-purple-600">#{{ $serviceRequest->ticket_number }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Indicador de archivos -->
                    <div class="mt-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Archivos Verificados
                                </h3>
                                <div class="mt-1 text-sm text-green-700">
                                    <p>La solicitud tiene <strong>{{ $fileEvidencesCount }} {{ $evidenceText }}</strong> adjuntos.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de archivos adjuntos -->
                    @if($fileEvidencesCount > 0)
                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-3">
                        <h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                            <i class="fas fa-paperclip mr-2"></i>
                            Archivos adjuntos:
                        </h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            @foreach($fileEvidences as $evidence)
                            <div class="flex items-center justify-between text-sm bg-white p-2 rounded border">
                                <div class="flex items-center truncate">
                                    <i class="fas fa-file text-gray-400 mr-2 flex-shrink-0"></i>
                                    <span class="truncate" title="{{ $evidence->title }}">
                                        {{ $evidence->title }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded flex-shrink-0">
                                    {{ \Illuminate\Support\Str::limit($evidence->description, 20) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Alerta de confirmación -->
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Acción irreversible
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>Una vez cerrada, la solicitud no podrá ser modificada.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de resumen -->
                    <div class="mt-4 bg-gray-50 rounded-md p-3">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Resumen:</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="text-gray-600">Ticket:</div>
                            <div class="font-mono text-gray-900">{{ $serviceRequest->ticket_number }}</div>

                            <div class="text-gray-600">Archivos:</div>
                            <div>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-file mr-1"></i>
                                    {{ $fileEvidencesCount }} {{ $evidenceText }}
                                </span>
                            </div>

                            <div class="text-gray-600">Estado actual:</div>
                            <div>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    RESUELTA
                                </span>
                            </div>

                            <div class="text-gray-600">Nuevo estado:</div>
                            <div>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    CERRADA
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form action="{{ route('service-requests.close', $serviceRequest) }}" method="POST" class="mt-5">
                        @csrf
                        @method('POST')

                        <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                                <span class="text-sm text-blue-700">
                                    Confirmo el cierre definitivo con {{ $fileEvidencesCount }} {{ $evidenceText }} adjuntos.
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                    @click="open = false"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-lock mr-2"></i>
                                Confirmar Cierre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Botón DESHABILITADO - Sin archivos -->
    <div class="inline relative" x-data="{ showTooltip: false }">
        <button type="button"
                disabled
                @mouseenter="showTooltip = true"
                @mouseleave="showTooltip = false"
                class="inline-flex items-center px-4 py-2 bg-gray-400 border border-transparent rounded-md shadow-sm text-sm font-medium text-white cursor-not-allowed opacity-75 group">

            <i class="fas fa-lock mr-2 text-gray-200"></i>
            Cerrar Solicitud
            <span class="ml-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full border border-red-300">
                0
            </span>
        </button>

        <!-- Tooltip de advertencia -->
        <div x-show="showTooltip"
             x-cloak
             class="absolute z-20 w-72 px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg shadow-sm -translate-x-1/2 left-1/2 -top-20">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-300 mr-2 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold">Archivos requeridos</p>
                    <p class="text-xs mt-1 text-red-100">Agrega al menos un archivo como evidencia antes de cerrar</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1 border-4 border-transparent border-t-red-600"></div>
        </div>
    </div>
    @endif

    <!-- Información debajo del botón -->
    <div class="mt-1 text-xs text-center">
        @if($hasFileEvidences)
        <span class="text-green-600 flex items-center justify-center">
            <i class="fas fa-check-circle mr-1"></i>
            {{ $fileEvidencesCount }} {{ $evidenceText }} - Listo para cerrar
        </span>
        @else
        <span class="text-red-600 flex items-center justify-center">
            <i class="fas fa-exclamation-circle mr-1"></i>
            Se requieren archivos adjuntos
        </span>
        @endif
    </div>
</div>

@else
<!-- Estado cuando no está RESUELTA -->
<div class="inline opacity-50 cursor-not-allowed">
    <button type="button"
            disabled
            class="inline-flex items-center px-4 py-2 bg-gray-400 border border-transparent rounded-md shadow-sm text-sm font-medium text-white">
        <i class="fas fa-lock mr-2"></i>
        Cerrar Solicitud
    </button>
    <div class="text-xs text-gray-500 mt-1 text-center">
        Estado actual: {{ $serviceRequest->status }}
    </div>
</div>
@endif

<style>
    [x-cloak] { display: none !important; }
</style>
