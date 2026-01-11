@extends('layouts.app')

@section('title', 'Editar Tarea')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-edit mr-3"></i>
                Editar Tarea: {{ $task->task_code }}
            </h2>
        </div>

        <form action="{{ route('tasks.update', $task) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Información Básica -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-yellow-600"></i>
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
                               value="{{ old('title', $task->title) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('title') border-red-500 @enderror"
                               required>
                        @error('title')
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('priority') border-red-500 @enderror"
                                required>
                            <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="urgent" {{ old('priority', $task->priority) == 'urgent' ? 'selected' : '' }}>Urgente</option>
                            <option value="critical" {{ old('priority', $task->priority) == 'critical' ? 'selected' : '' }}>Crítica</option>
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Las tareas críticas/altas con fecha de vencimiento se programan por la mañana</p>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="status"
                                id="status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('status') border-red-500 @enderror"
                                required>
                            <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="confirmed" {{ old('status', $task->status) == 'confirmed' ? 'selected' : '' }}>Confirmada</option>
                            <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                            <option value="blocked" {{ old('status', $task->status) == 'blocked' ? 'selected' : '' }}>Bloqueada</option>
                            <option value="in_review" {{ old('status', $task->status) == 'in_review' ? 'selected' : '' }}>En Revisión</option>
                            <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                            <option value="rescheduled" {{ old('status', $task->status) == 'rescheduled' ? 'selected' : '' }}>Reprogramada</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción (Opcional)
                        </label>
                        <textarea name="description"
                                  id="description"
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('description') border-red-500 @enderror"
                                  >{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Asignación -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user-check mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('technician_id') border-red-500 @enderror"
                                required>
                            @foreach($technicians as $technician)
                                @if($technician->user)
                                    <option value="{{ $technician->id }}" {{ old('technician_id', $task->technician_id) == $technician->id ? 'selected' : '' }}>
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
                               value="{{ old('scheduled_date', $task->scheduled_date->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('scheduled_date') border-red-500 @enderror"
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
                               value="{{ old('scheduled_start_time', $task->scheduled_start_time ? substr($task->scheduled_start_time, 0, 5) : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('scheduled_start_time') border-red-500 @enderror"
                               required>
                        @error('scheduled_start_time')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Duración Estimada -->
                    <div>
                        <label for="estimated_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Duración Estimada (horas) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="estimated_hours"
                               id="estimated_hours"
                               value="{{ old('estimated_hours', $task->estimated_hours) }}"
                               step="0.5"
                               min="0.5"
                               max="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('estimated_hours') border-red-500 @enderror"
                               required>
                        @error('estimated_hours')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">La duración se calcula automáticamente según las subtareas (unidad: 25 min)</p>
                    </div>
                </div>
            </div>

            <!-- Control y Seguimiento -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-calendar-check mr-2 text-yellow-600"></i>
                    Control y Seguimiento
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Fecha Límite -->
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha Límite
                        </label>
                        <input type="date"
                               name="due_date"
                               id="due_date"
                               value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('due_date') border-red-500 @enderror">
                        @error('due_date')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Hora Límite -->
                    <div>
                        <label for="due_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Hora Límite
                        </label>
                        <input type="time"
                               name="due_time"
                               id="due_time"
                               value="{{ old('due_time', $task->due_time ? substr($task->due_time, 0, 5) : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('due_time') border-red-500 @enderror">
                        @error('due_time')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tarea Crítica -->
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="is_critical"
                               id="is_critical"
                               value="1"
                               {{ old('is_critical', $task->is_critical) ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                        <label for="is_critical" class="ml-2 text-sm font-medium text-gray-700">
                            Marcar como tarea crítica
                        </label>
                    </div>

                    <!-- Requiere Evidencia -->
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="requires_evidence"
                               id="requires_evidence"
                               value="1"
                               {{ old('requires_evidence', $task->requires_evidence) ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                        <label for="requires_evidence" class="ml-2 text-sm font-medium text-gray-700">
                            Requiere evidencia obligatoria
                        </label>
                    </div>
                </div>
            </div>

            <!-- Asociaciones Opcionales -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-link mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('service_request_id') border-red-500 @enderror">
                            <option value="">Sin asociar</option>
                            @forelse($serviceRequests as $request)
                                <option value="{{ $request->id }}" {{ old('service_request_id', $task->service_request_id) == $request->id ? 'selected' : '' }}>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('project_id') border-red-500 @enderror">
                            <option value="">Sin asociar</option>
                            @forelse($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
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

            <!-- Información Técnica -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-code mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('technical_complexity') border-red-500 @enderror">
                            <option value="">No especificada</option>
                            <option value="1" {{ old('technical_complexity', $task->technical_complexity) == 1 ? 'selected' : '' }}>1 - Muy Baja</option>
                            <option value="2" {{ old('technical_complexity', $task->technical_complexity) == 2 ? 'selected' : '' }}>2 - Baja</option>
                            <option value="3" {{ old('technical_complexity', $task->technical_complexity) == 3 ? 'selected' : '' }}>3 - Media</option>
                            <option value="4" {{ old('technical_complexity', $task->technical_complexity) == 4 ? 'selected' : '' }}>4 - Alta</option>
                            <option value="5" {{ old('technical_complexity', $task->technical_complexity) == 5 ? 'selected' : '' }}>5 - Muy Alta</option>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('environment') border-red-500 @enderror">
                            <option value="">No especificado</option>
                            <option value="development" {{ old('environment', $task->environment) == 'development' ? 'selected' : '' }}>Desarrollo</option>
                            <option value="staging" {{ old('environment', $task->environment) == 'staging' ? 'selected' : '' }}>Staging</option>
                            <option value="production" {{ old('environment', $task->environment) == 'production' ? 'selected' : '' }}>Producción</option>
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
                               value="{{ old('technologies_input', is_string($task->technologies) ? implode(', ', json_decode($task->technologies, true) ?? []) : '') }}"
                               placeholder="PHP, Laravel, JavaScript, MySQL"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('technologies') border-red-500 @enderror">
                        <input type="hidden" name="technologies" id="technologies">
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
                               value="{{ old('required_accesses_input', is_string($task->required_accesses) ? implode(', ', json_decode($task->required_accesses, true) ?? []) : '') }}"
                               placeholder="VPN, Servidor Producción, Base de Datos"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('required_accesses') border-red-500 @enderror">
                        <input type="hidden" name="required_accesses" id="required_accesses">
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
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('technical_notes') border-red-500 @enderror"
                                  placeholder="Detalles técnicos adicionales...">{{ old('technical_notes', $task->technical_notes) }}</textarea>
                        @error('technical_notes')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('tasks.show', $task) }}"
                   class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
    // Convertir tecnologías y accesos a JSON antes de enviar
    document.querySelector('form').addEventListener('submit', function(e) {
        // Procesar tecnologías
        const techInput = document.getElementById('technologies_input').value;
        const technologies = techInput ? techInput.split(',').map(t => t.trim()).filter(t => t) : [];
        document.getElementById('technologies').value = JSON.stringify(technologies);

        // Procesar accesos requeridos
        const accessInput = document.getElementById('required_accesses_input').value;
        const accesses = accessInput ? accessInput.split(',').map(a => a.trim()).filter(a => a) : [];
        document.getElementById('required_accesses').value = JSON.stringify(accesses);

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
    });    // Validar campos de fecha y hora en tiempo real
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

