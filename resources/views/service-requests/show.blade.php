@extends('layouts.app')

@section('title', "Solicitud {$serviceRequest->ticket_number}")
@section('disableGlobalFlash', '1')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li class="inline-flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-700">Solicitudes de
                    Servicio</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitud {{ $serviceRequest->ticket_number }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')

    @php
        $isDeadState = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
        if (!isset($previousRequestNav)) {
            $previousRequestNav = \App\Models\ServiceRequest::where('id', '<', $serviceRequest->id)
                ->orderBy('id', 'desc')
                ->first();
        }
        if (!isset($nextRequestNav)) {
            $nextRequestNav = \App\Models\ServiceRequest::where('id', '>', $serviceRequest->id)->orderBy('id')->first();
        }
    @endphp

    <div class="sr-view space-y-4 sm:space-y-6 {{ $isDeadState ? 'sr-dead-state' : '' }}">
        <div class="flex items-center justify-between gap-2 rounded-md border {{ $isDeadState ? 'border-slate-200 bg-slate-50 text-slate-700' : 'border-slate-100 bg-white text-slate-600' }} px-3 py-1.5 text-xs"
            id="requestNavigation"
            data-prev-url="{{ $previousRequestNav ? route('service-requests.show', $previousRequestNav) : '' }}"
            data-next-url="{{ $nextRequestNav ? route('service-requests.show', $nextRequestNav) : '' }}" role="navigation"
            aria-label="Navegación entre solicitudes">
            @if ($previousRequestNav)
                <a href="{{ route('service-requests.show', $previousRequestNav) }}"
                    rel="prev"
                    class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-slate-700 hover:bg-white hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-200"
                    title="Ir a la solicitud {{ $previousRequestNav->ticket_number }}"
                    aria-label="Ver solicitud anterior {{ $previousRequestNav->ticket_number }}">
                    <i class="fas fa-arrow-left text-[10px]" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Anterior</span>
                    <span class="font-medium">{{ $previousRequestNav->ticket_number }}</span>
                </a>
            @else
                <span class="inline-flex items-center gap-2 px-2 py-1 text-slate-400" aria-disabled="true">
                    <i class="fas fa-arrow-left text-[10px]" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Anterior</span>
                </span>
            @endif

            @if ($nextRequestNav)
                <a href="{{ route('service-requests.show', $nextRequestNav) }}"
                    rel="next"
                    class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-slate-700 hover:bg-white hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-200"
                    title="Ir a la solicitud {{ $nextRequestNav->ticket_number }}"
                    aria-label="Ver siguiente solicitud {{ $nextRequestNav->ticket_number }}">
                    <span class="font-medium">{{ $nextRequestNav->ticket_number }}</span>
                    <span class="hidden sm:inline">Siguiente</span>
                    <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                </a>
            @else
                <span class="inline-flex items-center gap-2 px-2 py-1 text-slate-400" aria-disabled="true">
                    <span class="hidden sm:inline">Siguiente</span>
                    <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                </span>
            @endif
        </div>

        <!-- Header Principal con botón de edición -->
        <x-service-requests.show.header.main-header :serviceRequest="$serviceRequest" :technicians="$technicians" />

        <!-- Checklist de requisitos para siguiente paso -->
        <x-service-requests.show.next-step-checklist :serviceRequest="$serviceRequest" />

        <!-- Parent Request Link (if this is a derived request) -->
        @if($parentRequest)
            <div class="flex items-center gap-2 px-4 py-3 bg-violet-50 border border-violet-200 rounded-lg text-sm">
                <i class="fas fa-level-up-alt text-violet-500 text-xs"></i>
                <span class="text-gray-600">Solicitud padre:</span>
                <a href="{{ route('service-requests.show', $parentRequest->id) }}"
                   class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                    {{ $parentRequest->ticket_number }}
                </a>
                @if($parentRequest->title)
                    <span class="text-gray-500">— {{ Str::limit($parentRequest->title, 50) }}</span>
                @endif
            </div>
        @endif

        <!-- Navegación contextual por secciones + controles -->
        <div class="flex items-center justify-between gap-3">
            <x-service-requests.show.section-nav :serviceRequest="$serviceRequest" />
        </div>

        @php
            // Define priority sections per status (these stay expanded)
            $prioritySections = match($serviceRequest->status) {
                'PENDIENTE' => ['description', 'service-info'],
                'ACEPTADA' => ['description', 'tasks', 'service-info'],
                'EN_PROCESO' => ['evidences', 'tasks', 'timelines'],
                'PAUSADA' => ['timelines', 'description'],
                'RESUELTA' => ['actions', 'evidences', 'description'],
                'CERRADA', 'CANCELADA', 'RECHAZADA' => ['description', 'service-info', 'timelines'],
                'REABIERTO' => ['description', 'tasks'],
                default => ['description', 'service-info'],
            };
        @endphp

        <!-- Descripción del Problema (Lo más importante primero) -->
        <section id="sr-section-description"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="description"
                 data-priority="{{ in_array('description', $prioritySections) ? '1' : '0' }}">
            <x-service-requests.show.content.description-panel :serviceRequest="$serviceRequest" />
        </section>

        <!-- Información Clave en 2 columnas -->
        <section id="sr-section-service-info"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="service-info"
                 data-priority="{{ in_array('service-info', $prioritySections) ? '1' : '0' }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <x-service-requests.show.info-cards.service-info :serviceRequest="$serviceRequest" />
                <div class="space-y-4">
                    <x-service-requests.show.info-cards.assignment-info :serviceRequest="$serviceRequest" />
                    <x-service-requests.show.info-cards.project-link :serviceRequest="$serviceRequest" />
                </div>
            </div>
        </section>

        <!-- Tiempos y SLA -->
        <section id="sr-section-timelines"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="timelines"
                 data-priority="{{ in_array('timelines', $prioritySections) ? '1' : '0' }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <x-service-requests.show.info-cards.timelines-info :serviceRequest="$serviceRequest" />
                <x-service-requests.show.info-cards.sla-info :serviceRequest="$serviceRequest" />
            </div>
        </section>

        <!-- Sistema de Evidencias -->
        <section id="sr-section-evidences"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="evidences"
                 data-priority="{{ in_array('evidences', $prioritySections) ? '1' : '0' }}">
            <x-service-requests.show.evidences.evidence-gallery :serviceRequest="$serviceRequest" />
        </section>

        <!-- Tareas Asociadas -->
        <section id="sr-section-tasks"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="tasks"
                 data-priority="{{ in_array('tasks', $prioritySections) ? '1' : '0' }}">
            <x-service-requests.show.content.tasks-panel :serviceRequest="$serviceRequest" />
        </section>

        <!-- Meeting Sections (only for type "reunion") -->
        @if($serviceRequest->requestType && $serviceRequest->requestType->slug === 'reunion' && $meetingDetail)
            <section class="sr-section sr-collapsible scroll-mt-16"
                     data-section-key="meeting"
                     data-priority="{{ in_array('meeting', $prioritySections ?? []) ? '1' : '0' }}">
                @include('service-requests.partials._meeting-details-show')
                @include('service-requests.partials._meeting-participants')
                @include('service-requests.partials._meeting-commitments')
            </section>
        @endif

        <!-- Traceability Chain (if request has parent or children) -->
        @if($traceabilityChain)
            <section class="sr-section sr-collapsible scroll-mt-16"
                     data-section-key="traceability"
                     data-priority="0">
                @include('service-requests.partials._traceability-chain')
            </section>
        @endif

        <!-- Derived Requests (child requests) -->
        @if($childRequests->isNotEmpty() || ($serviceRequest->requestType && $serviceRequest->requestType->slug !== null))
            <section class="sr-section sr-collapsible scroll-mt-16"
                     data-section-key="derived"
                     data-priority="0">
                @include('service-requests.partials._derive-request')
            </section>
        @endif

        <!-- Assignment History -->
        @if($assignmentHistory->isNotEmpty())
            <section class="sr-section sr-collapsible scroll-mt-16"
                     data-section-key="assignment-history"
                     data-priority="0">
                @include('service-requests.partials._assignment-history')
            </section>
        @endif

        <!-- Panel de Rutas Web (solo si existen) -->
        @if ($serviceRequest->hasWebRoutes())
            <section class="sr-section sr-collapsible scroll-mt-16"
                     data-section-key="web-routes"
                     data-priority="0">
                <x-service-requests.show.content.web-routes-panel :serviceRequest="$serviceRequest" />
            </section>
        @endif

        <!-- Seguimiento y Notas (Bitácora operativa) -->
        <section id="sr-section-system-notes" class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="system-notes"
                 data-priority="1">
            <x-service-requests.show.evidences.system-notes :serviceRequest="$serviceRequest" />
        </section>

        <!-- Historial y Timeline (Al final, información histórica) -->
        {{-- <x-service-requests.show.history.history-timeline :serviceRequest="$serviceRequest" /> --}}


        <!-- Acciones Disponibles (Segundo en importancia) -->
        <section id="sr-section-actions"
                 class="sr-section sr-collapsible scroll-mt-16"
                 data-section-key="actions"
                 data-priority="{{ in_array('actions', $prioritySections ?? []) ? '1' : '0' }}">
            <x-service-requests.show.content.actions-panel :serviceRequest="$serviceRequest" />
        </section>
    </div>

    <!-- Accesibilidad: anuncios y feedback sin recargar -->
    <div id="srLiveRegion" class="sr-only" aria-live="polite" aria-atomic="true"></div>
    <div id="srToast" class="fixed top-20 right-4 z-50 hidden" role="status" aria-live="polite" aria-atomic="true"></div>
    <button id="backToTopButton"
        type="button"
        class="fixed bottom-4 left-4 z-50 hidden h-11 w-11 rounded-full bg-red-600 text-white shadow-lg transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        title="Volver arriba"
        aria-label="Volver a la parte superior">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>

    <!-- Menú contextual (clic derecho) -->
    <x-service-requests.show.context-menu :serviceRequest="$serviceRequest" :technicians="$technicians" />

    <!-- Modales de workflow (fuera del header para posicionamiento correcto) -->
    @if(in_array($serviceRequest->status, ['RESUELTA', 'CERRADA']))
        @include('components.service-requests.show.header.reopen-modal', ['serviceRequest' => $serviceRequest])
        @include('components.service-requests.show.header.close-modal', ['serviceRequest' => $serviceRequest])
    @endif

    <!-- Modal de asociar a proyecto -->
    @if(!$serviceRequest->project_id)
        @php
            $modalProjects = \App\Models\Project::where('company_id', (int) session('current_company_id'))
                ->active()->orderBy('name')->get(['id', 'name', 'code']);
        @endphp
        @if($modalProjects->isNotEmpty())
        <div id="link-project-modal-{{ $serviceRequest->id }}"
             class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
             role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1">
            <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Asociar a proyecto</h3>
                    <button type="button" onclick="closeModal('link-project-modal-{{ $serviceRequest->id }}')"
                            class="text-gray-400 hover:text-gray-600" aria-label="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" id="linkProjectModalForm">
                    @csrf
                    <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">
                    <div class="mb-4">
                        <select name="project_id" id="modalProjectSelector" required
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400">
                            <option value="">Seleccionar proyecto...</option>
                            @foreach($modalProjects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-between items-center">
                        <a href="{{ route('projects.create') }}" class="text-xs text-gray-500 hover:text-red-600">
                            <i class="fas fa-plus mr-1"></i>Crear proyecto
                        </a>
                        <button type="submit"
                                onclick="var sel = document.getElementById('modalProjectSelector'); if(sel.value) { this.closest('form').action = '/projects/' + sel.value + '/link-request'; return true; } return false;"
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                            Vincular
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endif

    <!-- Modal de crear recordatorio -->
    <div id="reminder-modal-{{ $serviceRequest->id }}"
         class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
         role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-900">
                    <i class="fas fa-bell text-red-500 mr-1.5"></i>Crear recordatorio
                </h3>
                <button type="button" onclick="closeModal('reminder-modal-{{ $serviceRequest->id }}')"
                        class="text-gray-400 hover:text-gray-600" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('operational-alerts.reminder.store') }}" method="POST">
                @csrf
                <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">¿Cuándo recordar?</label>
                    <input type="date" name="reminder_date" required min="{{ now()->format('Y-m-d') }}"
                           value="{{ now()->addDay()->format('Y-m-d') }}"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                    <textarea name="reminder_note" rows="2" required minlength="3" maxlength="500"
                              class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 resize-none"
                              placeholder="Ej: Verificar con Laura si publicó el archivo"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                        Programar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de vista previa para evidencias -->
    <x-service-requests.show.evidences.evidence-preview />
