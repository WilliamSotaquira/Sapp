<!-- resources/views/components/service-requests/show/header/main-header.blade.php -->
@props(['serviceRequest'])

<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-2xl overflow-hidden mb-8">
    <div class="px-8 py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Información Principal -->
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-ticket-alt text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Solicitud #{{ $serviceRequest->ticket_number }}</h1>
                    <p class="text-blue-100 opacity-90 mt-1">{{ $serviceRequest->title }}</p>
                </div>
            </div>

            <!-- Estado y Acciones -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center space-y-3 lg:space-y-0 lg:space-x-3">
                <!-- Badges -->
                <div class="flex items-center space-x-3">
                    <x-service-requests.badge type="status" :value="$serviceRequest->status" />
                    <x-service-requests.badge type="criticality" :value="$serviceRequest->criticality_level" />
                </div>

                <!-- Acciones del Flujo -->
                <x-service-requests.show.header.workflow-actions :serviceRequest="$serviceRequest" />

                <!-- Indicador de Estado -->
                <x-service-requests.show.header.status-indicator :serviceRequest="$serviceRequest" />
            </div>
        </div>
    </div>
</div>
