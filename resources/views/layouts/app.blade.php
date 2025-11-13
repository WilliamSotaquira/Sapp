<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Servicios')</title>

    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}
    <script src="https://cdn.tailwindcss.com"></script>


    <style type="text/tailwindcss">
        @theme {
            --color-clifford: #da373d;
        }
    </style>

    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> --}}
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
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        @media (max-width: 768px) {
            .dropdown-menu {
                position: static;
                box-shadow: none;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo y menú principal -->
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/') }}" class="text-xl font-bold flex items-center">
                        <i class="fas fa-cogs mr-2"></i>
                        Sistema Sapp
                    </a>

                    @auth
                        <!-- Menú para desktop -->
                        <div class="hidden md:flex space-x-1">
                            <!-- Acciones frecuentes (primero) -->
                            <a href="{{ route('service-requests.index') }}"
                                class="flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                  {{ request()->routeIs('service-requests.*') ? 'nav-item-active' : '' }}">
                                <i class="fas fa-tasks mr-2"></i>
                                Solicitudes
                            </a>

                            <a href="{{ route('reports.index') }}"
                                class="flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                  {{ request()->routeIs('reports.*') ? 'nav-item-active' : '' }}">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Reportes
                            </a>

                            <!-- Menú desplegable para catálogos -->
                            <div class="dropdown relative">
                                <button
                                    class="flex items-center hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200
                                          {{ request()->routeIs('service-families.*', 'services.*', 'sub-services.*', 'slas.*') ? 'nav-item-active' : '' }}">
                                    <i class="fas fa-list-alt mr-2"></i>
                                    Catálogos
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div class="dropdown-menu rounded-b mt-1">
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
                <div class="flex items-center space-x-4">
                    @auth
                        <div class="flex items-center space-x-2 bg-red-700 px-3 py-1 rounded-full">
                            <i class="fas fa-user-circle"></i>
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200"
                                title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden sm:inline ml-1">Salir</span>
                            </button>
                        </form>

                        <!-- Botón menú móvil -->
                        <button id="mobileMenuButton" class="md:hidden text-white focus:outline-none">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                            class="hover:bg-red-700 px-3 py-2 rounded transition-colors duration-200">
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
                        <a href="{{ route('service-requests.index') }}"
                            class="flex items-center px-4 py-2 hover:bg-red-600 {{ request()->routeIs('service-requests.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-tasks mr-3"></i>
                            Solicitudes
                        </a>

                        <a href="{{ route('reports.index') }}"
                            class="flex items-center px-4 py-2 hover:bg-red-600 {{ request()->routeIs('reports.*') ? 'bg-red-800' : '' }}">
                            <i class="fas fa-chart-bar mr-3"></i>
                            Reportes
                        </a>

                        <div class="px-4 py-2 font-semibold text-red-200 border-b border-red-500">
                            <i class="fas fa-list-alt mr-3"></i>
                            Catálogos
                        </div>

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
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        @if (session('success'))
            <div
                class="alert-flash bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-flash bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">@yield('title')</h1>
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

            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>


    @yield('scripts')

    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>
