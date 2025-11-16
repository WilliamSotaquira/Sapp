@props(['serviceRequest'])

{{-- SOLUCIÓN DEFINITIVA - Obtener técnicos directamente --}}
@php
    use App\Models\User;
    $technicians = User::orderBy('name')->get();
@endphp

<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden mb-4 sm:mb-6 md:mb-8 w-full">
    <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 flex-1">
                <div class="bg-white/20 p-2 sm:p-3 rounded-xl sm:rounded-2xl backdrop-blur-sm flex items-center flex-col self-start sm:self-auto">
                    <i class="fas fa-ticket-alt text-3xl sm:text-4xl md:text-5xl"></i>
                    <span class="mt-1.5 sm:mt-2 inline-flex">
                        <x-service-requests.show.header.criticality-indicator :criticality="$serviceRequest->criticality_level" />
                    </span>
                </div>
                <div class="flex flex-col max-w-full sm:max-w-md lg:max-w-lg">
                    <h1 class="text-lg sm:text-xl md:text-2xl font-bold">Solicitud #{{ $serviceRequest->ticket_number }}</h1>
                    <span class="inline-flex">
                        <p class="text-blue-100 opacity-90 mt-0.5 sm:mt-1 text-xs sm:text-sm line-clamp-2">{{ $serviceRequest->title }}</p>
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto lg:max-w-md">
                <!-- Componente unificado de acciones -->
                <x-service-requests.show.header.workflow-actions :serviceRequest="$serviceRequest" :technicians="$technicians" :showLabels="true"
                    :compact="false" />

                <!-- SOLUCIÓN: Usar solo el componente status-indicator -->
                <x-service-requests.show.header.status-indicator :serviceRequest="$serviceRequest" />
            </div>
        </div>
    </div>
</div>