@endsection

@push('styles')
<style>
/* === Collapsible Sections === */
.sr-collapsible {
    position: relative;
}

.sr-collapsible__toggle {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    padding: 2px 10px;
    margin-bottom: 4px;
    margin-left: auto;
    width: fit-content;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: rgba(255, 255, 255, 0.9);
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sr-collapsible__toggle:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #334155;
}

.sr-collapsible__toggle:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.sr-collapsible__toggle-icon {
    font-size: 0.6rem;
    transition: transform 0.3s ease;
}

.sr-collapsible--collapsed .sr-collapsible__toggle-icon {
    transform: rotate(-90deg);
}

/* Collapsed state */
.sr-collapsible__content {
    transition: max-height 0.35s ease, opacity 0.25s ease;
    overflow: hidden;
    max-height: 2000px;
    opacity: 1;
}

.sr-collapsible--collapsed .sr-collapsible__content {
    max-height: 0;
    opacity: 0;
}

/* Collapsed placeholder */
.sr-collapsible__placeholder {
    display: none;
    padding: 12px 16px;
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    border-radius: 12px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sr-collapsible__placeholder:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #64748b;
}

.sr-collapsible--collapsed .sr-collapsible__placeholder {
    display: block;
}

/* Expand all / Collapse all button */
.sr-collapse-controls {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-bottom: 4px;
}

