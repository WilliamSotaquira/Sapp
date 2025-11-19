@extends('layouts.app')

@section('title', 'Crear Nueva Tarea')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Modal de Validación Inicial -->
    <div id="initialValidationModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-[9999]">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300">
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-clipboard-check mr-2"></i>
                        Validación de Tarea
                    </h3>
                    <span id="modalStepIndicator" class="text-white text-sm font-semibold bg-white bg-opacity-20 px-3 py-1 rounded-full">Paso 1/3</span>
                </div>
                <!-- Barra de progreso -->
                <div class="mt-3 bg-white bg-opacity-20 rounded-full h-2">
                    <div id="progressBar" class="bg-white h-2 rounded-full transition-all duration-300" style="width: 33%"></div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Paso 1: Selección de Técnico -->
                <div id="step1" class="space-y-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Paso 1: Seleccione el técnico asignado
                        </p>
                    </div>

                    <div>
                        <label for="modal_technician_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Técnico Asignado <span class="text-red-500">*</span>
                        </label>
                        <select id="modal_technician_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="">Seleccione un técnico...</option>
                            @foreach($technicians as $technician)
                                @if($technician->user)
                                    <option value="{{ $technician->id }}">
                                        {{ $technician->user->name }} - {{ ucfirst($technician->specialization) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="continueToStep2" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        Continuar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>

                <!-- Paso 2: Vincular a Solicitud -->
                <div id="step2" class="hidden space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <p class="text-sm text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>
                            Paso 2: ¿Vincular a una solicitud de servicio?
                        </p>
                    </div>

                    <div class="space-y-3">
                        <button type="button" id="linkToRequestYes" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-link mr-2"></i>
                            Sí, vincular a solicitud
                        </button>
                        <button type="button" id="linkToRequestNo" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>
                            No, crear tarea independiente
                        </button>
                    </div>

                    <button type="button" id="backToStep1" class="w-full text-gray-600 hover:text-gray-800 text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </button>
                </div>

                <!-- Paso 3: Selección de Solicitud -->
                <div id="step3" class="hidden space-y-4">
                    <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                        <p class="text-sm text-purple-800">
                            <i class="fas fa-search mr-2"></i>
                            Paso 3: Seleccione la solicitud de servicio
                        </p>
                    </div>

                    <div>
                        <label for="modal_service_request_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Solicitud de Servicio
                        </label>
                        <div class="relative">
                            <select id="modal_service_request_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Seleccione una solicitud...</option>
                            </select>
                            <div id="loadingSpinner" class="hidden absolute right-3 top-3">
                                <i class="fas fa-spinner fa-spin text-red-600"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            <i class="fas fa-filter mr-1"></i>
                            Solo se mostrarán solicitudes asignadas al técnico seleccionado
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" id="backToStep2" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </button>
                        <button type="button" id="confirmAndLoadData" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Confirmar <i class="fas fa-check ml-2"></i>
                        </button>
                    </div>

                    <div id="noRequestsAlert" class="hidden mt-2">
                        <button type="button" id="skipRequestSelection" class="w-full bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors text-sm">
                            <i class="fas fa-forward mr-2"></i>Continuar sin solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario Principal (Oculto inicialmente) -->
    <div id="mainFormContainer" class="hidden">
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
                            <i class="fas fa-info-circle text-gray-400 ml-1 cursor-help" title="Ingrese un título descriptivo y conciso para la tarea"></i>
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

                    <!-- Subtareas -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Subtareas (Opcional)
                                <i class="fas fa-info-circle text-gray-400 ml-1 cursor-help" title="Divida la tarea en partes independientes asignables"></i>
                            </label>
                            <button type="button" id="toggleSubtasksBtn" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                                <i class="fas fa-plus mr-1"></i>
                                <span id="toggleSubtasksText">Agregar subtareas</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Subtareas (Dinámico) -->
            <div id="subtasksSection" class="border-b pb-4 hidden">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-tasks mr-2 text-red-600"></i>
                    Subtareas
                </h3>
                <div id="subtasksContainer" class="space-y-3">
                    <!-- Las subtareas se agregarán dinámicamente aquí -->
                </div>
                <button type="button" id="addSubtaskBtn" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Agregar Subtarea
                </button>
            </div>

            <!-- Asociaciones Opcionales -->
            <div class="border-b pb-4" id="associationsSection">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-link mr-2 text-red-600"></i>
                    Asociaciones (Opcional)
                </h3>

                <!-- Alerta de información cuando viene desde modal -->
                <div id="preselectedRequestAlert" class="hidden mb-4 bg-blue-50 border-l-4 border-blue-500 p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-800">
                                Solicitud vinculada automáticamente
                            </p>
                            <p class="text-xs text-blue-700 mt-1">
                                Los datos de técnico, prioridad y duración se cargaron desde la solicitud seleccionada.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Solicitud de Servicio -->
                    <div class="md:col-span-2">
                        <label for="service_request_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Solicitud de Servicio
                        </label>
                        <select name="service_request_id"
                                id="service_request_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('service_request_id') border-red-500 @enderror">
                            <option value="">Sin asociar</option>
                            @forelse($serviceRequests as $request)
                                @php
                                    // Calcular duración estimada desde el SLA
                                    $estimatedHours = 0;
                                    if ($request->sla && $request->sla->resolution_time_minutes) {
                                        $estimatedHours = round($request->sla->resolution_time_minutes / 60, 1);
                                    }
                                @endphp
                                <option value="{{ $request->id }}"
                                        data-technician="{{ $request->assignee?->technician?->id ?? '' }}"
                                        data-priority="{{ $request->criticality_level ?? '' }}"
                                        data-duration="{{ $estimatedHours }}"
                                        {{ old('service_request_id') == $request->id ? 'selected' : '' }}>
                                    #{{ $request->ticket_number }} - {{ Str::limit($request->title, 60) }}
                                    @if($request->assigned_to)
                                        (Técnico: {{ $request->assignee->name ?? 'N/A' }})
                                    @endif
                                </option>
                            @empty
                                <option value="" disabled>No hay solicitudes disponibles</option>
                            @endforelse
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Si selecciona una solicitud, se cargarán automáticamente el técnico, prioridad y duración estimada
                        </p>
                        @error('service_request_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Proyecto -->
                    <div class="md:col-span-2">
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

            <!-- Información Técnica (Colapsable) -->
            <div class="border-b pb-4">
                <button type="button" id="toggleTechnicalInfo" class="w-full flex items-center justify-between text-left py-2 hover:bg-gray-50 rounded-lg transition-colors">
                    <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                        <i class="fas fa-code mr-2 text-red-600"></i>
                        Información Técnica (Opcional)
                    </h3>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="technicalInfoIcon"></i>
                </button>

                <div id="technicalInfoContent" class="mt-4 grid-cols-1 md:grid-cols-2 gap-4" style="display: none;">
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

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('tasks.index') }}"
                   class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        id="submitBtn"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i>
                    <span id="submitBtnText">Crear Tarea</span>
                    <i class="fas fa-spinner fa-spin ml-2 hidden" id="submitSpinner"></i>
                </button>
            </div>
        </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }

    /* Mejorar transiciones de secciones */
    .section-transition {
        transition: all 0.3s ease-in-out;
    }

    /* Efecto de enfoque mejorado */
    input:focus, select:focus, textarea:focus {
        transform: scale(1.01);
        transition: transform 0.2s ease;
    }
