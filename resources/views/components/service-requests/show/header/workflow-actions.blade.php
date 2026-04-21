@props([
    'serviceRequest',
    'showLabels' => true,
    'compact' => false,
    'disabled' => false,
    'technicians' => collect(),
])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $canResolveByEvidence = ($serviceRequest->is_reportable === false)
        || $viewService->getResolvableEvidenceCount($serviceRequest) > 0;

    $workflowConfig = [
        'PENDIENTE' => [
            [
                'action' => 'create-service',
                'route' => 'service-requests.create',
                'icon' => 'plus-circle',
                'method' => 'GET',
                'label' => 'Crear Nuevo Servicio',
                'condition' => true,
                'appearance' => 'soft',
                'route_params' => [],
            ],
            [
                'action' => $serviceRequest->assigned_to ? 'accept' : 'assign-technician',
                'route' => $serviceRequest->assigned_to ? 'accept-modal' : 'assign-technician-modal', // Cambiar a modal
                'icon' => $serviceRequest->assigned_to ? 'handshake' : 'user-plus',
                'method' => $serviceRequest->assigned_to ? 'MODAL' : 'MODAL', // Ambos usan modal
                'label' => $serviceRequest->assigned_to ? 'Aceptar Solicitud' : 'Asignar Técnico',
                'condition' => true,
                'appearance' => 'primary',
                'modal_id' => $serviceRequest->assigned_to
                    ? 'accept-modal-' . $serviceRequest->id
                    : 'assign-technician-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'reject',
                'route' => 'reject-modal', // Cambiar a modal
                'icon' => 'times-circle',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Rechazar Solicitud',
                'condition' => true,
                'appearance' => 'danger-soft',
                'modal_id' => 'reject-modal-' . $serviceRequest->id, // Agregar modal_id
            ],
        ],
        'ACEPTADA' => [
            [
                'action' => 'start',
                'route' => 'start-modal', // Cambiar a modal
                'icon' => 'play',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Iniciar Servicio',
                'condition' => !empty($serviceRequest->assigned_to) && $serviceRequest->assigned_to > 0,
                'appearance' => 'primary',
                'modal_id' => 'start-modal-' . $serviceRequest->id, // Agregar modal_id
            ],
            [
                'action' => 'reassign',
                'route' => 'service-requests.reassign',
                'icon' => 'user-cog',
                'method' => 'GET',
                'label' => 'Reasignar Técnico',
                'condition' => true,
                'appearance' => 'soft',
                'route_params' => $serviceRequest,
            ],
        ],
        'EN_PROCESO' => [
            [
                'action' => 'resolve',
                'route' => 'resolve-modal',
                'icon' => 'check-circle',
                'method' => 'MODAL',
                'label' => 'Resolver Solicitud',
                'condition' => $canResolveByEvidence,
                'appearance' => 'primary',
                'modal_id' => 'resolve-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'pause',
                'route' => 'pause-modal',
                'icon' => 'pause',
                'method' => 'MODAL',
                'label' => 'Pausar Trabajo',
                'condition' => true,
                'appearance' => 'warning-soft',
                'modal_id' => 'pause-modal-' . $serviceRequest->id,
            ],
        ],
        'PAUSADA' => [
            [
                'action' => 'resume',
                'route' => 'resume-modal', // Cambiar a modal
                'icon' => 'play',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Reanudar Trabajo',
                'condition' => true,
                'appearance' => 'primary',
                'modal_id' => 'resume-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'close-vencimiento',
                'route' => 'vencimiento-modal',
                'icon' => 'clock',
                'method' => 'MODAL',
                'label' => 'Cerrar por Vencimiento',
                'condition' => true,
                'appearance' => 'danger-soft',
                'modal_id' => 'vencimiento-modal-' . $serviceRequest->id,
            ],
        ],
        'RESUELTA' => [
            [
                'action' => 'close',
                'route' => 'close-modal', // Cambiar a modal
                'icon' => 'lock',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Cerrar Solicitud',
                'condition' => true,
                'appearance' => 'primary',
                'modal_id' => 'close-modal-' . $serviceRequest->id,
            ],
            [
                'action' => 'reopen',
                'route' => 'reopen-modal', // Cambiar a modal
                'icon' => 'undo',
                'method' => 'MODAL', // Cambiar a MODAL
                'label' => 'Reabrir Solicitud',
                'condition' => true,
                'appearance' => 'soft',
                'modal_id' => 'reopen-modal-' . $serviceRequest->id,
            ],
        ],
        'CERRADA' => [
            [
                'action' => 'create-service',
                'route' => 'service-requests.create',
                'icon' => 'plus-circle',
                'method' => 'GET',
                'label' => 'Crear Nuevo Servicio',
                'condition' => true,
                'appearance' => 'soft',
                'route_params' => [],
            ],
            [
                'action' => 'download-pdf',
                'route' => 'service-requests.download-report',
                'icon' => 'download',
                'method' => 'GET',
                'label' => 'Descargar PDF',
                'condition' => true,
                'appearance' => 'soft',
                'route_params' => $serviceRequest,
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

    $resolveActionClasses = function (array $actionItem): string {
        $appearance = $actionItem['appearance'] ?? 'soft';

        $base = 'flex items-center justify-center w-full px-4 py-3 rounded-2xl font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-150 group min-h-[3rem] no-underline';

        return match ($appearance) {
            'primary' => $base . ' bg-emerald-600 border border-emerald-700 text-white shadow-sm hover:bg-emerald-700 hover:shadow-md focus:ring-emerald-500',
            'danger-soft' => $base . ' bg-red-50 border border-red-200 text-red-700 shadow-sm hover:bg-red-100 hover:border-red-300 focus:ring-red-400',
            'warning-soft' => $base . ' bg-amber-50 border border-amber-200 text-amber-800 shadow-sm hover:bg-amber-100 hover:border-amber-300 focus:ring-amber-400',
            default => $base . ' bg-white border border-slate-200 text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 hover:shadow-md focus:ring-slate-300',
        };
    };
@endphp

@if (count($actions) > 0 && !$disabled)
    <div class="{{ $compact ? 'flex flex-col gap-2' : 'grid ' . $gridClasses . ' gap-3' }}">
        @foreach ($actions as $actionItem)
            @if ($actionItem['condition'])
                <div class="flex">
                    {{-- BOTONES QUE ABREN MODALES --}}
                    @if ($actionItem['method'] === 'MODAL')
                        <button type="button"
                            data-service-request-id="{{ $serviceRequest->id }}"
                            data-workflow-action="{{ $actionItem['action'] }}"
                            data-modal-id="{{ $actionItem['modal_id'] ?? '' }}"
                            onclick="openModal('{{ $actionItem['modal_id'] }}', this)"
                            class="{{ $resolveActionClasses($actionItem) }}"
                            aria-label="{{ $actionItem['label'] }}">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} text-[13px] transition-transform group-hover:scale-105" aria-hidden="true"></i>
                            @if ($showLabels)
                                <span class="line-clamp-2 text-center leading-tight">{{ $actionItem['label'] }}</span>
                            @endif
                        </button>

                        {{-- BOTONES CON GET (LINKS) --}}
                    @elseif($actionItem['method'] === 'GET')
                        <a href="{{ route($actionItem['route'], $actionItem['route_params'] ?? $serviceRequest) }}"
                            class="{{ $resolveActionClasses($actionItem) }}"
                            aria-label="{{ $actionItem['label'] }}">
                            <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} text-[13px] transition-transform group-hover:scale-105" aria-hidden="true"></i>
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
                                class="{{ $resolveActionClasses($actionItem) }}"
                                onclick="return confirm('¿Estás seguro de que deseas {{ strtolower($actionItem['label']) }}?')"
                                aria-label="{{ $actionItem['label'] }}">
                                <i class="fas fa-{{ $actionItem['icon'] }} {{ $showLabels ? 'mr-2 flex-shrink-0' : '' }} text-[13px] transition-transform group-hover:scale-105" aria-hidden="true"></i>
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
                        class="flex items-center justify-center w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-2xl font-semibold text-slate-400 text-sm cursor-not-allowed opacity-80 min-h-[3rem]"
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
    @if ($currentStatus === 'PENDIENTE')
        @if (!$serviceRequest->assigned_to)
            @include('components.service-requests.show.header.assign-technician-modal', [
                'serviceRequest' => $serviceRequest,
                'technicians' => $technicians,
            ])
        @endif
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
