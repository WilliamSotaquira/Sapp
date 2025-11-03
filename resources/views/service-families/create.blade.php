@extends('layouts.app')

@section('title', 'Crear Familia de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('service-families.index') }}" class="text-blue-600 hover:text-blue-700">Familias de Servicio</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Crear Familia</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Card Principal -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 text-white px-6 py-4">
                <div class="flex items-center">
                    <i class="fas fa-plus-circle text-2xl mr-3"></i>
                    <div>
                        <h2 class="text-xl font-bold">Crear Nueva Familia de Servicio</h2>
                        <p class="text-blue-100 text-sm">Complete la información para registrar una nueva familia de servicio</p>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="p-6">
                <form action="{{ route('service-families.store') }}" method="POST" id="familyForm">
                    @csrf

                    <div class="space-y-6">
                        <!-- Nombre -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre de la Familia <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Ej: Soporte Técnico, Infraestructura TI, Desarrollo"
                                   required
                                   maxlength="255">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Nombre descriptivo de la familia de servicio</p>
                        </div>

                        <!-- Código -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                Código Único <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       name="code"
                                       id="code"
                                       value="{{ old('code') }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 uppercase @error('code') border-red-500 @enderror"
                                       placeholder="Ej: ST, ITI, DEV"
                                       required
                                       maxlength="10"
                                       oninput="this.value = this.value.toUpperCase()">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <span class="text-gray-400 text-sm">Máx. 10 chars</span>
                                </div>
                            </div>
                            @error('code')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Código único para identificar la familia (se convertirá a mayúsculas)</p>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                      placeholder="Describa el propósito y alcance de esta familia de servicio...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                            <p class="text-gray-500 text-xs mt-1">Información adicional sobre la familia de servicio (opcional)</p>
                        </div>

                        <!-- Estado -->
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
                                    <span class="text-sm font-medium text-gray-700">Familia Activa</span>
                                    <p class="text-gray-500 text-xs">Las familias inactivas no estarán disponibles para nuevos servicios</p>
                                </div>
                            </label>
                        </div>

                        <!-- Resumen de Validación -->
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

                    <!-- Botones de Acción -->
                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                        <a href="{{ route('service-families.index') }}"
                           class="bg-gray-300 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-400 transition duration-150 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>Cancelar
                        </a>
                        <button type="submit"
                                class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-150 flex items-center">
                            <i class="fas fa-save mr-2"></i>Guardar Familia
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                <div>
                    <h3 class="text-sm font-medium text-blue-800 mb-1">¿Qué es una Familia de Servicio?</h3>
                    <p class="text-sm text-blue-700">
                        Una familia de servicio agrupa servicios relacionados bajo una misma categoría.
                        Por ejemplo: "Soporte Técnico" puede contener servicios como "Soporte de Software",
                        "Soporte de Hardware", etc. Los Acuerdos de Nivel de Servicio (SLAs) se configuran
                        a nivel de familia y aplican a todos los servicios dentro de ella.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-enfocar el primer campo
        document.getElementById('name').focus();

        // Toggle para el switch de estado
        const toggleSwitch = document.getElementById('is_active');
        const toggleDot = document.querySelector('.dot');

        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                toggleDot.classList.add('translate-x-4', 'bg-green-500');
                toggleDot.classList.remove('bg-white');
            } else {
                toggleDot.classList.remove('translate-x-4', 'bg-green-500');
                toggleDot.classList.add('bg-white');
            }
        });

        // Validación en tiempo real para el código
        const codeInput = document.getElementById('code');
        codeInput.addEventListener('input', function() {
            // Remover espacios y caracteres especiales
            this.value = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        });

        // Validación del formulario antes de enviar
        const form = document.getElementById('familyForm');
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const code = document.getElementById('code').value.trim();

            if (!name) {
                e.preventDefault();
                showError('El nombre es obligatorio');
                document.getElementById('name').focus();
                return;
            }

            if (!code) {
                e.preventDefault();
                showError('El código es obligatorio');
                document.getElementById('code').focus();
                return;
            }

            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
            submitBtn.disabled = true;
        });

        function showError(message) {
            // Crear o actualizar mensaje de error
            let errorDiv = document.getElementById('liveError');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'liveError';
                errorDiv.className = 'bg-red-50 border border-red-200 rounded-md p-4 mb-4';
                form.prepend(errorDiv);
            }

            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <span class="text-sm text-red-700">${message}</span>
                </div>
            `;

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (errorDiv) {
                    errorDiv.remove();
                }
            }, 5000);
        }

        // Restaurar valores del formulario si hay errores de validación
        @if($errors->any())
            // Scroll to top para mostrar errores
            window.scrollTo(0, 0);
        @endif
    });
</script>

<style>
    /* Estilos para el toggle switch */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    input:checked ~ .dot {
        transform: translateX(100%);
        background-color: #10B981;
    }

    /* Transición suave para el toggle */
    .dot {
        transition: all 0.3s ease-in-out;
    }
</style>
@endsection
