<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Servicios')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style type="text/tailwindcss">
        @theme {
            --color-clifford: #da373d;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .nav-item-active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid white;
        }

        .mobile-menu {
            transition: all 0.3s ease;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #dc2626;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-radius: 0 0 4px 4px;
            top: 100%;
            left: 0;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-trigger:hover .dropdown-menu,
        .dropdown-menu:hover {
            display: block;
        }

        /* Estilos mejorados para los logos */
        .logo-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .logo-large, .logo-small {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .logo-large {
            height: 4rem;
            width: auto;
        }

        .logo-small {
            height: 2rem;
            width: auto;
        }

        .logo-large:hover, .logo-small:hover {
            transform: scale(1.05) rotate(2deg);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2)) brightness(1.1);
        }

        .logo-large:active, .logo-small:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }

        /* Efecto de pulso sutil al cargar */
        @keyframes gentlePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
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
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
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
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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
    <!-- Navigation -->
    <nav class="bg-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6">
            <div class="flex justify-between items-center py-2 sm:py-3 md:py-4">
                <!-- Logo y menú principal -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="{{ url('/dashboard') }}" class="text-xl font-bold flex items-center logo-container logo-particles" id="logoLink">
                        <!-- Icono grande para escritorio con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/icon-sapp_lg.svg" alt="Sistema Sapp"
                                 class="logo-large logo-glow logo-pulse"
                                 id="logoLarge">
                        </div>
                        <!-- Icono pequeño para móvil con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/icon-sapp_xs.svg" alt="Sistema Sapp"
                                 class="logo-small logo-glow logo-pulse"
                                 id="logoSmall">
                        </div>
                        {{-- <span class="hidden sm:inline transition-colors duration-300 hover:text-red-200">Sistema Sapp</span> --}}
                    </a>

                    @auth
                        <!-- Menú para desktop -->
                        <div class="hidden md:flex space-x-1">
                            <!-- Menú desplegable para Solicitudes -->
                            <div class="dropdown relative" id="requestsDropdown">
                                <button
                                    class="dropdown-trigger flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                          {{ request()->routeIs('service-requests.*') ? 'nav-item-active' : '' }}"
                                    id="requestsButton">
                                    <i class="fas fa-tasks mr-2"></i>
                                    Solicitudes
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div class="dropdown-menu rounded-b mt-1" id="requestsMenu">
                                    <a href="{{ route('service-requests.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('service-requests.index') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-list mr-2"></i>Ver Solicitudes
                                    </a>
                                    <a href="{{ route('service-requests.create') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('service-requests.create') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-plus-circle mr-2"></i>Crear Solicitud
                                    </a>
                                </div>
                            </div>

                            <a href="{{ route('reports.index') }}"
                                class="flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                  {{ request()->routeIs('reports.*') ? 'nav-item-active' : '' }}">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Reportes
                            </a>

                            <!-- Menú desplegable para Técnicos -->
                            <div class="dropdown relative" id="technicianDropdown">
                                <button
                                    class="dropdown-trigger flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                          {{ request()->routeIs('technicians.*', 'tasks.*', 'technician-schedule.*') ? 'nav-item-active' : '' }}"
                                    id="technicianButton">
                                    <i class="fas fa-user-cog mr-2"></i>
                                    Técnicos
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div class="dropdown-menu rounded-b mt-1" id="technicianMenu">
                                    <a href="{{ route('technician-schedule.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('technician-schedule.index') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-calendar-alt mr-2"></i>Calendario
                                    </a>
                                    <a href="{{ route('technician-schedule.my-agenda') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('technician-schedule.my-agenda') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-clipboard-list mr-2"></i>Mi Agenda
                                    </a>
                                    <a href="{{ route('technician-schedule.team-capacity') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('technician-schedule.team-capacity') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-chart-line mr-2"></i>Capacidad del Equipo
                                    </a>
                                    <div class="border-t border-red-500 my-1"></div>
                                    <a href="{{ route('tasks.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('tasks.index') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-tasks mr-2"></i>Tareas
                                    </a>
                                    <a href="{{ route('technicians.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('technicians.index') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-users-cog mr-2"></i>Gestión de Técnicos
                                    </a>
                                </div>
                            </div>

                            <!-- Menú desplegable para catálogos -->
                            <div class="dropdown relative" id="catalogDropdown">
                                <button
                                    class="dropdown-trigger flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                          {{ request()->routeIs('service-families.*', 'services.*', 'sub-services.*', 'slas.*') ? 'nav-item-active' : '' }}"
                                    id="catalogButton">
                                    <i class="fas fa-list-alt mr-2"></i>
                                    Catálogos
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div class="dropdown-menu rounded-b mt-1" id="catalogMenu">
                                    {{-- Solicitantes --}}
                                    <a href="{{ route('requester-management.requesters.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('requester-management.*') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-users mr-2"></i>Solicitantes
                                    </a>
                                    <a href="{{ route('service-families.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('service-families.*') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-layer-group mr-2"></i>Familias
                                    </a>
                                    <a href="{{ route('services.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('services.*') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-cog mr-2"></i>Servicios
                                    </a>
                                    <a href="{{ route('sub-services.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('sub-services.*') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-cogs mr-2"></i>Sub-Servicios
                                    </a>
                                    <a href="{{ route('slas.index') }}"
                                        class="block px-4 py-2 hover:bg-red-700 {{ request()->routeIs('slas.*') ? 'bg-red-700' : '' }}">
                                        <i class="fas fa-clock mr-2"></i>SLAs
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endauth
                </div>

                <!-- Menú de usuario -->
                <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-4">
                    @auth
                        <div class="flex items-center space-x-1 sm:space-x-2 bg-red-700 px-2 sm:px-3 py-1 rounded-full transition-all duration-300 hover:bg-red-800 hover:scale-105">
                            <i class="fas fa-user-circle text-sm sm:text-base"></i>
                            <span class="hidden sm:inline text-sm md:text-base truncate max-w-[100px] md:max-w-none">{{ Auth::user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="hover:bg-red-700 px-2 sm:px-3 py-1 sm:py-2 rounded transition-all duration-300 hover:scale-105 text-sm md:text-base"
                                title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden lg:inline ml-1">Salir</span>
                            </button>
                        </form>

                        <!-- Botón menú móvil -->
                        <button id="mobileMenuButton" class="md:hidden text-white focus:outline-none transition-transform duration-300 hover:scale-110 p-2">
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
                <div id="mobileMenu" class="mobile-menu md:hidden bg-red-700 mt-2 rounded-lg overflow-hidden hidden">
                    <div class="py-2 space-y-1">
                        <div class="px-4 py-2 font-semibold text-red-200 border-b border-red-500">
                            <i class="fas fa-tasks mr-3"></i>
                            Solicitudes
                        </div>

                        <a href="{{ route('service-requests.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('service-requests.index') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-list mr-3"></i>
                            Ver Solicitudes
                        </a>

                        <a href="{{ route('service-requests.create') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('service-requests.create') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-plus-circle mr-3"></i>
                            Crear Solicitud
                        </a>

                        <a href="{{ route('reports.index') }}"
                            class="flex items-center px-4 py-2 hover:bg-red-600 {{ request()->routeIs('reports.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-chart-bar mr-3"></i>
                            Reportes
                        </a>

                        <div class="px-4 py-2 font-semibold text-red-200 border-b border-red-500">
                            <i class="fas fa-user-cog mr-3"></i>
                            Técnicos
                        </div>

                        <a href="{{ route('technician-schedule.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('technician-schedule.index') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            Calendario
                        </a>

                        <a href="{{ route('technician-schedule.my-agenda') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('technician-schedule.my-agenda') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-clipboard-list mr-3"></i>
                            Mi Agenda
                        </a>

                        <a href="{{ route('technician-schedule.team-capacity') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('technician-schedule.team-capacity') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-chart-line mr-3"></i>
                            Capacidad del Equipo
                        </a>

                        <a href="{{ route('tasks.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('tasks.index') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-tasks mr-3"></i>
                            Tareas
                        </a>

                        <a href="{{ route('technicians.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('technicians.index') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-users-cog mr-3"></i>
                            Gestión de Técnicos
                        </a>

                        <div class="px-4 py-2 font-semibold text-red-200 border-b border-red-500">
                            <i class="fas fa-list-alt mr-3"></i>
                            Catálogos
                        </div>

                        <a href="{{ route('requester-management.requesters.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('requester-management.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-users mr-3"></i>
                            Solicitantes
                        </a>

                        <a href="{{ route('service-families.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('service-families.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-layer-group mr-3"></i>
                            Familias
                        </a>

                        <a href="{{ route('services.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('services.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-cog mr-3"></i>
                            Servicios
                        </a>

                        <a href="{{ route('sub-services.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('sub-services.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-cogs mr-3"></i>
                            Sub-Servicios
                        </a>

                        <a href="{{ route('slas.index') }}"
                            class="flex items-center px-6 py-2 hover:bg-red-600 {{ request()->routeIs('slas.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-clock mr-3"></i>
                            SLAs
                        </a>
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-3 sm:py-4 md:py-6 px-3 sm:px-4 md:px-6 lg:px-8">
        <!-- Flash Messages -->
        @if (session('success'))
            <div
                class="alert-flash bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2 sm:py-3 rounded relative mb-3 sm:mb-4 text-sm sm:text-base">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-flash bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded relative mb-3 sm:mb-4 text-sm sm:text-base">
                {{ session('error') }}
            </div>
        @endif

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">@yield('title')</h1>
            @yield('breadcrumb')
        </div>

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

        // Toggle menú móvil
        document.getElementById('mobileMenuButton').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden');
        });

        // Cerrar menú móvil al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuButton = document.getElementById('mobileMenuButton');

            if (mobileMenu && mobileMenuButton && !mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Control mejorado del menú desplegable de catálogos
        function setupDropdown(buttonId, menuId) {
            const button = document.getElementById(buttonId);
            const menu = document.getElementById(menuId);
            let closeTimeout;

            if (button && menu) {
                // Abrir menú al hacer hover o clic
                button.addEventListener('mouseenter', () => openMenu(menu));
                menu.addEventListener('mouseenter', () => openMenu(menu));

                // Cerrar menú con retraso al salir
                button.addEventListener('mouseleave', function() {
                    closeTimeout = setTimeout(() => {
                        if (!menu.matches(':hover')) {
                            closeMenu(menu);
                        }
                    }, 300);
                });

                menu.addEventListener('mouseleave', function() {
                    closeTimeout = setTimeout(() => {
                        if (!button.matches(':hover')) {
                            closeMenu(menu);
                        }
                    }, 300);
                });

                // Cancelar cierre si el cursor vuelve al menú
                menu.addEventListener('mouseenter', function() {
                    clearTimeout(closeTimeout);
                });

                button.addEventListener('mouseenter', function() {
                    clearTimeout(closeTimeout);
                });

                // Soporte para dispositivos táctiles
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (menu.classList.contains('show')) {
                        closeMenu(menu);
                    } else {
                        openMenu(menu);
                    }
                });

                // Cerrar menú al hacer clic fuera
                document.addEventListener('click', function(event) {
                    if (!button.contains(event.target) && !menu.contains(event.target)) {
                        closeMenu(menu);
                    }
                });
            }
        }

        function openMenu(menu) {
            if (menu) {
                menu.classList.add('show');
            }
        }

        function closeMenu(menu) {
            if (menu) {
                menu.classList.remove('show');
            }
        }

        // Inicializar todos los menús desplegables
        setupDropdown('catalogButton', 'catalogMenu');
        setupDropdown('requestsButton', 'requestsMenu');
        setupDropdown('technicianButton', 'technicianMenu');

        // Efectos especiales para el logo
        document.addEventListener('DOMContentLoaded', function() {
            const logoLink = document.getElementById('logoLink');
            const logoLarge = document.getElementById('logoLarge');
            const logoSmall = document.getElementById('logoSmall');

            // Efecto de partículas al hacer clic en el logo
            logoLink.addEventListener('click', function(e) {
                createParticles(e, logoLink);
            });

            // Efecto de vibración sutil al hacer hover
            logoLink.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            logoLink.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });

            // Efecto de carga inicial
            setTimeout(() => {
                if (logoLarge) logoLarge.style.animation = 'none';
                if (logoSmall) logoSmall.style.animation = 'none';
            }, 3000);
        });

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

    @yield('scripts')

    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>
