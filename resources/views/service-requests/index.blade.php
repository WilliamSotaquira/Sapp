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
        <!-- Fila 1: Estadísticas y Acción Principal -->
        <!-- En móvil: layout compacto, tablet: 2-3 cols, desktop: 5 cols -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 lg:gap-6">

            <!-- Tarjeta 1: Nueva Solicitud -->
            <x-service-requests.index.stats-cards.create-action />

            <!-- Tarjeta 2: Críticas -->
            <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />

            <!-- Tarjeta 3: Pendientes -->
            <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />

            <!-- Tarjeta 4: Abiertas -->
            <x-service-requests.index.stats-cards.open-stats :count="$openCount ?? 0" />

            <!-- Tarjeta 5: Total -->
            <x-service-requests.index.stats-cards.total-stats :count="$totalCount ?? $serviceRequests->total()" />

        </div>

        <!-- Filtros movidos dentro de la tabla -->

        <!-- Fila 3: Lista de Solicitudes -->
        <x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" :services="$services ?? null" />

        <!-- Fila 4: Paginación -->
        @if ($serviceRequests->hasPages())
            <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
        @endif

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
