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
                'action' => 'create-service',
                'route' => 'service-requests.create',
                'color' => 'green',
                'icon' => 'plus-circle',
                'method' => 'GET',
                'label' => 'Crear Nuevo Servicio',
                'condition' => true,
            ],
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
                'action' => 'create-service',
                'route' => 'service-requests.create',
                'color' => 'green',
                'icon' => 'plus-circle',
                'method' => 'GET',
                'label' => 'Crear Nuevo Servicio',
                'condition' => true,
            ],
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
    
    // Contar botones activos para distribuir dinámicamente
    $activeActions = collect($actions)->filter(fn($action) => $action['condition'])->count();
    
    // Determinar clases de grid según cantidad de botones
    $gridClasses = match(true) {
        $activeActions === 1 => 'grid-cols-1',
        $activeActions === 2 => 'grid-cols-1 sm:grid-cols-2',
        $activeActions === 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        $activeActions >= 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
    };
@endphp

@if (count($actions) > 0 && !$disabled)
    <div class="{{ $compact ? 'flex flex-col gap-2' : 'grid ' . $gridClasses . ' gap-4' }}">
        @foreach ($actions as $actionItem)
            @if ($actionItem['condition'])
                <div class="flex">
                    {{-- BOTONES QUE ABREN MODALES --}}
                    @if ($actionItem['method'] === 'MODAL')
                        <button type="button"
                            onclick="document.getElementById('{{ $actionItem['modal_id'] }}').classList.remove('hidden')"
                            class="flex items-center justify-center w-full px-4 py-3 bg-{{ $actionItem['color'] }}-600 border-2 border-{{ $actionItem['color'] }}-700 rounded-full font-semibold text-white text-sm hover:bg-{{ $actionItem['color'] }}-700 hover:border-{{ $actionItem['color'] }}-800 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 group min-h-[3rem]"
                            aria-label="{{ $actionItem['label'] }}">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} transition-transform group-hover:scale-110" aria-hidden="true"></i>
                            @if ($showLabels)
                                <span class="line-clamp-2 text-center leading-tight">{{ $actionItem['label'] }}</span>
                            @endif
                        </button>

                        {{-- BOTONES CON GET (LINKS) --}}
                    @elseif($actionItem['method'] === 'GET')
                        <a href="{{ route($actionItem['route'], $serviceRequest) }}"
                            class="flex items-center justify-center w-full px-4 py-3 bg-{{ $actionItem['color'] }}-600 border-2 border-{{ $actionItem['color'] }}-700 rounded-full font-semibold text-white text-sm hover:bg-{{ $actionItem['color'] }}-700 hover:border-{{ $actionItem['color'] }}-800 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 no-underline group min-h-[3rem]"
                            aria-label="{{ $actionItem['label'] }}">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} transition-transform group-hover:scale-110" aria-hidden="true"></i>
                            @if ($showLabels)
                                <span class="line-clamp-2 text-center leading-tight">{{ $actionItem['label'] }}</span>
                            @endif
                        </a>

                        {{-- BOTONES CON FORMULARIOS (POST, PATCH) --}}
                    @else
                        <form action="{{ route($actionItem['route'], $serviceRequest) }}" method="POST"
                            class="w-full">
                            @csrf
                            @if ($actionItem['method'] === 'PATCH')
                                @method('PATCH')
                            @endif

                            <button type="submit"
                                class="flex items-center justify-center w-full px-4 py-3 bg-{{ $actionItem['color'] }}-600 border-2 border-{{ $actionItem['color'] }}-700 rounded-full font-semibold text-white text-sm hover:bg-{{ $actionItem['color'] }}-700 hover:border-{{ $actionItem['color'] }}-800 active:bg-{{ $actionItem['color'] }}-800 focus:outline-none focus:ring-2 focus:ring-{{ $actionItem['color'] }}-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 group min-h-[3rem]"
                                onclick="return confirm('¿Estás seguro de que deseas {{ strtolower($actionItem['label']) }}?')"
                                aria-label="{{ $actionItem['label'] }}">
                                <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} transition-transform group-hover:scale-110" aria-hidden="true"></i>
                                @if ($showLabels)
                                    <span class="line-clamp-2 text-center leading-tight">{{ $actionItem['label'] }}</span>
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="flex">
                    <button type="button" disabled
                        class="flex items-center justify-center w-full px-4 py-3 bg-gray-400 border-2 border-gray-500 rounded-full font-semibold text-white text-sm cursor-not-allowed opacity-60 min-h-[3rem]"
                        title="{{ $actionItem['action'] === 'resolve' ? 'Debe agregar al menos una evidencia antes de resolver' : 'Acción no disponible en este momento' }}"
                        aria-label="{{ $actionItem['label'] }} (deshabilitado)">
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
