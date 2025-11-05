<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Servicios')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/') }}" class="text-xl font-bold">Sistema Sapp</a>
                    @auth
                    <div class="hidden md:flex space-x-4">
                        <a href="{{ route('service-families.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">Familias</a>
                        <a href="{{ route('services.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">Servicios</a>
                        <a href="{{ route('sub-services.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">Sub-Servicios</a>
                        <a href="{{ route('slas.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">SLAs</a>
                        <a href="{{ route('service-requests.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">Solicitudes</a>
                        <a href="{{ route('reports.index') }}" class="hover:bg-red-700 px-3 py-2 rounded">Reportes</a>
                    </div>
                    @endauth
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                    <span>{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:bg-blue-700 px-3 py-2 rounded">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </button>
                    </form>
                    @else
                    <a href="{{ route('login') }}" class="hover:bg-blue-700 px-3 py-2 rounded">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        @if(session('success'))
        <div class="alert-flash bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
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
    </script>

    @yield('scripts')
</body>

</html>
