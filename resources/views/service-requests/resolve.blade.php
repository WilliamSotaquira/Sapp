{{-- resources/views/service_requests/resolve-form.blade.php --}}
@extends('layouts.app')

@section('title', 'Resolver Solicitud ' . $serviceRequest->ticket_number)

@section('breadcrumb')
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-700">Solicitudes</a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-blue-600 hover:text-blue-700">{{ $serviceRequest->ticket_number }}</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Resolver</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white px-6 py-4">
            <h2 class="text-2xl font-bold">Resolver Solicitud: {{ $serviceRequest->ticket_number }}</h2>
            <p class="text-green-100">{{ $serviceRequest->title }}</p>
        </div>

        <!-- Evidencias Registradas -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-camera mr-2"></i>Evidencias Registradas
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                    {{ $serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count() }}
                </span>
            </h3>

            @if($serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count() > 0)
                <div class="space-y-3">
                    @foreach($serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->sortBy('step_number') as $evidence)
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    @if($evidence->evidence_type == 'PASO_A_PASO') bg-blue-100 text-blue-800
                                                    @elseif($evidence->evidence_type == 'ARCHIVO') bg-green-100 text-green-800
                                                    @endif">
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
                                    <p class="text-gray-600 text-sm mb-2">{{ $evidence->description }}</p>
                                    @endif

                                    @if($evidence->hasFile())
                                    <div class="flex items-center text-sm text-green-600 mt-2">
                                        <i class="fas fa-paperclip mr-1"></i>
                                        <span>{{ $evidence->file_original_name }}</span>
                                        <span class="text-gray-500 ml-2">
                                            ({{ $evidence->file_size ? number_format($evidence->file_size / 1024, 2) . ' KB' : '0 B' }})
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
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fas fa-camera text-gray-400 text-4xl mb-3"></i>
                    <p class="text-gray-500 mb-4">No hay evidencias registradas para esta solicitud.</p>
                    @if($serviceRequest->status !== 'CERRADA')
                    <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Agregar Evidencia
                    </a>
                    @endif
                </div>
            @endif
        </div>

        <!-- Formulario de Resolución -->
        <form action="{{ route('service-requests.resolve', $serviceRequest) }}" method="POST">
            @csrf

            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Información de Resolución</h3>

                <!-- Notas de Resolución -->
                <div class="mb-6">
                    <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notas de Resolución *
                    </label>
                    <textarea name="resolution_notes" id="resolution_notes" rows="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describa los detalles de la resolución, pasos realizados, solución aplicada, etc."
                        required>{{ old('resolution_notes') }}</textarea>
                    @error('resolution_notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tiempo Real de Resolución -->
                <div class="mb-6">
                    <label for="actual_resolution_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Tiempo Real de Resolución (minutos) *
                    </label>
                    <input type="number" name="actual_resolution_time" id="actual_resolution_time"
                        min="1" max="1440"
                        class="w-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="{{ old('actual_resolution_time') }}" required>
                    @error('actual_resolution_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Tiempo estimado por SLA: {{ $serviceRequest->sla->resolution_time_minutes }} minutos
                    </p>
                </div>

                <!-- Resumen de Evidencias -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-2">Resumen de Evidencias</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-list-ol text-blue-500 mr-2"></i>
                            <span>Evidencias Paso a Paso: </span>
                            <span class="font-semibold ml-1">{{ $serviceRequest->stepByStepEvidences->count() }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-paperclip text-green-500 mr-2"></i>
                            <span>Archivos Adjuntos: </span>
                            <span class="font-semibold ml-1">{{ $serviceRequest->fileEvidences->count() }}</span>
                        </div>
                    </div>

                    @if($serviceRequest->stepByStepEvidences->count() == 0 && $serviceRequest->fileEvidences->count() == 0)
                        <div class="mt-2 p-2 bg-yellow-100 border border-yellow-200 rounded">
                            <p class="text-yellow-800 text-sm">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                No hay evidencias registradas. Se recomienda agregar evidencias antes de resolver.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('service-requests.show', $serviceRequest) }}"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                        Cancelar
                    </a>

                    @if($serviceRequest->evidences->whereIn('evidence_type', ['PASO_A_PASO', 'ARCHIVO'])->count() > 0)
                        <button type="submit"
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            Confirmar Resolución
                        </button>
                    @else
                        <button type="button"
                            onclick="confirmResolutionWithoutEvidence()"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Resolver Sin Evidencias
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function confirmResolutionWithoutEvidence() {
        if (confirm('⚠️ ¿Está seguro de resolver esta solicitud sin evidencias?\n\nSe recomienda agregar al menos una evidencia paso a paso o archivo adjunto antes de resolver.')) {
            document.querySelector('form').submit();
        }
    }

    // Validación del tiempo de resolución
    document.getElementById('actual_resolution_time').addEventListener('change', function() {
        const slaTime = {{ $serviceRequest->sla->resolution_time_minutes }};
        const actualTime = parseInt(this.value);

        if (actualTime > slaTime * 2) {
            if (!confirm(`El tiempo ingresado (${actualTime} min) es significativamente mayor al tiempo estimado por SLA (${slaTime} min). ¿Desea continuar?`)) {
                this.focus();
            }
        }
    });
</script>
@endsection
