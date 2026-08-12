@props(['serviceRequest'])

@php
    $systemNotes = $serviceRequest->evidences->whereIn('evidence_type', ['SISTEMA', 'COMENTARIO']);
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
    $canAddNote = !$isDead;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gray-50 border-gray-200' }} px-5 py-3 border-b">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 flex items-center">
                <i class="fas fa-stream text-indigo-500 mr-2.5" aria-hidden="true"></i>
                Seguimiento y Notas
            </h3>
            <span class="text-xs text-gray-500">
                {{ $systemNotes->count() }} evento{{ $systemNotes->count() !== 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    <div class="px-5 py-4">
        {{-- Formulario rápido de notas --}}
        @if($canAddNote)
        <form action="{{ route('service-requests.evidences.quick-note', $serviceRequest) }}"
              method="POST"
              class="mb-4 pb-4 border-b border-gray-100"
              id="quickNoteForm">
            @csrf
            <div class="flex gap-2 items-start">
                <div class="flex-1">
                    <textarea
                        name="note_content"
                        id="quickNoteInput"
                        rows="2"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 resize-none placeholder-gray-400 transition"
                        placeholder="Agregar nota de seguimiento... (ej: Delegado a Laura, Verificado en producción, Pendiente confirmación)"
                        maxlength="1000"
                        aria-label="Nota de seguimiento"
                    ></textarea>
                    @error('note_content')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none transition disabled:opacity-50 disabled:cursor-not-allowed"
                        id="quickNoteSubmit"
                        title="Registrar nota">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Anotar</span>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">
                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                Las notas quedan registradas con fecha y hora en la línea de tiempo.
            </p>
        </form>
        @endif

        {{-- Mensajes de éxito/error --}}
        @if(session('evidence_success'))
            <div class="mb-3 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 flex items-center gap-2" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('evidence_success') }}
            </div>
        @endif
        @if(session('evidence_error'))
            <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 flex items-center gap-2" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                {{ session('evidence_error') }}
            </div>
        @endif

        {{-- Timeline de eventos --}}
        @if($systemNotes->count() > 0)
        <div class="sr-timeline">
            @foreach($systemNotes->sortByDesc('created_at')->values() as $index => $note)
                @php
                    $isSystem = $note->evidence_type === 'SISTEMA';
                    $isComment = $note->evidence_type === 'COMENTARIO';
                    $title = $note->title ?? 'Evento';

                    // Detect type from title for icon/color
                    $eventStyle = match(true) {
                        str_contains(strtolower($title), 'aceptad') => ['icon' => 'fa-handshake', 'color' => 'bg-emerald-500', 'ring' => 'ring-emerald-100'],
                        str_contains(strtolower($title), 'inici') || str_contains(strtolower($title), 'procesamiento') => ['icon' => 'fa-play', 'color' => 'bg-blue-500', 'ring' => 'ring-blue-100'],
                        str_contains(strtolower($title), 'resuelt') || str_contains(strtolower($title), 'resolv') => ['icon' => 'fa-check-circle', 'color' => 'bg-green-600', 'ring' => 'ring-green-100'],
                        str_contains(strtolower($title), 'cerrad') || str_contains(strtolower($title), 'cierre') => ['icon' => 'fa-lock', 'color' => 'bg-gray-600', 'ring' => 'ring-gray-100'],
                        str_contains(strtolower($title), 'pausad') => ['icon' => 'fa-pause', 'color' => 'bg-amber-500', 'ring' => 'ring-amber-100'],
                        str_contains(strtolower($title), 'reanud') => ['icon' => 'fa-play', 'color' => 'bg-teal-500', 'ring' => 'ring-teal-100'],
                        str_contains(strtolower($title), 'rechaz') => ['icon' => 'fa-times', 'color' => 'bg-red-500', 'ring' => 'ring-red-100'],
                        str_contains(strtolower($title), 'reasign') => ['icon' => 'fa-user-cog', 'color' => 'bg-violet-500', 'ring' => 'ring-violet-100'],
                        str_contains(strtolower($title), 'reabie') => ['icon' => 'fa-undo', 'color' => 'bg-orange-500', 'ring' => 'ring-orange-100'],
                        str_contains(strtolower($title), 'cancel') => ['icon' => 'fa-ban', 'color' => 'bg-red-600', 'ring' => 'ring-red-100'],
                        str_contains(strtolower($title), 'nota de seguimiento') || $isComment => ['icon' => 'fa-sticky-note', 'color' => 'bg-indigo-500', 'ring' => 'ring-indigo-100'],
                        $isSystem => ['icon' => 'fa-cog', 'color' => 'bg-slate-400', 'ring' => 'ring-slate-100'],
                        default => ['icon' => 'fa-comment', 'color' => 'bg-purple-400', 'ring' => 'ring-purple-100'],
                    };

                    $isLast = $index === $systemNotes->count() - 1;
                @endphp

                <div class="sr-timeline__item {{ $isLast ? 'sr-timeline__item--last' : '' }}">
                    {{-- Connector line --}}
                    @if (!$isLast)
                        <div class="sr-timeline__line" aria-hidden="true"></div>
                    @endif

                    {{-- Dot --}}
                    <div class="sr-timeline__dot {{ $eventStyle['color'] }} {{ $eventStyle['ring'] }}">
                        <i class="fas {{ $eventStyle['icon'] }}" aria-hidden="true"></i>
                    </div>

                    {{-- Content --}}
                    <div class="sr-timeline__content">
                        <div class="sr-timeline__header">
                            <span class="sr-timeline__title">
                                {{ $title }}
                                @if($isComment && isset($note->evidence_data['author']))
                                    <span class="text-gray-400 font-normal">— {{ $note->evidence_data['author'] }}</span>
                                @endif
                            </span>
                            <time class="sr-timeline__time" datetime="{{ $note->created_at->toISOString() }}">
                                {{ $note->created_at->format('d/m/Y H:i') }}
                            </time>
                        </div>
                        @if($note->description)
                            <p class="sr-timeline__desc">{{ $note->description }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @else
            @if(!$canAddNote)
            <p class="text-sm text-gray-400 text-center py-4">
                <i class="fas fa-inbox mr-1" aria-hidden="true"></i>
                Sin eventos registrados.
            </p>
            @endif
        @endif
    </div>
</div>

@push('styles')
<style>
/* === Timeline === */
.sr-timeline {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.sr-timeline__item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding-bottom: 16px;
}

.sr-timeline__item--last {
    padding-bottom: 0;
}

/* Vertical connector line */
.sr-timeline__line {
    position: absolute;
    left: 13px;
    top: 28px;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}

/* Dot */
.sr-timeline__dot {
    position: relative;
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.6rem;
    ring: 3px;
    box-shadow: 0 0 0 3px var(--tw-ring-color, #f1f5f9);
}

.sr-timeline__dot.ring-emerald-100 { box-shadow: 0 0 0 3px #d1fae5; }
.sr-timeline__dot.ring-blue-100 { box-shadow: 0 0 0 3px #dbeafe; }
.sr-timeline__dot.ring-green-100 { box-shadow: 0 0 0 3px #dcfce7; }
.sr-timeline__dot.ring-gray-100 { box-shadow: 0 0 0 3px #f3f4f6; }
.sr-timeline__dot.ring-amber-100 { box-shadow: 0 0 0 3px #fef3c7; }
.sr-timeline__dot.ring-teal-100 { box-shadow: 0 0 0 3px #ccfbf1; }
.sr-timeline__dot.ring-red-100 { box-shadow: 0 0 0 3px #fee2e2; }
.sr-timeline__dot.ring-violet-100 { box-shadow: 0 0 0 3px #ede9fe; }
.sr-timeline__dot.ring-orange-100 { box-shadow: 0 0 0 3px #ffedd5; }
.sr-timeline__dot.ring-slate-100 { box-shadow: 0 0 0 3px #f1f5f9; }
.sr-timeline__dot.ring-purple-100 { box-shadow: 0 0 0 3px #f3e8ff; }
.sr-timeline__dot.ring-indigo-100 { box-shadow: 0 0 0 3px #e0e7ff; }

/* Content */
.sr-timeline__content {
    flex: 1;
    min-width: 0;
    padding-top: 3px;
}

.sr-timeline__header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
}

.sr-timeline__title {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1e293b;
}

.sr-timeline__time {
    font-size: 0.7rem;
    color: #94a3b8;
    white-space: nowrap;
    flex-shrink: 0;
}

.sr-timeline__desc {
    margin-top: 2px;
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 640px) {
    .sr-timeline__item {
        gap: 10px;
        padding-bottom: 14px;
    }

    .sr-timeline__dot {
        width: 24px;
        height: 24px;
        font-size: 0.55rem;
    }

    .sr-timeline__line {
        left: 11px;
        top: 24px;
    }

    .sr-timeline__title {
        font-size: 0.78rem;
    }

    .sr-timeline__desc {
        font-size: 0.7rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quickNoteForm');
    const input = document.getElementById('quickNoteInput');
    const submit = document.getElementById('quickNoteSubmit');

    if (form && input && submit) {
        // Deshabilitar botón si textarea vacío
        function toggleSubmit() {
            submit.disabled = input.value.trim().length < 3;
        }
        input.addEventListener('input', toggleSubmit);
        toggleSubmit();

        // Enviar con Ctrl+Enter
        input.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter' && !submit.disabled) {
                form.submit();
            }
        });
    }
});
</script>
@endpush
