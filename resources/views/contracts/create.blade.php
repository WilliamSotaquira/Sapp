@extends('layouts.app')

@section('title', 'Crear Contrato')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('contracts.index') }}" class="text-blue-600 hover:text-blue-700">Contratos</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Crear Contrato</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-blue-600 text-white px-6 py-4">
                <div class="flex items-center">
                    <i class="fas fa-file-contract text-2xl mr-3"></i>
                    <div>
                        <h2 class="text-xl font-bold">Crear Nuevo Contrato</h2>
                        <p class="text-blue-100 text-sm">Complete la información para registrar un contrato</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <form action="{{ route('contracts.store') }}" method="POST" id="contractForm">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Espacio de trabajo <span class="text-red-500">*</span>
                            </label>
                            <select name="company_id" id="company_id"
                                    class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('company_id') border-red-500 @enderror"
                                    required>
                                <option value="">Seleccione un espacio</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ (string) old('company_id', $currentCompany->id ?? '') === (string) $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="number" class="block text-sm font-medium text-gray-700 mb-1">
                                Número de contrato <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="number"
                                   id="number"
                                   value="{{ old('number') }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('number') border-red-500 @enderror"
                                   placeholder="Ej: 20251069"
                                   required
                                   maxlength="50">
                            @error('number')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del contrato
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Ej: Contrato soporte 2025"
                                   maxlength="255">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                      placeholder="Detalles o alcance del contrato...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label for="is_active" class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox"
                                           name="is_active"
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}
                                           class="sr-only">
                                    <div class="block bg-gray-300 w-10 h-6 rounded-full"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform {{ old('is_active', true) ? 'translate-x-4 bg-green-500' : '' }}"></div>
                                </div>
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-700">Contrato Activo</span>
                                    <p class="text-gray-500 text-xs">Los contratos inactivos no estarán disponibles para nuevas familias</p>
                                </div>
                            </label>
                        </div>

                        @if($errors->any())
                            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                    <h3 class="text-sm font-medium text-red-800">Por favor corrige los siguientes errores:</h3>
                                </div>
                                <ul class="text-sm text-red-700 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                        <a href="{{ route('contracts.index') }}"
                           class="bg-gray-300 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-400 transition duration-150 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>Cancelar
                        </a>
                        <button type="submit"
                                class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-150 flex items-center">
                            <i class="fas fa-save mr-2"></i>Guardar Contrato
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSwitch = document.getElementById('is_active');
        const toggleDot = document.querySelector('.dot');

        toggleSwitch?.addEventListener('change', function() {
            if (this.checked) {
                toggleDot.classList.add('translate-x-4', 'bg-green-500');
                toggleDot.classList.remove('bg-white');
            } else {
                toggleDot.classList.remove('translate-x-4', 'bg-green-500');
                toggleDot.classList.add('bg-white');
            }
        });
    });
</script>
@endsection
