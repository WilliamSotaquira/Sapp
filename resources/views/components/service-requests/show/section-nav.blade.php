@props(['serviceRequest'])

@php
    $status = $serviceRequest->status;

    $sections = [
        ['id' => 'sr-section-description', 'label' => 'Descripción', 'icon' => 'fa-align-left', 'short' => 'Desc.'],
        ['id' => 'sr-section-service-info', 'label' => 'Servicio', 'icon' => 'fa-concierge-bell', 'short' => 'Serv.'],
        ['id' => 'sr-section-timelines', 'label' => 'Tiempos', 'icon' => 'fa-clock', 'short' => 'Tiem.'],
        ['id' => 'sr-section-evidences', 'label' => 'Evidencias', 'icon' => 'fa-images', 'short' => 'Evid.'],
        ['id' => 'sr-section-tasks', 'label' => 'Tareas', 'icon' => 'fa-tasks', 'short' => 'Tareas'],
        ['id' => 'sr-section-actions', 'label' => 'Acciones', 'icon' => 'fa-cog', 'short' => 'Acc.'],
    ];

    $recommendedSection = match($status) {
        'PENDIENTE' => 'sr-section-description',
        'ACEPTADA' => 'sr-section-tasks',
        'EN_PROCESO' => 'sr-section-evidences',
        'PAUSADA' => 'sr-section-timelines',
        'RESUELTA' => 'sr-section-actions',
        'CERRADA', 'CANCELADA', 'RECHAZADA', 'NO_VIABLE' => 'sr-section-description',
        default => 'sr-section-description',
    };
@endphp

<nav id="sr-section-nav"
     class="sr-section-nav"
     aria-label="Navegación por secciones"
     data-recommended="{{ $recommendedSection }}"
     data-status="{{ $status }}">
    <div class="sr-section-nav__inner">
        @foreach ($sections as $section)
            <a href="#{{ $section['id'] }}"
               class="sr-section-nav__item {{ $section['id'] === $recommendedSection ? 'sr-section-nav__item--recommended' : '' }}"
               data-section-target="{{ $section['id'] }}"
               aria-label="Ir a {{ $section['label'] }}"
               title="{{ $section['label'] }}">
                <i class="fas {{ $section['icon'] }} sr-section-nav__icon" aria-hidden="true"></i>
                <span class="sr-section-nav__label">{{ $section['label'] }}</span>
                <span class="sr-section-nav__label--short">{{ $section['short'] }}</span>
                @if ($section['id'] === $recommendedSection)
                    <span class="sr-section-nav__pulse" aria-hidden="true"></span>
                @endif
            </a>
        @endforeach
    </div>
</nav>

@once
@push('styles')
<style>
/* === Section Navigation Bar === */
.sr-section-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    transition: box-shadow 0.2s ease;
}

.sr-section-nav--stuck {
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.sr-section-nav__inner {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 5px 6px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.sr-section-nav__inner::-webkit-scrollbar {
    display: none;
}

/* --- Nav Items --- */
.sr-section-nav__item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
    white-space: nowrap;
    text-decoration: none;
    transition: background 0.15s ease, color 0.15s ease;
    flex-shrink: 0;
}

.sr-section-nav__item:hover {
    background: #f1f5f9;
    color: #334155;
}

.sr-section-nav__item:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Active state */
.sr-section-nav__item--active {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 600;
}

.sr-section-nav__item--active .sr-section-nav__icon {
    color: #3b82f6;
}

/* Recommended state */
.sr-section-nav__item--recommended {
    background: rgba(59, 130, 246, 0.06);
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.15);
}

.sr-section-nav__item--recommended .sr-section-nav__icon {
    color: #2563eb;
}

.sr-section-nav__item--recommended.sr-section-nav__item--active {
    background: rgba(59, 130, 246, 0.12);
    color: #1e3a8a;
    border-color: rgba(59, 130, 246, 0.25);
}

/* Pulse dot for recommended */
.sr-section-nav__pulse {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #3b82f6;
    animation: sr-pulse-dot 2s ease-in-out infinite;
}

@keyframes sr-pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.7); }
}

/* Icon styling */
.sr-section-nav__icon {
    font-size: 0.7rem;
    color: #94a3b8;
    transition: color 0.15s ease;
}

/* Labels */
.sr-section-nav__label--short {
    display: none;
}

@media (max-width: 768px) {
    .sr-section-nav__item {
        padding: 6px 8px;
        font-size: 0.72rem;
        gap: 4px;
    }

    .sr-section-nav__label {
        display: none;
    }

    .sr-section-nav__label--short {
        display: inline;
    }
}

@media (max-width: 480px) {
    .sr-section-nav__item {
        padding: 5px 6px;
        font-size: 0.68rem;
    }

    .sr-section-nav__label--short {
        display: none;
    }
}

/* Dead state */
.sr-dead-state .sr-section-nav__item--recommended {
    background: rgba(100, 116, 139, 0.06);
    color: #475569;
    border-color: rgba(100, 116, 139, 0.15);
}

.sr-dead-state .sr-section-nav__pulse {
    background: #94a3b8;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    var nav = document.getElementById('sr-section-nav');
    if (!nav) return;

    var links = nav.querySelectorAll('[data-section-target]');
    var sectionIds = Array.from(links).map(function(l) { return l.dataset.sectionTarget; });
    var sections = sectionIds.map(function(id) { return document.getElementById(id); }).filter(Boolean);
    var isScrolling = false;
    var scrollTimeout = null;
    var currentActive = null;

    function updateActiveSection() {
        if (isScrolling) return;
        var scrollPos = window.scrollY + 80;
        var active = null;

        for (var i = sections.length - 1; i >= 0; i--) {
            if (sections[i].offsetTop <= scrollPos) {
                active = sectionIds[i];
                break;
            }
        }
        if (!active && sections.length > 0) active = sectionIds[0];

        if (active !== currentActive) {
            currentActive = active;
            links.forEach(function(link) {
                link.classList.toggle('sr-section-nav__item--active', link.dataset.sectionTarget === active);
            });
        }
    }

    links.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.dataset.sectionTarget;
            var targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            // Expand if collapsed
            if (targetEl.classList.contains('sr-collapsible--collapsed')) {
                targetEl.classList.remove('sr-collapsible--collapsed');
                var toggle = targetEl.querySelector('.sr-collapsible__toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.querySelector('span').textContent = 'Colapsar';
                }
                var key = targetEl.dataset.sectionKey;
                if (key) {
                    try {
                        var prefs = JSON.parse(localStorage.getItem('sr-collapsed-sections') || '{}');
                        prefs[key] = 'expanded';
                        localStorage.setItem('sr-collapsed-sections', JSON.stringify(prefs));
                    } catch(ex) {}
                }
            }

            isScrolling = true;
            links.forEach(function(l) { l.classList.remove('sr-section-nav__item--active'); });
            this.classList.add('sr-section-nav__item--active');
            targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                isScrolling = false;
                updateActiveSection();
            }, 800);
        });
    });

    var ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateActiveSection();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    updateActiveSection();

    // Add shadow when nav becomes sticky
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            nav.classList.toggle('sr-section-nav--stuck', !entry.isIntersecting);
        });
    }, { threshold: [1], rootMargin: '-1px 0px 0px 0px' });
    observer.observe(nav);
})();
</script>
@endpush
@endonce
