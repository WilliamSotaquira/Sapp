@extends('layouts.app')

@section('title', 'Nueva Tarea Predefinida')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Nueva Tarea Predefinida</h2>
        <p class="text-gray-600 text-sm mt-1">Crea una plantilla de tarea para un subservicio</p>
    </div>

    <form action="{{ route('standard-tasks.store') }}" method="POST">
        @csrf

        <div class="bg-white shadow-md rounded-lg p-6 space-y-6">
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
                            <option value="">Seleccione...</option>
                            @foreach($subServices as $subService)
                                <option value="{{ $subService->id }}" {{ old('sub_service_id') == $subService->id ? 'selected' : '' }}>
                                    {{ $subService->name }} ({{ $subService->service->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('sub_service_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título <span class="text-red-600">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">{{ old('description') }}</textarea>
                        @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-600">*</span></label>
                        <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="regular" {{ old('type') == 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="impact" {{ old('type') == 'impact' ? 'selected' : '' }}>Impacto</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad <span class="text-red-600">*</span></label>
                        <select name="priority" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horas Estimadas <span class="text-red-600">*</span></label>
                        <input type="number" name="estimated_hours" value="{{ old('estimated_hours', '1.0') }}" step="0.25" min="0.1" max="99.99" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        @error('estimated_hours')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Complejidad Técnica (1-5)</label>
                        <input type="number" name="technical_complexity" value="{{ old('technical_complexity') }}" min="1" max="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Orden</label>
                        <input type="number" name="order" value="{{ old('order', '0') }}" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
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
                        <input type="text" name="technologies" value="{{ old('technologies') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Laravel, PHP, MySQL">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ambiente</label>
                        <select name="environment" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">Ninguno</option>
                            <option value="development" {{ old('environment') == 'development' ? 'selected' : '' }}>Development</option>
                            <option value="staging" {{ old('environment') == 'staging' ? 'selected' : '' }}>Staging</option>
                            <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Accesos Requeridos</label>
                        <input type="text" name="required_accesses" value="{{ old('required_accesses') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="VPN, SSH, Base de datos">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Técnicas</label>
                        <textarea name="technical_notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">{{ old('technical_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Subtareas -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-tasks text-green-600"></i>
                        Subtareas
                    </h3>
                    <button type="button" onclick="addSubtask()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i>Agregar Subtarea
                    </button>
                </div>
                <div id="subtasks-container" class="space-y-3"></div>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex gap-3 justify-end">
            <a href="{{ route('standard-tasks.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg transition-colors duration-200">
                Cancelar
            </a>
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center gap-2">
                <i class="fas fa-save"></i>Guardar Tarea
            </button>
        </div>
    </form>
</div>

<script>
let subtaskIndex = 0;

function addSubtask() {
    const container = document.getElementById('subtasks-container');
    const div = document.createElement('div');
    div.className = 'border border-gray-300 rounded-lg p-4 bg-gray-50';
    div.innerHTML = `
        <div class="flex gap-3 items-start">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <input type="text" name="subtasks[${subtaskIndex}][title]" placeholder="Título de la subtarea" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                </div>
                <div>
                    <select name="subtasks[${subtaskIndex}][priority]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                        <option value="medium">Media</option>
                        <option value="low">Baja</option>
                        <option value="high">Alta</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <input type="text" name="subtasks[${subtaskIndex}][description]" placeholder="Descripción (opcional)" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm">
                </div>
                <input type="hidden" name="subtasks[${subtaskIndex}][order]" value="${subtaskIndex}">
            </div>
            <button type="button" onclick="this.closest('div.border').remove()" class="text-red-600 hover:text-red-800 text-lg mt-2" title="Eliminar subtarea">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    subtaskIndex++;
}

// Agregar una subtarea por defecto
document.addEventListener('DOMContentLoaded', function() {
    addSubtask();
});
</script>
@endsection
