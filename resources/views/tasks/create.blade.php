@extends('layouts.app')

@section('title', 'Crear Nueva Tarea')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-plus-circle mr-3"></i>
                Crear Nueva Tarea
            </h2>
        </div>

        <form action="{{ route('tasks.store') }}" method="POST" class="p-6 space-y-6" id="taskForm">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <strong class="font-bold">Errores de validación:</strong>
                    <ul class="mt-2 ml-4 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Información Básica -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-red-600"></i>
                    Información Básica
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Título -->
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Título <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="title"
                               id="title"
                               value="{{ old('title') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('title') border-red-500 @enderror"
                               required>
                        @error('title')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipo de Tarea -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Tarea <span class="text-red-500">*</span>
                        </label>
                        <select name="type"
                                id="type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('type') border-red-500 @enderror"
                                required>
                            <option value="">Seleccione...</option>
                            <option value="impact" {{ old('type') == 'impact' ? 'selected' : '' }}>
                                <i class="fas fa-star"></i> Impacto (90 min)
                            </option>
                            <option value="regular" {{ old('type') == 'regular' ? 'selected' : '' }}>
                                Regular (25 min)
                            </option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Prioridad <span class="text-red-500">*</span>
                        </label>
                        <select name="priority"
                                id="priority"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('priority') border-red-500 @enderror"
                                required>
                            <option value="">Seleccione...</option>
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description"
                                  id="description"
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('description') border-red-500 @enderror"
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Asignación -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user-check mr-2 text-red-600"></i>
                    Asignación
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Técnico Asignado -->
                    <div>
                        <label for="technician_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Técnico Asignado <span class="text-red-500">*</span>
                        </label>
                        <select name="technician_id"
                                id="technician_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('technician_id') border-red-500 @enderror"
                                required>
                            <option value="">Seleccione un técnico...</option>
                            @foreach($technicians as $technician)
                                @if($technician->user)
                                    <option value="{{ $technician->id }}" {{ old('technician_id') == $technician->id ? 'selected' : '' }}>
                                        {{ $technician->user->name }} - {{ ucfirst($technician->specialization) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('technician_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha Programada -->
                    <div>
                        <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha Programada <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="scheduled_date"
                               id="scheduled_date"
                               value="{{ old('scheduled_date', date('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('scheduled_date') border-red-500 @enderror"
                               required>
                        @error('scheduled_date')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Hora de Inicio -->
                    <div>
                        <label for="scheduled_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Hora de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="time"
                               name="scheduled_start_time"
                               id="scheduled_start_time"
                               value="{{ old('scheduled_start_time', '09:00') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('scheduled_start_time') border-red-500 @enderror"
                               required>
                        @error('scheduled_start_time')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Duración Estimada -->
                    <div>
                        <label for="estimated_duration_value" class="block text-sm font-medium text-gray-700 mb-2">
                            Duración Estimada <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number"
                                   name="estimated_duration_value"
                                   id="estimated_duration_value"
                                   value="{{ old('estimated_duration_value', '90') }}"
                                   step="1"
                                   min="1"
                                   max="480"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                   required>
                            <select name="estimated_duration_unit"
                                    id="estimated_duration_unit"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="minutes" selected>Minutos</option>
                                <option value="hours">Horas</option>
                            </select>
                        </div>
                        <input type="hidden" name="estimated_hours" id="estimated_hours" value="{{ old('estimated_hours', '1.5') }}" required>
                        @error('estimated_hours')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Impacto: 90 min (1.5h) | Regular: 25 min (0.42h)</p>
                    </div>
                </div>
            </div>

            <!-- Información Técnica -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-code mr-2 text-red-600"></i>
                    Información Técnica (Opcional)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Complejidad Técnica -->
                    <div>
                        <label for="technical_complexity" class="block text-sm font-medium text-gray-700 mb-2">
                            Complejidad Técnica
                        </label>
                        <select name="technical_complexity"
                                id="technical_complexity"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('technical_complexity') border-red-500 @enderror">
                            <option value="">No especificada</option>
                            <option value="1" {{ old('technical_complexity') == 1 ? 'selected' : '' }}>1 - Muy Baja</option>
                            <option value="2" {{ old('technical_complexity') == 2 ? 'selected' : '' }}>2 - Baja</option>
                            <option value="3" {{ old('technical_complexity') == 3 ? 'selected' : '' }}>3 - Media</option>
                            <option value="4" {{ old('technical_complexity') == 4 ? 'selected' : '' }}>4 - Alta</option>
                            <option value="5" {{ old('technical_complexity') == 5 ? 'selected' : '' }}>5 - Muy Alta</option>
                        </select>
                        @error('technical_complexity')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ambiente -->
                    <div>
                        <label for="environment" class="block text-sm font-medium text-gray-700 mb-2">
                            Ambiente
                        </label>
                        <select name="environment"
                                id="environment"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('environment') border-red-500 @enderror">
                            <option value="">No especificado</option>
                            <option value="development" {{ old('environment') == 'development' ? 'selected' : '' }}>Desarrollo</option>
                            <option value="staging" {{ old('environment') == 'staging' ? 'selected' : '' }}>Staging</option>
                            <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>Producción</option>
                        </select>
                        @error('environment')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tecnologías -->
                    <div class="md:col-span-2">
                        <label for="technologies_input" class="block text-sm font-medium text-gray-700 mb-2">
                            Tecnologías (separadas por coma)
                        </label>
                        <input type="text"
                               name="technologies_input"
                               id="technologies_input"
                               value="{{ old('technologies_input') }}"
                               placeholder="PHP, Laravel, JavaScript, MySQL"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('technologies') border-red-500 @enderror">
                        <input type="hidden" name="technologies" id="technologies_hidden">
                        @error('technologies')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Accesos Requeridos -->
                    <div class="md:col-span-2">
                        <label for="required_accesses_input" class="block text-sm font-medium text-gray-700 mb-2">
                            Accesos Requeridos (separados por coma)
                        </label>
                        <input type="text"
                               name="required_accesses_input"
                               id="required_accesses_input"
                               value="{{ old('required_accesses_input') }}"
                               placeholder="VPN, Servidor Producción, Base de Datos"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('required_accesses') border-red-500 @enderror">
                        <input type="hidden" name="required_accesses" id="required_accesses_hidden">
                        @error('required_accesses')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notas Técnicas -->
                    <div class="md:col-span-2">
                        <label for="technical_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notas Técnicas
                        </label>
                        <textarea name="technical_notes"
                                  id="technical_notes"
                                  rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('technical_notes') border-red-500 @enderror"
                                  placeholder="Detalles técnicos adicionales...">{{ old('technical_notes') }}</textarea>
                        @error('technical_notes')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Asociaciones Opcionales -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-link mr-2 text-red-600"></i>
                    Asociaciones (Opcional)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Solicitud de Servicio -->
                    <div>
                        <label for="service_request_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Solicitud de Servicio
                        </label>
                        <select name="service_request_id"
                                id="service_request_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('service_request_id') border-red-500 @enderror">
                            <option value="">Sin asociar</option>
                            @forelse($serviceRequests as $request)
                                <option value="{{ $request->id }}" {{ old('service_request_id') == $request->id ? 'selected' : '' }}>
                                    #{{ $request->id }} - {{ Str::limit($request->title, 50) }}
                                </option>
                            @empty
                                <option value="" disabled>No hay solicitudes abiertas</option>
                            @endforelse
                        </select>
                        @error('service_request_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Proyecto -->
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Proyecto
                        </label>
                        <select name="project_id"
                                id="project_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('project_id') border-red-500 @enderror">
                            <option value="">Sin asociar</option>
                            @forelse($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @empty
                                <option value="" disabled>No hay proyectos activos</option>
                            @endforelse
                        </select>
                        @error('project_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('tasks.index') }}"
                   class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Crear Tarea
                </button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
    // Convertir tecnologías y accesos a JSON antes de enviar
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        // Procesar tecnologías
        const techInput = document.getElementById('technologies_input').value;
        const technologies = techInput ? techInput.split(',').map(t => t.trim()).filter(t => t) : [];
        document.getElementById('technologies_hidden').value = JSON.stringify(technologies);

        // Procesar accesos requeridos
        const accessInput = document.getElementById('required_accesses_input').value;
        const accesses = accessInput ? accessInput.split(',').map(a => a.trim()).filter(a => a) : [];
        document.getElementById('required_accesses_hidden').value = JSON.stringify(accesses);

        // Validar fecha y hora
        const scheduledDate = document.getElementById('scheduled_date').value;
        const scheduledTime = document.getElementById('scheduled_start_time').value;

        if (scheduledDate && scheduledTime) {
            const scheduledDateTime = new Date(`${scheduledDate}T${scheduledTime}`);
            const now = new Date();

            if (scheduledDateTime < now) {
                e.preventDefault();
                alert('No se puede asignar una tarea en una fecha y hora pasadas.');
                return false;
            }

            // Validar horario laboral (6:00 - 18:00)
            const hour = parseInt(scheduledTime.split(':')[0]);
            if (hour < 6 || hour >= 18) {
                e.preventDefault();
                alert('La hora debe estar dentro del horario laboral (6:00 - 18:00).');
                return false;
            }

            // Advertencias para horarios no hábiles
            const selectedDate = new Date(scheduledDate);
            const dayOfWeek = selectedDate.getDay();
            const warnings = [];

            // Domingo
            if (dayOfWeek === 0) {
                warnings.push('🗓️ DOMINGO - Día no hábil');
            }

            // Antes de las 8am o después de las 4pm
            if (hour < 8) {
                warnings.push('🕐 ANTES DE LAS 8:00 AM - Horario no hábil');
            } else if (hour >= 16) {
                warnings.push('🕐 DESPUÉS DE LAS 4:00 PM - Horario no hábil');
            }

            // Mostrar advertencia si aplica
            if (warnings.length > 0) {
                const message = '⚠️ ADVERTENCIA DE HORARIO NO HÁBIL:\n\n' + warnings.join('\n') + '\n\n¿Desea continuar con la asignación?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        }
    });    // Auto-ajustar duración estimada según el tipo de tarea
    document.getElementById('type').addEventListener('change', function() {
        const estimatedValueInput = document.getElementById('estimated_duration_value');
        const unitSelect = document.getElementById('estimated_duration_unit');

        if (this.value === 'impact') {
            estimatedValueInput.value = '90';
            unitSelect.value = 'minutes';
        } else if (this.value === 'regular') {
            estimatedValueInput.value = '25';
            unitSelect.value = 'minutes';
        }

        updateEstimatedHours();
    });

    // Convertir entre minutos y horas
    function updateEstimatedHours() {
        const value = parseFloat(document.getElementById('estimated_duration_value').value) || 0;
        const unit = document.getElementById('estimated_duration_unit').value;

        let hours = 0;
        if (unit === 'hours') {
            hours = value;
        } else if (unit === 'minutes') {
            hours = value / 60;
        }

        document.getElementById('estimated_hours').value = hours.toFixed(2);
    }

    // Actualizar cuando cambie el valor o la unidad
    document.getElementById('estimated_duration_value').addEventListener('input', updateEstimatedHours);
    document.getElementById('estimated_duration_unit').addEventListener('change', function() {
        const value = parseFloat(document.getElementById('estimated_duration_value').value) || 0;
        const newUnit = this.value;

        // Convertir el valor a la nueva unidad
        if (newUnit === 'hours' && value > 12) {
            // Convertir de minutos a horas
            document.getElementById('estimated_duration_value').value = (value / 60).toFixed(1);
        } else if (newUnit === 'minutes' && value <= 12) {
            // Convertir de horas a minutos
            document.getElementById('estimated_duration_value').value = Math.round(value * 60);
        }

        updateEstimatedHours();
    });

    // Inicializar
    updateEstimatedHours();

    // Validar disponibilidad del técnico (opcional - podría ser una llamada AJAX)
    document.getElementById('technician_id').addEventListener('change', function() {
        const technicianId = this.value;
        const scheduledDate = document.getElementById('scheduled_date').value;

        if (technicianId && scheduledDate) {
            // Aquí podrías agregar una llamada AJAX para verificar disponibilidad
            console.log('Verificando disponibilidad del técnico:', technicianId, 'en fecha:', scheduledDate);
        }
    });

    // Validar que la fecha y hora programadas no sean del pasado
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        const scheduledDate = document.getElementById('scheduled_date').value;
        const scheduledTime = document.getElementById('scheduled_start_time').value;

        if (scheduledDate && scheduledTime) {
            const scheduledDateTime = new Date(`${scheduledDate}T${scheduledTime}`);
            const now = new Date();

            if (scheduledDateTime < now) {
                e.preventDefault();
                alert('No se puede asignar una tarea en una fecha y hora pasadas. Por favor, seleccione una fecha y hora futuras.');
                return false;
            }
        }
    });

    // Validar campos de fecha y hora en tiempo real
    function validateDateTime() {
        const scheduledDate = document.getElementById('scheduled_date').value;
        const scheduledTime = document.getElementById('scheduled_start_time').value;

        if (scheduledDate && scheduledTime) {
            const scheduledDateTime = new Date(`${scheduledDate}T${scheduledTime}`);
            const now = new Date();

            if (scheduledDateTime < now) {
                document.getElementById('scheduled_date').setCustomValidity('La fecha y hora no pueden ser del pasado');
                document.getElementById('scheduled_start_time').setCustomValidity('La fecha y hora no pueden ser del pasado');
            } else {
                document.getElementById('scheduled_date').setCustomValidity('');
                document.getElementById('scheduled_start_time').setCustomValidity('');
            }
        }
    }

    document.getElementById('scheduled_date').addEventListener('change', validateDateTime);
    document.getElementById('scheduled_start_time').addEventListener('change', validateDateTime);

</script>
@endsection
@endsection
