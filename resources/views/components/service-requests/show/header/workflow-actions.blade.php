@props([
    'serviceRequest',
    'showLabels' => true,
    'compact' => false,
    'disabled' => false,
    'technicians' => collect(),
])

@php
    $workflowConfig = [
        'PENDIENTE' => [
            [
                'action' => $serviceRequest->assigned_to ? 'accept' : 'assign-technician',
                'route' => $serviceRequest->assigned_to ? 'accept-modal' : 'assign-technician-modal', // Cambiar a modal
                'color' => $serviceRequest->assigned_to ? 'emerald' : 'blue',
                'icon' => $serviceRequest->assigned_to ? 'handshake' : 'user-plus',
                'method' => $serviceRequest->assigned_to ? 'MODAL' : 'MODAL', // Ambos usan modal
                'label' => $serviceRequest->assigned_to ? 'Aceptar Solicitud' : 'Asignar Técnico Primero',
                'condition' => true,
                'modal_id' => $serviceRequest->assigned_to
                    ? 'accept-modal-' . $serviceRequest->id
                    : 'assign-technician-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'reject',
                'route' => 'reject-modal', // Cambiar a modal
                'color' => 'red',
                'icon' => 'times-circle',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Rechazar Solicitud',
                'condition' => true,
                'modal_id' => 'reject-modal-' . $serviceRequest->id, // Agregar modal_id
            ],
        ],
        'ACEPTADA' => [
            [
                'action' => 'start',
                'route' => 'start-modal', // Cambiar a modal
                'color' => 'cyan',
                'icon' => 'play',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Iniciar Servicio',
                'condition' => !empty($serviceRequest->assigned_to) && $serviceRequest->assigned_to > 0,
                'modal_id' => 'start-modal-' . $serviceRequest->id, // Agregar modal_id
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
                'route' => 'resolve-modal',
                'color' => 'green',
                'icon' => 'check-circle',
                'method' => 'MODAL',
                'label' => 'Resolver Solicitud',
                'condition' => $serviceRequest->evidences->where('evidence_type', 'ARCHIVO')->count() > 0,
                'modal_id' => 'resolve-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'pause',
                'route' => 'pause-modal',
                'color' => 'yellow',
                'icon' => 'pause',
                'method' => 'MODAL',
                'label' => 'Pausar Trabajo',
                'condition' => true,
                'modal_id' => 'pause-modal-' . $serviceRequest->id,
            ],
        ],
        'PAUSADA' => [
            [
                'action' => 'resume',
                'route' => 'resume-modal', // Cambiar a modal
                'color' => 'cyan',
                'icon' => 'play',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Reanudar Trabajo',
                'condition' => true,
                'modal_id' => 'resume-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'close-vencimiento',
                'route' => 'vencimiento-modal',
                'color' => 'red',
                'icon' => 'clock',
                'method' => 'MODAL',
                'label' => 'Cerrar por Vencimiento',
                'condition' => true,
                'modal_id' => 'vencimiento-modal-' . $serviceRequest->id,
            ],
        ],
        'RESUELTA' => [
            [
                'action' => 'close',
                'route' => 'close-modal', // Cambiar a modal
                'color' => 'purple',
                'icon' => 'lock',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Cerrar Solicitud',
                'condition' => true,
                'modal_id' => 'close-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'reopen',
                'route' => 'reopen-modal', // Cambiar a modal
                'color' => 'orange',
                'icon' => 'undo',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Reabrir Solicitud',
                'condition' => true,
                'modal_id' => 'reopen-modal-' . $serviceRequest->id,
            ],
        ],
        'CERRADA' => [
            [
                'action' => 'download-pdf',
                'route' => 'service-requests.download-report',
                'color' => 'blue',
                'icon' => 'download',
                'method' => 'GET',
                'label' => 'Descargar PDF',
                'condition' => true,
            ],
        ],
    ];

    $currentStatus = $serviceRequest->status;
    $actions = $workflowConfig[$currentStatus] ?? [];
@endphp

