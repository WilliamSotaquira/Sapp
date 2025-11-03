@extends('layouts.app')

@section('title', 'Solicitud ' . $serviceRequest->ticket_number)

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
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">{{ $serviceRequest->ticket_number }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <!-- Header -->
        <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">Solicitud: {{ $serviceRequest->ticket_number }}</h2>
                    <p class="text-blue-100">{{ $serviceRequest->title }}</p>
                </div>
                <div class="flex space-x-2">
                    @if($serviceRequest->status === 'PENDIENTE')
                        <form action="{{ route('service-requests.accept', $serviceRequest) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded">
                                <i class="fas fa-check mr-2"></i>Aceptar
                            </button>
                        </form>
                    @endif

                    @if($serviceRequest->status === 'ACEPTADA')
                        <form action="{{ route('service-requests.start', $serviceRequest) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-purple-500 hover:bg-purple-400 px-4 py-2 rounded">
                                <i class="fas fa-play mr-2"></i>Iniciar
                            </button>
                        </form>
                    @endif

                    @if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']) && !$serviceRequest->is_paused)
                        <button onclick="openPauseModal()" class="bg-orange-500 hover:bg-orange-400 px-4 py-2 rounded">
                            <i class="fas fa-pause mr-2"></i>Pausar
                        </button>
                    @endif

                    @if($serviceRequest->isPaused())
                        <form action="{{ route('service-requests.resume', $serviceRequest) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded">
                                <i class="fas fa-play mr-2"></i>Reanudar
                            </button>
                        </form>
                    @endif

                    @if($serviceRequest->status === 'EN_PROCESO')
                        @if($serviceRequest->stepByStepEvidences->count() > 0)
                            <a href="{{ route('service-requests.resolve-form', $serviceRequest) }}"
                               class="bg-green-500 hover:bg-green-400 px-4 py-2 rounded">
                                <i class="fas fa-check-circle mr-2"></i>Resolver
                            </a>
                        @else
                            <button class="bg-green-300 px-4 py-2 rounded cursor-not-allowed"
                                    title="Se requieren evidencias paso a paso para resolver">
                                <i class="fas fa-check-circle mr-2"></i>Resolver
                            </button>
                        @endif
                    @endif

                    @if($serviceRequest->status === 'RESUELTA')
                        <button onclick="openCloseModal()" class="bg-gray-500 hover:bg-gray-400 px-4 py-2 rounded">
                            <i class="fas fa-lock mr-2"></i>Cerrar
                        </button>
                    @endif

                    @if(in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA']))
                        <button onclick="openCancelModal()" class="bg-red-500 hover:bg-red-400 px-4 py-2 rounded">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Información General -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Información General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="font-medium text-gray-700">Estado:</label>
                    @php
                        $statusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                            'RESUELTA' => 'bg-green-100 text-green-800',
                            'CERRADA' => 'bg-gray-100 text-gray-800',
                            'CANCELADA' => 'bg-red-100 text-red-800'
                        ];
                    @endphp
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$serviceRequest->status] }}">
                        {{ $serviceRequest->status }}
                        @if($serviceRequest->is_paused && $serviceRequest->status === 'PAUSADA')
                            <i class="fas fa-pause ml-1"></i>
                        @endif
                    </span>
                </div>

                <div>
                    <label class="font-medium text-gray-700">Criticidad:</label>
                    @php
                        $criticalityColors = [
                            'BAJA' => 'bg-green-100 text-green-800',
                            'MEDIA' => 'bg-yellow-100 text-yellow-800',
                            'ALTA' => 'bg-orange-100 text-orange-800',
                            'CRITICA' => 'bg-red-100 text-red-800'
                        ];
                    @endphp
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$serviceRequest->criticality_level] }}">
                        {{ $serviceRequest->criticality_level }}
                    </span>
                </div>

                <div>
                    <label class="font-medium text-gray-700">Solicitante:</label>
                    <p class="text-gray-600">{{ $serviceRequest->requester->name }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700">Asignado a:</label>
                    <p class="text-gray-600">{{ $serviceRequest->assignee ? $serviceRequest->assignee->name : 'Sin asignar' }}</p>
                </div>
            </div>
        </div>

        <!-- Detalles del Servicio -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Detalles del Servicio</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="font-medium text-gray-700">Familia de Servicio:</label>
                    <p class="text-gray-600">{{ $serviceRequest->subService->service->family->name }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700">Servicio:</label>
                    <p class="text-gray-600">{{ $serviceRequest->subService->service->name }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700">Sub-Servicio:</label>
                    <p class="text-gray-600">{{ $serviceRequest->subService->name }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700">SLA:</label>
                    <p class="text-gray-600">{{ $serviceRequest->sla->name }}</p>
                </div>
            </div>
        </div>

        <!-- Descripción -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Descripción</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->description }}</p>
        </div>

        <!-- Tiempos del SLA -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Tiempos del SLA</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 border rounded-lg {{ $serviceRequest->accepted_at ? 'bg-green-50 border-green-200' : ($serviceRequest->acceptance_deadline->isPast() ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200') }}">
                    <div class="text-sm font-medium text-gray-600">Aceptación</div>
                    <div class="text-lg font-semibold">{{ $serviceRequest->sla->acceptance_time_minutes }} min</div>
                    <div class="text-xs text-gray-500">
                        @if($serviceRequest->accepted_at)
                            Aceptado: {{ $serviceRequest->accepted_at->format('d/m/Y H:i') }}
                        @else
                            Límite: {{ $serviceRequest->acceptance_deadline->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="text-center p-4 border rounded-lg {{ $serviceRequest->responded_at ? 'bg-green-50 border-green-200' : ($serviceRequest->response_deadline->isPast() ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200') }}">
                    <div class="text-sm font-medium text-gray-600">Respuesta</div>
                    <div class="text-lg font-semibold">{{ $serviceRequest->sla->response_time_minutes }} min</div>
                    <div class="text-xs text-gray-500">
                        @if($serviceRequest->responded_at)
                            Respondido: {{ $serviceRequest->responded_at->format('d/m/Y H:i') }}
                        @else
                            Límite: {{ $serviceRequest->response_deadline->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="text-center p-4 border rounded-lg {{ $serviceRequest->resolved_at ? 'bg-green-50 border-green-200' : ($serviceRequest->resolution_deadline->isPast() ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200') }}">
                    <div class="text-sm font-medium text-gray-600">Resolución</div>
                    <div class="text-lg font-semibold">{{ $serviceRequest->sla->resolution_time_minutes }} min</div>
                    <div class="text-xs text-gray-500">
                        @if($serviceRequest->resolved_at)
                            Resuelto: {{ $serviceRequest->resolved_at->format('d/m/Y H:i') }}
                        @else
                            Límite: {{ $serviceRequest->resolution_deadline->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE EVIDENCIAS - NUEVA -->
        <div class="p-6 border-b">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-camera mr-2"></i>Evidencias de Ejecución
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                        {{ $serviceRequest->evidences_count }}
                    </span>
                </h3>

                @if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']))
                <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i>Agregar Evidencia
                </a>
                @endif
            </div>

            @if($serviceRequest->evidences_count > 0)
                <div class="bg-gray-50 rounded-lg p-4">
                    <!-- Resumen de Evidencias -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-3 bg-white rounded-lg border">
                            <div class="text-2xl font-bold text-blue-600">{{ $serviceRequest->stepByStepEvidences->count() }}</div>
                            <div class="text-sm text-gray-600">Evidencias Paso a Paso</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg border">
                            <div class="text-2xl font-bold text-green-600">{{ $serviceRequest->fileEvidences->count() }}</div>
                            <div class="text-sm text-gray-600">Archivos Adjuntos</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg border">
                            <div class="text-2xl font-bold text-purple-600">{{ $serviceRequest->commentEvidences->count() }}</div>
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
                                        <span class="text-gray-500 ml-2">({{ $evidence->file_size ? number_format($evidence->file_size / 1024 / 1024, 2) . ' MB' : '0 B' }})</span>
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
                    @if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO']))
                    <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Agregar Primera Evidencia
                    </a>
                    @endif
                </div>
            @endif

            <!-- Validación para Resolución -->
            @if($serviceRequest->status == 'EN_PROCESO')
            <div class="mt-4 p-4 rounded-lg
                @if($serviceRequest->stepByStepEvidences->count() > 0)
                    bg-green-50 border border-green-200
                @else
                    bg-yellow-50 border border-yellow-200
                @endif">
                <div class="flex items-center">
                    @if($serviceRequest->stepByStepEvidences->count() > 0)
                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-green-800">Listo para Resolver</p>
                        <p class="text-green-700 text-sm">
                            La solicitud tiene {{ $serviceRequest->stepByStepEvidences->count() }} evidencias paso a paso.
                            Puede proceder con la resolución.
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
                            Para resolver la solicitud debe agregar al menos una evidencia paso a paso.
                        </p>
                    </div>
                    <a href="{{ route('service-requests.evidences.create', $serviceRequest) }}"
                       class="ml-auto bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-camera mr-2"></i>Agregar Evidencia
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>

        @if($serviceRequest->isPaused() || $serviceRequest->total_paused_minutes > 0)
        <!-- Información de Pausa -->
        <div class="p-6 border-b bg-orange-50">
            <h3 class="text-lg font-semibold mb-4 text-orange-800">
                <i class="fas fa-pause-circle mr-2"></i>Información de Pausa
            </h3>

            @if($serviceRequest->isPaused())
            <div class="bg-orange-100 border border-orange-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-orange-800">SOLICITUD PAUSADA</p>
                        <p class="text-orange-700">Pausada desde: {{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($serviceRequest->pause_reason)
                <div>
                    <label class="font-medium text-gray-700">Motivo de pausa:</label>
                    <p class="text-gray-700 mt-1">{{ $serviceRequest->pause_reason }}</p>
                </div>
                @endif

                <div>
                    <label class="font-medium text-gray-700">Tiempo total pausado:</label>
                    <p class="text-gray-700 mt-1 font-semibold">
                        {{ $serviceRequest->getTotalPausedTimeFormatted() }}
                    </p>
                </div>

                @if($serviceRequest->paused_at)
                <div>
                    <label class="font-medium text-gray-700">Inicio de pausa:</label>
                    <p class="text-gray-700 mt-1">{{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
                </div>
                @endif

                @if($serviceRequest->resumed_at)
                <div>
                    <label class="font-medium text-gray-700">Última reanudación:</label>
                    <p class="text-gray-700 mt-1">{{ $serviceRequest->resumed_at->format('d/m/Y H:i') }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($serviceRequest->resolution_notes)
        <!-- Notas de Resolución -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Notas de Resolución</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->resolution_notes }}</p>
            @if($serviceRequest->actual_resolution_time)
            <p class="text-sm text-gray-500 mt-2">
                <strong>Tiempo real de resolución:</strong> {{ $serviceRequest->actual_resolution_time }} minutos
            </p>
            @endif
        </div>
        @endif

        @if($serviceRequest->satisfaction_score)
        <!-- Calificación de Satisfacción -->
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold mb-4">Calificación de Satisfacción</h3>
            <div class="flex items-center">
                <div class="text-2xl font-bold text-{{ $serviceRequest->satisfaction_score >= 4 ? 'green' : ($serviceRequest->satisfaction_score >= 3 ? 'yellow' : 'red') }}-600 mr-4">
                    {{ $serviceRequest->satisfaction_score }}/5
                </div>
                <div class="flex">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= $serviceRequest->satisfaction_score ? 'text-yellow-400' : 'text-gray-300' }} mr-1"></i>
                    @endfor
                </div>
            </div>
        </div>
        @endif

        <!-- Historial de Estados -->
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Historial</h3>
            <div class="space-y-3">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Creada</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                @if($serviceRequest->accepted_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Aceptada</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->accepted_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($serviceRequest->responded_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">En Proceso</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->responded_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($serviceRequest->paused_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Pausada</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->paused_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($serviceRequest->resumed_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Reanudada</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->resumed_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($serviceRequest->resolved_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Resuelta</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->resolved_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($serviceRequest->closed_at)
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Cerrada</p>
                        <p class="text-xs text-gray-500">{{ $serviceRequest->closed_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Los modales existentes se mantienen igual -->
    <!-- Modal de Pausa -->
    <div id="pauseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <!-- ... contenido del modal de pausa ... -->
    </div>

    <!-- Modal de Cierre -->
    <div id="closeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <!-- ... contenido del modal de cierre ... -->
    </div>

    <!-- Modal de Cancelación -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <!-- ... contenido del modal de cancelación ... -->
    </div>
@endsection

@section('scripts')
<script>
// Scripts existentes se mantienen
let currentRating = 0;

function openPauseModal() {
    document.getElementById('pauseModal').classList.remove('hidden');
}

function closePauseModal() {
    document.getElementById('pauseModal').classList.add('hidden');
}

function openCloseModal() {
    document.getElementById('closeModal').classList.remove('hidden');
    resetRating();
}

function closeCloseModal() {
    document.getElementById('closeModal').classList.add('hidden');
}

function openCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}

function setRating(rating) {
    currentRating = rating;
    document.getElementById('satisfaction_score').value = rating;

    const stars = document.querySelectorAll('#starRating button');
    stars.forEach((star, index) => {
        const starIcon = star.querySelector('i');
        if (index < rating) {
            starIcon.classList.remove('text-gray-300');
            starIcon.classList.add('text-yellow-400');
        } else {
            starIcon.classList.remove('text-yellow-400');
            starIcon.classList.add('text-gray-300');
        }
    });

    const ratingText = document.getElementById('ratingText');
    const ratingMessages = {
        1: 'Muy insatisfecho',
        2: 'Insatisfecho',
        3: 'Neutral',
        4: 'Satisfecho',
        5: 'Muy satisfecho'
    };
    ratingText.textContent = ratingMessages[rating] || 'Seleccione una calificación';
}

function resetRating() {
    currentRating = 0;
    document.getElementById('satisfaction_score').value = '';

    const stars = document.querySelectorAll('#starRating button');
    stars.forEach(star => {
        const starIcon = star.querySelector('i');
        starIcon.classList.remove('text-yellow-400');
        starIcon.classList.add('text-gray-300');
    });

    document.getElementById('ratingText').textContent = 'Seleccione una calificación';
}

// Cerrar modales al hacer click fuera
document.getElementById('pauseModal').addEventListener('click', function(e) {
    if (e.target === this) closePauseModal();
});

document.getElementById('closeModal').addEventListener('click', function(e) {
    if (e.target === this) closeCloseModal();
});

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>
@endsection
