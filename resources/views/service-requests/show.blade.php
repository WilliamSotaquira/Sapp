@extends('layouts.app')

@section('title', "Solicitud $serviceRequest->ticket_number")

@section('breadcrumb')
<x-service-requests.layout.breadcrumb :serviceRequest="$serviceRequest" />
@endsection

@section('content')
{{-- Header --}}
<x-service-requests.layout.header :serviceRequest="$serviceRequest" />

<div class="bg-white shadow-md rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-1 gap-6 pb-6">
        <!-- Información del Servicio -->
        <x-service-requests.display.service-details :serviceRequest="$serviceRequest" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Información de Asignación y Criticidad -->
        @if(View::exists('components.service-requests.display.assignment-info'))
        <x-service-requests.display.assignment-info :serviceRequest="$serviceRequest" />
        @else
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Asignación y Criticidad</h2>
            <div>
                <p class="text-sm text-gray-500">Asignado a</p>
                <p class="font-medium">{{ $serviceRequest->assignedTo->name ?? 'No asignado' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Nivel de criticidad</p>
                <p class="font-medium">{{ $serviceRequest->criticalityLevel->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fecha límite</p>
                <p class="font-medium {{ $serviceRequest->due_date && $serviceRequest->due_date->isPast() ? 'text-red-600' : '' }}">
                    {{ $serviceRequest->due_date ? $serviceRequest->due_date->format('d/m/Y H:i') : 'No definida' }}
                </p>
            </div>
        </div>
        @endif

        <!-- Información SLA -->
        @if(View::exists('components.service-requests.display.sla-info'))
        <x-service-requests.display.sla-info :serviceRequest="$serviceRequest" />
        @else
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Información SLA</h2>
            <div>
                <p class="text-sm text-gray-500">Tiempo transcurrido</p>
                <p class="font-medium">{{ $serviceRequest->created_at->diffForHumans() }}</p>
            </div>
            @if($serviceRequest->due_date)
            <div>
                <p class="text-sm text-gray-500">Tiempo restante</p>
                <p class="font-medium {{ $serviceRequest->due_date->isPast() ? 'text-red-600' : 'text-green-600' }}">
                    {{ now()->diffForHumans($serviceRequest->due_date, true) }}
                </p>
            </div>
            @endif
        </div>
        @endif

        <!-- Rutas Web -->
        @if(View::exists('components.service-requests.display.web-routes-info'))
        <x-service-requests.display.web-routes-info :serviceRequest="$serviceRequest" />
        @endif
    </div>

    <!-- Sección de Evidencias -->
    @if(View::exists('components.service-requests.display.evidences-section'))
    <x-service-requests.display.evidences-section :serviceRequest="$serviceRequest" />
    @else
    <div class="mt-6 pt-6 border-t border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evidencias</h2>
        <p class="text-gray-500">No hay evidencias disponibles.</p>
    </div>
    @endif

    <!-- Historial -->
    <x-service-requests.display.history-timeline :serviceRequest="$serviceRequest" />
</div>

<!-- Botones de acción -->
<div class="mt-6 flex justify-end space-x-3">
    <a href="{{ route('service-requests.index') }}"
        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition duration-200">
        <i class="fas fa-arrow-left mr-2"></i>Volver
    </a>

    @if($serviceRequest->status !== 'CERRADA')
    <a href="{{ route('service-requests.edit', $serviceRequest) }}"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-200">
        <i class="fas fa-edit mr-2"></i>Editar
    </a>
    @endif
</div>

<!-- INCLUIR TODOS LOS MODALES -->
<x-service-requests.modals.all :serviceRequest="$serviceRequest" />

@endsection

@section('scripts')
<x-service-requests.layout.scripts
    :serviceRequest="$serviceRequest"
    :webRoutes="true"
    :slaManagement="true"
    :formValidation="false" />
@endsection
