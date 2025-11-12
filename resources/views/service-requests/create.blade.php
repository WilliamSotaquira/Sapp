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
                        'errors' => $errors,
                        'mode' => 'create',
                    ])
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
                <a href="{{ route('service-requests.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Crear Solicitud
                </button>
            </div>
        </div>
    </form>
@endsection
