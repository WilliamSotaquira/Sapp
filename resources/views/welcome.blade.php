<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Sapp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #454E59 0%, #747C8C 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-chart-line text-red-600 text-2xl mr-3"></i>
                    <span class="text-xl font-bold text-gray-800">Weirdoware - Sapp</span>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-tachometer-alt mr-2"></i>Sapp
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-800">
                                <i class="fas fa-sign-out-alt mr-1"></i>Salir
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-800">Iniciar Sesión</a>
                        <a href="{{ route('register') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Registrarse</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center">
                <div class="lg:w-1/2 mb-10 lg:mb-0">
                    <h1 class="text-4xl lg:text-5xl font-bold mb-6">Sistema de Gestión Sapp</h1>
                    <p class="text-xl mb-8 text-red-100">
                        Plataforma integral para la administración y seguimiento de servicios, solicitudes y acuerdos de nivel de servicio.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('public.tracking.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition text-center shadow-lg">
                            <i class="fas fa-search mr-2"></i>Consultar mi Solicitud
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-white text-gray-600 px-6 py-3 rounded-lg font-semibold hover:bg-red-50 transition text-center">
                                <i class="fas fa-tachometer-alt mr-2"></i>Ir a Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="bg-white text-red-600 px-6 py-3 rounded-lg font-semibold hover:bg-red-50 transition text-center">
                                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="lg:w-1/2 text-center">
                    <i class="fas fa-chart-network text-blue-200" style="font-size: 300px;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Funcionalidades Principales</h2>
                <p class="text-xl text-gray-600">Sistema diseñado para optimizar la gestión de servicios y solicitudes</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Gestión de Servicios -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-cogs text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Gestión de Servicios</h3>
                    <p class="text-gray-600">
                        Administración completa de familias de servicio, servicios y sub-servicios con estructura jerárquica.
                    </p>
                </div>

                <!-- Acuerdos de Nivel de Servicio -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-handshake text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Acuerdos de Nivel de Servicio</h3>
                    <p class="text-gray-600">
                        Configuración y seguimiento de SLA's con tiempos de respuesta y niveles de criticidad.
                    </p>
                </div>

                <!-- Solicitudes de Servicio -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-tasks text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Solicitudes de Servicio</h3>
                    <p class="text-gray-600">
                        Sistema completo de tickets con seguimiento de estado, asignación y tiempos de cumplimiento.
                    </p>
                </div>

                <!-- Sistema de Pausas -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-pause-circle text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Control de Pausas</h3>
                    <p class="text-gray-600">
                        Funcionalidad para pausar y reanudar solicitudes con registro de tiempos y motivos.
                    </p>
                </div>

                <!-- Reportes y Métricas -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-chart-bar text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Reportes y Métricas</h3>
                    <p class="text-gray-600">
                        Dashboard con estadísticas en tiempo real y reportes de cumplimiento de SLA.
                    </p>
                </div>

                <!-- Gestión de Usuarios -->
                <div class="feature-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-users text-red-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Gestión de Usuarios</h3>
                    <p class="text-gray-600">
                        Sistema de roles y permisos para asignación de solicitudes y control de acceso.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    @auth
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Resumen del Sistema</h2>
                <p class="text-xl text-gray-600">Estadísticas actuales de tu instancia</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="stats-card border-l-blue-500">
                    <div class="flex items-center">
                        <i class="fas fa-layer-group text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-600">Familias</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\ServiceFamily::count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="stats-card border-l-green-500">
                    <div class="flex items-center">
                        <i class="fas fa-cogs text-green-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-600">Servicios</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\Service::count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="stats-card border-l-purple-500">
                    <div class="flex items-center">
                        <i class="fas fa-list-alt text-purple-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-600">Sub-Servicios</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\SubService::count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="stats-card border-l-orange-500">
                    <div class="flex items-center">
                        <i class="fas fa-tasks text-orange-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-600">Solicitudes</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\ServiceRequest::count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('dashboard') }}" class="bg-red-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-red-700 transition inline-flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Ir al Sapp
                </a>
            </div>
        </div>
    </section>
    @endauth

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-red-400 text-2xl mr-3"></i>
                        <span class="text-xl font-bold">Sistema Sapp</span>
                    </div>
                    <p class="text-gray-400 mt-2">Plataforma de gestión integral de servicios</p>
                </div>
                <div class="text-gray-400">
                    <p>&copy; 2024 Sistema Sapp. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
