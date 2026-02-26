<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Servicios')</title>

    <link rel="icon" type="image/png" href="{{ asset('logo_sapp_xs.png') }}?v={{ filemtime(public_path('logo_sapp_xs.png')) }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/tailwindcss">
        @theme {
            --color-clifford: #da373d;
        }
    </script>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE"></script>

    @stack('styles')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        [x-cloak] {
            display: none !important;
        }

        .nav-item-active {
            background-color: transparent;
            box-shadow: none;
        }

        .primary-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            border-radius: 0.75rem;
            padding: 0.5rem 0.9rem;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .primary-nav-link i,
        .dropdown-menu a i,
        .mobile-nav-link i {
            width: 1rem;
            text-align: center;
        }

        .primary-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }

        .primary-nav-link.compact {
            padding-left: 0.6rem;
            padding-right: 0.6rem;
        }

        .mobile-menu {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .mobile-section-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
            color: #fee2e2;
            background-color: rgba(0, 0, 0, 0.12);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .mobile-section-trigger:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }

        .mobile-section-trigger[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1rem;
            border-radius: 0.75rem;
            color: white;
            background-color: rgba(255, 255, 255, 0.02);
            transition: background-color 0.2s ease;
        }

        .mobile-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .mobile-nav-link-active {
            background-color: rgba(0, 0, 0, 0.35);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #dc2626;
            min-width: 220px;
            max-width: 320px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.18);
            z-index: 1000;
            border-radius: 0 0 0.75rem 0.75rem;
            top: 100%;
            left: 0;
            padding: 0.35rem 0;
            box-sizing: border-box;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            transition: background-color 0.2s ease;
            white-space: normal;
            line-height: 1.25;
            width: 100%;
            box-sizing: border-box;
            word-break: break-word;
        }

        .dropdown-menu a:hover,
        .dropdown-menu a.bg-red-700 {
            background-color: #b91c1c;
        }

        /* Estilos mejorados para los logos */
        .logo-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .logo-large,
        .logo-small {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .logo-large {
            height: 3rem;
            width: auto;
        }

        .logo-small {
            height: 1.5rem;
            width: auto;
        }

        .logo-large:hover,
        .logo-small:hover {
            transform: scale(1.03) rotate(1deg);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2)) brightness(1.1);
        }

        .logo-large:active,
        .logo-small:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }

        /* Efecto de pulso sutil al cargar */
        @keyframes gentlePulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        .logo-pulse {
            animation: gentlePulse 2s ease-in-out;
        }

        /* Efecto de brillo al pasar el cursor */
        .logo-glow {
            position: relative;
            overflow: hidden;
        }

        .logo-glow::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
            transform: rotate(45deg);
        }

        .logo-glow:hover::after {
            opacity: 1;
        }

        /* Efecto de borde animado */
        .logo-border-animation {
            position: relative;
        }

        .logo-border-animation::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            /* background: linear-gradient(45deg, #ff6b6b, #ffd93d, #6bcf7f, #4d96ff); */
            border-radius: 8px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        .logo-border-animation:hover::before {
            opacity: 1;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dropdown-menu {
                position: static;
                box-shadow: none;
            }

            .logo-large {
                display: none;
            }

            .logo-small {
                display: block;
            }

            .logo-border-animation::before {
                border-radius: 6px;
            }
        }

        @media (min-width: 769px) {
            .logo-large {
                display: block;
            }

            .logo-small {
                display: none;
            }
        }

        /* Efecto de partículas para el logo (opcional) */
        .logo-particles {
            position: relative;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
        }

        @keyframes floatParticle {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }

            100% {
                transform: translate(var(--tx), var(--ty)) scale(0);
                opacity: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    @php
        $navSections = [
            [
                'key' => 'requests',
                'label' => 'Solicitudes',
                'icon' => 'fas fa-tasks',
                'type' => 'dropdown',
                'match' => ['service-requests.*'],
                'links' => [
                    [
                        'route' => 'service-requests.create',
                        'label' => 'Crear Solicitud',
                        'icon' => 'fas fa-plus-circle',
                        'match' => ['service-requests.create'],
                    ],
                    [
                        'route' => 'service-requests.index',
                        'label' => 'Ver Solicitudes',
                        'icon' => 'fas fa-list',
                        'match' => ['service-requests.index'],
                    ],
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'Reportes',
                'icon' => 'fas fa-chart-bar',
                'type' => 'dropdown',
                'match' => ['reports.*'],
                'links' => [
                    [
                        'route' => 'reports.index',
                        'label' => 'Dashboard de Reportes',
                        'icon' => 'fas fa-chart-pie',
                        'match' => ['reports.index'],
                    ],
                    [
                        'route' => 'reports.obligaciones.index',
                        'label' => 'Reporte de Obligaciones',
                        'icon' => 'fas fa-file-contract',
                        'match' => ['reports.obligaciones.*'],
                    ],
                    [
                        'route' => 'reports.sla-compliance',
                        'label' => 'Cumplimiento SLA',
                        'icon' => 'fas fa-check-circle',
                        'match' => ['reports.sla-compliance'],
                    ],
                    [
                        'route' => 'reports.requests-by-status',
                        'label' => 'Solicitudes por Estado',
                        'icon' => 'fas fa-list-check',
                        'match' => ['reports.requests-by-status'],
                    ],
                    [
                        'route' => 'reports.criticality-levels',
                        'label' => 'Niveles de Criticidad',
                        'icon' => 'fas fa-triangle-exclamation',
                        'match' => ['reports.criticality-levels'],
                    ],
                    [
                        'route' => 'reports.service-performance',
                        'label' => 'Rendimiento por Servicio',
                        'icon' => 'fas fa-gauge-high',
                        'match' => ['reports.service-performance'],
                    ],
                    [
                        'route' => 'reports.monthly-trends',
                        'label' => 'Tendencias Mensuales',
                        'icon' => 'fas fa-chart-line',
                        'match' => ['reports.monthly-trends'],
                    ],
                    [
                        'route' => 'reports.timeline.index',
                        'label' => 'Línea de Tiempo',
                        'icon' => 'fas fa-clock',
                        'match' => ['reports.timeline.*'],
                    ],
                    [
                        'route' => 'reports.timeline.by-ticket',
                        'label' => 'Timeline por Ticket',
                        'icon' => 'fas fa-ticket',
                        'match' => ['reports.timeline.by-ticket'],
                    ],
                    [
                        'route' => 'reports.time-range.index',
                        'label' => 'Reporte por Rango',
                        'icon' => 'fas fa-calendar-days',
                        'match' => ['reports.time-range.*'],
                    ],
                    [
                        'route' => 'reports.cuts.index',
                        'label' => 'Cortes',
                        'icon' => 'fas fa-layer-group',
                        'match' => ['reports.cuts.*'],
                    ],
                    [
                        'route' => 'reports.cuts.create',
                        'label' => 'Crear Corte',
                        'icon' => 'fas fa-plus',
                        'match' => ['reports.cuts.create'],
                    ],
                ],
            ],
            [
                'key' => 'technicians',
                'label' => 'Técnicos',
                'icon' => 'fas fa-user-cog',
                'type' => 'dropdown',
                'match' => ['technicians.*', 'tasks.*', 'standard-tasks.*', 'technician-schedule.*'],
                'links' => [
                    [
                        'route' => 'technician-schedule.my-agenda',
                        'label' => 'Mi Agenda',
                        'icon' => 'fas fa-clipboard-list',
                        'match' => ['technician-schedule.my-agenda'],
                    ],
                    [
                        'route' => 'technician-schedule.index',
                        'label' => 'Calendario',
                        'icon' => 'fas fa-calendar-alt',
                        'match' => ['technician-schedule.index'],
                    ],
                    [
                        'route' => 'technician-schedule.team-capacity',
                        'label' => 'Capacidad del Equipo',
                        'icon' => 'fas fa-chart-line',
                        'match' => ['technician-schedule.team-capacity'],
                    ],
                    [
                        'route' => 'tasks.index',
                        'label' => 'Tareas',
                        'icon' => 'fas fa-tasks',
                        'match' => ['tasks.*'],
                    ],
                    [
                        'route' => 'standard-tasks.index',
                        'label' => 'Tareas Predefinidas',
                        'icon' => 'fas fa-layer-group',
                        'match' => ['standard-tasks.*'],
                    ],
                    [
                        'route' => 'technicians.index',
                        'label' => 'Gestión de Técnicos',
                        'icon' => 'fas fa-users-cog',
                        'match' => ['technicians.*'],
                    ],
                ],
            ],
            [
                'key' => 'catalogs',
                'label' => 'Catálogos',
                'icon' => 'fas fa-list-alt',
                'type' => 'dropdown',
                'match' => ['requester-management.*', 'companies.*', 'service-families.*', 'services.*', 'sub-services.*', 'slas.*', 'users.*'],
                'links' => [
                    [
                        'route' => 'users.index',
                        'label' => 'Usuarios',
                        'icon' => 'fas fa-user',
                        'match' => ['users.*'],
                    ],
                    [
                        'route' => 'requester-management.requesters.index',
                        'label' => 'Solicitantes',
                        'icon' => 'fas fa-users',
                        'match' => ['requester-management.*'],
                    ],
                    [
                        'route' => 'requester-management.departments.index',
                        'label' => 'Departamentos',
                        'icon' => 'fas fa-sitemap',
                        'match' => ['requester-management.departments.*'],
                    ],
                    [
                        'route' => 'companies.index',
                        'label' => 'Entidades',
                        'icon' => 'fas fa-building',
                        'match' => ['companies.*'],
                    ],
                    [
                        'route' => 'contracts.index',
                        'label' => 'Contratos',
                        'icon' => 'fas fa-file-contract',
                        'match' => ['contracts.*'],
                    ],
                    [
                        'route' => 'service-families.index',
                        'label' => 'Familias',
                        'icon' => 'fas fa-layer-group',
                        'match' => ['service-families.*'],
                    ],
                    [
                        'route' => 'services.index',
                        'label' => 'Servicios',
                        'icon' => 'fas fa-cog',
                        'match' => ['services.*'],
                    ],
                    [
                        'route' => 'sub-services.index',
                        'label' => 'Sub-Servicios',
                        'icon' => 'fas fa-cogs',
                        'match' => ['sub-services.*'],
                    ],
                    [
                        'route' => 'slas.index',
                        'label' => 'SLAs',
                        'icon' => 'fas fa-clock',
                        'match' => ['slas.*'],
                    ],
                ],
            ],
        ];

        $isSectionActive = function ($patterns) {
            if (empty($patterns)) {
                return false;
            }

            return request()->routeIs(...(array) $patterns);
        };

        $workspaceName = $currentWorkspace->name ?? '';
        $workspaceDisplayName = $workspaceName;
        $workspaceKey = Str::lower($workspaceName);
        $workspaceAccent = $currentWorkspace->primary_color ?? '#DC2626';
        $workspaceAccentBg = $workspaceAccent . '1A';
        $workspaceLogo = !empty($currentWorkspace?->logo_path) ? asset('storage/' . $currentWorkspace->logo_path) : null;
        $activeContract = $currentWorkspace?->activeContract;
        $activeContractLabel = $activeContract ? ($activeContract->number ?: $activeContract->name) : 'Sin contrato activo';
        if (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'movilidad')) {
            $workspaceAccent = '#BED000';
            $workspaceAccentBg = '#BED0002E';
        } elseif (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'cultura')) {
            $workspaceAccent = '#493D86';
            $workspaceAccentBg = '#493D861F';
        }

        if (!$workspaceLogo && Str::contains($workspaceKey, 'movilidad')) {
            $workspaceLogo = asset('movilidad.jpg');
        } elseif (!$workspaceLogo && Str::contains($workspaceKey, 'cultura')) {
            $workspaceLogo = asset('cultura.png');
        }
    @endphp
    <!-- Navigation -->
    <nav class="bg-red-600 text-white shadow-lg border-b-4" id="mainNavigation"
        style="border-bottom-color: {{ $workspaceAccent }};">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6">
            <div class="flex justify-between items-center py-2 sm:py-3 md:py-4">
                <!-- Logo y menú principal -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="{{ url('/dashboard') }}"
                        class="text-xl font-bold flex items-center logo-container logo-particles" id="logoLink">
                        <!-- Icono grande para escritorio con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/sapp_logo_lg.png" alt="Sistema Sapp" class="logo-large logo-glow logo-pulse rounded-md"
                                id="logoLarge">
                        </div>
                        <!-- Icono pequeño para móvil con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/logo_sapp_xs.png" alt="Sistema Sapp" class="logo-small logo-glow logo-pulse rounded-md"
                                id="logoSmall">
                        </div>
                        {{-- <span class="hidden sm:inline transition-colors duration-300 hover:text-red-200">Sistema Sapp</span> --}}
                    </a>

                    @auth
                        <!-- Menú para desktop -->
                        <div class="hidden md:flex items-center space-x-2">
                            @foreach ($navSections as $section)
                                @php
                                    $sectionActive = $isSectionActive($section['match'] ?? []);
                                @endphp
                                @if (($section['type'] ?? 'link') === 'link')
                                    <a href="{{ route($section['route']) }}"
                                        class="primary-nav-link {{ $sectionActive ? 'nav-item-active' : '' }}">
                                        <i class="{{ $section['icon'] }}"></i>
                                        {{ $section['label'] }}
                                    </a>
                                @else
                                    <div class="relative" data-dropdown="{{ $section['key'] }}">
                                        <button type="button"
                                            class="primary-nav-link {{ $sectionActive ? 'nav-item-active' : '' }}"
                                            data-dropdown-toggle="{{ $section['key'] }}" aria-expanded="false"
                                            aria-haspopup="true">
                                            <i class="{{ $section['icon'] }}"></i>
                                            {{ $section['label'] }}
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </button>
                                        <div class="dropdown-menu" data-dropdown-menu="{{ $section['key'] }}">
                                            @foreach ($section['links'] as $link)
                                                <a href="{{ route($link['route']) }}"
                                                    class="{{ $isSectionActive($link['match'] ?? []) ? 'bg-red-700' : '' }}">
                                                    <i class="{{ $link['icon'] }}"></i>
                                                    {{ $link['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endauth
                </div>

                <!-- Menú de usuario -->
                <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-4">
                    @auth
                        @if(isset($currentWorkspace))
                            <a href="{{ route('workspaces.select') }}"
                               class="hidden lg:flex items-center w-[150px] px-2 py-1.5 rounded-xl border border-white/15 bg-white/10 backdrop-blur-sm hover:bg-white/20 transition"
                               title="Cambiar entidad activa">
                                <div class="flex items-center gap-2 w-full">
                                    @if ($workspaceLogo)
                                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-white ring-1 ring-black/10 shrink-0">
                                            <img src="{{ $workspaceLogo }}" alt="{{ $workspaceDisplayName }}" class="max-w-[1.75rem] max-h-[1.75rem] object-contain">
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center w-9 h-9 rounded-lg ring-1 ring-white/20 shrink-0" style="background-color: {{ $workspaceAccentBg }}; color: #ffffff;">
                                            <i class="fas fa-building text-sm"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] font-semibold text-white leading-tight truncate">{{ $workspaceDisplayName }}</p>
                                        <p class="text-[11px] text-white/80 leading-none truncate">{{ $activeContractLabel }}</p>
                                    </div>
                                </div>
                            </a>
                        @endif

                        <div
                            class="flex items-center space-x-1 sm:space-x-2 bg-red-700 px-2 sm:px-3 py-1 rounded-full transition-all duration-300 hover:bg-red-800 hover:scale-105">
                            <i class="fas fa-user-circle text-sm sm:text-base"></i>
                            <span
                                class="hidden sm:inline text-sm md:text-base truncate max-w-[100px] md:max-w-none">{{ Auth::user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="hover:bg-red-700 px-2 sm:px-3 py-1 sm:py-2 rounded transition-all duration-300 hover:scale-105 text-sm md:text-base"
                                title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden lg:inline ml-1">Salir</span>
                            </button>
                        </form>

                        <!-- Botón menú móvil -->
                        <button type="button"
                            class="md:hidden text-white focus:outline-none transition-transform duration-300 hover:scale-110 p-2"
                            data-mobile-menu-toggle aria-expanded="false" aria-controls="mobileMenuPanel">
                            <i class="fas fa-bars text-lg sm:text-xl"></i>
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                            class="hover:bg-red-700 px-3 py-2 rounded transition-all duration-300 hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-1"></i>
                            <span class="hidden sm:inline">Iniciar Sesión</span>
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Menú móvil -->
            @auth
                <div id="mobileMenuPanel"
                    class="mobile-menu md:hidden bg-red-700 mt-2 rounded-2xl shadow-xl overflow-hidden hidden">
                    <div class="py-4 px-4 space-y-3">
                        @if(isset($currentWorkspace))
                            <a href="{{ route('workspaces.select') }}" class="block bg-white/10 rounded-2xl p-3 hover:bg-white/20 transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-base text-white font-semibold truncate">{{ $workspaceDisplayName }}</p>
                                        <p class="text-sm text-white/80 truncate">{{ $activeContractLabel }}</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-white/80 text-xs"></i>
                                </div>
                            </a>
                        @endif

                        @foreach ($navSections as $section)
                            @php
                                $sectionActive = $isSectionActive($section['match'] ?? []);
                            @endphp
                            @if (($section['type'] ?? 'link') === 'link')
                                <a href="{{ route($section['route']) }}"
                                    class="mobile-nav-link {{ $sectionActive ? 'mobile-nav-link-active' : '' }}">
                                    <i class="{{ $section['icon'] }}"></i>
                                    <span>{{ $section['label'] }}</span>
                                </a>
                            @else
                                <div class="bg-white/5 rounded-2xl p-2">
                                    <button type="button" class="mobile-section-trigger"
                                        data-mobile-section-trigger="{{ $section['key'] }}"
                                        data-default-open="{{ $sectionActive ? 'true' : 'false' }}"
                                        aria-expanded="{{ $sectionActive ? 'true' : 'false' }}">
                                        <span class="flex items-center gap-3">
                                            <i class="{{ $section['icon'] }}"></i>
                                            {{ $section['label'] }}
                                        </span>
                                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                                    </button>
                                    <div class="mt-2 space-y-1 {{ $sectionActive ? '' : 'hidden' }}"
                                        data-mobile-section-panel="{{ $section['key'] }}">
                                        @foreach ($section['links'] as $link)
                                            <a href="{{ route($link['route']) }}"
                                                class="mobile-nav-link {{ $isSectionActive($link['match'] ?? []) ? 'mobile-nav-link-active' : '' }}">
                                                <i class="{{ $link['icon'] }}"></i>
                                                {{ $link['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-3 sm:py-4 md:py-6 px-3 sm:px-4 md:px-6 lg:px-8">
        <!-- Flash Messages (toast flotante para evitar salto de layout) -->
        @if (!($__env->hasSection('disableGlobalFlash')) && (session('success') || session('error')))
            <div class="fixed top-20 right-4 z-50 w-[calc(100%-2rem)] sm:w-auto sm:max-w-md space-y-2">
                @if (session('success'))
                    <div class="alert-flash flex items-start gap-3 bg-green-100 border border-green-400 text-green-800 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base"
                        role="status" aria-live="polite">
                        <div class="flex-1">
                            {{ session('success') }}
                        </div>
                        <button type="button" class="text-green-800/70 hover:text-green-900" aria-label="Cerrar"
                            data-flash-close>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-flash flex items-start gap-3 bg-red-100 border border-red-400 text-red-800 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base"
                        role="alert" aria-live="assertive">
                        <div class="flex-1">
                            {{ session('error') }}
                        </div>
                        <button type="button" class="text-red-800/70 hover:text-red-900" aria-label="Cerrar"
                            data-flash-close>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Page Header -->
        @php
            $hidePageHeader = $__env->hasSection('hidePageHeader');
            $pageTitle = trim($__env->yieldContent('title'));
        @endphp
        @if(!$hidePageHeader && $pageTitle !== '')
            <div class="mb-4 sm:mb-6">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ $pageTitle }}</h1>
                    @if(isset($serviceRequest) && ($serviceRequest->status ?? null) === 'CERRADA')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-red-500/70 bg-red-600 text-[11px] font-semibold uppercase tracking-wide text-white">
                            Cerrada
                        </span>
                    @endif
                </div>
                @yield('breadcrumb')
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
    </div>

    <!-- Scripts -->
    <script>
        // Función para confirmar eliminaciones
        function confirmDelete(message = '¿Está seguro de que desea eliminar este registro?') {
            return confirm(message);
        }

        // Auto-ocultar mensajes flash después de 5 segundos
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('.alert-flash, .flash-message');
            flashMessages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        document.addEventListener('click', function(event) {
            const closeBtn = event.target.closest('[data-flash-close]');
            if (!closeBtn) return;
            const flash = closeBtn.closest('.alert-flash, .flash-message');
            if (!flash) return;
            flash.style.transition = 'opacity 0.2s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 200);
        });

        document.addEventListener('DOMContentLoaded', function() {
            setupNavigationMenus();
            setupLogoEffects();
        });

        function setupNavigationMenus() {
            const dropdownWrappers = document.querySelectorAll('[data-dropdown]');
            const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');
            const mobileSectionButtons = document.querySelectorAll('[data-mobile-section-trigger]');
            const mobileSectionPanels = document.querySelectorAll('[data-mobile-section-panel]');
            let desktopOpenKey = null;
            let mobileMenuOpen = false;
            let currentMobileSection = null;

            function closeAllDesktopDropdowns() {
                dropdownWrappers.forEach(wrapper => {
                    const button = wrapper.querySelector('[data-dropdown-toggle]');
                    const menu = wrapper.querySelector('[data-dropdown-menu]');
                    if (button && menu) {
                        menu.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
                desktopOpenKey = null;
            }

            function closeAllMobileSections() {
                mobileSectionPanels.forEach(panel => panel.classList.add('hidden'));
                mobileSectionButtons.forEach(button => button.setAttribute('aria-expanded', 'false'));
                currentMobileSection = null;
            }

            function resetMobileSectionsToDefault() {
                closeAllMobileSections();
                mobileSectionButtons.forEach(button => {
                    if (button.dataset.defaultOpen === 'true') {
                        const key = button.dataset.mobileSectionTrigger;
                        const panel = document.querySelector(
                            `[data-mobile-section-panel=\"${key}\"]`
                        );
                        if (panel) {
                            panel.classList.remove('hidden');
                            button.setAttribute('aria-expanded', 'true');
                            currentMobileSection = key;
                        }
                    }
                });
            }

            function closeMobileMenu() {
                if (mobileMenuPanel) {
                    mobileMenuPanel.classList.add('hidden');
                }
                if (mobileMenuToggle) {
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
                mobileMenuOpen = false;
                resetMobileSectionsToDefault();
            }

            dropdownWrappers.forEach(wrapper => {
                const button = wrapper.querySelector('[data-dropdown-toggle]');
                const menu = wrapper.querySelector('[data-dropdown-menu]');
                if (!button || !menu) {
                    return;
                }
                const key = wrapper.dataset.dropdown;

                const openMenu = () => {
                    closeAllDesktopDropdowns();
                    menu.classList.add('show');
                    button.setAttribute('aria-expanded', 'true');
                    desktopOpenKey = key;
                };

                const closeMenu = () => {
                    menu.classList.remove('show');
                    button.setAttribute('aria-expanded', 'false');
                    if (desktopOpenKey === key) {
                        desktopOpenKey = null;
                    }
                };

                let hoverTimeout;

                button.addEventListener('click', event => {
                    event.preventDefault();
                    if (desktopOpenKey === key) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });

                button.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                    openMenu();
                });

                button.addEventListener('mouseleave', () => {
                    hoverTimeout = setTimeout(closeMenu, 200);
                });

                menu.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                });

                menu.addEventListener('mouseleave', () => {
                    hoverTimeout = setTimeout(closeMenu, 200);
                });
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('[data-dropdown]')) {
                    closeAllDesktopDropdowns();
                }
            });

            mobileSectionButtons.forEach(button => {
                const key = button.dataset.mobileSectionTrigger;
                const panel = document.querySelector(`[data-mobile-section-panel=\"${key}\"]`);
                if (!panel) {
                    return;
                }

                button.addEventListener('click', () => {
                    const isOpen = currentMobileSection === key;
                    if (isOpen) {
                        panel.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                        currentMobileSection = null;
                    } else {
                        closeAllMobileSections();
                        panel.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        currentMobileSection = key;
                    }
                });
            });

            resetMobileSectionsToDefault();

            if (mobileMenuToggle && mobileMenuPanel) {
                mobileMenuToggle.addEventListener('click', () => {
                    mobileMenuOpen = !mobileMenuOpen;
                    if (mobileMenuOpen) {
                        mobileMenuPanel.classList.remove('hidden');
                        mobileMenuToggle.setAttribute('aria-expanded', 'true');
                        resetMobileSectionsToDefault();
                    } else {
                        closeMobileMenu();
                    }
                });

                document.addEventListener('click', event => {
                    if (
                        mobileMenuOpen &&
                        !mobileMenuPanel.contains(event.target) &&
                        !mobileMenuToggle.contains(event.target)
                    ) {
                        closeMobileMenu();
                    }
                });
            }

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeAllDesktopDropdowns();
                    if (mobileMenuOpen) {
                        closeMobileMenu();
                    }
                }
            });
        }

        function setupLogoEffects() {
            const logoLink = document.getElementById('logoLink');
            const logoLarge = document.getElementById('logoLarge');
            const logoSmall = document.getElementById('logoSmall');

            if (!logoLink) {
                return;
            }

            logoLink.addEventListener('click', function(e) {
                createParticles(e, logoLink);
            });

            logoLink.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            logoLink.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });

            setTimeout(() => {
                if (logoLarge) logoLarge.style.animation = 'none';
                if (logoSmall) logoSmall.style.animation = 'none';
            }, 3000);
        }

        // Función para crear partículas (efecto opcional)
        function createParticles(event, element) {
            const rect = element.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            for (let i = 0; i < 8; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                const angle = Math.random() * Math.PI * 2;
                const distance = 20 + Math.random() * 30;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance;

                particle.style.setProperty('--tx', `${tx}px`);
                particle.style.setProperty('--ty', `${ty}px`);
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;

                particle.style.animation = `floatParticle 0.6s ease-out forwards`;
                element.appendChild(particle);

                setTimeout(() => {
                    particle.remove();
                }, 600);
            }
        }
    </script>
    <script>
        function onClick(e) {
            e.preventDefault();
            grecaptcha.enterprise.ready(async () => {
                const token = await grecaptcha.enterprise.execute('6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE', {
                    action: 'LOGIN'
                });
            });
        }
    </script>

    @yield('scripts')
    @stack('scripts')

    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>
