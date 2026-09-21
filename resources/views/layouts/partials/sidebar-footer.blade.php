{{--
    Context_Footer del sidebar: workspace + alertas + usuario, al pie del menú.

    Conserva los ids de la campana (navAlertBell, navAlertBadge, alertDropdown,
    alertDropdownList, alertBellWrapper) que el script de polling del layout usa.
    Estos ids deben existir UNA sola vez en el DOM: el markup equivalente se retira
    del navbar legacy (fase 8), aquí queda la única instancia.

    Variables: $workspaceDisplayName, $activeContractLabel, $workspaceLogo,
    $userContracts, $currentContract provienen del layout/middleware.
--}}
@php
    $footerContracts = isset($userContracts) ? $userContracts : collect();
    $footerCurrentContractId = $currentContract->id ?? null;
    $footerRedirect = request()->fullUrl();
@endphp

<div class="app-footer">
    {{-- ===== Selector de entidad / workspace ===== --}}
    @if(isset($currentWorkspace))
        <div class="app-footer__block" x-data="{ wsOpen: false }" @click.outside="wsOpen = false">
            <button type="button" class="app-footer__btn"
                    @click="wsOpen = !wsOpen" :aria-expanded="wsOpen.toString()"
                    aria-haspopup="true"
                    title="{{ $workspaceDisplayName }} — {{ $activeContractLabel }}">
                @if($workspaceLogo)
                    <span class="app-footer__ws-logo"><img src="{{ $workspaceLogo }}" alt="{{ $workspaceDisplayName }}"></span>
                @else
                    <i class="fas fa-building app-footer__icon"></i>
                @endif
                <span class="app-footer__ws-text" x-show="!collapsed" x-cloak>
                    <span class="app-footer__ws-name">{{ $workspaceDisplayName }}</span>
                    <span class="app-footer__ws-contract">{{ $activeContractLabel }}</span>
                </span>
                <i class="fas fa-chevron-up app-footer__chevron" x-show="!collapsed" x-cloak></i>
            </button>

            <div class="app-footer__menu" x-show="wsOpen" x-cloak x-transition>
                <div class="app-footer__menu-title">Cambiar de entidad</div>
                @forelse($footerContracts as $contract)
                    @php
                        $isCurrent = (int) $contract->id === (int) $footerCurrentContractId;
                        $entityName = $contract->company->name ?? 'Entidad';
                        $contractLabel = $contract->number ?: $contract->name;
                    @endphp
                    <form method="POST" action="{{ route('workspaces.switch') }}">
                        @csrf
                        <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                        <input type="hidden" name="redirect_to" value="{{ $footerRedirect }}">
                        <button type="submit" class="app-footer__menu-item {{ $isCurrent ? 'app-footer__menu-item--current' : '' }}"
                                @if($isCurrent) aria-current="true" @endif>
                            <i class="fas fa-building"></i>
                            <span class="min-w-0">
                                <span class="block truncate">{{ $entityName }}</span>
                                <span class="block text-xs text-gray-400 truncate">{{ $contractLabel }}</span>
                            </span>
                            @if($isCurrent)<i class="fas fa-check ml-auto text-red-600"></i>@endif
                        </button>
                    </form>
                @empty
                    <div class="app-footer__menu-empty">Sin entidades disponibles</div>
                @endforelse
                <a href="{{ route('workspaces.select') }}" class="app-footer__menu-link">
                    <i class="fas fa-sliders-h"></i> Ver todas / pantalla completa
                </a>
            </div>
        </div>
    @endif

    {{-- ===== Campana de alertas (conserva ids del polling) ===== --}}
    <div class="app-footer__block" id="alertBellWrapper">
        <button type="button" class="app-footer__btn" id="navAlertBell" title="Alertas operativas">
            <span class="app-footer__icon-wrap">
                <i class="fas fa-bell app-footer__icon"></i>
                <span id="navAlertBadge" class="app-footer__badge hidden"></span>
            </span>
            <span class="app-footer__label" x-show="!collapsed" x-cloak>Alertas</span>
        </button>

        <div id="alertDropdown" class="app-footer__menu app-footer__menu--alerts hidden">
            <div class="app-footer__menu-title flex items-center justify-between">
                <span>Alertas recientes</span>
                <a href="{{ route('operational-alerts.index') }}" class="text-xs text-red-600 hover:text-red-700 font-medium">Ver todas</a>
            </div>
            <div id="alertDropdownList" class="max-h-[300px] overflow-y-auto">
                <div class="px-4 py-6 text-center text-xs text-gray-400">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Cargando...
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Usuario ===== --}}
    <div class="app-footer__block app-footer__user">
        <span class="app-footer__icon-wrap"><i class="fas fa-user-circle app-footer__icon"></i></span>
        <span class="app-footer__user-name" x-show="!collapsed" x-cloak>{{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" class="app-footer__logout" x-show="!collapsed" x-cloak>
            @csrf
            <button type="submit" title="Cerrar sesión" aria-label="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </form>
    </div>
</div>
