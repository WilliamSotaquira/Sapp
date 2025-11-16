@extends('layouts.app')

@section('title', 'Perfil de Técnico Requerido')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-8 text-center">
            <div class="mb-4">
                <i class="fas fa-user-cog text-6xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">Perfil de Técnico Requerido</h2>
            <p class="text-yellow-100">Para acceder a esta sección necesitas un perfil de técnico</p>
        </div>

        <div class="p-8">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            {{ $message }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        ¿Qué es un perfil de técnico?
                    </h3>
                    <p class="text-gray-600">
                        Un perfil de técnico te permite gestionar tu agenda de trabajo, ver las tareas asignadas,
                        registrar el tiempo invertido y acceder al calendario del equipo.
                    </p>
                </div>

                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <i class="fas fa-clipboard-check text-green-500 mr-2"></i>
                        ¿Qué puedes hacer?
                    </h3>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li>Ver tu agenda personal de tareas</li>
                        <li>Gestionar tareas de impacto (90 min) y regulares (25 min)</li>
                        <li>Registrar el tiempo real invertido en cada tarea</li>
                        <li>Ver la capacidad del equipo técnico</li>
                        <li>Acceder al calendario compartido</li>
                    </ul>
                </div>

                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <i class="fas fa-user-shield text-purple-500 mr-2"></i>
                        Solicitar Acceso
                    </h3>
                    <p class="text-gray-600 mb-3">
                        Contacta al administrador del sistema para que te asigne un perfil de técnico.
                        Necesitarás proporcionar:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li>Tu especialización (Frontend, Backend, Full Stack, etc.)</li>
                        <li>Años de experiencia</li>
                        <li>Nivel de habilidad (Junior, Mid, Senior, Lead)</li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="{{ route('dashboard') }}"
                   class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-home mr-2"></i>
                    Ir al Inicio
                </a>
                <a href="{{ route('technician-schedule.index') }}"
                   class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Ver Calendario del Equipo
                </a>
            </div>
        </div>

        <!-- Información del usuario actual -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div class="flex items-center">
                    <i class="fas fa-user-circle text-2xl text-gray-400 mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-800">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                    Sin perfil de técnico
                </span>
            </div>
        </div>
    </div>

    <!-- Tarjetas informativas adicionales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-white shadow rounded-lg p-4 text-center">
            <div class="text-3xl mb-2">📅</div>
            <h4 class="font-semibold text-gray-800 mb-1">Agenda Personal</h4>
            <p class="text-xs text-gray-600">Gestiona tus tareas diarias</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4 text-center">
            <div class="text-3xl mb-2">⏱️</div>
            <h4 class="font-semibold text-gray-800 mb-1">Seguimiento de Tiempo</h4>
            <p class="text-xs text-gray-600">Registra horas trabajadas</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4 text-center">
            <div class="text-3xl mb-2">👥</div>
            <h4 class="font-semibold text-gray-800 mb-1">Capacidad del Equipo</h4>
            <p class="text-xs text-gray-600">Visualiza la carga de trabajo</p>
        </div>
    </div>
</div>
@endsection
