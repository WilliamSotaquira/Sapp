@extends('layouts.app')

@section('title', 'Crear Solicitud de Servicio')

@section('breadcrumb')
@include('service-requests.partials.breadcrumb-create')
@endsection

@section('content')
<div class="bg-gradient-to-br from-white to-blue-50 shadow-xl rounded-2xl overflow-hidden">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-plus-circle text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Crear Nueva Solicitud</h1>
                    <p class="text-blue-100 opacity-90">Complete la información para crear una nueva solicitud de servicio</p>
                </div>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
                <span class="text-sm font-semibold">Paso 1 de 3</span>
            </div>
        </div>
    </div>

    <form action="{{ route('service-requests.store') }}" method="POST" id="serviceRequestForm" novalidate enctype="multipart/form-data" class="p-8">
        @csrf

        <!-- Mensajes de validación -->
        @if ($errors->any())
        <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-red-800 font-semibold">Error en el formulario</h3>
                    <ul class="mt-2 text-red-700 text-sm list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Alertas de éxito -->
        @if (session('success'))
        <div class="mb-8 bg-green-50 border-l-4 border-green-500 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Columna Izquierda -->
            <div class="space-y-8">

                <!-- Información de la Solicitud -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-orange-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-edit text-orange-600 mr-3"></i>
                            Información de la Solicitud
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Campo Título -->
                            <div>
                                <label for="title" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-heading text-orange-500 mr-2"></i>
                                    Título de la Solicitud <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                    maxlength="255"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                    placeholder="Ingrese un título descriptivo para la solicitud">
                                @error('title')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </p>
                                @enderror
                                <div class="mt-2 flex justify-between items-center">
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-lightbulb mr-1"></i>Sea específico y descriptivo
                                    </p>
                                    <span class="text-xs font-medium text-gray-500" id="titleCount">0/255</span>
                                </div>
                            </div>

                            <!-- Campo Descripción -->
                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-align-left text-orange-500 mr-2"></i>
                                    Descripción Detallada <span class="text-red-500 ml-1">*</span>
                                </label>
                                <textarea name="description" id="description" required rows="8"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 resize-none"
                                    placeholder="Describa en detalle el problema o requerimiento...">{{ old('description') }}</textarea>
                                @error('description')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </p>
                                @enderror
                                <div class="mt-3 flex justify-between items-center">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center space-x-2">
                                            <div id="descriptionIndicator" class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            <span class="text-xs font-medium text-gray-500" id="descriptionCount">0 caracteres</span>
                                        </div>
                                        <span class="text-xs text-red-500 font-medium">
                                            <i class="fas fa-asterisk mr-1"></i>Campo obligatorio
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros de Servicio -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-filter text-blue-600 mr-3"></i>
                            Filtros de Servicio
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="assignment-fields space-y-4">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 pb-4">Detalles del Servicio</h3>

                                <!-- Sub-Servicio -->
                                <div class="md:col-span-1 mb-4">
                                    <label for="sub_service_id" class="block text-sm font-medium text-gray-700 mb-2">Sub-Servicio *</label>
                                    <select name="sub_service_id" id="sub_service_id" required
                                        class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Seleccione un sub-servicio</option>
                                        @foreach($subServices as $familyName => $familySubServices)
                                        <optgroup label="{{ $familyName }}" data-family="{{ $familyName }}">
                                            @foreach($familySubServices as $subService)
                                            <option value="{{ $subService->id }}"
                                                data-family="{{ $familyName }}"
                                                data-service="{{ $subService->service->name }}"
                                                {{ old('sub_service_id') == $subService->id ? 'selected' : '' }}>
                                                {{ $subService->name }} - {{ $subService->service->name }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                    @error('sub_service_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Selector de SLA -->
                                <div class="mb-4">
                                    <label for="sla_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Seleccionar SLA
                                    </label>
                                    <select id="sla_id" name="sla_id"
                                        class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 mb-2">
                                        <option value="">Seleccione un sub-servicio primero</option>
                                    </select>

                                    <!-- Botón en nueva fila -->
                                    <div class="flex justify-start">
                                        <button type="button" id="updateSlaSelect"
                                            class="hidden bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded whitespace-nowrap text-sm font-medium">
                                            <i class="fas fa-plus mr-2"></i>Crear Nuevo SLA
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solicitante -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-purple-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-user-circle text-purple-600 mr-3"></i>
                            Información del Solicitante
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Campo Solicitante -->
                            <div>
                                <label for="requested_by" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-user text-purple-500 mr-2"></i>
                                    Solicitante <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="requested_by" id="requested_by" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200">
                                    <option value="">Seleccione un solicitante</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('requested_by', auth()->id()) == $user->id ? 'selected' : '' }}
                                        data-email="{{ $user->email }}"
                                        data-department="{{ $user->department ?? 'Sin departamento' }}"
                                        data-avatar="{{ $user->avatar_url ?? '' }}"
                                        data-name="{{ $user->name }}">
                                        {{ $user->name }} - {{ $user->email }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('requested_by')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </p>
                                @enderror
                            </div>

                            <!-- Información del Solicitante -->
                            <div id="requesterInfo" class="bg-gradient-to-r from-purple-50 to-white p-5 rounded-xl border-2 border-purple-100">
                                <!-- Este div se mostrará siempre, no necesita hidden -->
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-id-card text-purple-600 mr-2"></i>
                                    Información del Solicitante Seleccionado
                                </h4>
                                <div class="grid grid-cols-1 gap-3 text-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold" id="requesterAvatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-gray-600">Nombre:</span>
                                            <span id="requesterName" class="ml-2 text-gray-800">{{ auth()->user()->name }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-envelope text-purple-500 w-5 text-center"></i>
                                        <div>
                                            <span class="font-semibold text-gray-600">Email:</span>
                                            <span id="requesterEmail" class="ml-2 text-gray-800">{{ auth()->user()->email }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-building text-purple-500 w-5 text-center"></i>
                                        <div>
                                            <span class="font-semibold text-gray-600">Departamento:</span>
                                            <span id="requesterDepartment" class="ml-2 text-gray-800">{{ auth()->user()->department ?? 'Sin departamento' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campo Asignado a (se muestra cuando auto_assign NO está marcado) -->
                            <div id="assignedToField" class="hidden">
                                <label for="assigned_to" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-user-check text-blue-500 mr-2"></i>
                                    Asignar a <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="assigned_to" id="assigned_to"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Seleccione un asignado</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} - {{ $user->email }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </p>
                                @enderror
                            </div>

                            <!-- Auto-asignación -->
                            <div class="bg-yellow-50 border-2 border-yellow-100 rounded-xl p-4">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" name="auto_assign" id="auto_assign" value="1"
                                        class="h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                                        {{ old('auto_assign', true) ? 'checked' : '' }}>
                                    <label for="auto_assign" class="text-sm text-gray-700 font-medium">
                                        Auto-asignarme como responsable de esta solicitud
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-yellow-700">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Si está desmarcado, deberá seleccionar manualmente un asignado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Columna Derecha -->
            <div class="space-y-8">


                <!-- Rutas Web -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4 border-b border-indigo-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-globe text-indigo-600 mr-3"></i>
                            Rutas Web
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="web-routes-section">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Rutas Web</h3>
                                    <button type="button" id="add-route-btn"
                                        class="bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600">
                                        <i class="fas fa-plus mr-1"></i>Agregar Ruta
                                    </button>
                                </div>

                                <div id="web-routes-container" class="space-y-2">
                                    <!-- Los campos de ruta se agregarán aquí dinámicamente -->
                                </div>
                                <p class="text-sm text-gray-500 mt-2">
                                    Agregue las rutas web asociadas a esta solicitud de servicio.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evidencias iniciales -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-paperclip text-green-600 mr-3"></i>
                            Evidencias Iniciales
                            <span class="ml-2 text-sm font-normal text-green-600 bg-green-100 px-2 py-1 rounded-full">Opcional</span>
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-file-upload text-green-500 mr-2"></i>
                                    Archivos Adjuntos
                                </label>
                                <div class="border-2 border-dashed border-green-200 rounded-xl p-6 text-center hover:border-green-400 hover:bg-green-50">
                                    <input type="file" name="initial_evidences[]" multiple
                                        class="hidden" id="fileInput"
                                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                                    <div class="cursor-pointer" onclick="document.getElementById('fileInput').click()">
                                        <i class="fas fa-cloud-upload-alt text-green-400 text-3xl mb-3"></i>
                                        <p class="text-sm text-gray-600 font-medium">Haga clic para seleccionar archivos</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Formatos: JPG, PNG, PDF, DOC, XLS • Máx. 5 archivos
                                        </p>
                                    </div>
                                </div>
                                <div id="filePreview" class="mt-3 space-y-2 hidden"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-sticky-note text-green-500 mr-2"></i>
                                    Notas Iniciales
                                </label>
                                <textarea name="initial_notes"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                    rows="3"
                                    placeholder="Agregar notas o comentarios iniciales...">{{ old('initial_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Información del SLA -->
        <div id="slaInfo" class="mt-8 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl shadow-lg p-6 hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg">Información del SLA</h4>
                        <p class="text-blue-100 text-sm">Tiempos de respuesta y resolución</p>
                    </div>
                </div>
                <div class="bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
                    <i class="fas fa-shield-alt mr-2"></i>Garantía de Servicio
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                <div class="text-center bg-white/10 p-4 rounded-xl backdrop-blur-sm">
                    <div class="text-2xl font-bold" id="slaResponseTime">-</div>
                    <div class="text-blue-100 text-sm mt-1">Tiempo de Respuesta</div>
                </div>
                <div class="text-center bg-white/10 p-4 rounded-xl backdrop-blur-sm">
                    <div class="text-2xl font-bold" id="slaResolutionTime">-</div>
                    <div class="text-blue-100 text-sm mt-1">Tiempo de Resolución</div>
                </div>
                <div class="text-center bg-white/10 p-4 rounded-xl backdrop-blur-sm">
                    <div class="text-2xl font-bold" id="slaServiceLevel">-</div>
                    <div class="text-blue-100 text-sm mt-1">Nivel de Servicio</div>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="mt-12 flex justify-end space-x-4">
            <a href="{{ route('service-requests.index') }}"
                class="group bg-white text-gray-700 px-8 py-3 rounded-xl shadow-lg border-2 border-gray-200 hover:border-gray-300 hover:shadow-xl flex items-center font-semibold">
                <i class="fas fa-times mr-3"></i>
                Cancelar
            </a>
            <button type="submit" id="submitButton"
                class="group bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-8 py-3 rounded-xl shadow-lg hover:shadow-2xl flex items-center font-semibold hover:from-blue-700 hover:to-indigo-800">
                <i class="fas fa-plus-circle mr-3"></i>
                Crear Solicitud
            </button>
        </div>
    </form>
</div>

<!-- Modal para crear nuevo SLA -->
<div id="createSlaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo SLA</h3>

            <form id="createSlaForm">
                @csrf
                <input type="hidden" id="modal_sub_service_id" name="sub_service_id">

                <div class="grid grid-cols-1 gap-4 mb-4">
                    <!-- Nombre del SLA -->
                    <div>
                        <label for="sla_name" class="block text-sm font-medium text-gray-700">Nombre del SLA *</label>
                        <input type="text" id="sla_name" name="name" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ej: SLA Básico - Crítico">
                    </div>

                    <!-- Nivel de Criticidad -->
                    <div>
                        <label for="sla_criticality" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
                        <select id="sla_criticality" name="criticality_level" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione criticidad</option>
                            @foreach($criticalityLevels as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tiempos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="modal_acceptance_time" class="block text-sm font-medium text-gray-700">Tiempo de Aceptación (minutos) *</label>
                            <input type="number" id="modal_acceptance_time" name="acceptance_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="30">
                        </div>
                        <div>
                            <label for="modal_response_time" class="block text-sm font-medium text-gray-700">Tiempo de Respuesta (minutos) *</label>
                            <input type="number" id="modal_response_time" name="response_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="60">
                        </div>
                        <div>
                            <label for="modal_resolution_time" class="block text-sm font-medium text-gray-700">Tiempo de Resolución (minutos) *</label>
                            <input type="number" id="modal_resolution_time" name="resolution_time_minutes" required min="1"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="240">
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="sla_description" class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea id="sla_description" name="description" rows="3"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Descripción opcional del SLA"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" id="closeSlaModal"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Crear SLA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-10 mx-auto p-5 w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl" id="modalContent">
            <div class="p-8">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 mb-4">
                        <i class="fas fa-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Confirmar Creación</h3>
                    <p class="text-gray-600 mb-6">
                        ¿Estás seguro de que deseas crear esta solicitud de servicio?
                    </p>

                    <div id="requestPreview" class="bg-gray-50 rounded-xl p-4 mb-6 text-left border-2 border-gray-100">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Solicitante</div>
                                <div class="font-semibold text-gray-800" id="previewRequester">-</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <div class="text-xs text-gray-500">Título</div>
                                <div class="font-medium text-gray-800 truncate" id="previewTitle">-</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Descripción</div>
                                <div class="text-sm text-gray-600 line-clamp-2" id="previewDescription">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button id="cancelConfirm"
                        class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-xl hover:bg-gray-200 font-semibold flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>Cancelar
                    </button>
                    <button id="confirmSubmit"
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-xl hover:shadow-lg font-semibold flex items-center justify-center">
                        <i class="fas fa-check mr-2"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Script de creación de solicitudes cargado');

        // =========================================================================
        // ELEMENTOS DEL DOM - AGREGAR NUEVOS ELEMENTOS
        // =========================================================================
        const form = document.getElementById('serviceRequestForm');
        const requestedBySelect = document.getElementById('requested_by');
        const requesterInfo = document.getElementById('requesterInfo');
        const requesterName = document.getElementById('requesterName');
        const requesterEmail = document.getElementById('requesterEmail');
        const requesterDepartment = document.getElementById('requesterDepartment');
        const requesterAvatar = document.getElementById('requesterAvatar');
        const titleInput = document.getElementById('title');
        const descriptionInput = document.getElementById('description');
        const descriptionCount = document.getElementById('descriptionCount');
        const descriptionIndicator = document.getElementById('descriptionIndicator');
        const titleCount = document.getElementById('titleCount');
        const submitButton = document.getElementById('submitButton');
        const confirmationModal = document.getElementById('confirmationModal');
        const confirmSubmit = document.getElementById('confirmSubmit');
        const cancelConfirm = document.getElementById('cancelConfirm');
        const previewRequester = document.getElementById('previewRequester');
        const previewTitle = document.getElementById('previewTitle');
        const previewDescription = document.getElementById('previewDescription');

        // ELEMENTOS PARA SLAs
        const subServiceSelect = document.getElementById('sub_service_id');
        const slaSelect = document.getElementById('sla_id');
        const createSlaButton = document.getElementById('updateSlaSelect');
        const slaInfo = document.getElementById('slaInfo');
        const modalSubServiceId = document.getElementById('modal_sub_service_id');

        // ELEMENTOS PARA RUTAS WEB
        const addRouteBtn = document.getElementById('add-route-btn');
        const routesContainer = document.getElementById('web-routes-container');

        // NUEVOS ELEMENTOS PARA AUTO-ASIGNACIÓN
        const autoAssignCheckbox = document.getElementById('auto_assign');
        const assignedToField = document.getElementById('assignedToField');
        const assignedToSelect = document.getElementById('assigned_to');

        // =========================================================================
        // FUNCIONALIDAD DE AUTO-ASIGNACIÓN - NUEVA SECCIÓN
        // =========================================================================
        function initializeAutoAssignment() {
            console.log('🔄 Inicializando auto-asignación...');

            if (!autoAssignCheckbox || !assignedToField) {
                console.warn('⚠️ Elementos de auto-asignación no encontrados');
                return;
            }

            function toggleAssignedToField() {
                if (autoAssignCheckbox.checked) {
                    assignedToField.classList.add('hidden');
                    console.log('✅ Auto-asignación activada - ocultando campo asignado');
                } else {
                    assignedToField.classList.remove('hidden');
                    console.log('✅ Auto-asignación desactivada - mostrando campo asignado');
                }
            }

            // Event listener para el checkbox
            autoAssignCheckbox.addEventListener('change', toggleAssignedToField);

            // Inicializar estado
            toggleAssignedToField();
        }

        // =========================================================================
        // FUNCIÓN PARA PROCESAR AUTO-ASIGNACIÓN EN EL FORMULARIO - NUEVA
        // =========================================================================
        function processAutoAssignment() {
            console.log('🔧 Procesando auto-asignación...');

            // Eliminar campo hidden anterior si existe
            const existingAutoAssignField = document.getElementById('auto_assign_processed');
            if (existingAutoAssignField) {
                existingAutoAssignField.remove();
            }

            // Crear campo hidden para auto_assign
            const autoAssignField = document.createElement('input');
            autoAssignField.type = 'hidden';
            autoAssignField.name = 'auto_assign';
            autoAssignField.id = 'auto_assign_processed';
            autoAssignField.value = autoAssignCheckbox.checked ? '1' : '0';
            form.appendChild(autoAssignField);

            console.log('📤 Auto-asignación procesada:', autoAssignField.value);

            // Si auto_assign está activado, limpiar el campo assigned_to
            if (autoAssignCheckbox.checked && assignedToSelect) {
                console.log('🔄 Auto-asignación activada - limpiando campo assigned_to');
                assignedToSelect.value = '';
            }
        }

        // =========================================================================
        // FUNCIÓN PARA ACTUALIZAR INFORMACIÓN DEL SOLICITANTE - MEJORADA
        // =========================================================================
        function updateRequesterInfo() {
            const selectedOption = requestedBySelect.options[requestedBySelect.selectedIndex];
            console.log('🔄 Actualizando información del solicitante:', selectedOption.value);

            if (selectedOption.value) {
                const name = selectedOption.getAttribute('data-name');
                const email = selectedOption.getAttribute('data-email');
                const department = selectedOption.getAttribute('data-department');
                const avatar = selectedOption.getAttribute('data-avatar');

                console.log('📋 Datos del solicitante:', {
                    name,
                    email,
                    department,
                    avatar
                });

                requesterName.textContent = name || '-';
                requesterEmail.textContent = email || '-';
                requesterDepartment.textContent = department || '-';

                // Actualizar avatar
                if (avatar && avatar !== '') {
                    requesterAvatar.innerHTML = `<img src="${avatar}" class="w-10 h-10 rounded-full object-cover" alt="Avatar">`;
                } else {
                    const initials = name ? name.charAt(0).toUpperCase() : 'U';
                    requesterAvatar.innerHTML = initials;
                    requesterAvatar.className = 'w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold';
                }

                requesterInfo.classList.remove('hidden');
                console.log('✅ Información del solicitante actualizada');
            } else {
                requesterInfo.classList.add('hidden');
                console.log('❌ No hay solicitante seleccionado');
            }
        }

        // =========================================================================
        // INICIALIZAR SOLICITANTE POR DEFECTO - MEJORADA
        // =========================================================================
        function initializeRequester() {
            console.log('🔄 Inicializando solicitante...');

            // Si no hay valor seleccionado y hay opciones, seleccionar la primera no vacía
            if (!requestedBySelect.value && requestedBySelect.options.length > 1) {
                // Buscar la opción que coincida con el usuario actual
                const currentUserId = "{{ auth()->id() }}";
                let found = false;

                for (let i = 0; i < requestedBySelect.options.length; i++) {
                    if (requestedBySelect.options[i].value == currentUserId) {
                        requestedBySelect.selectedIndex = i;
                        found = true;
                        console.log('✅ Solicitante inicializado con usuario actual:', currentUserId);
                        break;
                    }
                }

                // Si no se encontró el usuario actual, seleccionar la primera opción no vacía
                if (!found && requestedBySelect.options.length > 1) {
                    requestedBySelect.selectedIndex = 1; // Saltar la opción vacía
                    console.log('✅ Solicitante inicializado con primera opción disponible');
                }
            }

            // Actualizar la información visual
            updateRequesterInfo();
        }

        // =========================================================================
        // FUNCIÓN PARA PROCESAR INFORMACIÓN DEL SLA
        // =========================================================================
        function processSlaData() {
            console.log('🔧 Procesando datos del SLA...');

            const selectedSlaOption = slaSelect.options[slaSelect.selectedIndex];

            // Eliminar campos hidden anteriores si existen
            const existingCriticalityField = document.getElementById('criticality_level_processed');
            if (existingCriticalityField) {
                existingCriticalityField.remove();
            }

            const existingSlaNameField = document.getElementById('sla_name_processed');
            if (existingSlaNameField) {
                existingSlaNameField.remove();
            }

            // Si hay un SLA seleccionado, agregar campos hidden con la información
            if (selectedSlaOption.value && selectedSlaOption.dataset.serviceLevel) {
                // Campo para criticality_level
                const criticalityField = document.createElement('input');
                criticalityField.type = 'hidden';
                criticalityField.name = 'criticality_level';
                criticalityField.id = 'criticality_level_processed';
                criticalityField.value = selectedSlaOption.dataset.serviceLevel;
                form.appendChild(criticalityField);
                console.log('📤 Criticality level:', criticalityField.value);

                // Campo opcional para el nombre del SLA
                const slaNameField = document.createElement('input');
                slaNameField.type = 'hidden';
                slaNameField.name = 'sla_name';
                slaNameField.id = 'sla_name_processed';
                slaNameField.value = selectedSlaOption.dataset.fullName || selectedSlaOption.text;
                form.appendChild(slaNameField);
                console.log('📤 SLA name:', slaNameField.value);
            } else {
                console.warn('⚠️ No hay SLA seleccionado o no tiene criticality level');
            }
        }

        // =========================================================================
        // FUNCIÓN PARA PROCESAR RUTAS WEB
        // =========================================================================
        function processWebRoutes() {
            console.log('🔧 Procesando rutas web...');

            // Obtener todos los inputs de rutas
            const routeInputs = document.querySelectorAll('input[name="web_routes[]"]');
            console.log('📝 Inputs de rutas encontrados:', routeInputs.length);

            // Filtrar rutas válidas (no vacías)
            const validRoutes = Array.from(routeInputs)
                .map(input => input.value.trim())
                .filter(route => route !== '');

            console.log('✅ Rutas válidas:', validRoutes);

            // Eliminar campo hidden anterior si existe
            const existingHiddenField = document.getElementById('web_routes_processed');
            if (existingHiddenField) {
                existingHiddenField.remove();
                console.log('🗑️ Campo hidden anterior eliminado');
            }

            // Si hay rutas válidas, crear campo hidden
            if (validRoutes.length > 0) {
                const hiddenRoutesField = document.createElement('input');
                hiddenRoutesField.type = 'hidden';
                hiddenRoutesField.name = 'web_routes';
                hiddenRoutesField.id = 'web_routes_processed';
                hiddenRoutesField.value = validRoutes.join(', ');
                form.appendChild(hiddenRoutesField);
                console.log('📤 Rutas procesadas como string:', hiddenRoutesField.value);
            } else {
                console.log('ℹ️ No hay rutas web para procesar');
            }
        }

        // =========================================================================
        // FUNCIÓN PARA PROCESAR TODOS LOS DATOS - ACTUALIZADA
        // =========================================================================
        function processAllFormData() {
            console.log('🔧 Procesando todos los datos del formulario...');
            processSlaData();
            processWebRoutes();
            processAutoAssignment(); // ← NUEVO: Procesar auto-asignación
        }

        // =========================================================================
        // FUNCIÓN PARA MOSTRAR ALERTAS DE ERROR
        // =========================================================================
        function showErrorAlert(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            alertDiv.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="font-medium">${message}</span>
                </div>
            `;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.remove();
            }, 4000);
        }

        // =========================================================================
        // FUNCIONALIDAD DE RUTAS WEB
        // =========================================================================
        function initializeWebRoutes() {
            if (!addRouteBtn || !routesContainer) {
                console.error('❌ No se encontraron elementos de rutas web');
                return;
            }

            let routeCount = 0;

            function addRouteField(routeValue = '') {
                routeCount++;
                const routeField = document.createElement('div');
                routeField.className = 'route-field flex items-center space-x-2 bg-gray-50 p-3 rounded-lg';
                routeField.innerHTML = `
                    <input type="url"
                           name="web_routes[]"
                           value="${routeValue}"
                           placeholder="https://ejemplo.com/ruta"
                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           pattern="https?://.+"
                           title="Ingrese una URL válida (http:// o https://)">
                    <button type="button"
                            class="remove-route bg-red-500 text-white p-2 rounded-md hover:bg-red-600"
                            title="Eliminar ruta">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                routesContainer.appendChild(routeField);

                const removeBtn = routeField.querySelector('.remove-route');
                removeBtn.addEventListener('click', function() {
                    routeField.remove();
                    updateEmptyState();
                });

                updateEmptyState();
            }

            function updateEmptyState() {
                const existingRoutes = routesContainer.querySelectorAll('.route-field');
                const emptyMessage = routesContainer.querySelector('.empty-routes-message');

                if (existingRoutes.length === 0) {
                    if (!emptyMessage) {
                        const message = document.createElement('p');
                        message.className = 'empty-routes-message text-sm text-gray-500 text-center py-4';
                        message.textContent = 'No hay rutas web agregadas. Haga clic en "Agregar Ruta" para añadir una.';
                        routesContainer.appendChild(message);
                    }
                } else {
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                }
            }

            addRouteBtn.addEventListener('click', function() {
                addRouteField();
            });

            const oldRoutes = @json(old('web_routes', []));
            if (oldRoutes.length > 0) {
                oldRoutes.forEach(route => {
                    if (route) addRouteField(route);
                });
            } else {
                updateEmptyState();
            }

            console.log('✅ Funcionalidad de rutas web inicializada');
        }

        // =========================================================================
        // CONTADORES DE CARACTERES
        // =========================================================================
        function updateCounters() {
            const titleLength = titleInput.value.length;
            const descriptionLength = descriptionInput.value.length;

            titleCount.textContent = `${titleLength}/255`;

            // Actualizar indicador de descripción
            if (descriptionLength < 10) {
                descriptionIndicator.className = 'w-3 h-3 rounded-full bg-red-500';
                descriptionCount.className = 'text-xs font-medium text-red-500';
            } else if (descriptionLength < 50) {
                descriptionIndicator.className = 'w-3 h-3 rounded-full bg-yellow-500';
                descriptionCount.className = 'text-xs font-medium text-yellow-500';
            } else {
                descriptionIndicator.className = 'w-3 h-3 rounded-full bg-green-500';
                descriptionCount.className = 'text-xs font-medium text-green-500';
            }

            descriptionCount.textContent = `${descriptionLength} caracteres`;
        }

        // =========================================================================
        // FUNCIONALIDAD DE SLAs
        // =========================================================================
        function loadSlasBySubService(subServiceId) {
            console.log('📡 Cargando SLAs para sub-service:', subServiceId);

            if (!subServiceId) {
                resetSlaSelect();
                return;
            }

            // Mostrar loading
            slaSelect.innerHTML = '<option value="">Cargando SLAs...</option>';
            slaSelect.disabled = true;
            if (createSlaButton) createSlaButton.disabled = true;

            // Hacer petición AJAX para obtener los SLAs
            fetch(`/api/sub-services/${subServiceId}/slas`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => {
                    console.log('📨 Respuesta recibida, status:', response.status);

                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(slas => {
                    console.log('📨 SLAs recibidos:', slas);
                    updateSlaSelect(slas);
                    updateCreateSlaButton(subServiceId);
                })
                .catch(error => {
                    console.error('❌ Error cargando SLAs:', error);
                    slaSelect.innerHTML = '<option value="">Error al cargar SLAs</option>';
                    if (createSlaButton) createSlaButton.classList.add('hidden');
                })
                .finally(() => {
                    slaSelect.disabled = false;
                    if (createSlaButton) createSlaButton.disabled = false;
                });
        }

        function updateSlaSelect(slas) {
            slaSelect.innerHTML = '';

            const hasValidSlas = Array.isArray(slas) && slas.length > 0;

            if (!hasValidSlas) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No hay SLAs disponibles';
                slaSelect.appendChild(option);

                if (createSlaButton) {
                    createSlaButton.classList.remove('hidden');
                }
            } else {
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Seleccione un SLA';
                slaSelect.appendChild(defaultOption);

                slas.forEach(sla => {
                    const option = document.createElement('option');
                    option.value = sla.id;

                    const responseTime = formatTime(sla.response_time_minutes);
                    const resolutionTime = formatTime(sla.resolution_time_minutes);

                    option.textContent = `${sla.name} (${sla.criticality_level})`;
                    option.title = `Respuesta: ${responseTime} | Resolución: ${resolutionTime}`;

                    option.dataset.responseTime = sla.response_time_minutes;
                    option.dataset.resolutionTime = sla.resolution_time_minutes;
                    option.dataset.serviceLevel = sla.criticality_level;
                    option.dataset.fullName = sla.name;

                    slaSelect.appendChild(option);
                });

                if (createSlaButton) {
                    createSlaButton.classList.add('hidden');
                }
            }

            hideSlaInfo();
        }

        function resetSlaSelect() {
            slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
            if (createSlaButton) {
                createSlaButton.classList.add('hidden');
            }
            hideSlaInfo();
        }

        function updateCreateSlaButton(subServiceId) {
            if (modalSubServiceId) {
                modalSubServiceId.value = subServiceId;
            }
        }

        function showSlaInfo(responseTime, resolutionTime, serviceLevel) {
            document.getElementById('slaResponseTime').textContent = formatTime(responseTime);
            document.getElementById('slaResolutionTime').textContent = formatTime(resolutionTime);
            document.getElementById('slaServiceLevel').textContent = serviceLevel;
            slaInfo.classList.remove('hidden');
        }

        function hideSlaInfo() {
            slaInfo.classList.add('hidden');
        }

        function formatTime(minutes) {
            if (!minutes || minutes === 0 || minutes === '0') return '-';

            minutes = parseInt(minutes);

            if (minutes < 60) {
                return `${minutes} min`;
            } else if (minutes < 1440) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
            } else {
                const days = Math.floor(minutes / 1440);
                const hours = Math.floor((minutes % 1440) / 60);
                return hours > 0 ? `${days}d ${hours}h` : `${days}d`;
            }
        }

        // =========================================================================
        // MANEJO DE MODALES
        // =========================================================================
        function showModal() {
            confirmationModal.classList.remove('hidden');
        }

        function hideModal() {
            confirmationModal.classList.add('hidden');
        }

        // =========================================================================
        // EVENT LISTENERS
        // =========================================================================
        titleInput.addEventListener('input', updateCounters);
        descriptionInput.addEventListener('input', updateCounters);
        requestedBySelect.addEventListener('change', updateRequesterInfo);

        if (subServiceSelect) {
            subServiceSelect.addEventListener('change', function() {
                const subServiceId = this.value;
                loadSlasBySubService(subServiceId);
            });
        }

        if (slaSelect) {
            slaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];

                if (selectedOption.value && selectedOption.dataset.responseTime) {
                    showSlaInfo(
                        selectedOption.dataset.responseTime,
                        selectedOption.dataset.resolutionTime,
                        selectedOption.dataset.serviceLevel
                    );
                } else {
                    hideSlaInfo();
                }
            });
        }

        if (createSlaButton) {
            createSlaButton.addEventListener('click', function() {
                document.getElementById('createSlaModal').classList.remove('hidden');
            });
        }

        if (document.getElementById('closeSlaModal')) {
            document.getElementById('closeSlaModal').addEventListener('click', function() {
                document.getElementById('createSlaModal').classList.add('hidden');
            });
        }

        // =========================================================================
        // MANEJO DEL FORMULARIO PRINCIPAL - ACTUALIZADO CON AUTO-ASIGNACIÓN
        // =========================================================================
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('📝 Iniciando validación del formulario...');

            // VALIDACIÓN 1: Título
            const titleValue = titleInput.value.trim();
            if (!titleValue) {
                console.error('❌ Error: Título vacío');
                showErrorAlert('Por favor, ingrese un título para la solicitud');
                titleInput.focus();
                titleInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                return;
            } else {
                titleInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }

            // VALIDACIÓN 2: Descripción
            const descriptionValue = descriptionInput.value.trim();
            if (!descriptionValue) {
                console.error('❌ Error: Descripción vacía');
                showErrorAlert('Por favor, ingrese una descripción para la solicitud');
                descriptionInput.focus();
                descriptionInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                return;
            } else {
                descriptionInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }

            // VALIDACIÓN 3: Sub-servicio
            if (!subServiceSelect.value) {
                console.error('❌ Error: Sub-servicio no seleccionado');
                showErrorAlert('Por favor, seleccione un sub-servicio');
                subServiceSelect.focus();
                subServiceSelect.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                return;
            } else {
                subServiceSelect.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }

            // VALIDACIÓN 4: SLA
            if (!slaSelect.value) {
                console.error('❌ Error: SLA no seleccionado');
                showErrorAlert('Por favor, seleccione un SLA');
                slaSelect.focus();
                slaSelect.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                return;
            } else {
                // Verificar que el SLA seleccionado tenga criticality level
                const selectedOption = slaSelect.options[slaSelect.selectedIndex];
                if (!selectedOption.dataset.serviceLevel) {
                    console.error('❌ Error: SLA no tiene criticality level');
                    showErrorAlert('El SLA seleccionado no tiene nivel de criticidad. Por favor, seleccione otro SLA.');
                    slaSelect.focus();
                    slaSelect.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    return;
                }
                slaSelect.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }

            // VALIDACIÓN 5: Solicitante
            const selectedRequesterOption = requestedBySelect.options[requestedBySelect.selectedIndex];
            if (!selectedRequesterOption.value) {
                console.error('❌ Error: Solicitante no seleccionado');
                showErrorAlert('Por favor, seleccione un solicitante');
                requestedBySelect.focus();
                requestedBySelect.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                return;
            } else {
                requestedBySelect.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                console.log('✅ Solicitante seleccionado:', selectedRequesterOption.value, selectedRequesterOption.text);
            }

            // VALIDACIÓN 6: Asignado (solo si auto-asignación está desactivada)
            if (!autoAssignCheckbox.checked && assignedToSelect) {
                if (!assignedToSelect.value) {
                    console.error('❌ Error: Asignado no seleccionado (auto-asignación desactivada)');
                    showErrorAlert('Por favor, seleccione una persona asignada o active la auto-asignación');
                    assignedToSelect.focus();
                    assignedToSelect.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    return;
                } else {
                    assignedToSelect.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                    console.log('✅ Asignado seleccionado:', assignedToSelect.value);
                }
            }

            console.log('✅ Todas las validaciones pasadas');

            // Actualizar preview
            const selectedRequester = requestedBySelect.options[requestedBySelect.selectedIndex];
            const requesterName = selectedRequester.getAttribute('data-name') || selectedRequester.text.split(' - ')[0] || 'No seleccionado';
            previewRequester.textContent = requesterName;
            previewTitle.textContent = titleInput.value || 'Sin título';
            previewDescription.textContent = descriptionInput.value ?
                (descriptionInput.value.substring(0, 100) + (descriptionInput.value.length > 100 ? '...' : '')) :
                'Sin descripción';

            showModal();
        });

        // =========================================================================
        // CONFIRMACIÓN DE ENVÍO
        // =========================================================================
        confirmSubmit.addEventListener('click', function() {
            console.log('🔄 Procesando formulario antes de enviar...');

            // Procesar todos los datos antes de enviar
            processAllFormData();

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Creando Solicitud...';

            hideModal();
            setTimeout(() => {
                console.log('🚀 Enviando formulario...');

                // DEBUG: Mostrar qué datos se están enviando
                const formData = new FormData(form);
                console.log('📦 Datos que se enviarán:');
                for (let [key, value] of formData.entries()) {
                    console.log(`- ${key}: ${value}`);
                }

                form.submit();
            }, 500);
        });

        cancelConfirm.addEventListener('click', hideModal);

        // =========================================================================
        // INICIALIZACIÓN - ACTUALIZADA
        // =========================================================================
        initializeWebRoutes();
        updateCounters();
        initializeRequester();
        initializeAutoAssignment(); // ← NUEVO: Inicializar auto-asignación

        // Cargar SLAs si ya hay un sub-servicio seleccionado
        if (subServiceSelect && subServiceSelect.value) {
            setTimeout(() => {
                loadSlasBySubService(subServiceSelect.value);
            }, 100);
        }

        // Prevenir envío múltiple
        let formSubmitted = false;
        form.addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });

        console.log('✅ Inicialización completada');
    });
