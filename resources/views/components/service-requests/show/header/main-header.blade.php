@props(['serviceRequest'])

{{-- SOLUCIÓN DEFINITIVA - Obtener técnicos directamente --}}
@php
    use App\Models\User;
    $technicians = User::orderBy('name')->get();
@endphp

<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-2xl overflow-hidden mb-8">
    <div class="px-8 py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-ticket-alt text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Solicitud #{{ $serviceRequest->ticket_number }}</h1>
                    <p class="text-blue-100 opacity-90 mt-1">{{ $serviceRequest->title }}</p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row items-start lg:items-center space-y-3 lg:space-y-0 lg:space-x-3">
                <!-- Badges de estado y criticidad -->
                <div class="flex items-center space-x-3">
                    <x-service-requests.show.header.status-badge :status="$serviceRequest->status" />
                    <x-service-requests.show.header.criticality-indicator :criticality="$serviceRequest->criticality_level" />
                </div>

                <!-- Componente unificado de acciones -->
                <x-service-requests.show.header.workflow-actions
                    :serviceRequest="$serviceRequest"
                    :technicians="$technicians"
                    :showLabels="true"
                    :compact="false"
                />

                <!-- SOLUCIÓN: Usar solo el componente status-indicator -->
                <x-service-requests.show.header.status-indicator :serviceRequest="$serviceRequest" />
            </div>
        </div>
    </div>
</div>
