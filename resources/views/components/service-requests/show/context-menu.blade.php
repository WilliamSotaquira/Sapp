@props(['serviceRequest', 'technicians' => collect()])

@php
    $status = $serviceRequest->status;
    $isDeadState = in_array($status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
    $viewService = app(\App\Services\ServiceRequestViewService::class);

    // Determine pending requirements (what's blocking the next transition)
    $pendingRequirement = null;
    $hasEvidence = false;
    $hasSubtask = true;
    if ($status === 'EN_PROCESO') {
        $hasEvidence = ($serviceRequest->is_reportable === false) || $viewService->getResolvableEvidenceCount($serviceRequest) > 0;
        $hasSubtask = $viewService->hasCompletedSubtask($serviceRequest);

        if (!$hasEvidence) {
            $pendingRequirement = ['label' => 'Subir evidencia', 'icon' => 'fa-images', 'scroll' => 'sr-section-evidences'];
        } elseif (!$hasSubtask) {
            $pendingRequirement = ['label' => 'Completar tarea', 'icon' => 'fa-tasks', 'scroll' => 'sr-section-tasks'];
        }
    } elseif ($status === 'PENDIENTE' && empty($serviceRequest->assigned_to)) {
        $pendingRequirement = ['label' => 'Asignar técnico', 'icon' => 'fa-user-plus', 'modal' => 'assign-technician-modal-'.$serviceRequest->id];
    } elseif ($status === 'ACEPTADA' && empty($serviceRequest->assigned_to)) {
        $pendingRequirement = ['label' => 'Asignar técnico', 'icon' => 'fa-user-plus', 'modal' => 'assign-technician-modal-'.$serviceRequest->id];
    }

    $allRequirementsMet = is_null($pendingRequirement);

    // Workflow actions available per status
    $workflowActions = match($status) {
        'PENDIENTE' => [
            ['label' => $serviceRequest->assigned_to ? 'Aceptar Solicitud' : 'Asignar Técnico', 'icon' => $serviceRequest->assigned_to ? 'fa-handshake' : 'fa-user-plus', 'modal' => $serviceRequest->assigned_to ? 'accept-modal-'.$serviceRequest->id : 'assign-technician-modal-'.$serviceRequest->id],
            ['label' => 'Rechazar', 'icon' => 'fa-times-circle', 'modal' => 'reject-modal-'.$serviceRequest->id],
        ],
        'ACEPTADA' => [
            ['label' => 'Iniciar Servicio', 'icon' => 'fa-play', 'modal' => 'start-modal-'.$serviceRequest->id],
            ['label' => 'Reasignar', 'icon' => 'fa-user-cog', 'url' => route('service-requests.reassign', $serviceRequest)],
        ],
        'EN_PROCESO' => [
            ['label' => 'Resolver Solicitud', 'icon' => 'fa-check-circle', 'modal' => 'resolve-modal-'.$serviceRequest->id],
            ['label' => 'Pausar Trabajo', 'icon' => 'fa-pause', 'modal' => 'pause-modal-'.$serviceRequest->id],
        ],
        'PAUSADA' => [
            ['label' => 'Reanudar Trabajo', 'icon' => 'fa-play', 'modal' => 'resume-modal-'.$serviceRequest->id],
            ['label' => 'Cerrar por Vencimiento', 'icon' => 'fa-clock', 'modal' => 'vencimiento-modal-'.$serviceRequest->id],
        ],
        'RESUELTA' => [
            ['label' => 'Cerrar Solicitud', 'icon' => 'fa-lock', 'modal' => 'close-modal-'.$serviceRequest->id],
            ['label' => 'Reabrir', 'icon' => 'fa-undo', 'modal' => 'reopen-modal-'.$serviceRequest->id],
        ],
        'CERRADA' => [
            ['label' => 'Reabrir Solicitud', 'icon' => 'fa-undo', 'modal' => 'reopen-modal-'.$serviceRequest->id],
        ],
        default => [],
    };

    // Navigation sections
    $navSections = [
        ['id' => 'sr-section-description', 'label' => 'Descripción', 'icon' => 'fa-align-left'],
        ['id' => 'sr-section-service-info', 'label' => 'Servicio', 'icon' => 'fa-concierge-bell'],
        ['id' => 'sr-section-timelines', 'label' => 'Tiempos', 'icon' => 'fa-clock'],
        ['id' => 'sr-section-evidences', 'label' => 'Evidencias', 'icon' => 'fa-images'],
        ['id' => 'sr-section-tasks', 'label' => 'Tareas', 'icon' => 'fa-tasks'],
        ['id' => 'sr-section-actions', 'label' => 'Acciones', 'icon' => 'fa-cog'],
    ];
@endphp

<!-- Context Menu -->
<div id="sr-context-menu" class="sr-ctx hidden" role="menu" aria-label="Menú contextual">
    {{-- Dead state: offer to create a new request for quick flow --}}
    @if ($isDeadState)
        <div class="sr-ctx__group">
            <div class="sr-ctx__group-label">Nueva solicitud</div>
            <a href="{{ route('service-requests.create') }}" class="sr-ctx__item sr-ctx__item--primary" role="menuitem" data-ctx-default>
                <i class="fas fa-plus-circle sr-ctx__icon" aria-hidden="true"></i>
                Crear nueva solicitud
                <kbd class="sr-ctx__kbd">Tab</kbd>
            </a>
            <a href="{{ route('service-requests.create', ['service_request_id' => $serviceRequest->id]) }}" class="sr-ctx__item" role="menuitem">
                <i class="fas fa-code-branch sr-ctx__icon" aria-hidden="true"></i>
                Crear solicitud derivada
            </a>
        </div>
    @endif

    {{-- Primary action: pending requirement OR workflow transition --}}
    @if (!$isDeadState)
        <div class="sr-ctx__group" data-ctx-group="next-step">
            <div class="sr-ctx__group-label">Siguiente paso</div>

            @if ($status === 'EN_PROCESO' && $hasEvidence)
                {{-- Dynamic: show "Completar tarea" or "Resolver Solicitud" based on live subtask state --}}
                <button type="button" class="sr-ctx__item sr-ctx__item--primary {{ $hasSubtask ? 'hidden' : '' }}" role="menuitem"
                        data-ctx-scroll="sr-section-tasks"
                        @if(!$hasSubtask) data-ctx-default @endif
                        data-ctx-pending-subtask>
                    <i class="fas fa-tasks sr-ctx__icon" aria-hidden="true"></i>
                    Completar tarea
                    <kbd class="sr-ctx__kbd">Tab</kbd>
                </button>
                <button type="button" class="sr-ctx__item sr-ctx__item--primary {{ !$hasSubtask ? 'hidden' : '' }}" role="menuitem"
                        data-ctx-modal="resolve-modal-{{ $serviceRequest->id }}"
                        @if($hasSubtask) data-ctx-default @endif
                        data-ctx-resolve-ready>
                    <i class="fas fa-check-circle sr-ctx__icon" aria-hidden="true"></i>
                    Resolver Solicitud
                    <kbd class="sr-ctx__kbd">Tab</kbd>
                </button>
            @elseif ($pendingRequirement)
                {{-- Requirement not met: guide user to fulfill it --}}
                @if (isset($pendingRequirement['modal']))
                    <button type="button" class="sr-ctx__item sr-ctx__item--primary" role="menuitem"
                            data-ctx-modal="{{ $pendingRequirement['modal'] }}" data-ctx-default>
                        <i class="fas {{ $pendingRequirement['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $pendingRequirement['label'] }}
                        <kbd class="sr-ctx__kbd">Tab</kbd>
                    </button>
                @else
                    <button type="button" class="sr-ctx__item sr-ctx__item--primary" role="menuitem"
                            data-ctx-scroll="{{ $pendingRequirement['scroll'] }}" data-ctx-default>
                        <i class="fas {{ $pendingRequirement['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $pendingRequirement['label'] }}
                        <kbd class="sr-ctx__kbd">Tab</kbd>
                    </button>
                @endif
            @elseif (count($workflowActions) > 0)
                {{-- All requirements met: show workflow transition --}}
                @php $primary = $workflowActions[0]; @endphp
                @if (isset($primary['modal']))
                    <button type="button" class="sr-ctx__item sr-ctx__item--primary" role="menuitem"
                            data-ctx-modal="{{ $primary['modal'] }}" data-ctx-default>
                        <i class="fas {{ $primary['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $primary['label'] }}
                        <kbd class="sr-ctx__kbd">Tab</kbd>
                    </button>
                @elseif (isset($primary['url']))
                    <a href="{{ $primary['url'] }}" class="sr-ctx__item sr-ctx__item--primary" role="menuitem" data-ctx-default>
                        <i class="fas {{ $primary['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $primary['label'] }}
                        <kbd class="sr-ctx__kbd">Tab</kbd>
                    </a>
                @endif
            @endif
        </div>
    @endif

    {{-- Other workflow actions --}}
    @if (!$isDeadState && count($workflowActions) > ($allRequirementsMet ? 1 : 0))
        <div class="sr-ctx__group">
            <div class="sr-ctx__group-label">Acciones</div>
            @foreach ($workflowActions as $index => $action)
                @if ($allRequirementsMet && $index === 0) @continue @endif
                {{-- Skip "Resolver Solicitud" when it's handled dynamically in next-step --}}
                @if ($status === 'EN_PROCESO' && $hasEvidence && $index === 0) @continue @endif
                @if (isset($action['modal']))
                    <button type="button" class="sr-ctx__item" role="menuitem"
                            data-ctx-modal="{{ $action['modal'] }}">
                        <i class="fas {{ $action['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $action['label'] }}
                    </button>
                @elseif (isset($action['url']))
                    <a href="{{ $action['url'] }}" class="sr-ctx__item" role="menuitem">
                        <i class="fas {{ $action['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Navigate section --}}
    <div class="sr-ctx__group">
        <div class="sr-ctx__group-label">Ir a</div>
        @foreach ($navSections as $section)
            <button type="button" class="sr-ctx__item" role="menuitem"
                    data-ctx-scroll="{{ $section['id'] }}">
                <i class="fas {{ $section['icon'] }} sr-ctx__icon" aria-hidden="true"></i>
                {{ $section['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <div class="sr-ctx__group">
        <div class="sr-ctx__group-label">Rápido</div>
        <a href="{{ route('service-requests.create') }}" class="sr-ctx__item" role="menuitem">
            <i class="fas fa-plus-circle sr-ctx__icon" aria-hidden="true"></i>
            Crear nueva solicitud
        </a>
        <a href="{{ route('service-requests.index') }}" class="sr-ctx__item" role="menuitem">
            <i class="fas fa-list sr-ctx__icon" aria-hidden="true"></i>
            Ver todas las solicitudes
        </a>
        <button type="button" class="sr-ctx__item" role="menuitem" data-ctx-action="copy-ticket">
            <i class="fas fa-copy sr-ctx__icon" aria-hidden="true"></i>
            Copiar ticket
        </button>
        <a href="{{ route('service-requests.edit', $serviceRequest) }}" class="sr-ctx__item" role="menuitem">
            <i class="fas fa-edit sr-ctx__icon" aria-hidden="true"></i>
            Editar solicitud
        </a>
        <button type="button" class="sr-ctx__item" role="menuitem" data-ctx-action="scroll-top">
            <i class="fas fa-arrow-up sr-ctx__icon" aria-hidden="true"></i>
            Volver arriba
        </button>
    </div>
</div>

@once
@push('styles')
<style>
/* === Context Menu === */
.sr-ctx {
    position: fixed;
    z-index: 9999;
    min-width: 200px;
    max-width: 260px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(15, 23, 42, 0.06);
    padding: 4px;
    animation: ctx-scale-in 0.12s ease-out;
}

@keyframes ctx-scale-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.sr-ctx__group {
    padding: 2px 0;
}

.sr-ctx__group + .sr-ctx__group {
    border-top: 1px solid #f1f5f9;
    margin-top: 2px;
    padding-top: 4px;
}

.sr-ctx__group-label {
    padding: 4px 10px 2px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
}

.sr-ctx__item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 7px 10px;
    border-radius: 6px;
    border: none;
    background: none;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    text-decoration: none;
    cursor: pointer;
    text-align: left;
    transition: background 0.1s ease;
}

.sr-ctx__item:hover,
.sr-ctx__item:focus {
    background: #f1f5f9;
    color: #0f172a;
    outline: none;
}

.sr-ctx__item:active {
    background: #e2e8f0;
}

.sr-ctx__icon {
    width: 14px;
    text-align: center;
    font-size: 11px;
    color: #64748b;
    flex-shrink: 0;
}

.sr-ctx__item:hover .sr-ctx__icon {
    color: #3b82f6;
}

/* Primary item (next step) */
.sr-ctx__item--primary {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    font-weight: 600;
    color: #166534;
}

.sr-ctx__item--primary:hover,
.sr-ctx__item--primary:focus {
    background: #dcfce7;
    border-color: #86efac;
}

.sr-ctx__item--primary .sr-ctx__icon {
    color: #16a34a;
}

.sr-ctx__kbd {
    margin-left: auto;
    padding: 1px 5px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #64748b;
    font-size: 10px;
    font-weight: 600;
    font-family: inherit;
}

.sr-ctx__item--primary .sr-ctx__kbd {
    background: #bbf7d0;
    color: #166534;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    var menu = document.getElementById('sr-context-menu');
    if (!menu) return;

    var isOpen = false;

    /**
     * Update context menu "Siguiente paso" based on live subtask state.
     * Called from tasks-panel after subtask/task toggles.
     */
    window.updateContextMenuNextStep = function() {
        var pendingBtn = menu.querySelector('[data-ctx-pending-subtask]');
        var resolveBtn = menu.querySelector('[data-ctx-resolve-ready]');
        if (!pendingBtn || !resolveBtn) return;

        // Reuse the existing hasAnyCompletedSubtask function from tasks-panel
        var hasCompleted = (typeof hasAnyCompletedSubtask === 'function')
            ? hasAnyCompletedSubtask(@js($serviceRequest->id))
            : false;

        if (hasCompleted) {
            pendingBtn.classList.add('hidden');
            pendingBtn.removeAttribute('data-ctx-default');
            resolveBtn.classList.remove('hidden');
            resolveBtn.setAttribute('data-ctx-default', '');
        } else {
            resolveBtn.classList.add('hidden');
            resolveBtn.removeAttribute('data-ctx-default');
            pendingBtn.classList.remove('hidden');
            pendingBtn.setAttribute('data-ctx-default', '');
        }
    };

    function show(x, y) {
        menu.classList.remove('hidden');
        isOpen = true;

        var vw = window.innerWidth;
        var vh = window.innerHeight;

        // Posicionar fuera de vista para medir
        menu.style.left = '-9999px';
        menu.style.top = '0px';
        menu.style.visibility = 'hidden';

        var menuW = menu.offsetWidth;
        var menuH = menu.offsetHeight;

        // Calcular offset del item predeterminado
        var defaultItem = menu.querySelector('[data-ctx-default]:not(.hidden)') || menu.querySelector('.sr-ctx__item:not(.hidden)');
        var posX = x;
        var posY = y;

        if (defaultItem) {
            var primaryCenterY = defaultItem.offsetTop + Math.round(defaultItem.offsetHeight / 2);
            posY = y - primaryCenterY;
            posX = x - 40;
        }

        if (posX + menuW > vw) posX = vw - menuW - 8;
        if (posX < 4) posX = 4;
        if (posY < 4) posY = 4;
        if (posY + menuH > vh) posY = vh - menuH - 8;

        menu.style.left = posX + 'px';
        menu.style.top = posY + 'px';
        menu.style.visibility = '';

        // Focus the default (next step) item
        setTimeout(function() {
            if (defaultItem) {
                defaultItem.focus();
            }
        }, 50);
    }

    function hide() {
        menu.classList.add('hidden');
        isOpen = false;
    }

    // Right-click anywhere on the page
    document.addEventListener('contextmenu', function(e) {
        // Don't override on text inputs (allow native paste menu)
        if (e.target.closest('input[type="text"], input[type="url"], input[type="email"], textarea, select')) return;
        // Don't override inside modals
        if (e.target.closest('[role="dialog"]')) return;

        e.preventDefault();
        show(e.clientX, e.clientY);
    });

    // Close on click outside
    document.addEventListener('mousedown', function(e) {
        if (isOpen && !menu.contains(e.target)) {
            hide();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            hide();
        }
    });

    // Close on scroll
    window.addEventListener('scroll', function() {
        if (isOpen) hide();
    }, { passive: true });

    // Handle menu item clicks
    menu.addEventListener('click', function(e) {
        var item = e.target.closest('.sr-ctx__item');
        if (!item) return;

        // Modal action
        var modalId = item.dataset.ctxModal;
        if (modalId && typeof window.openModal === 'function') {
            hide();
            window.openModal(modalId, item);
            return;
        }

        // Scroll action
        var scrollTarget = item.dataset.ctxScroll;
        if (scrollTarget) {
            hide();
            var el = document.getElementById(scrollTarget);
            if (el) {
                // Expand if collapsed
                if (el.classList.contains('sr-collapsible--collapsed')) {
                    el.classList.remove('sr-collapsible--collapsed');
                    var toggle = el.querySelector('.sr-collapsible__toggle');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'true');
                        toggle.querySelector('span').textContent = 'Colapsar';
                    }
                    var key = el.dataset.sectionKey;
                    if (key) {
                        try {
                            var prefs = JSON.parse(localStorage.getItem('sr-collapsed-sections') || '{}');
                            prefs[key] = 'expanded';
                            localStorage.setItem('sr-collapsed-sections', JSON.stringify(prefs));
                        } catch(ex) {}
                    }
                }
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        // Quick actions
        var action = item.dataset.ctxAction;
        if (action === 'copy-ticket') {
            hide();
            var ticket = @js($serviceRequest->ticket_number);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(ticket).then(function() {
                    if (typeof window.srNotify === 'function') window.srNotify(true, 'Ticket ' + ticket + ' copiado');
                });
            }
            return;
        }

        if (action === 'scroll-top') {
            hide();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Links (href) handle themselves
        hide();
    });

    // Keyboard navigation within menu
    menu.addEventListener('keydown', function(e) {
        var items = Array.from(menu.querySelectorAll('.sr-ctx__item:not(.hidden)'));
        var current = document.activeElement;
        var idx = items.indexOf(current);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var next = items[(idx + 1) % items.length];
            if (next) next.focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var prev = items[(idx - 1 + items.length) % items.length];
            if (prev) prev.focus();
        } else if (e.key === 'Tab' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (current) current.click();
        }
    });
})();
</script>
@endpush
@endonce