.sr-collapse-controls__btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sr-collapse-controls__btn:hover {
    background: #f1f5f9;
    color: #334155;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    var STORAGE_KEY = 'sr-collapsed-sections';
    var sections = document.querySelectorAll('.sr-collapsible');
    if (!sections.length) return;

    // Section labels for placeholder text
    var sectionLabels = {
        'description': 'Descripción',
        'service-info': 'Información del Servicio',
        'timelines': 'Tiempos y SLA',
        'evidences': 'Evidencias',
        'tasks': 'Tareas',
        'actions': 'Acciones',
        'meeting': 'Reunión',
        'traceability': 'Cadena de Trazabilidad',
        'derived': 'Solicitudes Derivadas',
        'assignment-history': 'Historial de Asignación',
        'web-routes': 'Rutas Web',
        'system-notes': 'Seguimiento y Notas'
    };

    // Load user preferences from localStorage
    function getPreferences() {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            return stored ? JSON.parse(stored) : {};
        } catch(e) { return {}; }
    }

    function savePreferences(prefs) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs)); } catch(e) {}
    }

    var prefs = getPreferences();

    sections.forEach(function(section) {
        var key = section.dataset.sectionKey;
        var isPriority = section.dataset.priority === '1';
        if (!key) return;

        // Wrap existing content in a collapsible container
        var content = document.createElement('div');
        content.className = 'sr-collapsible__content';
        while (section.firstChild) {
            content.appendChild(section.firstChild);
        }
        section.appendChild(content);

        // Add toggle button
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'sr-collapsible__toggle';
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Colapsar sección');
        toggle.innerHTML = '<i class="fas fa-chevron-down sr-collapsible__toggle-icon" aria-hidden="true"></i><span>Colapsar</span>';
        section.insertBefore(toggle, content);

        // Add placeholder (visible when collapsed)
        var placeholder = document.createElement('div');
        placeholder.className = 'sr-collapsible__placeholder';
        placeholder.innerHTML = '<i class="fas fa-chevron-right" style="margin-right:6px"></i>' + (sectionLabels[key] || key) + ' <span style="opacity:0.7">(click para expandir)</span>';
        section.appendChild(placeholder);

        // Determine initial state
        var shouldCollapse = false;
        if (prefs.hasOwnProperty(key)) {
            // User has explicit preference
            shouldCollapse = prefs[key] === 'collapsed';
        } else {
            // Auto-collapse non-priority sections
            shouldCollapse = !isPriority;
        }

        function collapse() {
            section.classList.add('sr-collapsible--collapsed');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Expandir sección');
            toggle.querySelector('span').textContent = 'Expandir';
        }

        function expand() {
            section.classList.remove('sr-collapsible--collapsed');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Colapsar sección');
            toggle.querySelector('span').textContent = 'Colapsar';
        }

        function toggleSection() {
            var isCollapsed = section.classList.contains('sr-collapsible--collapsed');
            if (isCollapsed) {
                expand();
                prefs[key] = 'expanded';
            } else {
                collapse();
                prefs[key] = 'collapsed';
            }
            savePreferences(prefs);
        }

        // Set initial state
        if (shouldCollapse) {
            collapse();
        }

        // Event listeners
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSection();
        });

        placeholder.addEventListener('click', function() {
            expand();
            prefs[key] = 'expanded';
            savePreferences(prefs);
        });
    });

    // Add expand/collapse all controls
    var navComponent = document.getElementById('sr-section-nav');
    if (navComponent) {
        var controls = document.createElement('div');
        controls.className = 'sr-collapse-controls';
        controls.innerHTML =
            '<button type="button" class="sr-collapse-controls__btn" data-action="expand-all"><i class="fas fa-expand-alt" style="font-size:0.6rem"></i> Expandir todo</button>' +
            '<button type="button" class="sr-collapse-controls__btn" data-action="collapse-all"><i class="fas fa-compress-alt" style="font-size:0.6rem"></i> Colapsar todo</button>';

        navComponent.parentNode.insertBefore(controls, navComponent.nextSibling);

        controls.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) return;

            var action = btn.dataset.action;
            sections.forEach(function(section) {
                var key = section.dataset.sectionKey;
                if (!key) return;

                if (action === 'expand-all') {
                    section.classList.remove('sr-collapsible--collapsed');
                    var t = section.querySelector('.sr-collapsible__toggle');
                    if (t) {
                        t.setAttribute('aria-expanded', 'true');
                        t.querySelector('span').textContent = 'Colapsar';
                    }
                    prefs[key] = 'expanded';
                } else {
                    section.classList.add('sr-collapsible--collapsed');
                    var t = section.querySelector('.sr-collapsible__toggle');
                    if (t) {
                        t.setAttribute('aria-expanded', 'false');
                        t.querySelector('span').textContent = 'Expandir';
                    }
                    prefs[key] = 'collapsed';
                }
            });
            savePreferences(prefs);
        });
    }
})();
</script>
@endpush