</script>

<style>
    .hidden {
        display: none !important;
    }

    #sub_service_id,
    #sla_id,
    #requested_by {
        background-color: white;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
        min-height: 42px;
        box-sizing: border-box;
    }

    #sub_service_id:focus,
    #sla_id:focus,
    #requested_by:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    #sub_service_id option,
    #sla_id option,
    #requested_by option {
        padding: 8px 12px;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    #updateSlaSelect {
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
    }

    #sub_service_id:disabled,
    #sla_id:disabled,
    #requested_by:disabled {
        background-color: #f9fafb;
        cursor: not-allowed;
        opacity: 0.7;
    }

    label[for="sub_service_id"],
    label[for="sla_id"],
    label[for="requested_by"] {
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .route-field {
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

    .remove-route:hover {
        transform: scale(1.1);
    }

    .empty-routes-message {
        border: 2px dashed #e5e7eb;
        border-radius: 0.75rem;
    }

    .border-red-500 {
        border-color: #ef4444 !important;
    }

    .ring-2 {
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    }

    input.border-red-500,
    select.border-red-500,
    textarea.border-red-500 {
        border-width: 2px !important;
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .field-error {
        color: #ef4444;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 768px) {
        .route-field {
            flex-direction: column;
            gap: 0.5rem;
        }

        .route-field input {
            width: 100%;
        }

        .remove-route {
            align-self: flex-end;
        }
    }
</style>
@endsection
