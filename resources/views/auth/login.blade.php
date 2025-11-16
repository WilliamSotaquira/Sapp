<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" style="background: linear-gradient(135deg, #F4F6F8 0%, #E3E7E8 100%);">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
        <!-- Logo y Header -->
        <div class="text-center mb-6">
            <a href="/" class="inline-block">
                <div class="w-16 h-16 rounded-xl shadow-md flex items-center justify-center mb-4 mx-auto transform hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                    <i class="fas fa-user-shield text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Bienvenido de nuevo</h1>
            <p class="text-gray-600 text-sm">Inicia sesión para acceder al sistema</p>
        </div>

        <!-- Card Principal -->
        <div class="w-full sm:max-w-md">
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                <!-- Header del Card -->
                <div class="px-6 py-5 border-b" style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </h2>
                </div>

                <div class="px-6 py-8">
                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

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

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

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
                                       style="@error('email') @else focus:ring-color: #D00B1D; @enderror"
                                       placeholder="tu@ejemplo.com"
                                       required
                                       autofocus
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
                                       style="@error('password') @else focus:ring-color: #D00B1D; @enderror"
                                       placeholder="••••••••"
                                       required
                                       autocomplete="current-password">
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Remember Me y Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                                <input id="remember_me"
                                       type="checkbox"
                                       name="remember"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                <span class="ml-2 text-sm text-gray-700">Recordarme</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm font-medium hover:underline" style="color: #D00B1D;">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
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

                        <!-- Botón Submit -->
                        <button type="submit"
                                class="w-full text-white font-semibold py-2.5 px-6 rounded-lg transition-all duration-200 flex items-center justify-center shadow-md hover:shadow-lg"
                                style="background: linear-gradient(135deg, #D00B1D 0%, #A60A17 100%);">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Iniciar Sesión
                        </button>
                    </form>
                </div>

                <!-- Footer del Card -->
                <div class="px-6 py-4 border-t border-gray-200" style="background-color: #F4F6F8;">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            ¿No tienes una cuenta?
                            <a href="{{ route('register') }}" class="font-medium hover:underline ml-1" style="color: #D00B1D;">
                                Regístrate aquí
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

