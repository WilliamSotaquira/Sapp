@extends('layouts.app')

@section('title', 'Editar ' . $serviceFamily->name)

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
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('service-families.show', $serviceFamily) }}" class="text-blue-600 hover:text-blue-700">{{ $serviceFamily->name }}</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Editar</span>
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
                    <i class="fas fa-edit text-2xl mr-3"></i>
                    <div>
                        <h2 class="text-xl font-bold">Editar Familia de Servicio</h2>
                        <p class="text-blue-100 text-sm">Actualice la información de la familia de servicio</p>
                    </div>
                </div>
            </div>

            <!-- Formulario de EDICIÓN -->
            <div class="p-6">
                <form action="{{ route('service-families.update', $serviceFamily) }}" method="POST" id="familyForm">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Contrato -->
                        <div>
                            <label for="contract_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Contrato <span class="text-red-500">*</span>
                            </label>
                            <select name="contract_id" id="contract_id"
                                    class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('contract_id') border-red-500 @enderror"
                                    required>
                                <option value="">Seleccione un contrato</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}"
                                        {{ (string) old('contract_id', $serviceFamily->contract_id) === (string) $contract->id ? 'selected' : '' }}>
                                        {{ $contract->number }}{{ $contract->name ? ' - ' . $contract->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contract_id')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre de la Familia <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $serviceFamily->name) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Ej: Soporte Técnico, Infraestructura TI, Desarrollo"
                                   required
                                   maxlength="255">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
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
                                       value="{{ old('code', $serviceFamily->code) }}"
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
                                      placeholder="Describa el propósito y alcance de esta familia de servicio...">{{ old('description', $serviceFamily->description) }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Estado - CORREGIDO -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label for="is_active" class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <!-- Input hidden para enviar '0' cuando está desactivado -->
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox"
                                           name="is_active"
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', $serviceFamily->is_active) ? 'checked' : '' }}
                                           class="sr-only real-checkbox">
                                    <div class="block bg-gray-300 w-10 h-6 rounded-full toggle-bg"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform {{ old('is_active', $serviceFamily->is_active) ? 'translate-x-4 bg-green-500' : '' }}"></div>
                                </div>
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-700">Familia Activa</span>
                                    <p class="text-gray-500 text-xs">
                                        @if($serviceFamily->is_active)
                                            Actualmente <span class="text-green-600 font-medium">ACTIVA</span> - Los servicios están disponibles
                                        @else
                                            Actualmente <span class="text-red-600 font-medium">INACTIVA</span> - Los servicios no están disponibles
                                        @endif
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Información de Impacto -->
                        @if($serviceFamily->services->count() > 0 || $serviceFamily->serviceLevelAgreements->count() > 0)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-yellow-800 mb-2">Impacto de los Cambios</h3>
                                    <ul class="text-sm text-yellow-700 space-y-1">
                                        @if($serviceFamily->services->count() > 0)
                                            <li class="flex items-center">
                                                <i class="fas fa-cog mr-2"></i>
                                                Afecta a <strong>{{ $serviceFamily->services->count() }}</strong> servicio(s) asociado(s)
                                            </li>
                                        @endif
                                        @if($serviceFamily->serviceLevelAgreements->count() > 0)
                                            <li class="flex items-center">
                                                <i class="fas fa-handshake mr-2"></i>
                                                Afecta a <strong>{{ $serviceFamily->serviceLevelAgreements->count() }}</strong> SLA(s) configurado(s)
                                            </li>
                                        @endif
                                        @if(!$serviceFamily->is_active)
                                            <li class="flex items-center">
                                                <i class="fas fa-ban mr-2"></i>
                                                Al desactivar, todos los servicios dejarán de estar disponibles
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endif

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
                    <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                        <div class="flex space-x-3 order-2 sm:order-1">
                            <a href="{{ route('service-families.show', $serviceFamily) }}"
                               class="bg-gray-300 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-400 transition duration-150 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>Volver
                            </a>
                        </div>
                        <div class="flex space-x-3 order-1 sm:order-2">
                            <button type="button"
                                    onclick="resetForm()"
                                    class="bg-yellow-500 text-white px-6 py-3 rounded-md hover:bg-yellow-600 transition duration-150 flex items-center">
                                <i class="fas fa-undo mr-2"></i>Restablecer
                            </button>
                            <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-150 flex items-center">
                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sección de Eliminación (FUERA del formulario de edición) -->
        @if($serviceFamily->services->count() === 0)
        <div class="mt-6 bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-red-600 text-white px-6 py-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <h2 class="text-xl font-bold">Zona de Peligro</h2>
                        <p class="text-red-100 text-sm">Acciones irreversibles</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Eliminar Familia de Servicio</h3>
                        <p class="text-gray-600 text-sm">
                            Esta acción no se puede deshacer. Se eliminará permanentemente la familia de servicio
                            y toda su información asociada.
                        </p>
                    </div>
                    <!-- Formulario de ELIMINACIÓN SEPARADO -->
                    <form action="{{ route('service-families.destroy', $serviceFamily) }}" method="POST"
                          onsubmit="return confirmDelete()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="bg-red-600 text-white px-6 py-3 rounded-md hover:bg-red-700 transition duration-150 flex items-center whitespace-nowrap">
                            <i class="fas fa-trash mr-2"></i>Eliminar Familia
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Información de la Familia -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Resumen de Servicios -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-cogs text-green-500 mr-2"></i>
                    Servicios Asociados
                </h3>
                @if($serviceFamily->services->count() > 0)
                    <div class="space-y-3">
                        @foreach($serviceFamily->services as $service)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $service->name }}</span>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">{{ $service->code }}</span>
                                </div>
                                <span class="text-xs px-2 py-1 rounded {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-500 mt-3 text-center">
                        Total: {{ $serviceFamily->services->count() }} servicio(s)
                    </p>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-cogs text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No hay servicios asociados</p>
                    </div>
                @endif
            </div>

            <!-- Resumen de SLAs -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-handshake text-orange-500 mr-2"></i>
                    SLAs Configurados
                </h3>
                @if($serviceFamily->serviceLevelAgreements->count() > 0)
                    <div class="space-y-3">
                        @foreach($serviceFamily->serviceLevelAgreements as $sla)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-medium text-gray-900">{{ $sla->name }}</span>
                                    @php
                                        $criticalityColors = [
                                            'BAJA' => 'bg-green-100 text-green-800',
                                            'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                            'ALTA' => 'bg-orange-100 text-orange-800',
                                            'CRITICA' => 'bg-red-100 text-red-800'
                                        ];
                                    @endphp
                                    <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $criticalityColors[$sla->criticality_level] }}">
                                        {{ $sla->criticality_level }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600">
                                    Resolución: {{ $sla->resolution_time_minutes }} min
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-500 mt-3 text-center">
                        Total: {{ $serviceFamily->serviceLevelAgreements->count() }} SLA(s)
                    </p>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-handshake text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No hay SLAs configurados</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-enfocar el primer campo
        document.getElementById('name').focus();

        // Toggle para el switch de estado - CORREGIDO
        const toggleSwitch = document.getElementById('is_active');
        const toggleDot = document.querySelector('.dot');
        const toggleBg = document.querySelector('.toggle-bg');
        const hiddenInput = document.querySelector('input[type="hidden"][name="is_active"]');

        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                toggleDot.classList.add('translate-x-4', 'bg-green-500');
                toggleDot.classList.remove('bg-white');
                toggleBg.classList.add('bg-green-400');
                toggleBg.classList.remove('bg-gray-300');
                // Cuando está activo, el checkbox visible envía '1'
                // El hidden input se ignora porque el checkbox tiene el mismo nombre
            } else {
                toggleDot.classList.remove('translate-x-4', 'bg-green-500');
                toggleDot.classList.add('bg-white');
                toggleBg.classList.remove('bg-green-400');
                toggleBg.classList.add('bg-gray-300');
                // Cuando está desactivado, solo el hidden input envía '0'
                // El checkbox visible no se envía porque no está checked
            }
        });

        // Inicializar el estado visual del toggle
        if (toggleSwitch.checked) {
            toggleBg.classList.add('bg-green-400');
            toggleBg.classList.remove('bg-gray-300');
        } else {
            toggleBg.classList.remove('bg-green-400');
            toggleBg.classList.add('bg-gray-300');
        }

        // Validación en tiempo real para el código
        const codeInput = document.getElementById('code');
        codeInput.addEventListener('input', function() {
            // Remover espacios y caracteres especiales
            this.value = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        });

        // Validación del formulario antes de enviar
        const form = document.getElementById('familyForm');
        const originalData = {
            name: document.getElementById('name').value,
            code: document.getElementById('code').value,
            description: document.getElementById('description').value,
            is_active: document.getElementById('is_active').checked
        };

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

            // Verificar si hay cambios
            const currentData = {
                name: document.getElementById('name').value,
                code: document.getElementById('code').value,
                description: document.getElementById('description').value,
                is_active: document.getElementById('is_active').checked
            };

            const hasChanges = JSON.stringify(originalData) !== JSON.stringify(currentData);

            if (!hasChanges) {
                e.preventDefault();
                showWarning('No se detectaron cambios para guardar');
                return;
            }

            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
            submitBtn.disabled = true;
        });

        // Función para confirmar eliminación
        window.confirmDelete = function() {
            return confirm('¿ESTÁ SEGURO?\n\nEsta acción eliminará permanentemente la familia de servicio "' +
                          '{{ $serviceFamily->name }}' +
                          '".\n\nEsta operación NO se puede deshacer.');
        };

        // Función para restablecer el formulario
        window.resetForm = function() {
            if (confirm('¿Está seguro de que desea restablecer todos los cambios?')) {
                document.getElementById('name').value = originalData.name;
                document.getElementById('code').value = originalData.code;
                document.getElementById('description').value = originalData.description;
                document.getElementById('is_active').checked = originalData.is_active;

                // Actualizar el toggle visual
                if (originalData.is_active) {
                    toggleDot.classList.add('translate-x-4', 'bg-green-500');
                    toggleDot.classList.remove('bg-white');
                    toggleBg.classList.add('bg-green-400');
                    toggleBg.classList.remove('bg-gray-300');
                } else {
                    toggleDot.classList.remove('translate-x-4', 'bg-green-500');
                    toggleDot.classList.add('bg-white');
                    toggleBg.classList.remove('bg-green-400');
                    toggleBg.classList.add('bg-gray-300');
                }

                showSuccess('Formulario restablecido a los valores originales');
            }
        };

        function showError(message) {
            showMessage(message, 'red');
        }

        function showWarning(message) {
            showMessage(message, 'yellow');
        }

        function showSuccess(message) {
            showMessage(message, 'green');
        }

        function showMessage(message, type) {
            const colors = {
                red: 'bg-red-50 border-red-200 text-red-700',
                yellow: 'bg-yellow-50 border-yellow-200 text-yellow-700',
                green: 'bg-green-50 border-green-200 text-green-700'
            };

            const icons = {
                red: 'fa-exclamation-circle',
                yellow: 'fa-exclamation-triangle',
                green: 'fa-check-circle'
            };

            // Remover mensajes existentes
            const existingMessages = document.querySelectorAll('.live-message');
            existingMessages.forEach(msg => msg.remove());

            // Crear nuevo mensaje
            const messageDiv = document.createElement('div');
            messageDiv.className = `live-message ${colors[type]} border rounded-md p-4 mb-4`;
            messageDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icons[type]} mr-2"></i>
                    <span class="text-sm">${message}</span>
                </div>
            `;

            form.prepend(messageDiv);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }

        // Restaurar valores del formulario si hay errores de validación
        @if($errors->any())
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

    .toggle-bg {
        transition: background-color 0.3s ease-in-out;
    }

    input:checked ~ .toggle-bg {
        background-color: #34D399 !important;
    }

    /* Transición suave para el toggle */
    .dot {
        transition: all 0.3s ease-in-out;
    }

    /* Estilos para los mensajes */
    .live-message {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection
