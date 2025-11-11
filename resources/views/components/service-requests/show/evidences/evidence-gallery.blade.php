@props(['serviceRequest'])

<div id="evidences-section" class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4 border-b border-amber-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-images text-amber-600 mr-3 text-xl"></i>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Evidencias y Archivos Adjuntos</h3>
                    <p class="text-sm text-amber-700 mt-1">Documentos, imágenes y archivos relacionados con la solicitud</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-800">
                    <i class="fas fa-file-alt mr-2"></i>
                    {{ $serviceRequest->evidences->count() }} archivo{{ $serviceRequest->evidences->count() !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>

    <div class="p-6">
        @if($serviceRequest->evidences->count() > 0)
            <!-- Estadísticas rápidas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @php
                    $imagesCount = $serviceRequest->evidences->where('file_type', 'like', 'image%')->count();
                    $documentsCount = $serviceRequest->evidences->whereIn('file_type', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->count();
                    $othersCount = $serviceRequest->evidences->count() - $imagesCount - $documentsCount;
                    $totalSize = $serviceRequest->evidences->sum('file_size');
                @endphp

                <div class="bg-blue-50 rounded-lg p-3 text-center border border-blue-100">
                    <div class="text-blue-600 mb-1">
                        <i class="fas fa-file-image text-lg"></i>
                    </div>
                    <div class="text-lg font-bold text-gray-800">{{ $imagesCount }}</div>
                    <div class="text-xs text-gray-600">Imágenes</div>
                </div>

                <div class="bg-green-50 rounded-lg p-3 text-center border border-green-100">
                    <div class="text-green-600 mb-1">
                        <i class="fas fa-file-pdf text-lg"></i>
                    </div>
                    <div class="text-lg font-bold text-gray-800">{{ $documentsCount }}</div>
                    <div class="text-xs text-gray-600">Documentos</div>
                </div>

                <div class="bg-purple-50 rounded-lg p-3 text-center border border-purple-100">
                    <div class="text-purple-600 mb-1">
                        <i class="fas fa-file-alt text-lg"></i>
                    </div>
                    <div class="text-lg font-bold text-gray-800">{{ $othersCount }}</div>
                    <div class="text-xs text-gray-600">Otros</div>
                </div>

                <div class="bg-amber-50 rounded-lg p-3 text-center border border-amber-100">
                    <div class="text-amber-600 mb-1">
                        <i class="fas fa-hdd text-lg"></i>
                    </div>
                    <div class="text-lg font-bold text-gray-800">{{ number_format($totalSize / 1024 / 1024, 1) }}MB</div>
                    <div class="text-xs text-gray-600">Total</div>
                </div>
            </div>

            <!-- Grid de evidencias -->
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-folder-open text-amber-500 mr-2"></i>
                    Archivos adjuntos
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($serviceRequest->evidences as $evidence)
                    <x-service-requests.show.evidences.evidence-card :evidence="$evidence" />
                    @endforeach
                </div>
            </div>
        @else
            <!-- Estado vacío mejorado -->
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 rounded-full mb-4">
                    <i class="fas fa-images text-3xl text-amber-500"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-2">No hay evidencias adjuntas</h4>
                <p class="text-gray-500 mb-6 max-w-md mx-auto">
                    Aún no se han agregado archivos a esta solicitud. Puedes comenzar subiendo la primera evidencia.
                </p>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 max-w-md mx-auto mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-amber-500 mt-1 mr-3"></i>
                        <div class="text-left">
                            <p class="text-sm font-medium text-amber-800">Sugerencia</p>
                            <p class="text-xs text-amber-700">Puedes subir imágenes, PDFs, documentos y otros archivos relacionados</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Sección de subida de archivos -->
        <div class="{{ $serviceRequest->evidences->count() > 0 ? 'mt-8 pt-6 border-t border-gray-200' : '' }}">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-cloud-upload-alt text-gray-600 mr-2"></i>
                        <h4 class="text-md font-semibold text-gray-700">
                            {{ $serviceRequest->evidences->count() > 0 ? 'Agregar más archivos' : 'Subir primera evidencia' }}
                        </h4>
                    </div>
                    <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
                        Máx. 10MB por archivo
                    </span>
                </div>
                <x-service-requests.show.evidences.evidence-uploader :serviceRequest="$serviceRequest" />
            </div>
        </div>

        <!-- Información adicional -->
        @if($serviceRequest->evidences->count() > 0)
        <div class="mt-4 text-center">
            <p class="text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Todos los archivos están almacenados de forma segura y son accesibles para los usuarios autorizados
            </p>
        </div>
        @endif
    </div>
</div>
