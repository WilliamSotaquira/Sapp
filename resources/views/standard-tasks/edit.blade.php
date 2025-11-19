@extends('layouts.app')

@section('title', 'Editar Tarea Predefinida')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header con navegación -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <a href="{{ route('standard-tasks.show', $standardTask) }}" class="text-blue-600 hover:text-blue-800 mb-3 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Editar: {{ $standardTask->title }}</h2>
        <p class="text-gray-600 text-sm mt-1">Modifica la información de la tarea predefinida</p>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('standard-tasks.update', $standardTask) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Información Básica -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-600"></i>
                    Información Básica
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subservicio <span class="text-red-600">*</span></label>
                        <select name="sub_service_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            @foreach($subServices as $sub)
                                <option value="{{ $sub->id }}" {{ $standardTask->sub_service_id == $sub->id ? 'selected' : '' }}>
                                    {{ $sub->name }} ({{ $sub->service->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título <span class="text-red-600">*</span></label>
                        <input type="text" name="title" value="{{ $standardTask->title }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">{{ $standardTask->description }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-600">*</span></label>
                        <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="regular" {{ $standardTask->type == 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="impact" {{ $standardTask->type == 'impact' ? 'selected' : '' }}>Impacto</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad <span class="text-red-600">*</span></label>
                        <select name="priority" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="low" {{ $standardTask->priority == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="medium" {{ $standardTask->priority == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="high" {{ $standardTask->priority == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="critical" {{ $standardTask->priority == 'critical' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horas Estimadas <span class="text-red-600">*</span></label>
                        <input type="number" name="estimated_hours" value="{{ $standardTask->estimated_hours }}" step="0.25" min="0.1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Complejidad Técnica (1-5)</label>
                        <input type="number" name="technical_complexity" value="{{ $standardTask->technical_complexity }}" min="1" max="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Orden</label>
                        <input type="number" name="order" value="{{ $standardTask->order }}" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $standardTask->is_active ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span class="ml-2 text-sm font-medium text-gray-700">Tarea activa</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Detalles Técnicos -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-cog text-purple-600"></i>
                    Detalles Técnicos (Opcional)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tecnologías</label>
                        <input type="text" name="technologies" value="{{ $standardTask->technologies }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Laravel, PHP, MySQL">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ambiente</label>
                        <select name="environment" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">Ninguno</option>
                            <option value="development" {{ $standardTask->environment == 'development' ? 'selected' : '' }}>Development</option>
                            <option value="staging" {{ $standardTask->environment == 'staging' ? 'selected' : '' }}>Staging</option>
                            <option value="production" {{ $standardTask->environment == 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Accesos Requeridos</label>
                        <input type="text" name="required_accesses" value="{{ $standardTask->required_accesses }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="VPN, SSH, Base de datos">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Técnicas</label>
                        <textarea name="technical_notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">{{ $standardTask->technical_notes }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex flex-wrap gap-3 pt-6 border-t">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center gap-2">
                    <i class="fas fa-save"></i>Guardar Cambios
                </button>
                <a href="{{ route('standard-tasks.show', $standardTask) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg transition-colors duration-200">
                    Cancelar
                </a>
            </div>
        </form>

        <!-- Nota sobre Subtareas -->
        <div class="mt-8 pt-6 border-t">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-md font-semibold text-blue-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i>
                    Gestión de Subtareas
                </h3>
                <p class="text-sm text-blue-800">
                    Las subtareas se gestionan desde la vista de detalle.
                    <a href="{{ route('standard-tasks.show', $standardTask) }}" class="text-blue-600 hover:text-blue-800 font-medium underline">
                        Ver detalles de la tarea
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