@push('scripts')
<script>
(function(){
    var liveRegion = document.getElementById('srLiveRegion');
    var toastEl = document.getElementById('srToast');
    var returnFocus = new Map();

    function announce(message) {
        if (!liveRegion) return;
        liveRegion.textContent = '';
        setTimeout(function(){ liveRegion.textContent = message || ''; }, 20);
    }

    function toast(message, type) {
        if (!toastEl) return;
        toastEl.textContent = message || '';
        toastEl.className = 'fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm ' +
            ((type === 'error') ? 'bg-red-600' : (type === 'info') ? 'bg-blue-600' : 'bg-green-600');
        toastEl.classList.remove('hidden');
        setTimeout(function(){ toastEl.classList.add('hidden'); }, 5000);
    }

    var backToTopButton = document.getElementById('backToTopButton');
    function handleBackToTopVisibility() {
        if (!backToTopButton) return;
        if (window.scrollY > 300) {
            backToTopButton.classList.remove('hidden');
        } else {
            backToTopButton.classList.add('hidden');
        }
    }

    if (backToTopButton) {
        window.addEventListener('scroll', handleBackToTopVisibility, { passive: true });
        handleBackToTopVisibility();
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            announce('Volviste al inicio de la página');
        });
    }

    if (typeof window.showCopyNotification !== 'function') {
        window.showCopyNotification = function(ticketNumber, success, options) {
            if (success) {
                toast((options && options.successMessage) ? options.successMessage : ('Ticket ' + ticketNumber + ' copiado'), 'success');
                announce((options && options.successTitle) ? options.successTitle : 'Número copiado');
                return;
            }
            toast((options && options.errorMessage) ? options.errorMessage : 'No se pudo copiar el número de ticket', 'error');
            announce((options && options.errorTitle) ? options.errorTitle : 'No se pudo copiar');
        };
    }

    var currentUrl = new URL(window.location.href);
    var initialInfoMessage = @json(session('info'));
    if (initialInfoMessage) {
        announce(initialInfoMessage);
        toast(initialInfoMessage, 'info');
    }
    var initialSuccessMessage = @json(session('success'));
    if (initialSuccessMessage) {
        announce(initialSuccessMessage);
        toast(initialSuccessMessage, 'success');
    } else if (currentUrl.searchParams.get('updated') === '1') {
        announce('Solicitud de servicio actualizada exitosamente.');
        toast('Solicitud de servicio actualizada exitosamente.', 'success');
    }
    if (currentUrl.searchParams.get('updated') === '1') {
        currentUrl.searchParams.delete('updated');
        var cleanQuery = currentUrl.searchParams.toString();
        var cleanUrl = currentUrl.pathname + (cleanQuery ? ('?' + cleanQuery) : '') + currentUrl.hash;
        window.history.replaceState({}, document.title, cleanUrl);
    }

    function getFocusable(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'))
            .filter(function(n){ return !n.disabled && n.offsetParent !== null; });
    }

    function bindModal(modal) {
        if (!modal || modal.dataset.srBound) return;
        modal.dataset.srBound = '1';

        modal.addEventListener('click', function(e){
            if (e.target === modal) window.closeModal(modal.id);
        });

        modal.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                e.preventDefault();
                window.closeModal(modal.id);
                return;
            }
            if (e.key === 'Tab') {
                var focusables = getFocusable(modal);
                if (focusables.length === 0) return;
                var first = focusables[0];
                var last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    }

    window.openModal = function(modalId, triggerEl) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        bindModal(modal);
        returnFocus.set(modalId, triggerEl || document.activeElement);

        // Para el modal de resolución: generar contenido primero, mostrar después
        if (modalId.startsWith('resolve-modal-') && typeof initResolveModal === 'function') {
            var srId = modalId.replace('resolve-modal-', '');
            showResolveLoader(srId);
            initResolveModal(srId, function() {
                hideResolveLoader(srId);
                revealModal(modal, modalId);
            });
            return;
        }

        revealModal(modal, modalId);
    };

    function revealModal(modal, modalId) {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        if (!modal.hasAttribute('tabindex')) modal.setAttribute('tabindex', '-1');
        if (!modal.hasAttribute('role')) modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        document.body.classList.add('overflow-hidden');

        // Auto-resize textareas que fueron llenadas mientras el modal estaba oculto
        modal.querySelectorAll('textarea').forEach(function(ta) {
            if (ta.value) {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            }
        });

        var focusables = getFocusable(modal);
        setTimeout(function(){
            if (focusables.length > 0) focusables[0].focus();
            else modal.focus();
        }, 0);
    }

    function showResolveLoader(srId) {
        var existing = document.getElementById('resolve-loader-' + srId);
        if (existing) { existing.classList.remove('hidden'); return; }

        var loader = document.createElement('div');
        loader.id = 'resolve-loader-' + srId;
        loader.className = 'fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50';
        loader.innerHTML = '<div class="bg-white rounded-lg shadow-xl px-8 py-6 flex flex-col items-center gap-3">'
            + '<i class="fas fa-spinner fa-spin text-2xl text-green-600"></i>'
            + '<p class="text-sm font-medium text-gray-700">Preparando resolución...</p>'
            + '<p class="text-xs text-gray-400">Generando descripción de resolución</p>'
            + '</div>';
        document.body.appendChild(loader);
        document.body.classList.add('overflow-hidden');
    }

    function hideResolveLoader(srId) {
        var loader = document.getElementById('resolve-loader-' + srId);
        if (loader) loader.remove();
    }

    window.closeModal = function(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        var el = returnFocus.get(modalId);
        returnFocus.delete(modalId);
        if (el && typeof el.focus === 'function') {
            setTimeout(function(){ el.focus(); }, 0);
        }
    };

    window.srNotify = function(success, message) {
        if (!message) return;
        announce(message);
        toast(message, success ? 'success' : 'error');
    };

    // Mejorar formularios AJAX existentes (si retornan JSON con message)
    document.addEventListener('submit', function(e){
        var form = e.target;
        if (!form || !form.matches || !form.matches('form[data-sr-ajax="1"]')) return;
        e.preventDefault();
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        form.setAttribute('aria-busy','true');
        fetch(form.action, {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
            body: new FormData(form)
        })
            .then(function(r){ return r.json().catch(function(){ return null; }).then(function(data){ return { ok:r.ok, data:data, status:r.status }; }); })
            .then(function(res){
                if (res.ok && res.data && typeof res.data.message === 'string') srNotify(true, res.data.message);
                else if (!res.ok) srNotify(false, (res.data && res.data.message) || 'No se pudo completar la acción.');
            })
            .catch(function(){ srNotify(false, 'No se pudo completar la acción.'); })
            .finally(function(){
                if (submitBtn) submitBtn.disabled = false;
                form.removeAttribute('aria-busy');
            });
    }, true);
})();
</script>
@endpush

