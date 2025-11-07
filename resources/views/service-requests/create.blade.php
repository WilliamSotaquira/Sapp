@extends('layouts.app')

@section('title', 'Crear Solicitud de Servicio')

@section('breadcrumb')
    <x-service-requests.layout.breadcrumb />
@endsection

@section('content')
<div class="bg-white shadow-md rounded-lg p-6">
    <form action="{{ route('service-requests.store') }}" method="POST" id="serviceRequestForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Filtros de Servicio -->
            <x-service-requests.form.fields.service-family-filter :subServices="$subServices" />
            <x-service-requests.form.fields.sub-service-select :subServices="$subServices" />

            <!-- Campos de SLA -->
            <x-service-requests.form.sla.sla-fields />

            <!-- Campos básicos -->
            <x-service-requests.form.fields.basic-fields />

            <!-- Rutas Web -->
            @include('service-requests.partials.web-routes-create')

            <!-- Asignación y Criticidad -->
            <x-service-requests.form.fields.assignment-fields
                :criticalityLevels="$criticalityLevels"
                :users="$users"
            />
        </div>

        <!-- Botones -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('service-requests.index') }}"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Cancelar
            </a>
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Crear Solicitud
            </button>
        </div>
    </form>
</div>

<!-- Modal para crear nuevo SLA -->
<x-service-requests.modals.sla-create :criticalityLevels="$criticalityLevels" />
@endsection

@section('scripts')
    <x-service-requests.layout.scripts
        :webRoutes="true"
        :slaManagement="true"
        :formValidation="true"
    />
@endsection
