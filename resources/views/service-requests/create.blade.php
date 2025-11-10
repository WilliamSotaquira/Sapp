{{-- resources/views/service-requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nueva Solicitud de Servicio')

@section('content')
<form action="{{ route('service-requests.store') }}" method="POST">
    @csrf
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                <h2 class="text-xl font-bold text-gray-800">Nueva Solicitud de Servicio</h2>
            </div>
            <div class="p-6">
                @include('components.service-requests.forms.basic-fields', [
                    'services' => $services,
                    'subServices' => $subServices,
                    'errors' => $errors,
                    'mode' => 'create'
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
