<script>
// === SISTEMA DE FILTROS AVANZADOS ===

// Configuración
const STORAGE_KEYS = {
    searchHistory: 'sr_search_history',
    filterPresets: 'sr_filter_presets',
    activeFilters: 'sr_active_filters'
};

// === Historial de Búsqueda ===
function saveSearchHistory(term) {
    if (!term || term.length < 3) return;
    try {
        let history = JSON.parse(localStorage.getItem(STORAGE_KEYS.searchHistory) || '[]');
        history = history.filter(h => h !== term);
        history.unshift(term);
        history = history.slice(0, 10); // Mantener solo 10
        localStorage.setItem(STORAGE_KEYS.searchHistory, JSON.stringify(history));
    } catch(e) {}
}

function loadSearchHistory() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.searchHistory) || '[]');
    } catch(e) {
        return [];
    }
}

function renderSearchHistory() {
    const history = loadSearchHistory();
    const list = document.getElementById('searchHistoryList');
    const container = document.getElementById('searchHistory');
    
    if (history.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    list.innerHTML = history.map(term => `
        <li class="px-4 py-2 hover:bg-gray-50 cursor-pointer text-sm text-gray-700 flex items-center gap-2" 
            onclick="applySearchFromHistory('${term.replace(/'/g,"\\'")}')">
            <i class="fas fa-history text-gray-400 text-xs"></i>
            ${term}
        </li>
    `).join('');
}

function applySearchFromHistory(term) {
    document.getElementById('searchFilter').value = term;
    document.getElementById('searchHistory').classList.add('hidden');
    applyFilters();
}

// === Filtros Rápidos ===
function applyQuickFilter(field, value) {
    // Limpiar filtros actuales
    clearAllFilters(false);
    
    // Aplicar el filtro rápido
    if (field === 'open') {
        const openInput = document.getElementById('openFilter');
        if (!openInput) {
            const form = document.getElementById('advancedFiltersForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.id = 'openFilter';
            input.name = 'open';
            input.value = value;
            form.appendChild(input);
        }
    } else if (field === 'status') {
        const statusSelect = document.getElementById('statusFilterAdv');
        if (statusSelect) statusSelect.value = value;
    } else if (field === 'criticality') {
        const critSelect = document.getElementById('criticalityFilterAdv');
        if (critSelect) critSelect.value = value;
    }
    
    applyFilters();
}

// === Sistema de Presets ===
function savePreset() {
    const name = prompt('Nombre del preset:');
    if (!name) return;
    
    const filters = gatherFilters();
    try {
        let presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        presets[name] = filters;
        localStorage.setItem(STORAGE_KEYS.filterPresets, JSON.stringify(presets));
        renderPresets();
        showToast('Preset guardado exitosamente', 'success');
    } catch(e) {
        showToast('Error al guardar preset', 'error');
    }
}

function loadPreset(name) {
    try {
        const presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        const filters = presets[name];
        if (!filters) return;
        
        // Aplicar filtros
        Object.keys(filters).forEach(key => {
            const el = document.getElementById(key);
            if (el) el.value = filters[key];
        });
        
        applyFilters();
        document.getElementById('filtersSidebar').classList.add('translate-x-full');
        showToast(`Preset "${name}" aplicado`, 'success');
    } catch(e) {}
}

function deletePreset(name) {
    if (!confirm(`¿Eliminar preset "${name}"?`)) return;
    
    try {
        let presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        delete presets[name];
        localStorage.setItem(STORAGE_KEYS.filterPresets, JSON.stringify(presets));
        renderPresets();
        showToast('Preset eliminado', 'success');
    } catch(e) {}
}

function renderPresets() {
    const container = document.getElementById('presetsContainer');
    if (!container) return;
    
    try {
        const presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        const names = Object.keys(presets);
        
        if (names.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No hay presets guardados</p>';
            return;
        }
        
        container.innerHTML = names.map(name => `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <button type="button" onclick="loadPreset('${name.replace(/'/g,"\\'")}'))"
                        class="flex-1 text-left text-sm font-medium text-gray-700">
                    <i class="fas fa-star text-purple-500 mr-2"></i>${name}
                </button>
                <button type="button" onclick="deletePreset('${name.replace(/'/g,"\\'")}'))"
                        class="text-red-500 hover:text-red-700 ml-2">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        `).join('');
    } catch(e) {}
}

// === Aplicar Filtros ===
function gatherFilters() {
    return {
        search: document.getElementById('searchFilter')?.value || '',
        status: document.getElementById('statusFilterAdv')?.value || '',
        criticality: document.getElementById('criticalityFilterAdv')?.value || '',
        requester: document.getElementById('requesterFilterAdv')?.value || '',
        start_date: document.getElementById('startDateFilterAdv')?.value || '',
        end_date: document.getElementById('endDateFilterAdv')?.value || ''
    };
}

function applyFilters() {
    const filters = gatherFilters();
    
    // Guardar búsqueda en historial
    if (filters.search) {
        saveSearchHistory(filters.search);
    }
    
    // Construir URL
    const params = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) params.append(key, filters[key]);
    });
    
    // Navegar
    window.location.href = `{{ route('service-requests.index') }}?${params.toString()}`;
}

