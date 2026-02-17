{{-- resources/views/requester-management/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear Solicitante')

@section('breadcrumb')
<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-red-600">
                <i class="fas fa-home mr-2"></i>
                Inicio
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('requester-management.requesters.index') }}" class="text-sm font-medium text-gray-700 hover:text-red-600">
                    Gestión de Solicitantes
                </a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Crear Solicitante</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-user-plus mr-3 text-red-600"></i>
            Datos del solicitante
        </h2>
        <p class="text-gray-600 mt-2">Complete la información del nuevo solicitante</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <form action="{{ route('requester-management.requesters.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="company_id" value="{{ session('current_company_id') }}">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Espacio de trabajo</label>
                        <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                            {{ $currentWorkspace->name ?? 'Sin espacio seleccionado' }}
                        </div>
                        @error('company_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Teléfono
                        </label>
                        <input type="text"
                               id="phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cargo -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                            Cargo
                        </label>
                        <input type="text"
                               id="position"
                               name="position"
                               value="{{ old('position') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('position') border-red-500 @enderror">
                        @error('position')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Departamento -->
                    <div class="md:col-span-2">
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                            Departamento
                        </label>
                        @php
                            $selectedDepartment = old('department');
                            $hasSelectedDepartment = $selectedDepartment && in_array($selectedDepartment, $departmentOptions, true);
                        @endphp
                        <select id="department"
                                name="department"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('department') border-red-500 @enderror">
                            <option value="">Seleccione un departamento</option>
                            @if($selectedDepartment && !$hasSelectedDepartment)
                                <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }} (actual)</option>
                            @endif
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department }}" {{ $selectedDepartment === $department ? 'selected' : '' }}>
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Lista de departamentos de la organización activa.</p>
                        @error('department')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                </div>

                <!-- Estado -->
                <div class="mt-6">
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               checked
                               class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Solicitante activo
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Los solicitantes inactivos no podrán crear nuevas solicitudes
                    </p>
                </div>

                <!-- Botones -->
                <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-4 space-y-4 sm:space-y-0">
                    <a href="{{ route('requester-management.requesters.index') }}"
                       class="inline-flex justify-center items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Crear Solicitante
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Los campos marcados con <span class="text-red-500">*</span> son obligatorios</li>
                        <li>El email debe ser único en el sistema</li>
                        <li>Los solicitantes activos podrán crear nuevas solicitudes de servicio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación adicional del formulario
        const form = document.querySelector('form');
        const nameInput = document.getElementById('name');

        form.addEventListener('submit', function(e) {
            if (nameInput.value.trim() === '') {
                e.preventDefault();
                nameInput.focus();
                // Podrías agregar un toast o alerta aquí
            }
        });

        // Formato automático para teléfono
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value;
        });
    });
</script>
@endsection
