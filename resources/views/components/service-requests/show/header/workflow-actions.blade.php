@props([
    'serviceRequest',
    'showLabels' => true,
    'compact' => false,
    'disabled' => false,
    'technicians' => collect(),
])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $hasCompletedSubtask = $viewService->hasCompletedSubtask($serviceRequest);
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
                'condition' => $canResolveByEvidence && $hasCompletedSubtask,
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
                'action' => 'reopen',
                'route' => 'reopen-modal',
                'icon' => 'undo',
                'method' => 'MODAL',
                'label' => 'Reabrir Solicitud',
                'condition' => true,
                'appearance' => 'soft',
                'modal_id' => 'reopen-modal-' . $serviceRequest->id,
            ],
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

    // Separar acciones principales (primary) de secundarias para layout mejorado
    $primaryActions = collect($actions)->filter(fn($a) => $a['condition'] && ($a['appearance'] ?? 'soft') === 'primary')->values();
    $secondaryActions = collect($actions)->filter(fn($a) => $a['condition'] && ($a['appearance'] ?? 'soft') !== 'primary')->values();

    // Grid siempre horizontal para mejor usabilidad
    $gridClasses = match(true) {
        $activeActions === 1 => 'grid-cols-1',
        $activeActions === 2 => 'grid-cols-2',
        $activeActions === 3 => 'grid-cols-3',
        $activeActions >= 4 => 'grid-cols-2 sm:grid-cols-4',
        default => 'grid-cols-2 sm:grid-cols-3'
    };

    $resolveActionClasses = function (array $actionItem): string {
        $appearance = $actionItem['appearance'] ?? 'soft';

        $base = 'inline-flex items-center justify-center px-3 py-2 rounded-lg font-medium text-[13px] focus:outline-none focus:ring-2 focus:ring-offset-1 transition-all duration-150 group no-underline gap-1.5 flex-1 min-w-0';

        return match ($appearance) {
            'primary' => $base . ' bg-emerald-600 border border-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
            'danger-soft' => $base . ' bg-white border border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 focus:ring-red-400',
            'warning-soft' => $base . ' bg-white border border-amber-200 text-amber-700 hover:bg-amber-50 hover:border-amber-300 focus:ring-amber-400',
            default => $base . ' bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 focus:ring-slate-300',
        };
    };
@endphp

