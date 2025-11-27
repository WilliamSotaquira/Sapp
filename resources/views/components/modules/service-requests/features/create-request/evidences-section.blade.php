@props(['serviceRequest'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $evidencesSummary = $viewService->getEvidencesSummary($serviceRequest);
    $resolveData = $viewService->getResolveButtonData($serviceRequest);
@endphp

<!-- Evidencias de Ejecución -->
<div class="p-6 border-b">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">
            <i class="fas fa-camera mr-2"></i>Evidencias de Ejecución
            <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                {{ $evidencesSummary['total'] }}
            </span>
        </h3>

        @if($serviceRequest->status === 'EN_PROCESO')
        <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i>Agregar Evidencia
        </a>
        @endif
    </div>

    @if($evidencesSummary['total'] > 0)
    <div class="bg-gray-50 rounded-lg p-4">
        <!-- Resumen de Evidencias -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-white rounded-lg border">
                <div class="text-2xl font-bold text-blue-600">{{ $evidencesSummary['step_by_step'] }}</div>
                <div class="text-sm text-gray-600">Evidencias Paso a Paso</div>
            </div>
            <div class="text-center p-3 bg-white rounded-lg border">
                <div class="text-2xl font-bold text-green-600">{{ $evidencesSummary['files'] }}</div>
                <div class="text-sm text-gray-600">Archivos Adjuntos</div>
            </div>
            <div class="text-center p-3 bg-white rounded-lg border">
                <div class="text-2xl font-bold text-purple-600">{{ $evidencesSummary['comments'] }}</div>
                <div class="text-sm text-gray-600">Comentarios</div>
            </div>
        </div>

        <!-- Lista de Evidencias -->
        <div class="space-y-3">
            @foreach($serviceRequest->evidences->sortBy('step_number') as $evidence)
            <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($evidence->evidence_type == 'PASO_A_PASO') bg-blue-100 text-blue-800
                            @elseif($evidence->evidence_type == 'ARCHIVO') bg-green-100 text-green-800
                            @elseif($evidence->evidence_type == 'COMENTARIO') bg-purple-100 text-purple-800
                            @else bg-gray-100 text-gray-800 @endif">
                                {{ $evidence->evidence_type }}
                                @if($evidence->step_number)
                                - Paso {{ $evidence->step_number }}
                                @endif
                            </span>
                            <span class="text-sm text-gray-500 ml-2">
                                {{ $evidence->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <h4 class="font-semibold text-gray-800 mb-1">{{ $evidence->title }}</h4>

                        @if($evidence->description)
                        <p class="text-gray-600 text-sm mb-2">{{ Str::limit($evidence->description, 100) }}</p>
                        @endif

                        @if($evidence->hasFile())
                        <div class="flex items-center text-sm text-green-600">
                            <i class="fas fa-paperclip mr-1"></i>
                            <span>{{ $evidence->file_original_name }}</span>
                            <span class="text-gray-500 ml-2">
                                @php
                                $fileSize = $evidence->file_size && is_numeric($evidence->file_size)
                                ? number_format($evidence->file_size / 1024 / 1024, 2) . ' MB'
                                : 'Tamaño no disponible';
                                @endphp
                                ({{ $fileSize }})
                            </span>
                        </div>
                        @endif
                    </div>

                    <div class="flex space-x-2 ml-4">
                        <a href="{{ route('service-requests.evidences.show', [$serviceRequest, $evidence]) }}"
                            class="text-blue-600 hover:text-blue-800" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>

                        @if($evidence->hasFile())
                        <a href="{{ route('service-requests.evidences.download', [$serviceRequest, $evidence]) }}"
                            class="text-green-600 hover:text-green-800" title="Descargar archivo">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif

                        @if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']))
                        <form action="{{ route('service-requests.evidences.destroy', [$serviceRequest, $evidence]) }}"
                            method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800"
                                onclick="return confirm('¿Está seguro de eliminar esta evidencia?')"
                                title="Eliminar evidencia">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="text-center py-8 bg-gray-50 rounded-lg">
        <i class="fas fa-camera text-gray-400 text-4xl mb-3"></i>
        <p class="text-gray-500 mb-4">No hay evidencias registradas para esta solicitud.</p>
        @if($serviceRequest->status === 'EN_PROCESO')
        <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>Agregar Primera Evidencia
        </a>
        @endif
    </div>
    @endif

    <!-- Validación para Resolución -->
    @if($serviceRequest->status == 'EN_PROCESO')
    <div class="mt-4 p-4 rounded-lg {{ $resolveData['can_resolve'] ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
        <div class="flex items-center">
            @if($resolveData['can_resolve'])
            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
            <div>
                <p class="font-semibold text-green-800">Listo para Resolver</p>
                <p class="text-green-700 text-sm">
                    @if($resolveData['has_step_evidences'] && $resolveData['has_file_evidences'])
                    ✅ Tiene {{ $resolveData['step_evidences_count'] }} evidencias paso a paso<br>
                    ✅ Tiene {{ $resolveData['file_evidences_count'] }} archivos adjuntos
                    @elseif($resolveData['has_step_evidences'])
                    ✅ Tiene {{ $resolveData['step_evidences_count'] }} evidencias paso a paso
                    @else
                    ✅ Tiene {{ $resolveData['file_evidences_count'] }} archivos adjuntos
                    @endif
                </p>
            </div>
            <a href="{{ route('service-requests.resolve-form', $serviceRequest) }}"
                class="ml-auto bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-check-double mr-2"></i>Resolver Solicitud
            </a>
            @else
            <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3"></i>
            <div>
                <p class="font-semibold text-yellow-800">Evidencias Requeridas</p>
                <p class="text-yellow-700 text-sm">
                    Para resolver la solicitud debe agregar al menos:<br>
                    • Una evidencia paso a paso, O<br>
                    • Un archivo adjunto
                </p>
            </div>
            @if($serviceRequest->status !== 'CERRADA')
            <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                class="ml-auto bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-plus mr-2"></i>Agregar Evidencia
            </a>
            @endif
            @endif
        </div>
    </div>
    @endif
</div>