@push('styles')
    <style>
        .sr-view h1,
        .sr-view h2,
        .sr-view h3,
        .sr-view h4 {
            font-weight: 600 !important;
        }

        .nav-direction {
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            border-radius: 9999px;
            padding: 0.65rem 1rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 10px 25px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .nav-direction:hover,
        .nav-direction:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 15px 30px rgba(59, 130, 246, 0.2);
        }

        .nav-direction__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.25));
            color: #1d4ed8;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.65);
        }

        .nav-direction__content {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.2;
        }

        .nav-direction__eyebrow {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .nav-direction__ticket {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
        }

        .nav-direction--reverse {
            flex-direction: row-reverse;
        }

        .nav-direction--reverse .nav-direction__content {
            text-align: right;
        }

        .nav-direction--disabled {
            color: #94a3b8;
            border-color: rgba(148, 163, 184, 0.35);
            background: rgba(248, 250, 252, 0.9);
            box-shadow: inset 0 0 0 rgba(255, 255, 255, 0);
        }

        .nav-pill-current {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            min-width: 180px;
            padding: 0.6rem 1rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 6px 12px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .nav-pill-current__eyebrow {
            font-size: 0.6rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .nav-pill-current__ticket {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        @media (max-width: 640px) {
            #requestNavigation {
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .nav-direction {
                padding: 0.5rem 0.75rem;
                border-radius: 1.25rem;
                gap: 0.5rem;
                min-height: 3.25rem;
            }

            .nav-direction__icon {
                width: 2rem;
                height: 2rem;
            }

            .nav-direction__eyebrow {
                font-size: 0.6rem;
            }

            .nav-direction__ticket {
                font-size: 0.85rem;
            }

            .nav-pill-current {
                width: 100%;
                min-width: 0;
                padding: 0.55rem 0.85rem;
            }

            .nav-pill-current__ticket {
                font-size: 0.9rem;
            }
        }

        .sr-dead-state {
            filter: grayscale(1);
        }

        .sr-dead-state a,
        .sr-dead-state button,
        .sr-dead-state i,
        .sr-dead-state svg {
            filter: grayscale(1) saturate(0) !important;
        }

        .sr-dead-state a:hover,
        .sr-dead-state button:hover {
            background-image: none !important;
        }

        /* Timeline Styles */
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 1.5rem;
            width: 1rem;
            height: 2px;
            background: #e5e7eb;
        }

        .group:hover .timeline-dot {
            transform: scale(1.1);
            transition: transform 0.2s ease-in-out;
        }

        /* Smooth transitions for timeline */
        .timeline-enter {
            opacity: 0;
            transform: translateX(-20px);
        }

        .timeline-enter-active {
            opacity: 1;
            transform: translateX(0);
            transition: opacity 0.3s, transform 0.3s;
        }

        /* Estilos para evidencias */
        .evidence-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
        }

        .evidence-preview:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }

        @media (max-width: 768px) {
            .timeline-item::before {
                left: -1.5rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Service Request Show page loaded - Evidences system ready');

            // Abrir automáticamente el modal indicado por ?action= (desde el menú contextual del Centro de Gestión)
            (function openActionFromQuery() {
                const params = new URLSearchParams(window.location.search);
                const action = params.get('action');
                if (!action) return;

                const srId = @json($serviceRequest->id);
                const status = @json($serviceRequest->status);

                // Mapear cada acción a su modal, respetando el estado válido
                const actionModals = {
                    resolve: { modal: 'resolve-modal-' + srId, validStatus: ['EN_PROCESO'] },
                    pause:   { modal: 'pause-modal-' + srId,   validStatus: ['EN_PROCESO'] },
                    reject:  { modal: 'reject-modal-' + srId,  validStatus: ['PENDIENTE'] },
                    reopen:  { modal: 'reopen-modal-' + srId,  validStatus: ['RESUELTA','CERRADA'] },
                };

                // "Cerrar" usa modal distinto según el estado: RESUELTA → close, PAUSADA → vencimiento
                if (action === 'close') {
                    actionModals.close = status === 'PAUSADA'
                        ? { modal: 'vencimiento-modal-' + srId, validStatus: ['PAUSADA'] }
                        : { modal: 'close-modal-' + srId, validStatus: ['RESUELTA'] };
                }

                const cfg = actionModals[action];
                if (!cfg || !cfg.validStatus.includes(status)) return;

                // Esperar a que openModal y el DOM del modal estén disponibles
                setTimeout(() => {
                    const modalEl = document.getElementById(cfg.modal);
                    if (modalEl && typeof openModal === 'function') {
                        openModal(cfg.modal);
                        // Limpiar el query param para no re-abrir al recargar
                        params.delete('action');
                        const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                        window.history.replaceState({}, '', clean);
                    }
                }, 300);
            })();

            const evidenceCount = @json($serviceRequest->evidences?->count() ?? 0);
            if (evidenceCount > 0) {
                console.log('Evidencias cargadas:', evidenceCount);
            } else {
                console.log('No hay evidencias para esta solicitud');
            }

            // Script para manejar errores de carga de imágenes
            document.addEventListener('error', function(e) {
                if (e.target.tagName === 'IMG' && e.target.classList.contains('evidence-image')) {
                    console.warn('Error cargando imagen de evidencia:', e.target.src);
                    e.target.style.display = 'none';
                    // Mostrar placeholder de error
                    const parent = e.target.parentElement;
                    if (parent) {
                        parent.innerHTML = `
                        <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 text-center">Error cargando imagen</p>
                    `;
                    }
                }
            }, true);

            setupNavigationInteractions();
        });

        function setupNavigationInteractions() {
            const navContainer = document.getElementById('requestNavigation');
            if (!navContainer) {
                return;
            }

            const prevUrl = navContainer.dataset.prevUrl;
            const nextUrl = navContainer.dataset.nextUrl;

            function goTo(url) {
                if (url) {
                    window.location.href = url;
                }
            }

            document.addEventListener('keydown', function(e) {
                if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    return;
                }
                if (e.key === 'ArrowLeft') {
                    goTo(prevUrl);
                }
                if (e.key === 'ArrowRight') {
                    goTo(nextUrl);
                }
            });

            let touchStartX = null;
            let touchStartY = null;

            navContainer.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                }
            }, {
                passive: true
            });

            navContainer.addEventListener('touchend', function(e) {
                if (touchStartX === null || touchStartY === null) {
                    return;
                }

                const touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const diffX = touchEndX - touchStartX;
                const diffY = Math.abs(touchEndY - touchStartY);

                if (Math.abs(diffX) > 60 && diffY < 40) {
                    if (diffX > 0) {
                        goTo(prevUrl);
                    } else {
                        goTo(nextUrl);
                    }
                }

                touchStartX = null;
                touchStartY = null;
            }, {
                passive: true
            });
        }

        // ✅ FUNCIONES GLOBALES para el modal de vista previa
        function openPreview(fileUrl, fileName) {
            const modal = document.getElementById('previewModal');
            const image = document.getElementById('previewImage');
            const title = document.getElementById('previewTitle');
            const info = document.getElementById('previewInfo');
            const downloadLink = document.getElementById('previewDownload');

            // Mostrar loader mientras carga
            image.style.display = 'none';
            modal.classList.remove('hidden');

            const tempImage = new Image();
            tempImage.onload = function() {
                image.src = fileUrl;
                image.style.display = 'block';
                title.textContent = fileName;
                info.textContent = `Vista previa de ${fileName}`;
                downloadLink.href = fileUrl;
                downloadLink.download = fileName;
            };

            tempImage.onerror = function() {
                image.style.display = 'none';
                title.textContent = 'Error';
                info.textContent = 'No se pudo cargar la imagen';
                downloadLink.style.display = 'none';
            };

            tempImage.src = fileUrl;
            document.body.style.overflow = 'hidden';
        }

        function closePreview() {
            const modal = document.getElementById('previewModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });

        // Cerrar modal haciendo click fuera
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('previewModal');
            if (e.target === modal) {
                closePreview();
            }
        });

        // Función para copiar link público al portapapeles
        function copyPublicLink(url, ticketNumber) {
            // Intentar usar la API moderna del portapapeles
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function() {
                    showCopyNotification(ticketNumber, true);
                }).catch(function(err) {
                    // Fallback si falla
                    copyToClipboardFallback(url, ticketNumber);
                });
            } else {
                // Fallback para navegadores antiguos
                copyToClipboardFallback(url, ticketNumber);
            }
        }

        // Método alternativo para copiar
        function copyToClipboardFallback(text, ticketNumber) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                showCopyNotification(ticketNumber, successful);
            } catch (err) {
                showCopyNotification(ticketNumber, false);
            }

            document.body.removeChild(textArea);
        }

        function showCopyNotification(ticketNumber, success, options = {}) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 ${
                success ? 'bg-green-500' : 'bg-red-500'
            } text-white`;

            const defaultSuccessTitle = '¡Link copiado!';
            const defaultErrorTitle = 'Error al copiar';
            const defaultSuccessMessage = 'El link público del ticket ' + ticketNumber + ' está en tu portapapeles';
            const defaultErrorMessage = 'Por favor, copia el link manualmente';

            const successTitle = options.successTitle || defaultSuccessTitle;
            const errorTitle = options.errorTitle || defaultErrorTitle;
            const successMessage = options.successMessage || defaultSuccessMessage;
            const errorMessage = options.errorMessage || defaultErrorMessage;

            const titleText = success ? successTitle : errorTitle;
            const bodyText = success ? successMessage : errorMessage;

            notification.innerHTML = `
                <i class="fas ${success ? 'fa-check-circle' : 'fa-exclamation-circle'} text-xl"></i>
                <div>
                    <div class="font-semibold">${titleText}</div>
                    <div class="text-sm opacity-90">${bodyText}</div>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        function copyTicketNumber(ticketNumber, button) {
            if (!ticketNumber) {
                return;
            }

            const iconElement = button ? button.querySelector('i') : null;
            const defaultIconClass = button ? (button.getAttribute('data-default-icon') || 'fa-copy') : 'fa-copy';
            const successIconClass = button ? (button.getAttribute('data-success-icon') || 'fa-check') : 'fa-check';

            const showButtonFeedback = () => {
                if (!button) {
                    return;
                }

                button.classList.add('bg-white/40');
                button.setAttribute('aria-label', 'Número copiado');
                if (iconElement) {
                    iconElement.classList.remove(defaultIconClass);
                    iconElement.classList.add(successIconClass);
                }

                setTimeout(() => {
                    button.classList.remove('bg-white/40');
                    button.setAttribute('aria-label', 'Copiar número de ticket');
                    if (iconElement) {
                        iconElement.classList.remove(successIconClass);
                        iconElement.classList.add(defaultIconClass);
                    }
                }, 1500);
            };

            const onCopySuccess = () => {
                showButtonFeedback();
                showCopyNotification(ticketNumber, true, {
                    successTitle: 'Número copiado',
                    successMessage: 'Número de ticket ' + ticketNumber + ' copiado al portapapeles',
                });
            };

            const onCopyFailure = () => {
                showCopyNotification(ticketNumber, false, {
                    errorTitle: 'No se pudo copiar',
                    errorMessage: 'No se pudo copiar el número de ticket. Por favor, cópialo manualmente.',
                });
            };

            const fallbackCopy = () => {
                const textArea = document.createElement('textarea');
                textArea.value = ticketNumber;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        onCopySuccess();
                    } else {
                        onCopyFailure();
                    }
                } catch (err) {
                    onCopyFailure();
                }

                document.body.removeChild(textArea);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(ticketNumber)
                    .then(onCopySuccess)
                    .catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
        }

        // Función para generar notas de resolución en tercera persona
        function generateThirdPersonNotes(serviceRequestId) {
            const textarea = document.querySelector('#resolution_notes_' + serviceRequestId);
            if (!textarea) {
                alert('No se encontró el campo de notas de resolución.');
                return;
            }

            textarea.value = 'Generando...';
            textarea.disabled = true;

            fetch('/service-requests/' + serviceRequestId + '/generate-resolution-third-person', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = data.resolution_text;
                    alert('Observaciones generadas exitosamente.');
                } else {
                    textarea.value = '';
                    textarea.disabled = false;
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                textarea.value = '';
                textarea.disabled = false;
                alert('Error de conexión. Intente nuevamente.');
                console.error('Error:', error);
            });
        }
    </script>
@endpush
