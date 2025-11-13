@extends('layouts.app')

@section('title', 'Nueva Solicitud de Servicio')

@section('content')

    {{-- Mostrar todos los errores de validación --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-lg font-medium text-red-800 mb-2">Errores de validación:</h3>
            <ul class="list-disc list-inside text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            {{-- Mostrar datos enviados --}}
            <div class="mt-4 p-3 bg-red-100 rounded">
                <h4 class="font-medium text-red-800">Datos enviados:</h4>
                <pre class="text-sm text-red-700 mt-2">{{ json_encode(old(), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    <form action="{{ route('service-requests.store') }}" method="POST">
        @csrf

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                    <h2 class="text-xl font-bold text-gray-800">Nueva Solicitud de Servicio</h2>
                </div>
                <div class="p-6">
                    @include('components.service-requests.forms.basic-fields', [
                        'subServices' => $subServices,
                        'requesters' => $requesters,
                        'errors' => $errors,
                        'mode' => 'create',
                    ])
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-end gap-3">
                    <!-- Botón Cancelar - Simplificado -->
                    <a href="{{ route('service-requests.index') }}"
                        class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Cancelar
                    </a>

                    <!-- Botón Crear - Simplificado -->
                    <button type="submit"
                        class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Crear Solicitud
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
