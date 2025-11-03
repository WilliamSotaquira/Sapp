{{-- resources/views/service-requests/resolve.blade.php --}}
@extends('layouts.app')

@section('title', 'Resolver Solicitud #' . $serviceRequest->id)

@section('content')
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Resolver Solicitud</h2>
                <p class="text-gray-600 text-sm">#{{ $serviceRequest->ticket_number }}</p>
            </div>
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                ← Volver a la solicitud
            </a>
        </div>

        <div class="space-y-6">
            <!-- Resumen de Evidencias -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">Evidencias Registradas</h4>
                        @if($serviceRequest->stepByStepEvidences->count() > 0)
                            <p class="text-blue-700 text-sm mb-2">
                                Se han registrado <strong>{{ $serviceRequest->stepByStepEvidences->count() }}</strong> evidencias paso a paso:
                            </p>
                            <ul class="text-blue-700 text-sm space-y-1">
                                @foreach($serviceRequest->stepByStepEvidences as $evidence)
                                <li class="flex items-center">
                                    <strong class="mr-2">Paso {{ $evidence->step_number }}:</strong>
                                    <span class="flex-1">{{ $evidence->title }}</span>
                                    @if($evidence->hasFile())
                                        <i class="fas fa-paperclip text-blue-500 ml-2" title="Con archivo adjunto"></i>
                                    @endif
                                    <span class="text-blue-500 text-xs ml-2">
                                        ({{ $evidence->created_at->format('d/m H:i') }})
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>No hay evidencias paso a paso registradas.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información de la Solicitud -->
            <div class="border border-gray-200 rounded-lg">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700">Información de la Solicitud</h4>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-2">
                            <div>
                                <span class="font-medium text-gray-700">Sub-Servicio:</span>
                                <span class="text-gray-900 ml-2">{{ $serviceRequest->subService->name }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Servicio:</span>
                                <span class="text-gray-900 ml-2">{{ $serviceRequest->subService->service->name }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Familia:</span>
                                <span class="text-gray-900 ml-2">{{ $serviceRequest->subService->service->family->name }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <span class="font-medium text-gray-700">Criticidad:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($serviceRequest->criticality_level == 'ALTA') bg-red-100 text-red-800
                                    @elseif($serviceRequest->criticality_level == 'MEDIA') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800 @endif ml-2">
                                    {{ $serviceRequest->criticality_level }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">SLA:</span>
                                <span class="text-gray-900 ml-2">{{ $serviceRequest->sla->name ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Tiempo SLA:</span>
                                <span class="text-gray-900 ml-2">{{ $serviceRequest->sla->resolution_time_minutes ?? 'N/A' }} minutos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Resolución -->
            <form action="{{ route('service-requests.resolve-with-evidence', $serviceRequest) }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <!-- Notas de Resolución -->
                    <div>
                        <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notas de Resolución *
                        </label>
                        <textarea name="resolution_notes" id="resolution_notes" rows="5" required
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                  placeholder="Describa en detalle la resolución final del problema, incluyendo la solución aplicada, resultados obtenidos y cualquier recomendación...">{{ old('resolution_notes') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Incluya información detallada sobre la solución aplicada y los resultados obtenidos.
                        </p>
                    </div>

                    <!-- Tiempos de Resolución -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="actual_resolution_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Tiempo Real de Resolución (minutos) *
                            </label>
                            <input type="number" name="actual_resolution_time" id="actual_resolution_time"
                                   value="{{ old('actual_resolution_time') }}" required min="1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                   placeholder="Tiempo total empleado en minutos">
                            <p class="text-xs text-gray-500 mt-1">Tiempo real empleado en resolver la solicitud.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tiempo SLA Esperado
                            </label>
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                                {{ $serviceRequest->sla->resolution_time_minutes ?? 'N/A' }} minutos
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Tiempo máximo establecido en el SLA.</p>
                        </div>
                    </div>
                </div>

                <!-- Validación de Evidencias -->
                @if($serviceRequest->stepByStepEvidences->count() == 0)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="text-yellow-800 text-sm">
                                <strong>Advertencia:</strong> No se han registrado evidencias paso a paso.
                            </p>
                            <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                               class="text-yellow-700 hover:text-yellow-800 text-sm font-medium underline">
                                Agregar la primera evidencia
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Botones -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-6">
                    <a href="{{ route('service-requests.show', $serviceRequest) }}"
                       class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors">
                        ← Volver a la solicitud
                    </a>

                    @if($serviceRequest->stepByStepEvidences->count() > 0)
                    <button type="submit"
                            onclick="return confirm('¿Está seguro de marcar esta solicitud como resuelta? Esta acción registrará el cierre formal del caso.')"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-200 transition-colors">
                        <i class="fas fa-check-double mr-2"></i>
                        Confirmar Resolución
                    </button>
                    @else
                    <button type="button" disabled
                            class="px-5 py-2.5 text-sm font-medium text-gray-400 bg-gray-200 border border-transparent rounded-lg cursor-not-allowed">
                        <i class="fas fa-check-double mr-2"></i>
                        Se Requieren Evidencias
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
