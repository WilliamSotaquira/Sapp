@props(['serviceRequest', 'showLabels' => true, 'compact' => false, 'disabled' => false])

@php
    // Configuración centralizada de acciones por estado - ACTUALIZADA con modal para pausa
    $workflowConfig = [
        'PENDIENTE' => [
            [
                'action' => 'accept',
                'route' => 'service-requests.accept',
                'color' => 'emerald',
                'icon' => 'handshake',
                'method' => 'PATCH',
                'label' => 'Aceptar Solicitud',
                'confirm' => '¿Estás seguro de que deseas aceptar esta solicitud? Serás asignado como responsable.',
                'condition' => true,
            ],
            [
                'action' => 'reject',
                'route' => 'service-requests.reject',
                'color' => 'red',
                'icon' => 'times-circle',
                'method' => 'POST',
                'label' => 'Rechazar Solicitud',
                'confirm' => '¿Estás seguro de que deseas rechazar esta solicitud?',
                'condition' => true,
            ],
        ],
        'ACEPTADA' => [
            [
                'action' => 'start',
                'route' => 'service-requests.start',
                'color' => 'cyan',
                'icon' => 'play',
                'method' => 'PATCH',
                'label' => 'Iniciar Proceso',
                'confirm' => '¿Estás listo para comenzar a trabajar en esta solicitud?',
                'condition' => $serviceRequest->assigned_to,
                'disabledMessage' => 'Asigna un técnico antes de iniciar el proceso',
                'disabledTooltip' => 'Se requiere técnico asignado para iniciar el proceso',
            ],
            [
                'action' => 'reassign',
                'route' => 'service-requests.reassign',
                'color' => 'blue',
                'icon' => 'user-cog',
                'method' => 'GET',
                'label' => 'Reasignar Técnico',
                'condition' => true,
            ],
        ],
        'EN_PROCESO' => [
            [
                'action' => 'resolve',
                'component' => 'service-requests.show.header.resolve-form',
                'condition' => true,
            ],
            [
                'action' => 'pause',
                'component' => 'service-requests.show.header.pause-modal',
                'condition' => true,
            ],
        ],
        'PAUSADA' => [
            [
                'action' => 'resume',
                'route' => 'service-requests.resume',
                'color' => 'cyan',
                'icon' => 'play',
                'method' => 'POST',
                'label' => 'Reanudar Trabajo',
                'confirm' => '¿Deseas reanudar el trabajo en esta solicitud?',
                'condition' => true,
            ],
        ],
        'RESUELTA' => [
            [
                'action' => 'close',
                'route' => 'service-requests.close',
                'color' => 'green',
                'icon' => 'check-double',
                'method' => 'POST',
                'label' => 'Cerrar Solicitud',
                'confirm' => '¿Confirmas el cierre definitivo de esta solicitud?',
                'condition' => true,
            ],
            [
                'action' => 'reopen',
                'route' => 'service-requests.reopen',
                'color' => 'orange',
                'icon' => 'undo',
                'method' => 'POST',
                'label' => 'Reabrir Solicitud',
                'confirm' => '¿Deseas reabrir esta solicitud para más trabajo?',
                'condition' => true,
            ],
        ],
    ];

    $currentStatus = $serviceRequest->status;
    $actions = $workflowConfig[$currentStatus] ?? [];

    // Clases para modo compacto
    $containerClass = $compact ? 'flex flex-col gap-1' : 'flex flex-wrap gap-2';
    $buttonClass = $compact ? 'w-full justify-center' : '';

@endphp

@if (count($actions) > 0 && !$disabled)
    <div class="{{ $containerClass }}" role="group" aria-label="Acciones de flujo de trabajo">
        @foreach ($actions as $actionItem)
            @if ($actionItem['condition'] ?? true)
                @if (isset($actionItem['component']))
                    {{-- Componente personalizado --}}
                    <x-dynamic-component :component="$actionItem['component']" :serviceRequest="$serviceRequest" />
                @else
                    {{-- Botón de acción estándar --}}
                    <x-service-requests.show.header.action-button
                        :route="route($actionItem['route'], $serviceRequest)"
                        :color="$actionItem['color'] ?? 'blue'"
                        :icon="$actionItem['icon'] ?? 'cog'"
                        :method="$actionItem['method'] ?? 'POST'"
                        :confirm="$actionItem['confirm'] ?? null"
                        :class="$buttonClass">
                        @if ($showLabels)
                            {{ $actionItem['label'] }}
                        @else
                            <span class="sr-only">{{ $actionItem['label'] }}</span>
                        @endif
                    </x-service-requests.show.header.action-button>
                @endif
            @else
                {{-- Botón deshabilitado --}}
                <button type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed {{ $compact ? 'w-full justify-center' : '' }}"
                    disabled title="{{ $actionItem['disabledTooltip'] ?? 'Acción no disponible' }}"
                    aria-describedby="disabled-reason-{{ $actionItem['action'] }}">
                    <i class="fas fa-{{ $actionItem['icon'] ?? 'ban' }} mr-2 text-gray-400" aria-hidden="true"></i>
                    @if ($showLabels)
                        {{ $actionItem['label'] }}
                    @else
                        <span class="sr-only">{{ $actionItem['label'] }}</span>
                    @endif
                </button>

                @if (isset($actionItem['disabledMessage']) && !$compact)
                    <div id="disabled-reason-{{ $actionItem['action'] }}"
                        class="w-full text-xs text-red-600 mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1" aria-hidden="true"></i>
                        {{ $actionItem['disabledMessage'] }}
                    </div>
                @endif
            @endif
        @endforeach
    </div>
@elseif($disabled)
    <div class="text-sm text-gray-500 italic py-2">
        <i class="fas fa-lock mr-2" aria-hidden="true"></i>
        Las acciones no están disponibles en este momento
    </div>
@else
    <div class="text-sm text-gray-500 italic py-2">
        <i class="fas fa-check-circle mr-2" aria-hidden="true"></i>
        No hay acciones disponibles para este estado
    </div>
@endif
