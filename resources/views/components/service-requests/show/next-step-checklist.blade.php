@props(['serviceRequest'])

@php
    $status = $serviceRequest->status;
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $isDeadState = in_array($status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);

    // Don't show checklist for dead states
    if ($isDeadState) {
        $requirements = [];
        $nextAction = null;
        $nextLabel = null;
    } else {
        // Define requirements per status (what's needed for the NEXT transition)
        $requirements = match($status) {
            'PENDIENTE' => [
                [
                    'key' => 'technician',
                    'label' => 'Técnico asignado',
                    'met' => !empty($serviceRequest->assigned_to),
                    'hint' => 'Asigna un técnico antes de aceptar',
                    'anchor' => 'sr-section-service-info',
                ],
            ],
            'ACEPTADA' => [
                [
                    'key' => 'technician',
                    'label' => 'Técnico asignado',
                    'met' => !empty($serviceRequest->assigned_to),
                    'hint' => 'Se requiere técnico para iniciar',
                    'anchor' => 'sr-section-service-info',
                ],
            ],
            'EN_PROCESO' => [
                [
                    'key' => 'evidence',
                    'label' => 'Evidencia cargada',
                    'met' => ($serviceRequest->is_reportable === false) || $viewService->getResolvableEvidenceCount($serviceRequest) > 0,
                    'hint' => 'Sube al menos una evidencia',
                    'anchor' => 'sr-section-evidences',
                ],
                [
                    'key' => 'subtask',
                    'label' => 'Tarea completada',
                    'met' => $viewService->hasCompletedSubtask($serviceRequest),
                    'hint' => 'Completa al menos una subtarea',
                    'anchor' => 'sr-section-tasks',
                ],
            ],
            'PAUSADA' => [
                [
                    'key' => 'resume_ready',
                    'label' => 'Lista para reanudar',
                    'met' => true,
                    'hint' => 'Reanuda cuando el bloqueo se resuelva',
                    'anchor' => null,
                ],
            ],
            'RESUELTA' => [
                [
                    'key' => 'resolution_notes',
                    'label' => 'Notas de resolución',
                    'met' => !empty($serviceRequest->resolution_notes),
                    'hint' => 'Ya están registradas las notas',
                    'anchor' => 'sr-section-description',
                ],
            ],
            default => [],
        };

        $nextAction = match($status) {
            'PENDIENTE' => 'Aceptar solicitud',
            'ACEPTADA' => 'Iniciar trabajo',
            'EN_PROCESO' => 'Resolver solicitud',
            'PAUSADA' => 'Reanudar trabajo',
            'RESUELTA' => 'Cerrar solicitud',
            default => null,
        };

        $nextLabel = match($status) {
            'PENDIENTE' => 'Para aceptar',
            'ACEPTADA' => 'Para iniciar',
            'EN_PROCESO' => 'Para resolver',
            'PAUSADA' => 'Para reanudar',
            'RESUELTA' => 'Para cerrar',
            default => null,
        };
    }

    $totalRequirements = count($requirements);
    $metCount = collect($requirements)->where('met', true)->count();
    $allMet = $totalRequirements > 0 && $metCount === $totalRequirements;
@endphp

@if ($totalRequirements > 0)
    <div class="sr-checklist {{ $allMet ? 'sr-checklist--ready' : '' }}" role="status" aria-label="Requisitos para siguiente paso">
        <div class="sr-checklist__header">
            <div class="sr-checklist__title">
                <i class="fas {{ $allMet ? 'fa-rocket' : 'fa-clipboard-list' }} sr-checklist__title-icon" aria-hidden="true"></i>
                <span class="sr-checklist__title-text">
                    {{ $nextLabel }}:
                </span>
                <span class="sr-checklist__counter">{{ $metCount }}/{{ $totalRequirements }}</span>
            </div>
            @if ($allMet && $nextAction)
                <span class="sr-checklist__ready-badge">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    Listo
                </span>
            @endif
        </div>

        <ul class="sr-checklist__items">
            @foreach ($requirements as $req)
                <li class="sr-checklist__item {{ $req['met'] ? 'sr-checklist__item--met' : 'sr-checklist__item--pending' }}">
                    @if (!$req['met'] && !empty($req['anchor']))
                        <a href="#{{ $req['anchor'] }}"
                           class="sr-checklist__link"
                           data-checklist-anchor="{{ $req['anchor'] }}"
                           aria-label="Ir a {{ $req['label'] }}">
                            <span class="sr-checklist__check" aria-hidden="true">
                                <i class="far fa-circle"></i>
                            </span>
                            <span class="sr-checklist__label">{{ $req['label'] }}</span>
                            <span class="sr-checklist__hint">{{ $req['hint'] }}</span>
                            <i class="fas fa-arrow-right sr-checklist__arrow" aria-hidden="true"></i>
                        </a>
                    @else
                        <span class="sr-checklist__check" aria-hidden="true">
                            @if ($req['met'])
                                <i class="fas fa-check-circle"></i>
                            @else
                                <i class="far fa-circle"></i>
                            @endif
                        </span>
                        <span class="sr-checklist__label">{{ $req['label'] }}</span>
                        @if (!$req['met'])
                            <span class="sr-checklist__hint">{{ $req['hint'] }}</span>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($allMet && $nextAction)
            <div class="sr-checklist__action-hint">
                <i class="fas fa-arrow-up" aria-hidden="true"></i>
                Usa el botón <strong>"{{ $nextAction }}"</strong> en el header
            </div>
        @endif
    </div>
