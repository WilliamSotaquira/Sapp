<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Solicitud - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" style="background: linear-gradient(135deg, #F4F6F8 0%, #E3E7E8 100%);">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-3">
                Consultar Estado de Solicitud
            </h1>
            <p class="text-gray-600 text-base sm:text-lg mb-2">
                Rastrea el progreso de tu solicitud de servicio en tiempo real
            </p>
            <p class="text-gray-500 text-sm">
                Sin necesidad de iniciar sesión en el sistema
            </p>
        </div>

        <!-- Formulario de Búsqueda -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 border border-gray-200">
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('public.tracking.search') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Tipo de Búsqueda -->
                    <div>
                        <label class="block text-base font-semibold text-gray-800 mb-4">
                            <i class="fas fa-question-circle mr-2" style="color: #D00B1D;"></i>
                            ¿Cómo deseas buscar tu solicitud?
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex items-center p-5 border-2 rounded-lg cursor-pointer hover:shadow-md transition-all" style="border-color: #D00B1D; background-color: #FFF5F5;">
                                <input type="radio" name="type" value="ticket" class="mr-3 w-4 h-4" checked>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 text-base mb-1">
                                        <i class="fas fa-ticket-alt mr-2" style="color: #D00B1D;"></i>
                                        Número de Ticket
                                    </div>
                                    <div class="text-xs text-gray-600">Si conoces el código único de tu solicitud</div>
                                    <div class="text-xs font-mono mt-1 px-2 py-1 rounded inline-block" style="background-color: #FFF9E6; color: #666;">Ej: INF-PU-M-251112-001</div>
                                </div>
                            </label>
                            <label class="relative flex items-center p-5 border-2 border-gray-300 rounded-lg cursor-pointer hover:shadow-md transition-all" style="hover:border-color: #D00B1D;">
                                <input type="radio" name="type" value="email" class="mr-3 w-4 h-4">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800 text-base mb-1">
                                        <i class="fas fa-envelope mr-2" style="color: #D00B1D;"></i>
                                        Correo Electrónico
                                    </div>
                                    <div class="text-xs text-gray-600">Para ver todas tus solicitudes registradas</div>
                                    <div class="text-xs font-mono mt-1 px-2 py-1 rounded inline-block" style="background-color: #FFF9E6; color: #666;">Ej: usuario@ejemplo.com</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Campo de Búsqueda -->
                    <div>
                        <label for="query" class="block text-sm font-semibold text-gray-800 mb-2">
                            <i class="fas fa-edit mr-2" style="color: #D00B1D;"></i>
                            Ingresa tu búsqueda
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text"
                                   name="query"
                                   id="query"
                                   class="w-full pl-10 pr-4 py-2.5 border @error('query') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:border-transparent focus:outline-none transition-all"
                                   placeholder="Número de ticket o correo electrónico"
                                   value="{{ old('query') }}"
                                   required>
                        </div>
                        @error('query')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Google reCAPTCHA -->
                    <div>
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                        @error('g-recaptcha-response')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Botón de Búsqueda -->
                    <button type="submit"
                            class="w-full text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center shadow-md hover:shadow-lg"
                            style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                        <i class="fas fa-search mr-2"></i>
                        Buscar Mi Solicitud
                    </button>
                </form>

                <!-- Información Adicional -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle mr-2" style="color: #D00B1D;"></i>
                        ¿Cómo funciona?
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: #FFF5F5;">
                                    <span class="font-bold" style="color: #D00B1D;">1</span>
                                </div>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Busca por Ticket</p>
                                <p>Si recibiste un código de ticket, ingrésalo para ver el estado específico de esa solicitud.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: #FFF5F5;">
                                    <span class="font-bold" style="color: #D00B1D;">2</span>
                                </div>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Busca por Email</p>
                                <p>Ingresa tu correo electrónico registrado para ver todas tus solicitudes activas e históricas.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-4 rounded-lg border" style="background-color: #FFF9E6; border-color: #E5C340;">
                        <div class="flex items-start">
                            <i class="fas fa-lightbulb mt-0.5 mr-2" style="color: #E5C340;"></i>
                            <div class="text-sm">
                                <p class="font-semibold text-gray-800 mb-1">Consejo</p>
                                <p class="text-gray-700">Guarda el número de ticket que aparece en la confirmación de tu solicitud. Te permitirá consultar su estado fácilmente.</p>
                            </div>
                        </div>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-info-circle" style="color: #D00B1D;"></i>
                        ¿Necesitas ayuda?
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 mt-1" style="color: #D00B1D;"></i>
                            <span>El número de ticket fue enviado a tu correo al crear la solicitud</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 mt-1" style="color: #D00B1D;"></i>
                            <span>Puedes buscar todas tus solicitudes usando tu correo electrónico</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check mr-2 mt-1" style="color: #D00B1D;"></i>
                            <span>No necesitas iniciar sesión para consultar el estado</span>
                        </li>
                    </ul>
                </div>

                <!-- Link al sistema -->
                @auth
                    <div class="mt-6 text-center">
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium hover:underline" style="color: #D00B1D;">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver al Dashboard
                        </a>
                    </div>
                @else
                    <div class="mt-6 text-center">
                        <a href="{{ route('login') }}" class="text-sm font-medium hover:underline" style="color: #D00B1D;">
                            <i class="fas fa-sign-in-alt mr-1"></i>
                            ¿Eres parte del equipo? Inicia sesión aquí
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