@if (count($actions) > 0 && !$disabled)
    <div class="{{ $compact ? 'flex flex-col gap-2' : 'grid gap-3 md:grid-cols-2' }}">
        @foreach ($actions as $actionItem)
            @if ($actionItem['condition'])
                <div class="{{ $compact ? '' : 'rounded-full border-2 border border-slate-300 shadow-sm' }}">
                    {{-- BOTONES QUE ABREN MODALES --}}
                    @if ($actionItem['method'] === 'MODAL')
                        <button type="button"
                            onclick="document.getElementById('{{ $actionItem['modal_id'] }}').classList.remove('hidden')"
                            class="inline-flex items-center justify-center w-full px-6 py-2 bg-{{ $actionItem['color'] }}-600 border border-transparent text-sm rounded-full border-2 font-semibold text-white hover:bg-{{ $actionItem['color'] }}-700 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition ease-in-out duration-150 {{ $compact ? 'text-sm' : '' }} leading-tight">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2' : '' }}"></i>
                            @if ($showLabels)
                                {{ $actionItem['label'] }}
                            @endif
                        </button>

                        {{-- BOTONES CON GET (LINKS) --}}
                    @elseif($actionItem['method'] === 'GET')
                        <a href="{{ route($actionItem['route'], $serviceRequest) }}"
                            class="inline-flex items-center justify-center w-full px-6 py-2 bg-{{ $actionItem['color'] }}-600 border border-transparent text-sm rounded-full border-2 font-semibold text-white hover:bg-{{ $actionItem['color'] }}-700 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition ease-in-out duration-150 no-underline {{ $compact ? 'text-sm' : '' }} leading-tight">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2' : '' }}"></i>
                            @if ($showLabels)
                                {{ $actionItem['label'] }}
                            @endif
                        </a>

                        {{-- BOTONES CON FORMULARIOS (POST, PATCH) --}}
                    @else
                        <form action="{{ route($actionItem['route'], $serviceRequest) }}" method="POST"
                            class="inline w-full">
                            @csrf
                            @if ($actionItem['method'] === 'PATCH')
                                @method('PATCH')
                            @endif

                            <button type="submit"
                                class="inline-flex items-center justify-center w-full px-6 py-2 bg-{{ $actionItem['color'] }}-600 border border-transparent text-sm rounded-lg font-semibold text-white hover:bg-{{ $actionItem['color'] }}-700 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition ease-in-out duration-150 {{ $compact ? 'text-sm' : '' }} leading-tight"
                                onclick="return confirm('¿Estás seguro de que deseas {{ strtolower($actionItem['label']) }}?')">
                                <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2' : '' }}"></i>
                                @if ($showLabels)
                                    {{ $actionItem['label'] }}
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="{{ $compact ? '' : 'bg-gray-50 rounded-2xl border border-gray-200' }}">
                    <button type="button" disabled
                        class="inline-flex items-center justify-center w-full px-6 py-2 bg-gray-400 border border-transparent text-sm font-semibold text-white cursor-not-allowed {{ $compact ? 'text-sm' : '' }} leading-tight rounded-2xl"
                        title="{{ $actionItem['action'] === 'resolve' ? 'Debe agregar al menos una evidencia antes de resolver' : 'Acción no disponible en este momento' }}">
                        <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2' : '' }}"></i>
                        @if ($showLabels)
                            {{ $actionItem['label'] }}
                        @endif
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@elseif($disabled)
    <div class="bg-gray-100 border border-gray-300 rounded-2xl text-center">
        <p class="text-gray-600">
            <i class="fas fa-lock mr-2"></i>
            Las acciones no están disponibles en este momento
        </p>
    </div>
@else
    <div class="bg-blue-50 border border-blue-300 rounded-2xl text-center">
        <p class="text-blue-700">
            <i class="fas fa-check-circle mr-2"></i>
            No hay acciones disponibles para el estado: <strong>{{ $currentStatus }}</strong>
        </p>
    </div>
@endif

@if (!$disabled)
    @if ($currentStatus === 'PAUSADA')
        @include('components.service-requests.show.header.vencimiento-modal', [
            'serviceRequest' => $serviceRequest,
        ])
        @include('components.service-requests.show.header.resume-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
    @if ($currentStatus === 'PENDIENTE' && !$serviceRequest->assigned_to)
        @include('components.service-requests.show.header.assign-technician-modal', [
            'serviceRequest' => $serviceRequest,
            'technicians' => $technicians,
        ])
    @endif
    @if ($currentStatus === 'PENDIENTE' && $serviceRequest->assigned_to)
        @include('components.service-requests.show.header.accept-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
    @if ($currentStatus === 'PENDIENTE')
        @include('components.service-requests.show.header.reject-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
    {{-- ✅ AGREGAR START-MODAL --}}
    @if ($currentStatus === 'ACEPTADA')
        @include('components.service-requests.show.header.start-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
    @if ($currentStatus === 'EN_PROCESO')
        @include('components.service-requests.show.header.pause-modal', [
            'serviceRequest' => $serviceRequest,
        ])
        {{-- ✅ AGREGAR RESOLVE-MODAL --}}
        @include('components.service-requests.show.header.resolve-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
    @if ($currentStatus === 'RESUELTA')
        @include('components.service-requests.show.header.reopen-modal', [
            'serviceRequest' => $serviceRequest,
        ])
        {{-- ✅ AGREGAR CLOSE-MODAL --}}
        @include('components.service-requests.show.header.close-modal', [
            'serviceRequest' => $serviceRequest,
        ])
    @endif
@endif
