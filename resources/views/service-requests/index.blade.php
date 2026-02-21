@extends('layouts.app')

@section('title', 'Solicitudes de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitudes de Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <!-- Header Principal -->
    <x-service-requests.index.header.main-header />

    <div class="space-y-3 md:space-y-6" id="resultsContainer">
        @if(($slaAlerts['overdue'] ?? 0) > 0 || ($slaAlerts['dueSoon'] ?? 0) > 0)
            <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-amber-800 text-sm font-semibold">
                    <i class="fas fa-bell"></i>
                    Alertas SLA
                </div>
                @if(($slaAlerts['overdue'] ?? 0) > 0)
                    <a href="{{ route('service-requests.index', array_merge(request()->except('page'), ['open' => 1])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ $slaAlerts['overdue'] }} vencidas
                    </a>
                @endif
                @if(($slaAlerts['dueSoon'] ?? 0) > 0)
                    <a href="{{ route('service-requests.index', array_merge(request()->except('page'), ['open' => 1])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                        <i class="fas fa-clock"></i>
                        {{ $slaAlerts['dueSoon'] }} por vencer (24h)
                    </a>
                @endif
            </div>
        @endif

        <!-- Fila 1: Estadísticas y Acción Principal -->
        <!-- En móvil: layout compacto, tablet: 2-3 cols, desktop: 6 cols -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">

            <!-- Tarjeta 1: Críticas -->
            <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />

            <!-- Tarjeta 2: En curso -->
            <x-service-requests.index.stats-cards.create-action :count="$inCourseCount ?? 0" />

            <!-- Tarjeta 3: En proceso -->
            <x-service-requests.index.stats-cards.in-process-stats :count="$inProcessCount ?? 0" />

            <!-- Tarjeta 4: Pendientes -->
            <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />

            <!-- Tarjeta 5: Abiertas -->
            <x-service-requests.index.stats-cards.open-stats :count="$openCount ?? 0" />

            <!-- Tarjeta 6: Total -->
            <x-service-requests.index.stats-cards.total-stats :count="$totalCount ?? $serviceRequests->total()" />

        </div>

        <!-- Filtros movidos dentro de la tabla -->

        <!-- Fila 3: Lista de Solicitudes -->
        <x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" :services="$services ?? null" :savedFilters="$savedFilters ?? collect()" />

        <!-- Fila 4: Paginación -->
        @if ($serviceRequests->hasPages())
            <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
        @endif

    </div>

    <!-- Región para anuncios accesibles (mensajes sin recargar) -->
    <div id="srLiveRegion" class="sr-only" aria-live="polite" aria-atomic="true"></div>

    <!-- Toast visual (complementa aria-live) -->
    <div id="srToast" class="fixed bottom-4 right-4 z-50 hidden" role="status" aria-live="polite" aria-atomic="true"></div>

    <!-- Diálogo accesible para pausar (evita prompt/alert) -->
    <div id="pauseReasonModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="pauseReasonTitle" aria-describedby="pauseReasonDesc">
        <div class="absolute inset-0 bg-black/40" data-modal-overlay></div>
        <div class="relative mx-auto mt-24 w-[92%] max-w-lg rounded-lg bg-white shadow-lg border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200 flex items-start justify-between gap-3">
                <div>
                    <h2 id="pauseReasonTitle" class="text-sm font-semibold text-gray-900">Pausar solicitud</h2>
                    <p id="pauseReasonDesc" class="text-xs text-gray-600 mt-1">Indica el motivo de la pausa (mínimo 10 caracteres).</p>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-modal-close aria-label="Cerrar diálogo">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-5 py-4">
                <label for="pauseReasonInput" class="block text-xs font-medium text-gray-700 mb-2">Motivo</label>
                <textarea id="pauseReasonInput" rows="3" minlength="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: Esperando información del solicitante"></textarea>
                <p id="pauseReasonError" class="text-xs text-red-600 mt-2 hidden" role="alert"></p>
            </div>
            <div class="px-5 py-4 border-t border-gray-200 bg-gray-50 flex gap-3 justify-end">
                <button type="button" class="px-4 py-2 rounded-lg text-sm border border-gray-300 text-gray-700 hover:bg-gray-100" data-modal-cancel>Cancelar</button>
                <button type="button" class="px-4 py-2 rounded-lg text-sm bg-blue-600 text-white hover:bg-blue-700" data-modal-confirm>Confirmar pausa</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function() {
    var resultsContainer = document.getElementById('resultsContainer');
    var timeout = null;
    var isUpdating = false;
    var STORAGE_KEY = 'sr_filters_v1';
    var suggestionIndex = -1;
    var pauseModal = document.getElementById('pauseReasonModal');
    var activePauseForm = null;
    var lastActiveElement = null;

    function getFilterElements() {
        return {
            search: document.getElementById('searchFilter'),
            status: document.getElementById('statusFilter'),
            criticality: document.getElementById('criticalityFilter'),
            requester: document.getElementById('requesterFilter'),
            startDate: document.getElementById('startDateFilter'),
            endDate: document.getElementById('endDateFilter'),
            open: document.getElementById('openFilter'),
            suggestions: document.getElementById('requesterSuggestions'),
            badge: document.getElementById('filtersActiveBadge'),
            spinner: document.getElementById('loadingSpinner')
        };
    }

    function countActiveFilters(el) {
        var c = 0;
        if(el.search && el.search.value.trim()) c++;
        if(el.status && el.status.value) c++;
        if(el.criticality && el.criticality.value) c++;
        if(el.requester && el.requester.value.trim()) c++;
        if(el.startDate && el.startDate.value) c++;
        if(el.endDate && el.endDate.value) c++;
        if(el.open && el.open.value) c++;
        return c;
    }

    function updateBadge() {
        var el = getFilterElements();
        var active = countActiveFilters(el);
        if(!el.badge) return;
        if(active > 0) {
            el.badge.textContent = active;
            el.badge.classList.remove('hidden');
        } else {
            el.badge.classList.add('hidden');
        }
    }

    function placeCaretAtEnd(input) {
        if (!input) return;
        try {
            var len = input.value ? input.value.length : 0;
            input.setSelectionRange(len, len);
        } catch(e) {}
    }

    function announce(message) {
        var live = document.getElementById('srLiveRegion');
        if (!live) return;
        live.textContent = '';
        setTimeout(function(){ live.textContent = message || ''; }, 20);
    }

    function toast(message, type) {
        var el = document.getElementById('srToast');
        if (!el) return;
        el.textContent = message || '';
        el.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm ' +
            ((type === 'error') ? 'bg-red-600' : 'bg-green-600');
        el.classList.remove('hidden');
        setTimeout(function(){
            el.classList.add('hidden');
        }, 3000);
    }

    function setPauseError(message) {
        var el = document.getElementById('pauseReasonError');
        if (!el) return;
        if (!message) {
            el.textContent = '';
            el.classList.add('hidden');
            return;
        }
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function getFocusable(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex=\"-1\"])'))
            .filter(function(n){ return !n.disabled && n.offsetParent !== null; });
    }

    function openPauseModal(form, triggerEl) {
        if (!pauseModal) return;
        activePauseForm = form;
        lastActiveElement = triggerEl || document.activeElement;
        setPauseError('');

        // Cerrar menú "Más opciones" si aplica
        var menu = form && form.closest ? form.closest('.sr-more-menu') : null;
        if (menu) {
            menu.classList.add('hidden');
            var btn = menu.parentElement && menu.parentElement.querySelector('.sr-more-btn');
            if (btn) btn.setAttribute('aria-expanded','false');
        }

        pauseModal.classList.remove('hidden');
        var input = document.getElementById('pauseReasonInput');
        if (input) {
            input.value = '';
            setTimeout(function(){ input.focus(); }, 0);
        }
    }

    function closePauseModal() {
        if (!pauseModal) return;
        pauseModal.classList.add('hidden');
        activePauseForm = null;
        setPauseError('');
        if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
            setTimeout(function(){ lastActiveElement.focus(); }, 0);
        }
    }

    function validatePauseReason(value) {
        var v = (value || '').trim();
        if (v.length < 10) return null;
        return v;
    }

    function persistState() {
        var el = getFilterElements();
        var state = {
            search: el.search ? el.search.value : '',
            status: el.status ? el.status.value : '',
            criticality: el.criticality ? el.criticality.value : '',
            requester: el.requester ? el.requester.value : '',
            start_date: el.startDate ? el.startDate.value : '',
            end_date: el.endDate ? el.endDate.value : '',
            open: el.open ? el.open.value : ''
        };
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch(e) {}
    }

    function restoreState() {
        var el = getFilterElements();
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if(!raw) return;
            var state = JSON.parse(raw);
            if(el.search && !el.search.value) el.search.value = state.search || '';
            if(el.status && !el.status.value) el.status.value = state.status || '';
            if(el.criticality && !el.criticality.value) el.criticality.value = state.criticality || '';
            if(el.requester && !el.requester.value) el.requester.value = state.requester || '';
            if(el.startDate && !el.startDate.value) el.startDate.value = state.start_date || '';
            if(el.endDate && !el.endDate.value) el.endDate.value = state.end_date || '';
            if(el.open && state.open) el.open.value = state.open;
            updateBadge();
            if (el.search && document.activeElement === el.search) placeCaretAtEnd(el.search);
        } catch(e) {}
    }

    // Evitar envío nativo del formulario
    document.addEventListener('submit', function(e){
        if(e.target && (e.target.id === 'filtersForm' || e.target.id === 'inlineFiltersForm')) {
            e.preventDefault();
            e.stopPropagation();
            updateResults();
        }
    });

    function buildParams(el){
        var params = new URLSearchParams();
        if (el.search && el.search.value.trim()) params.append('search', el.search.value.trim());
        if (el.status && el.status.value) params.append('status', el.status.value);
        if (el.criticality && el.criticality.value) params.append('criticality', el.criticality.value);
        if (el.requester && el.requester.value.trim()) params.append('requester', el.requester.value.trim());
        if (el.startDate && el.startDate.value) params.append('start_date', el.startDate.value);
        if (el.endDate && el.endDate.value) params.append('end_date', el.endDate.value);
        if (el.open && el.open.value) params.append('open', el.open.value);
        return params;
    }

    function updateResults() {
        if (isUpdating) return;
        isUpdating = true;
        var el = getFilterElements();
        // Persistir ANTES de re-renderizar: si el usuario borró el search,
        // evitamos que restoreState() vuelva a poner el valor anterior.
        persistState();
        if(el.spinner){ el.spinner.classList.remove('hidden'); el.spinner.classList.add('flex'); }
        var params = buildParams(el);
        if(el.search) el.search.style.borderColor = '#3b82f6';
        fetch('{{ route("service-requests.index") }}?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(function(r){ return r.text(); })
            .then(function(html){
                resultsContainer.innerHTML = html;
                var newEls = getFilterElements();
                if (newEls.search) {
                    newEls.search.focus();
                    newEls.search.style.borderColor = '#d1d5db';
                    placeCaretAtEnd(newEls.search);
                }
                restoreState();
                updateBadge();
            })
            .finally(function(){
                persistState();
                var els = getFilterElements();
                if(els.spinner){ els.spinner.classList.add('hidden'); els.spinner.classList.remove('flex'); }
                isUpdating = false;
            });
    }

    function clearFilters() {
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
        fetch('{{ route("service-requests.index") }}', { headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(r=>r.text())
            .then(html => {
                resultsContainer.innerHTML = html;
                suggestionIndex = -1;
                restoreState();
                updateBadge();
            });
    }

    // Delegación de eventos
    resultsContainer.addEventListener('input', function(e){
        if(e.target.id === 'searchFilter') {
            clearTimeout(timeout);
            timeout = setTimeout(updateResults, 800);
        }
    });

    resultsContainer.addEventListener('keydown', function(e){
        if(e.target.id === 'searchFilter' && e.key === 'Enter') {
            e.preventDefault(); e.stopPropagation(); clearTimeout(timeout); updateResults();
        }
        if(['requesterFilter','startDateFilter','endDateFilter'].includes(e.target.id) && e.key === 'Enter') {
            e.preventDefault(); e.stopPropagation(); updateResults();
        }
        // Navegación sugerencias
        var els = getFilterElements();
        if(e.target.id === 'requesterFilter' && els.suggestions && !els.suggestions.classList.contains('hidden')) {
            var items = Array.from(els.suggestions.querySelectorAll('li'));
            if(items.length === 0) return;
            if(e.key === 'ArrowDown') {
                e.preventDefault(); suggestionIndex = (suggestionIndex + 1) % items.length; focusSuggestion(items);
            } else if(e.key === 'ArrowUp') {
                e.preventDefault(); suggestionIndex = (suggestionIndex - 1 + items.length) % items.length; focusSuggestion(items);
            } else if(e.key === 'Escape') {
                els.suggestions.classList.add('hidden'); e.target.setAttribute('aria-expanded','false'); suggestionIndex = -1;
            } else if(e.key === 'Enter' && suggestionIndex >= 0) {
                e.preventDefault(); items[suggestionIndex].click();
            }
        } else if(e.target.parentElement && e.target.parentElement.id === 'requesterSuggestions') {
            var items2 = Array.from(e.target.parentElement.querySelectorAll('li'));
            if(items2.length === 0) return;
            if(e.key === 'ArrowDown') { e.preventDefault(); suggestionIndex = (suggestionIndex + 1) % items2.length; focusSuggestion(items2); }
            else if(e.key === 'ArrowUp') { e.preventDefault(); suggestionIndex = (suggestionIndex - 1 + items2.length) % items2.length; focusSuggestion(items2); }
            else if(e.key === 'Enter') { e.preventDefault(); e.target.click(); }
            else if(e.key === 'Escape') { var rf = document.getElementById('requesterFilter'); e.target.parentElement.classList.add('hidden'); rf && rf.setAttribute('aria-expanded','false'); suggestionIndex = -1; rf && rf.focus(); }
        }
    });

    function focusSuggestion(items){
        items.forEach((it,i)=>{ it.setAttribute('aria-selected', i===suggestionIndex?'true':'false'); if(i===suggestionIndex) it.focus(); });
    }

    resultsContainer.addEventListener('change', function(e){
        if(['statusFilter','criticalityFilter','startDateFilter','endDateFilter'].includes(e.target.id)) {
            updateResults();
        }
    });

    resultsContainer.addEventListener('input', function(e){
        if(e.target.id === 'requesterFilter') {
            const rf = document.getElementById('requesterFilter');
            const rs = document.getElementById('requesterSuggestions');
            const value = rf.value.trim();
            clearTimeout(timeout);
            if(value.length < 2) {
                if(rs) rs.classList.add('hidden');
                rf.setAttribute('aria-expanded','false');
                suggestionIndex = -1;
                timeout = setTimeout(updateResults, 500);
                return;
            }
            timeout = setTimeout(function(){
                fetch("{{ route('service-requests.suggest-requesters') }}?term=" + encodeURIComponent(value), { headers:{'X-Requested-With':'XMLHttpRequest'} })
                    .then(r=>r.json())
                    .then(list => {
                        if(!rs) return;
                        rs.innerHTML = '';
                        if(list.length === 0) {
                            rs.classList.add('hidden'); rf.setAttribute('aria-expanded','false'); suggestionIndex = -1; return;
                        }
                        list.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = item.display;
                            li.tabIndex = -1;
                            li.className = 'px-2.5 py-1.5 hover:bg-blue-50 cursor-pointer';
                            li.setAttribute('role','option');
                            li.dataset.value = item.email || item.name;
                            li.addEventListener('click', () => {
                                rf.value = item.email || item.name;
                                rs.classList.add('hidden');
                                rf.setAttribute('aria-expanded','false');
                                updateResults();
                            });
                            rs.appendChild(li);
                        });
                        rs.classList.remove('hidden');
                        rf.setAttribute('aria-expanded','true');
                        suggestionIndex = -1;
                    })
                    .catch(()=>{});
            }, 350);
            setTimeout(updateResults, 900);
        }
    });

    document.addEventListener('click', function(e){
        var els = getFilterElements();
        if(!els.suggestions) return;
        if(!els.suggestions.contains(e.target) && e.target !== els.requester){
            els.suggestions.classList.add('hidden');
            els.requester && els.requester.setAttribute('aria-expanded','false');
            suggestionIndex = -1;
        }
        // Cerrar menú Más opciones si clic fuera
        if(!e.target.closest('.sr-more-btn') && !e.target.closest('.sr-more-menu')) {
            resultsContainer.querySelectorAll('.sr-more-menu:not(.hidden)').forEach(function(m){
                m.classList.add('hidden');
                var btn = m.parentElement.querySelector('.sr-more-btn');
                if(btn) btn.setAttribute('aria-expanded','false');
            });
        }
    });

    resultsContainer.addEventListener('click', function(e){
        if(e.target.id === 'clearFiltersBtn' || e.target.closest('#clearFiltersBtn')) {
            e.preventDefault(); clearFilters();
        }
        // Toggle menú Más opciones
        var moreBtn = e.target.closest('.sr-more-btn');
        if(moreBtn) {
            e.preventDefault();
            var wrapper = moreBtn.parentElement; // relative div
            var menu = wrapper.querySelector('.sr-more-menu');
            if(!menu) return;
            // Cerrar otros menús
            resultsContainer.querySelectorAll('.sr-more-menu:not(.hidden)').forEach(function(m){
                if(m !== menu) {
                    m.classList.add('hidden');
                    var btn = m.parentElement.querySelector('.sr-more-btn');
                    if(btn) btn.setAttribute('aria-expanded','false');
                }
            });
            var isHidden = menu.classList.contains('hidden');
            if(isHidden) {
                menu.classList.remove('hidden');
                moreBtn.setAttribute('aria-expanded','true');
            } else {
                menu.classList.add('hidden');
                moreBtn.setAttribute('aria-expanded','false');
            }
        }
    });

    // Acciones workflow desde el listado sin recargar la página (AJAX).
    resultsContainer.addEventListener('submit', function(e){
        var form = e.target && e.target.closest ? e.target.closest('form.sr-action-form') : null;
        if (!form) return;
        e.preventDefault();

        // Pausar requiere motivo (mín. 10 chars): pedirlo en diálogo accesible.
        if (form.dataset && form.dataset.action === 'pause') {
            var input = form.querySelector('input[name=\"pause_reason\"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'pause_reason';
                form.appendChild(input);
            }
            var current = (input.value || '').trim();
            if (current.length < 10) {
                openPauseModal(form, e.submitter || form.querySelector('button[type=\"submit\"]'));
                return;
            }
        }

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        form.setAttribute('aria-busy', 'true');

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function(r){
                // Para validaciones (422) Laravel devolverá JSON con errors.
                var ct = (r.headers.get('content-type') || '').toLowerCase();
                if (ct.indexOf('application/json') !== -1) {
                    return r.json().catch(function(){ return null; }).then(function(data){
                        return { ok: r.ok, status: r.status, data: data };
                    });
                }
                return r.text().then(function(){
                    return { ok: false, status: r.status, data: null };
                });
            })
            .then(function(res){
                if (!res.ok) {
                    var msg = (res.data && (res.data.message || res.data.error)) || 'No se pudo completar la acción.';
                    announce(msg);
                    toast(msg, 'error');
                    return;
                }
                if (res.data && res.data.message) {
                    announce(res.data.message);
                    toast(res.data.message, 'success');
                }
            })
            .catch(function(){
                announce('No se pudo completar la acción.');
                toast('No se pudo completar la acción.', 'error');
            })
            .finally(function(){
                if (submitBtn) submitBtn.disabled = false;
                form.removeAttribute('aria-busy');
                // Cerrar menú "Más opciones" si aplica
                var menu = form.closest('.sr-more-menu');
                if (menu) {
                    menu.classList.add('hidden');
                    var btn = menu.parentElement && menu.parentElement.querySelector('.sr-more-btn');
                    if (btn) btn.setAttribute('aria-expanded','false');
                }
                updateResults();
            });
    });

    // === Modal Pausar: cierre, foco y confirmación ===
    if (pauseModal) {
        pauseModal.addEventListener('click', function(e){
            if (!e.target) return;
            if (e.target.matches('[data-modal-overlay]') || e.target.closest('[data-modal-close]') || e.target.closest('[data-modal-cancel]')) {
                e.preventDefault();
                closePauseModal();
            }
        });

        pauseModal.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                e.preventDefault();
                closePauseModal();
                return;
            }
            if (e.key === 'Tab') {
                var focusables = getFocusable(pauseModal);
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

        var confirmBtn = pauseModal.querySelector('[data-modal-confirm]');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(){
                if (!activePauseForm) return;
                var input = document.getElementById('pauseReasonInput');
                var reason = validatePauseReason(input ? input.value : '');
                if (!reason) {
                    setPauseError('Escribe un motivo de al menos 10 caracteres.');
                    if (input) input.focus();
                    return;
                }
                var hidden = activePauseForm.querySelector('input[name=\"pause_reason\"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'pause_reason';
                    activePauseForm.appendChild(hidden);
                }
                hidden.value = reason;
                closePauseModal();
                if (activePauseForm.requestSubmit) activePauseForm.requestSubmit();
                else activePauseForm.submit();
            });
        }
    }

    // ESC global para cerrar menús Más opciones
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') {
            resultsContainer.querySelectorAll('.sr-more-menu:not(.hidden)').forEach(function(m){
                m.classList.add('hidden');
                var btn = m.parentElement.querySelector('.sr-more-btn');
                if(btn) btn.setAttribute('aria-expanded','false');
                if(btn) btn.focus();
            });
        }
    });

    function initialLoad(){ restoreState(); updateBadge(); }
    initialLoad();
})();
</script>
@endsection

@push('styles')
<style type="text/tailwindcss">
    @layer utilities {
        .bg-gradient-to-br {
            background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
        }

        .backdrop-blur-sm {
            backdrop-filter: blur(4px);
        }

        .transition {
            transition: all 0.2s ease-in-out;
        }

        .hover\:bg-gray-50:hover {
            background-color: rgba(249, 250, 251, 0.8);
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, monospace;
        }
    }
</style>
@endpush
