<!-- resources/views/components/service-requests/show/header/workflow-actions.blade.php -->
@props(['serviceRequest'])

<div class="flex flex-wrap gap-2">
    @switch($serviceRequest->status)
        @case('PENDIENTE')
            <!-- ✅ ACEPTAR: Solo cambia estado, no requiere assigned_to -->
            <x-service-requests.show.header.action-button
                :route="route('service-requests.accept', $serviceRequest)"
                color="emerald"
                icon="handshake"
                method="PATCH"
                confirm="¿Estás seguro de que deseas aceptar esta solicitud?"
            >
                Aceptar Solicitud
            </x-service-requests.show.header.action-button>
            @break

        @case('ACEPTADA')
            <!-- ✅ INICIAR PROCESO: Requiere assigned_to -->
            @if($serviceRequest->assigned_to)
                <x-service-requests.show.header.action-button
                    :route="route('service-requests.start', $serviceRequest)"
                    color="cyan"
                    icon="play"
                    method="PATCH"
                    confirm="¿Estás listo para comenzar a trabajar en esta solicitud?"
                >
                    Iniciar Proceso
                </x-service-requests.show.header.action-button>
            @else
                <!-- Botón deshabilitado cuando no hay técnico -->
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed"
                    disabled
                    title="Se requiere técnico asignado para iniciar el proceso"
                >
                    <i class="fas fa-play mr-2 text-gray-400"></i>
                    Iniciar Proceso
                </button>
                <div class="w-full text-xs text-red-600 mt-1 flex items-center">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Asigna un técnico antes de iniciar el proceso
                </div>
            @endif
            @break

        @case('EN_PROCESO')
            <x-service-requests.show.header.resolve-form :serviceRequest="$serviceRequest" />
            @break
    @endswitch
</div>
