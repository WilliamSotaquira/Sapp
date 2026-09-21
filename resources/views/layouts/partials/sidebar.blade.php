{{--
    Sidebar de navegación principal.

    Datos: $navSections y $isSectionActive los provee App\View\Composers\NavigationComposer.
    Variables de workspace ($workspaceDisplayName, $activeContractLabel, $workspaceLogo,
    $workspaceAccent, $userContracts, $currentContract) provienen del layout / middleware.

    Estado de UI (colapso, grupos abiertos, flyouts, móvil) lo gobierna el componente
    Alpine `appShell()` definido a nivel del app-shell en app.blade.php. Este partial
    asume disponibles: collapsed (bool), mobileOpen (bool), openGroup (string|null),
    toggleGroup(key), isGroupOpen(key).
--}}
<aside id="appSidebar"
       class="app-sidebar"
       :class="{ 'app-sidebar--collapsed': collapsed, 'app-sidebar--mobile-open': mobileOpen }"
       @mouseenter="expandOnHover()"
       @mouseleave="collapseOnLeave()"
       aria-label="Navegación principal">

    {{-- ===== Logo / marca ===== --}}
    {{-- Logo alargado (con nombre) cuando está expandido; logo cuadrado cuando
         está colapsado. El menú se expande al pasar el mouse y se colapsa al salir. --}}
    <div class="app-sidebar__brand">
        <a href="{{ route('my-space.index') }}" class="app-sidebar__brand-link" title="Ir al inicio">
            <img src="/sapp_logo_lg.png" alt="Sistema Sapp" class="app-sidebar__brand-logo-full"
                 x-show="!collapsed" x-cloak
                 x-transition:enter="app-logo-fade-enter" x-transition:enter-start="app-logo-fade-start" x-transition:enter-end="app-logo-fade-end"
                 x-transition:leave="app-logo-fade-leave" x-transition:leave-start="app-logo-fade-end" x-transition:leave-end="app-logo-fade-start">
            <img src="/logo_sapp_xs.png" alt="Sistema Sapp" class="app-sidebar__brand-logo-mark"
                 x-show="collapsed" x-cloak
                 x-transition:enter="app-logo-fade-enter" x-transition:enter-start="app-logo-fade-start" x-transition:enter-end="app-logo-fade-end"
                 x-transition:leave="app-logo-fade-leave" x-transition:leave-start="app-logo-fade-end" x-transition:leave-end="app-logo-fade-start">
        </a>
    </div>

    {{-- ===== Navegación ===== --}}
    <nav class="app-sidebar__nav" aria-label="Secciones">
        @foreach ($navSections as $section)
            @php $sectionActive = $isSectionActive($section['match'] ?? []); @endphp

            @if (($section['type'] ?? 'link') === 'link')
                {{-- Sección de enlace directo --}}
                <a href="{{ route($section['route']) }}"
                   class="app-sidebar__item {{ $sectionActive ? 'app-sidebar__item--active' : '' }}"
                   @if($sectionActive) aria-current="page" @endif
                   @click="mobileOpen = false"
                   title="{{ $section['label'] }}">
                    <i class="{{ $section['icon'] }} app-sidebar__icon"></i>
                    <span class="app-sidebar__label" x-show="!collapsed" x-cloak>{{ $section['label'] }}</span>
                </a>
            @else
                {{-- Sección con sub-ítems: grupo expandible (expanded) / flyout por CLIC (rail) --}}
                <div class="app-sidebar__group"
                     data-group="{{ $section['key'] }}"
                     @click.outside="flyout === '{{ $section['key'] }}' && (flyout = null)">
                    <button type="button"
                            class="app-sidebar__item app-sidebar__group-toggle {{ $sectionActive ? 'app-sidebar__item--active' : '' }}"
                            @click="collapsed ? (flyout = (flyout === '{{ $section['key'] }}' ? null : '{{ $section['key'] }}')) : toggleGroup('{{ $section['key'] }}')"
                            :aria-expanded="(collapsed ? flyout === '{{ $section['key'] }}' : isGroupOpen('{{ $section['key'] }}')).toString()"
                            aria-haspopup="true"
                            title="{{ $section['label'] }}">
                        <i class="{{ $section['icon'] }} app-sidebar__icon"></i>
                        <span class="app-sidebar__label" x-show="!collapsed" x-cloak>{{ $section['label'] }}</span>
                        <i class="fas fa-chevron-down app-sidebar__chevron"
                           x-show="!collapsed" x-cloak
                           :class="{ 'app-sidebar__chevron--open': isGroupOpen('{{ $section['key'] }}') }"></i>
                    </button>

                    {{-- Sub-ítems inline (modo expandido) --}}
                    <div class="app-sidebar__submenu"
                         x-show="!collapsed && isGroupOpen('{{ $section['key'] }}')"
                         x-transition x-cloak>
                        @foreach ($section['links'] as $link)
                            @php $linkActive = $isSectionActive($link['match'] ?? []); @endphp
                            <a href="{{ route($link['route']) }}"
                               class="app-sidebar__subitem {{ $linkActive ? 'app-sidebar__subitem--active' : '' }}"
                               @if($linkActive) aria-current="page" @endif
                               @click="mobileOpen = false">
                                <i class="{{ $link['icon'] }} app-sidebar__subicon"></i>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Flyout lateral (modo rail): aparece al hacer CLIC sobre el ícono --}}
                    <div class="app-sidebar__flyout"
                         x-show="collapsed && flyout === '{{ $section['key'] }}'"
                         x-cloak x-transition>
                        <div class="app-sidebar__flyout-title">{{ $section['label'] }}</div>
                        @foreach ($section['links'] as $link)
                            @php $linkActive = $isSectionActive($link['match'] ?? []); @endphp
                            <a href="{{ route($link['route']) }}"
                               class="app-sidebar__subitem {{ $linkActive ? 'app-sidebar__subitem--active' : '' }}"
                               @if($linkActive) aria-current="page" @endif
                               @click="mobileOpen = false">
                                <i class="{{ $link['icon'] }} app-sidebar__subicon"></i>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- ===== Context_Footer (workspace, alertas, usuario) ===== --}}
    {{-- Se rellena en la Fase 6. Placeholder de estructura para no romper el layout. --}}
    <div class="app-sidebar__footer" id="appSidebarFooter">
        @includeWhen(View::exists('layouts.partials.sidebar-footer'), 'layouts.partials.sidebar-footer')
    </div>
</aside>