@if (count($actions) > 0 && !$disabled)
    <div class="flex flex-wrap gap-2">
        @foreach ($actions as $actionItem)
            @if ($actionItem['condition'])
                {{-- BOTONES QUE ABREN MODALES --}}
                @if ($actionItem['method'] === 'MODAL')
                    <button type="button"
                        data-service-request-id="{{ $serviceRequest->id }}"
                        data-workflow-action="{{ $actionItem['action'] }}"
                        data-modal-id="{{ $actionItem['modal_id'] ?? '' }}"
                        onclick="openModal('{{ $actionItem['modal_id'] }}', this)"
                        class="{{ $resolveActionClasses($actionItem) }}"
                        aria-label="{{ $actionItem['label'] }}">
                        <i class="fas fa-{{ $actionItem['icon'] }} text-[11px] flex-shrink-0" aria-hidden="true"></i>
                        @if ($showLabels)
                            <span class="truncate">{{ $actionItem['label'] }}</span>
                        @endif
                    </button>

                    {{-- BOTONES CON GET (LINKS) --}}
                @elseif($actionItem['method'] === 'GET')
                    <a href="{{ route($actionItem['route'], $actionItem['route_params'] ?? $serviceRequest) }}"
                        class="{{ $resolveActionClasses($actionItem) }}"
                        aria-label="{{ $actionItem['label'] }}">
                        <i class="fas fa-{{ $actionItem['icon'] }} text-[11px] flex-shrink-0" aria-hidden="true"></i>
                        @if ($showLabels)
                            <span class="truncate">{{ $actionItem['label'] }}</span>
                        @endif
                    </a>

                    {{-- BOTONES CON FORMULARIOS (POST, PATCH) --}}
                @else
                    <form action="{{ route($actionItem['route'], $serviceRequest) }}" method="POST" class="flex flex-1 min-w-0">
                        @csrf
                        @if ($actionItem['method'] === 'PATCH')
                            @method('PATCH')
                        @endif

                        <button type="submit"
                            class="{{ $resolveActionClasses($actionItem) }}"
                            onclick="return confirm('¿Estás seguro de que deseas {{ strtolower($actionItem['label']) }}?')"
                            aria-label="{{ $actionItem['label'] }}">
                            <i class="fas fa-{{ $actionItem['icon'] }} text-[11px] flex-shrink-0" aria-hidden="true"></i>
                            @if ($showLabels)
                                <span class="truncate">{{ $actionItem['label'] }}</span>
                            @endif
                        </button>
                    </form>
                @endif
            @else
                @if ($actionItem['action'] === 'resolve' && $canResolveByEvidence && !$hasCompletedSubtask)
                    {{-- Anchor button to tasks section when evidence is met but subtask validation fails --}}
                    <a href="#tasks-panel-{{ $serviceRequest->id }}"
                       onclick="event.preventDefault(); document.getElementById('tasks-panel-{{ $serviceRequest->id }}').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                       class="{{ $resolveActionClasses(['appearance' => 'primary']) }}"
                       aria-label="Ir a Tareas Asociadas"
                       data-header-subtask-anchor="{{ $serviceRequest->id }}">
                        <i class="fas fa-arrow-down text-[11px] flex-shrink-0" aria-hidden="true"></i>
                        @if ($showLabels)
                            <span class="truncate">Completar Tareas</span>
                        @endif
                    </a>
                    {{-- Hidden resolve button that appears when subtask is completed --}}
                    <button type="button"
                        data-service-request-id="{{ $serviceRequest->id }}"
                        data-workflow-action="resolve"
                        data-modal-id="resolve-modal-{{ $serviceRequest->id }}"
                        data-header-resolve-button="{{ $serviceRequest->id }}"
                        onclick="openModal('resolve-modal-{{ $serviceRequest->id }}', this)"
                        class="{{ $resolveActionClasses(['appearance' => 'primary']) }} hidden"
                        aria-label="Resolver Solicitud">
                        <i class="fas fa-check-circle text-[11px] flex-shrink-0" aria-hidden="true"></i>
                        @if ($showLabels)
                            <span class="truncate">Resolver Solicitud</span>
                        @endif
                    </button>
                @else
                    {{-- Default disabled button --}}
                    <button type="button" disabled
                        class="inline-flex items-center justify-center px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg font-medium text-slate-400 text-[13px] cursor-not-allowed opacity-70 gap-1.5 flex-1 min-w-0"
                        title="{{ $actionItem['action'] === 'resolve' ? 'Debe agregar al menos una evidencia antes de resolver' : 'Acción no disponible' }}"
                        aria-label="{{ $actionItem['label'] }} (deshabilitado)">
                        <i class="fas fa-{{ $actionItem['icon'] }} text-[11px]"></i>
                        @if ($showLabels)
                            <span class="truncate">{{ $actionItem['label'] }}</span>
                        @endif
                    </button>
                @endif
            @endif
        @endforeach
    </div>
@elseif($disabled)
    <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-center">
        <p class="text-gray-500 text-sm">
            <i class="fas fa-lock mr-1.5"></i>
            Acciones no disponibles
        </p>
    </div>
@else
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-2.5 text-center">
        <p class="text-blue-600 text-sm">
            <i class="fas fa-check-circle mr-1.5"></i>
            Sin acciones para: <strong>{{ $currentStatus }}</strong>
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
    @if ($currentStatus === 'RESUELTA' || $currentStatus === 'CERRADA')
        {{-- Modal se renderiza al final de show.blade.php para evitar problemas de posicionamiento --}}
    @endif
@endif
