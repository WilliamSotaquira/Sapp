@props(['serviceRequest'])

@php
    $status = $serviceRequest->status;

    // Define the main lifecycle steps
    $steps = [
        ['key' => 'PENDIENTE', 'label' => 'Pendiente', 'icon' => 'fa-inbox'],
        ['key' => 'ACEPTADA', 'label' => 'Aceptada', 'icon' => 'fa-handshake'],
        ['key' => 'EN_PROCESO', 'label' => 'En Proceso', 'icon' => 'fa-cogs'],
        ['key' => 'RESUELTA', 'label' => 'Resuelta', 'icon' => 'fa-check-circle'],
        ['key' => 'CERRADA', 'label' => 'Cerrada', 'icon' => 'fa-lock'],
    ];

    // Map status to step index
    $statusIndex = match($status) {
        'PENDIENTE' => 0,
        'ACEPTADA' => 1,
        'EN_PROCESO', 'PAUSADA' => 2,
        'RESUELTA' => 3,
        'CERRADA' => 4,
        'CANCELADA' => -1,
        'RECHAZADA' => -1,
        'REABIERTO' => 1,
        default => 0,
    };

    $isCancelled = $status === 'CANCELADA';
    $isRejected = $status === 'RECHAZADA';
    $isPaused = $status === 'PAUSADA';
    $isReopened = $status === 'REABIERTO';
    $isDeadState = in_array($status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);

    // Timestamps for completed steps
    $stepTimestamps = [
        0 => $serviceRequest->created_at,
        1 => $serviceRequest->accepted_at,
        2 => $serviceRequest->responded_at,
        3 => $serviceRequest->resolved_at,
        4 => $serviceRequest->closed_at,
    ];
@endphp

