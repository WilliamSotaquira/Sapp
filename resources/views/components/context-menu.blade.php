{{--
    Componente reutilizable: Menú Contextual (clic derecho)

    Uso:
    <x-context-menu :items="[
        ['label' => 'Nueva solicitud', 'icon' => 'fa-plus-circle', 'iconColor' => 'text-blue-500', 'href' => route('service-requests.create'), 'bold' => true],
        ['divider' => true],
        ['label' => 'Actualizar listado', 'icon' => 'fa-sync-alt', 'action' => 'reload'],
        ['label' => 'Limpiar filtros', 'icon' => 'fa-eraser', 'action' => 'clear-filters'],
        ['divider' => true],
        ['label' => 'Ir al Dashboard', 'icon' => 'fa-tachometer-alt', 'href' => url('/dashboard')],
    ]" />

    Props:
    - items: array de opciones del menú. Cada item puede ser:
        - divider: ['divider' => true] para separador
        - link:   ['label' => '...', 'icon' => '...', 'href' => '...']
        - action: ['label' => '...', 'icon' => '...', 'action' => '...']
    - id: (opcional) id del elemento, default 'globalContextMenu'
--}}

@props(['items' => [], 'id' => 'globalContextMenu'])

<div id="{{ $id }}"
     class="fixed hidden z-[9999] min-w-[200px] max-w-[260px] bg-white border border-gray-200 rounded-xl shadow-lg p-1 select-none"
     role="menu"
     aria-label="Menú contextual"
     style="animation: ctx-scale-in 0.12s ease-out;">

    @foreach($items as $item)
        @if(!empty($item['divider']))
            <div class="border-t border-gray-100 my-1 mx-1"></div>
        @elseif(!empty($item['href']))
            <a href="{{ $item['href'] }}"
               class="context-menu-item flex items-center gap-2 w-full px-3 py-2 rounded-md text-[13px] font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors no-underline {{ !empty($item['bold']) ? 'font-semibold' : '' }}"
               role="menuitem">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }} {{ $item['iconColor'] ?? 'text-gray-400' }} w-4 text-center text-xs"></i>
                <span>{{ $item['label'] }}</span>
                @if(!empty($item['kbd']))
                    <kbd class="ml-auto px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-bold">{{ $item['kbd'] }}</kbd>
                @endif
            </a>
        @elseif(!empty($item['action']))
            <button type="button"
                    data-ctx-action="{{ $item['action'] }}"
                    class="context-menu-item flex items-center gap-2 w-full px-3 py-2 rounded-md text-[13px] font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors text-left border-none bg-transparent cursor-pointer {{ !empty($item['bold']) ? 'font-semibold' : '' }}"
                    role="menuitem">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }} {{ $item['iconColor'] ?? 'text-gray-400' }} w-4 text-center text-xs"></i>
                <span>{{ $item['label'] }}</span>
                @if(!empty($item['kbd']))
                    <kbd class="ml-auto px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-bold">{{ $item['kbd'] }}</kbd>
                @endif
            </button>
        @endif
    @endforeach
</div>

<style>
@keyframes ctx-scale-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<script>
(function() {
    var ctxMenu = document.getElementById('{{ $id }}');
    if (!ctxMenu) return;
    var isOpen = false;

    function show(x, y) {
        ctxMenu.classList.remove('hidden');
        isOpen = true;
        var rect = ctxMenu.getBoundingClientRect();

        if (x + rect.width > window.innerWidth) {
            x = window.innerWidth - rect.width - 8;
        }
        if (y + rect.height > window.innerHeight) {
            y = window.innerHeight - rect.height - 8;
        }

        ctxMenu.style.left = x + 'px';
        ctxMenu.style.top = y + 'px';

        // Focus first item
        setTimeout(function() {
            var first = ctxMenu.querySelector('.context-menu-item');
            if (first) first.focus();
        }, 50);
    }

    function hide() {
        ctxMenu.classList.add('hidden');
        isOpen = false;
    }

    document.addEventListener('contextmenu', function(e) {
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        if (e.target.closest('[role="dialog"]')) return;

        e.preventDefault();
        show(e.clientX, e.clientY);
    });

    document.addEventListener('mousedown', function(e) {
        if (isOpen && !ctxMenu.contains(e.target)) hide();
    });

    document.addEventListener('keydown', function(e) {
        if (!isOpen) return;
        if (e.key === 'Escape') { hide(); return; }
        if (e.key === 'Tab') {
            e.preventDefault();
            var focused = document.activeElement;
            if (focused && focused.closest('#{{ $id }}')) {
                focused.click();
            } else {
                var first = ctxMenu.querySelector('.context-menu-item');
                if (first) first.click();
            }
        }
    });

    document.addEventListener('scroll', function() { if (isOpen) hide(); }, true);

    ctxMenu.addEventListener('click', function(e) {
        var link = e.target.closest('a[href]');
        if (link) { hide(); return; }

        var btn = e.target.closest('[data-ctx-action]');
        if (!btn) return;

        var action = btn.dataset.ctxAction;
        hide();
        document.dispatchEvent(new CustomEvent('context-menu-action', { detail: { action: action } }));
    });

    ctxMenu.addEventListener('keydown', function(e) {
        var items = Array.from(ctxMenu.querySelectorAll('.context-menu-item'));
        var idx = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[(idx + 1) % items.length].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[(idx - 1 + items.length) % items.length].focus();
        } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (document.activeElement) document.activeElement.click();
        }
    });
})();
</script>
