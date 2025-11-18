<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if(config('services.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" style="background: linear-gradient(135deg, #F4F6F8 0%, #E3E7E8 100%);">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
        <!-- Logo y Header -->
        <div class="text-center mb-6">
            <a href="/" class="inline-block">
                <div class="w-16 h-16 rounded-xl shadow-md flex items-center justify-center mb-4 mx-auto transform hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Únete a nosotros</h1>
            <p class="text-gray-600 text-sm">Crea tu cuenta para comenzar</p>
        </div>

        <!-- Card Principal -->
        <div class="w-full sm:max-w-md">
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                <!-- Header del Card -->
                <div class="px-6 py-5 border-b" style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-user-circle mr-2"></i>
                        Crear Nueva Cuenta
                    </h2>
                </div>

                <div class="px-6 py-8">
                    <!-- Errores Generales -->
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <span class="font-semibold">Por favor corrige los siguientes errores:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf

                        <!-- Nombre Completo -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2" style="color: #D00B1D;"></i>
                                Nombre Completo
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-gray-400"></i>
                                </div>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       class="w-full pl-10 pr-4 py-2.5 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:border-transparent transition-all"
                                       placeholder="Juan Pérez"
                                       required
                                       autofocus
                                       autocomplete="name">
                            </div>
                            @error('name')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2" style="color: #D00B1D;"></i>
                                Correo Electrónico
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-at text-gray-400"></i>
                                </div>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       class="w-full pl-10 pr-4 py-2.5 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:border-transparent transition-all"
                                       placeholder="tu@ejemplo.com"
                                       required
                                       autocomplete="username">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2" style="color: #D00B1D;"></i>
                                Contraseña
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="w-full pl-10 pr-4 py-2.5 border @error('password') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:border-transparent transition-all"
                                       placeholder="••••••••"
                                       required
                                       autocomplete="new-password">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Mínimo 8 caracteres, incluye mayúsculas, minúsculas y números
                            </p>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2" style="color: #D00B1D;"></i>
                                Confirmar Contraseña
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all"
                                       placeholder="••••••••"
                                       required
                                       autocomplete="new-password">
                            </div>
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Google reCAPTCHA -->
                        @if(config('services.recaptcha.site_key') && config('services.recaptcha.secret_key'))
                        <div>
                            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                            @error('g-recaptcha-response')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        @endif

                        <!-- Términos y Condiciones -->
                        <div class="rounded-lg p-3 border" style="background-color: #FFF9E6; border-color: #E5C340;">
                            <p class="text-xs text-gray-700">
                                <i class="fas fa-info-circle mr-1" style="color: #E5C340;"></i>
                                Al registrarte, aceptas nuestros términos de servicio y política de privacidad.
                            </p>
                        </div>

                        <!-- Botón Submit -->
                        <button type="submit"
                                class="w-full text-white font-semibold py-2.5 px-6 rounded-lg transition-all duration-200 flex items-center justify-center shadow-md hover:shadow-lg"
                                style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                            <i class="fas fa-user-plus mr-2"></i>
                            Crear Mi Cuenta
                        </button>
                    </form>
                </div>

                <!-- Footer del Card -->
                <div class="px-6 py-4 border-t border-gray-200" style="background-color: #F4F6F8;">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            ¿Ya tienes una cuenta?
                            <a href="{{ route('login') }}" class="font-medium hover:underline ml-1" style="color: #D00B1D;">
                                Inicia sesión aquí
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Link para volver -->
            <div class="mt-6 text-center">
                <a href="/" class="inline-flex items-center text-gray-600 hover:text-gray-800 text-sm font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>