<div class="sr-stepper-wrapper rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-3" role="navigation" aria-label="Progreso del flujo de trabajo">
    {{-- Special status badges --}}
    @if ($isPaused)
        <div class="sr-stepper-badge sr-stepper-badge--paused">
            <i class="fas fa-pause-circle" aria-hidden="true"></i>
            <span>Pausada</span>
            @if ($serviceRequest->pause_reason)
                <span class="sr-stepper-badge__reason">— {{ Str::limit($serviceRequest->pause_reason, 40) }}</span>
            @endif
        </div>
    @elseif ($isCancelled)
        <div class="sr-stepper-badge sr-stepper-badge--cancelled">
            <i class="fas fa-ban" aria-hidden="true"></i>
            <span>Cancelada</span>
        </div>
    @elseif ($isRejected)
        <div class="sr-stepper-badge sr-stepper-badge--rejected">
            <i class="fas fa-times-circle" aria-hidden="true"></i>
            <span>Rechazada</span>
            @if ($serviceRequest->rejection_reason)
                <span class="sr-stepper-badge__reason">— {{ Str::limit($serviceRequest->rejection_reason, 40) }}</span>
            @endif
        </div>
    @elseif ($isReopened)
        <div class="sr-stepper-badge sr-stepper-badge--reopened">
            <i class="fas fa-undo" aria-hidden="true"></i>
            <span>Reabierta</span>
        </div>
    @endif

    {{-- Stepper bar --}}
    <ol class="sr-stepper" aria-label="Pasos del proceso">
        @foreach ($steps as $index => $step)
            @php
                $isCompleted = $statusIndex > $index;
                $isCurrent = $statusIndex === $index;
                $isFuture = $statusIndex < $index;
                $timestamp = $stepTimestamps[$index] ?? null;

                $stepClass = 'sr-stepper__step';
                if ($isCompleted) $stepClass .= ' sr-stepper__step--completed';
                elseif ($isCurrent) $stepClass .= ' sr-stepper__step--current';
                else $stepClass .= ' sr-stepper__step--future';

                if ($isDeadState && $isCancelled) $stepClass .= ' sr-stepper__step--dead';
            @endphp

            <li class="{{ $stepClass }}"
                aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                title="{{ $step['label'] }}{{ $timestamp ? ' — ' . $timestamp->format('d/m/Y H:i') : '' }}">

                {{-- Connector line (before step, except first) --}}
                @if ($index > 0)
                    <div class="sr-stepper__connector {{ $isCompleted || $isCurrent ? 'sr-stepper__connector--filled' : '' }}" aria-hidden="true"></div>
                @endif

                {{-- Step circle --}}
                <div class="sr-stepper__circle">
                    @if ($isCompleted)
                        <i class="fas fa-check" aria-hidden="true"></i>
                    @elseif ($isCurrent && $isPaused)
                        <i class="fas fa-pause" aria-hidden="true"></i>
                    @else
                        <i class="fas {{ $step['icon'] }}" aria-hidden="true"></i>
                    @endif
                </div>

                {{-- Label --}}
                <span class="sr-stepper__label">{{ $step['label'] }}</span>

                {{-- Timestamp (only for completed/current) --}}
                @if ($timestamp && ($isCompleted || $isCurrent))
                    <span class="sr-stepper__time">{{ $timestamp->format('d/m H:i') }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</div>

@once
@push('styles')
<style>
/* === Workflow Stepper === */
.sr-stepper-wrapper {
    padding: 0;
}

.sr-stepper-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.sr-stepper-badge--paused {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.sr-stepper-badge--cancelled {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.sr-stepper-badge--rejected {
    background: #fce4ec;
    color: #880e4f;
    border: 1px solid #f48fb1;
}

.sr-stepper-badge--reopened {
    background: #e0f2fe;
    color: #075985;
    border: 1px solid #7dd3fc;
}

.sr-stepper-badge__reason {
    font-weight: 400;
    opacity: 0.8;
}

/* Stepper layout */
.sr-stepper {
    display: flex;
    align-items: flex-start;
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
}

.sr-stepper__step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    text-align: center;
    min-width: 0;
}

/* Connector line */
.sr-stepper__connector {
    position: absolute;
    top: 12px;
    right: 50%;
    width: 100%;
    height: 2px;
    background: #e2e8f0;
    z-index: 0;
}

.sr-stepper__connector--filled {
    background: #10b981;
}

/* Step circle */
.sr-stepper__circle {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    font-size: 0.6rem;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.sr-stepper__step--completed .sr-stepper__circle {
    background: #10b981;
    color: white;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

.sr-stepper__step--current .sr-stepper__circle {
    background: #3b82f6;
    color: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2), 0 2px 8px rgba(59, 130, 246, 0.3);
    animation: sr-stepper-pulse 2s ease-in-out infinite;
}

.sr-stepper__step--future .sr-stepper__circle {
    background: #f1f5f9;
    color: #94a3b8;
    border: 2px solid #e2e8f0;
}

.sr-stepper__step--dead .sr-stepper__circle {
    opacity: 0.5;
}

@keyframes sr-stepper-pulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2), 0 2px 8px rgba(59, 130, 246, 0.3); }
    50% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.1), 0 2px 8px rgba(59, 130, 246, 0.2); }
}

/* Labels */
.sr-stepper__label {
    margin-top: 6px;
    font-size: 0.7rem;
    font-weight: 500;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.sr-stepper__step--current .sr-stepper__label {
    color: #1e40af;
    font-weight: 700;
}

.sr-stepper__step--completed .sr-stepper__label {
    color: #047857;
    font-weight: 600;
}

/* Timestamps */
.sr-stepper__time {
    margin-top: 2px;
    font-size: 0.6rem;
    color: #94a3b8;
    font-weight: 400;
}

/* Responsive */
@media (max-width: 640px) {
    .sr-stepper__circle {
        width: 24px;
        height: 24px;
        font-size: 0.6rem;
    }

    .sr-stepper__connector {
        top: 11px;
    }

    .sr-stepper__label {
        font-size: 0.6rem;
    }

    .sr-stepper__time {
        display: none;
    }
}

@media (max-width: 400px) {
    .sr-stepper__label {
        font-size: 0.55rem;
    }

    .sr-stepper__circle {
        width: 20px;
        height: 20px;
        font-size: 0.55rem;
    }

    .sr-stepper__connector {
        top: 9px;
    }
}
</style>
@endpush
@endonce