</style>

<script>
    // Contador de caracteres para descripción
    document.getElementById('description').addEventListener('input', function() {
        const charCount = this.value.length;
        const counter = document.getElementById('charCount');
        counter.textContent = `${charCount} caracteres`;

        if (charCount > 800) {
            counter.classList.add('text-red-500');
            counter.classList.remove('text-gray-500');
        } else {
            counter.classList.remove('text-red-500');
            counter.classList.add('text-gray-500');
        }
    });

    // Actualizar indicadores de progreso en modal
    function updateModalProgress(step) {
        const indicator = document.getElementById('modalStepIndicator');
        const progressBar = document.getElementById('progressBar');

        if (indicator) {
            indicator.textContent = `Paso ${step}/3`;
        }

        if (progressBar) {
            const progress = (step / 3) * 100;
            progressBar.style.width = progress + '%';
        }
    }

    // Manejo de subtareas con botón toggle
    var subtaskCounter = 0;
    const subtasksSection = document.getElementById('subtasksSection');
    const toggleSubtasksBtn = document.getElementById('toggleSubtasksBtn');
    const toggleSubtasksText = document.getElementById('toggleSubtasksText');

    // Toggle para mostrar/ocultar sección de subtareas
    toggleSubtasksBtn.addEventListener('click', function() {
        if (subtasksSection.classList.contains('hidden')) {
            subtasksSection.classList.remove('hidden');
            subtasksSection.classList.add('animate-fade-in');
            toggleSubtasksText.textContent = 'Ocultar subtareas';
            this.querySelector('i').classList.remove('fa-plus');
            this.querySelector('i').classList.add('fa-minus');

            // Agregar primera subtarea si no hay ninguna
            if (subtaskCounter === 0) {
                addSubtask();
            }
        } else {
            subtasksSection.classList.add('hidden');
            toggleSubtasksText.textContent = 'Agregar subtareas';
            this.querySelector('i').classList.remove('fa-minus');
            this.querySelector('i').classList.add('fa-plus');
        }
    });

    // Agregar subtarea
    document.getElementById('addSubtaskBtn').addEventListener('click', addSubtask);

    function addSubtask() {
        subtaskCounter++;
        var container = document.getElementById('subtasksContainer');
        var subtaskHtml = `
            <div class="subtask-item bg-gray-50 p-4 rounded-lg border border-gray-200 animate-fade-in" data-subtask="${subtaskCounter}">
                <div class="flex justify-between items-start mb-3">
                    <h4 class="font-semibold text-gray-700">Subtarea #${subtaskCounter}</h4>
                    <button type="button" onclick="removeSubtask(${subtaskCounter})" class="text-red-500 hover:text-red-700 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                        <input type="text" name="subtasks[${subtaskCounter}][title]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="subtasks[${subtaskCounter}][description]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duración estimada (min)</label>
                        <input type="number" name="subtasks[${subtaskCounter}][estimated_minutes]" value="15" min="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                        <select name="subtasks[${subtaskCounter}][priority]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', subtaskHtml);
    }

    window.removeSubtask = function(id) {
        var element = document.querySelector(`[data-subtask="${id}"]`);
        if (element) {
            // Animación de salida
            element.style.opacity = '0';
            element.style.transform = 'scale(0.95)';
            element.style.transition = 'all 0.2s ease-out';
            setTimeout(() => {
                element.remove();
                subtaskCounter--;

                // Si no quedan subtareas, ocultar la sección
                if (document.querySelectorAll('.subtask-item').length === 0) {
                    subtasksSection.classList.add('hidden');
                    toggleSubtasksText.textContent = 'Agregar subtareas';
                    toggleSubtasksBtn.querySelector('i').classList.remove('fa-minus');
                    toggleSubtasksBtn.querySelector('i').classList.add('fa-plus');
                }
            }, 200);
        }
    };

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
    });

    // Toggle para Información Técnica
    const toggleTechnicalInfo = document.getElementById('toggleTechnicalInfo');
    const technicalInfoContent = document.getElementById('technicalInfoContent');
    const technicalInfoIcon = document.getElementById('technicalInfoIcon');

    toggleTechnicalInfo.addEventListener('click', function() {
        if (technicalInfoContent.style.display === 'none' || !technicalInfoContent.style.display) {
            technicalInfoContent.style.display = 'grid';
        } else {
            technicalInfoContent.style.display = 'none';
        }
        technicalInfoIcon.classList.toggle('fa-chevron-down');
        technicalInfoIcon.classList.toggle('fa-chevron-up');
    });

    // Cargar datos desde solicitud de servicio
    const serviceRequestSelect = document.getElementById('service_request_id');
    const technicianSelect = document.getElementById('technician_id');
    const prioritySelect = document.getElementById('priority');
    const estimatedDurationValue = document.getElementById('estimated_duration_value');
    const estimatedDurationUnit = document.getElementById('estimated_duration_unit');

    serviceRequestSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            // Cargar técnico si está disponible
            const technicianId = selectedOption.dataset.technician;
            if (technicianId) {
                technicianSelect.value = technicianId;
            }

            // Cargar prioridad según criticidad
            const criticality = selectedOption.dataset.priority;
            if (criticality) {
                // Mapear criticidad a prioridad
                const priorityMap = {
                    'low': 'low',
                    'medium': 'medium',
                    'high': 'high',
                    'critical': 'urgent'
                };
                prioritySelect.value = priorityMap[criticality] || 'medium';
            }

            // Cargar duración estimada
            const duration = selectedOption.dataset.duration;
            if (duration && duration > 0) {
                const hours = parseFloat(duration);
                if (hours >= 1) {
                    estimatedDurationValue.value = hours.toFixed(1);
                    estimatedDurationUnit.value = 'hours';
                } else {
                    estimatedDurationValue.value = Math.round(hours * 60);
                    estimatedDurationUnit.value = 'minutes';
                }
                updateEstimatedHours();
            }

            // Mostrar mensaje informativo
            console.log('Datos cargados desde solicitud de servicio:', {
                technician: technicianId,
                priority: criticality,
                duration: duration
            });
        }
    });

    // Auto-ajustar duración estimada según el tipo de tarea
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

    // Mejorar feedback visual al enviar formulario
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitBtnText');
        const submitSpinner = document.getElementById('submitSpinner');

        if (submitBtn && submitText && submitSpinner) {
            submitBtn.disabled = true;
            submitText.textContent = 'Creando...';
            submitSpinner.classList.remove('hidden');
        }
    });

    // ===== GESTIÓN DEL MODAL DE VALIDACIÓN INICIAL =====
    const initialModal = document.getElementById('initialValidationModal');
    const mainFormContainer = document.getElementById('mainFormContainer');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');

    let selectedTechnicianId = null;
    let selectedServiceRequestId = null;
    let linkToRequest = false;

    // Paso 1 -> Paso 2
    document.getElementById('continueToStep2').addEventListener('click', function() {
        selectedTechnicianId = document.getElementById('modal_technician_id').value;

        if (!selectedTechnicianId) {
            alert('Por favor seleccione un técnico');
            return;
        }

        step1.classList.add('hidden');
        step2.classList.remove('hidden');
        updateModalProgress(2);
    });

    // Paso 2 -> Opción SÍ vincular a solicitud
    document.getElementById('linkToRequestYes').addEventListener('click', function() {
        linkToRequest = true;
        loadServiceRequestsForTechnician(selectedTechnicianId);
        step2.classList.add('hidden');
        step3.classList.remove('hidden');
        updateModalProgress(3);
    });

    // Paso 2 -> Opción NO vincular a solicitud
    document.getElementById('linkToRequestNo').addEventListener('click', function() {
        linkToRequest = false;
        selectedServiceRequestId = null;
        closeModalAndLoadForm();
    });

    // Volver de Paso 2 a Paso 1
    document.getElementById('backToStep1').addEventListener('click', function() {
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
        updateModalProgress(1);
    });

    // Volver de Paso 3 a Paso 2
    document.getElementById('backToStep2').addEventListener('click', function() {
        step3.classList.add('hidden');
        step2.classList.remove('hidden');
        updateModalProgress(2);
    });

    // Confirmar y cargar datos
    document.getElementById('confirmAndLoadData').addEventListener('click', function() {
        selectedServiceRequestId = document.getElementById('modal_service_request_id').value;

        if (!selectedServiceRequestId) {
            alert('Por favor seleccione una solicitud de servicio');
            return;
        }

        closeModalAndLoadForm();
    });

    // Cargar solicitudes para el técnico seleccionado
    function loadServiceRequestsForTechnician(technicianId) {
        const select = document.getElementById('modal_service_request_id');
        const noRequestsAlert = document.getElementById('noRequestsAlert');
        const spinner = document.getElementById('loadingSpinner');

        select.innerHTML = '<option value="">Cargando...</option>';
        select.disabled = true;
        noRequestsAlert.classList.add('hidden');

        if (spinner) {
            spinner.classList.remove('hidden');
        }

        fetch(`/api/service-requests/by-technician/${technicianId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                select.disabled = false;
                if (spinner) {
                    spinner.classList.add('hidden');
                }

                select.innerHTML = '<option value="">Seleccione una solicitud...</option>';

                if (!data || data.error) {
                    select.innerHTML = '<option value="">Error: ' + (data.error || 'Error desconocido') + '</option>';
                    noRequestsAlert.classList.remove('hidden');
                    return;
                }

                if (data.length === 0) {
                    select.innerHTML = '<option value="">No hay solicitudes ACEPTADAS disponibles</option>';
                    noRequestsAlert.classList.remove('hidden');
                    return;
                }

                data.forEach(request => {
                    const option = document.createElement('option');
                    option.value = request.id;
                    option.textContent = `#${request.ticket_number} - ${request.title}`;
                    option.dataset.technician = request.assigned_technician_id || '';
                    option.dataset.priority = request.criticality_level || '';
                    option.dataset.duration = request.estimated_hours || '';
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error cargando solicitudes:', error);
                select.innerHTML = '<option value="">Error al cargar solicitudes</option>';
                noRequestsAlert.classList.remove('hidden');
            });
    }

    // Botón para omitir selección de solicitud
    document.getElementById('skipRequestSelection').addEventListener('click', function() {
        selectedServiceRequestId = null;
        linkToRequest = false;
        closeModalAndLoadForm();
    });

    // Cerrar modal y mostrar formulario
    function closeModalAndLoadForm() {
        // Animación de salida del modal
        const modalContent = initialModal.querySelector('.bg-white');
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';

        setTimeout(() => {
            initialModal.classList.add('hidden');
            mainFormContainer.classList.remove('hidden');
            mainFormContainer.classList.add('animate-fade-in');

            // Reset modal animation
            modalContent.style.transform = '';
            modalContent.style.opacity = '';
        }, 200);

        // Asignar técnico al formulario
        document.getElementById('technician_id').value = selectedTechnicianId;

        // Si hay solicitud vinculada, cargar datos automáticamente
        if (linkToRequest && selectedServiceRequestId) {
            document.getElementById('service_request_id').value = selectedServiceRequestId;

            // Mostrar alerta informativa
            document.getElementById('preselectedRequestAlert').classList.remove('hidden');

            // Trigger change event para cargar datos automáticamente
            const event = new Event('change');
            document.getElementById('service_request_id').dispatchEvent(event);

            // Scroll a la sección de asociaciones para que vea la alerta
            setTimeout(() => {
                document.getElementById('associationsSection').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);
        }
    }

</script>
@endsection