function clearAllFilters(reload = true) {
    document.getElementById('searchFilter').value = '';
    const advForm = document.getElementById('advancedFiltersForm');
    if (advForm) {
        advForm.querySelectorAll('input, select').forEach(el => {
            if (el.type === 'hidden') return;
            el.value = '';
        });
    }
    
    if (reload) {
        window.location.href = '{{ route('service-requests.index') }}';
    }
}

// === UI Helpers ===
function showToast(message, type = 'info') {
    // Toast simple
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white z-50 transition-opacity ${
        type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function updateActiveFiltersCount() {
    const filters = gatherFilters();
    const count = Object.values(filters).filter(v => v).length;
    const badge = document.getElementById('activeFiltersCount');
    
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// === Event Listeners ===
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Sidebar
    document.getElementById('toggleFiltersSidebar')?.addEventListener('click', function() {
        document.getElementById('filtersSidebar').classList.remove('translate-x-full');
    });
    
    document.getElementById('closeFiltersSidebar')?.addEventListener('click', function() {
        document.getElementById('filtersSidebar').classList.add('translate-x-full');
    });
    
    // Búsqueda
    const searchInput = document.getElementById('searchFilter');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    searchInput?.addEventListener('input', function() {
        clearSearchBtn.classList.toggle('hidden', !this.value);
    });
    
    searchInput?.addEventListener('focus', function() {
        renderSearchHistory();
        if (loadSearchHistory().length > 0) {
            document.getElementById('searchHistory').classList.remove('hidden');
        }
    });
    
    searchInput?.addEventListener('blur', function() {
        setTimeout(() => {
            document.getElementById('searchHistory').classList.add('hidden');
        }, 200);
    });
    
    clearSearchBtn?.addEventListener('click', function() {
        searchInput.value = '';
        this.classList.add('hidden');
        applyFilters();
    });
    
    // Clear History
    document.getElementById('clearHistoryBtn')?.addEventListener('click', function() {
        localStorage.removeItem(STORAGE_KEYS.searchHistory);
        document.getElementById('searchHistory').classList.add('hidden');
    });
    
    // Presets
    document.getElementById('showPresetsBtn')?.addEventListener('click', function() {
        renderPresets();
        document.getElementById('filtersSidebar').classList.remove('translate-x-full');
        // Scroll to presets section
        setTimeout(() => {
            document.getElementById('presetsContainer')?.scrollIntoView({ behavior: 'smooth' });
        }, 300);
    });
    
    // Actualizar contador
    updateActiveFiltersCount();
});
</script>
