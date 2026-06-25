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
     class="fixed hidden z-[100] min-w-[220px] bg-white rounded-lg shadow-xl border border-gray-200 py-1.5 text-sm select-none"
     role="menu"
     aria-label="Menú contextual">

    @foreach($items as $item)
        @if(!empty($item['divider']))
            <div class="border-t border-gray-100 my-1"></div>
        @elseif(!empty($item['href']))
            <a href="{{ $item['href'] }}"
               class="context-menu-item flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"
               role="menuitem">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }} {{ $item['iconColor'] ?? 'text-gray-400' }} w-4 text-center"></i>
                <span class="{{ !empty($item['bold']) ? 'font-medium' : '' }}">{{ $item['label'] }}</span>
            </a>
        @elseif(!empty($item['action']))
            <button type="button"
                    data-ctx-action="{{ $item['action'] }}"
                    class="context-menu-item w-full flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors text-left"
                    role="menuitem">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }} {{ $item['iconColor'] ?? 'text-gray-400' }} w-4 text-center"></i>
                <span class="{{ !empty($item['bold']) ? 'font-medium' : '' }}">{{ $item['label'] }}</span>
            </button>
        @endif
    @endforeach
</div>

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
