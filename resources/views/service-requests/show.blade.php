@extends('layouts.app')

@section('title', 'Solicitud ' . $serviceRequest->ticket_number)

@section('breadcrumb')
    @include('components.service-requests.breadcrumb')
@endsection

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden mb-6" role="main" aria-labelledby="request-title">

    <!-- Alertas -->
    @include('components.alerts')

    <!-- Header con botones -->
    @include('components.service-requests.header', ['request' => $serviceRequest])

    <!-- Información General -->
    @include('components.service-requests.general-info', ['request' => $serviceRequest])

    <!-- Detalles del Servicio -->
    @include('components.service-requests.service-details', ['request' => $serviceRequest])

    <!-- Descripción -->
    @include('components.service-requests.description', ['request' => $serviceRequest])

    <!-- Rutas Web -->
    @include('components.service-requests.web-routes', ['request' => $serviceRequest])

    <!-- Tiempos SLA -->
    @include('components.service-requests.sla-timers', ['request' => $serviceRequest])

    <!-- Evidencias -->
    @include('components.service-requests.evidences-section', ['request' => $serviceRequest])

    <!-- Información de Pausa (condicional) -->
    @php
        $viewService = app(\App\Services\ServiceRequestViewService::class);
    @endphp
    @if($viewService->shouldShowPauseInfo($serviceRequest))
        @include('components.service-requests.pause-info', ['request' => $serviceRequest])
    @endif

    <!-- Notas de Resolución (condicional) -->
    @if($serviceRequest->resolution_notes)
        @include('components.service-requests.resolution-notes', ['request' => $serviceRequest])
    @endif

    <!-- Calificación de Satisfacción (condicional) -->
    @if($serviceRequest->satisfaction_score)
        @include('components.service-requests.satisfaction-score', ['request' => $serviceRequest])
    @endif

    <!-- Historial -->
    @include('components.service-requests.history-timeline', ['request' => $serviceRequest])

</div>

<!-- Modales -->
@include('components.service-requests.modals.all', ['request' => $serviceRequest])
@endsection

@section('scripts')
    @include('components.service-requests.scripts')
@endsection