@endif

@once
@push('styles')
<style>
/* === Next Step Checklist === */
.sr-checklist {
    padding: 12px 16px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    font-size: 0.82rem;
}

.sr-checklist--ready {
    background: #ecfdf5;
    border-color: #6ee7b7;
}

.sr-checklist__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.sr-checklist__title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #92400e;
}

.sr-checklist--ready .sr-checklist__title {
    color: #065f46;
}

.sr-checklist__title-icon {
    font-size: 0.85rem;
}

.sr-checklist__title-text {
    font-size: 0.8rem;
}

.sr-checklist__counter {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    background: rgba(146, 64, 14, 0.1);
    color: #92400e;
    font-weight: 700;
}

.sr-checklist--ready .sr-checklist__counter {
    background: rgba(6, 95, 70, 0.1);
    color: #065f46;
}

.sr-checklist__ready-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 8px;
    background: #10b981;
    color: white;
    font-size: 0.68rem;
    font-weight: 600;
}

/* Items */
.sr-checklist__items {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 6px 16px;
}

.sr-checklist__item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
}

.sr-checklist__check {
    font-size: 0.8rem;
    flex-shrink: 0;
}

.sr-checklist__item--met .sr-checklist__check {
    color: #10b981;
}

.sr-checklist__item--pending .sr-checklist__check {
    color: #d97706;
}

.sr-checklist__item--met .sr-checklist__label {
    color: #065f46;
    text-decoration: line-through;
    opacity: 0.75;
}

.sr-checklist__item--pending .sr-checklist__label {
    color: #92400e;
    font-weight: 500;
}

.sr-checklist__hint {
    font-size: 0.68rem;
    color: #b45309;
    opacity: 0.8;
    font-style: italic;
}

/* Action hint */
.sr-checklist__action-hint {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid rgba(16, 185, 129, 0.2);
    font-size: 0.72rem;
    color: #047857;
    display: flex;
    align-items: center;
    gap: 5px;
}

.sr-checklist__action-hint i {
    font-size: 0.65rem;
    animation: sr-checklist-bounce 1.5s ease-in-out infinite;
}

@keyframes sr-checklist-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

/* Clickable link for pending items */
.sr-checklist__link {
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    padding: 3px 8px 3px 0;
    border-radius: 6px;
    transition: background 0.15s ease;
    cursor: pointer;
}

.sr-checklist__link:hover {
    background: rgba(217, 119, 6, 0.08);
}

.sr-checklist__link:hover .sr-checklist__label {
    text-decoration: underline;
}

.sr-checklist__link:hover .sr-checklist__arrow {
    transform: translateX(2px);
}

.sr-checklist__link:focus-visible {
    outline: 2px solid #d97706;
    outline-offset: 2px;
}

.sr-checklist__arrow {
    font-size: 0.6rem;
    color: #d97706;
    opacity: 0.6;
    transition: transform 0.2s ease;
    margin-left: 2px;
}

/* Responsive */
@media (max-width: 640px) {
    .sr-checklist {
        padding: 10px 12px;
    }

    .sr-checklist__items {
        flex-direction: column;
        gap: 4px;
    }

    .sr-checklist__hint {
        display: none;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    var anchors = document.querySelectorAll('[data-checklist-anchor]');
    if (!anchors.length) return;

    anchors.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.dataset.checklistAnchor;
            var targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            // Expand section if collapsed
            if (targetEl.classList.contains('sr-collapsible--collapsed')) {
                targetEl.classList.remove('sr-collapsible--collapsed');
                var toggle = targetEl.querySelector('.sr-collapsible__toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.querySelector('span').textContent = 'Colapsar';
                }
                // Save preference
                var key = targetEl.dataset.sectionKey;
                if (key) {
                    try {
                        var prefs = JSON.parse(localStorage.getItem('sr-collapsed-sections') || '{}');
                        prefs[key] = 'expanded';
                        localStorage.setItem('sr-collapsed-sections', JSON.stringify(prefs));
                    } catch(ex) {}
                }
            }

            // Smooth scroll to section
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>
@endpush
@endonce
