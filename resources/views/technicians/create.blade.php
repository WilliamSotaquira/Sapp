@extends('layouts.app')

@section('title', 'Crear Nuevo Técnico')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-user-plus mr-3"></i>
                Datos del técnico
            </h2>
        </div>

        <form action="{{ route('technicians.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Usuario Asociado -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-red-600"></i>
                    Usuario Asociado
                </h3>

                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Seleccionar Usuario <span class="text-red-500">*</span>
                    </label>
                    <select name="user_id"
                            id="user_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('user_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleccione un usuario...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Seleccione el usuario del sistema que será técnico</p>
                </div>
            </div>

            <!-- Información Profesional -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-red-600"></i>
                    Información Profesional
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Especialización -->
                    <div>
                        <label for="specialization" class="block text-sm font-medium text-gray-700 mb-2">
                            Especialización <span class="text-red-500">*</span>
                        </label>
                        <select name="specialization"
                                id="specialization"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('specialization') border-red-500 @enderror"
                                required>
                            <option value="">Seleccione...</option>
                            <option value="frontend" {{ old('specialization') == 'frontend' ? 'selected' : '' }}>Frontend</option>
                            <option value="backend" {{ old('specialization') == 'backend' ? 'selected' : '' }}>Backend</option>
                            <option value="fullstack" {{ old('specialization') == 'fullstack' ? 'selected' : '' }}>Full Stack</option>
                            <option value="devops" {{ old('specialization') == 'devops' ? 'selected' : '' }}>DevOps</option>
                            <option value="support" {{ old('specialization') == 'support' ? 'selected' : '' }}>Soporte Técnico</option>
                            <option value="qa" {{ old('specialization') == 'qa' ? 'selected' : '' }}>QA/Testing</option>
                        </select>
                        @error('specialization')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Años de Experiencia -->
                    <div>
                        <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-2">
                            Años de Experiencia <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="years_experience"
                               id="years_experience"
                               value="{{ old('years_experience', 0) }}"
                               min="0"
                               max="50"
                               step="0.5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('years_experience') border-red-500 @enderror"
                               required>
                        @error('years_experience')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nivel de Habilidad -->
                    <div>
                        <label for="skill_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Habilidad <span class="text-red-500">*</span>
                        </label>
                        <select name="skill_level"
                                id="skill_level"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('skill_level') border-red-500 @enderror"
                                required>
                            <option value="">Seleccione...</option>
                            <option value="junior" {{ old('skill_level') == 'junior' ? 'selected' : '' }}>Junior</option>
                            <option value="mid" {{ old('skill_level') == 'mid' ? 'selected' : '' }}>Mid-Level</option>
                            <option value="senior" {{ old('skill_level') == 'senior' ? 'selected' : '' }}>Senior</option>
                            <option value="lead" {{ old('skill_level') == 'lead' ? 'selected' : '' }}>Lead</option>
                        </select>
                        @error('skill_level')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Capacidad Diaria -->
                    <div>
                        <label for="max_daily_capacity_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Capacidad Diaria (horas) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="max_daily_capacity_hours"
                               id="max_daily_capacity_hours"
                               value="{{ old('max_daily_capacity_hours', 8) }}"
                               min="1"
                               max="12"
                               step="0.5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('max_daily_capacity_hours') border-red-500 @enderror"
                               required>
                        @error('max_daily_capacity_hours')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Horas máximas de trabajo productivo por día</p>
                    </div>
                </div>
            </div>

            <!-- Habilidades Técnicas -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-code mr-2 text-red-600"></i>
                    Habilidades Técnicas
                </h3>

                <div id="skills-container" class="space-y-3">
                    <div class="skill-item grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Habilidad</label>
                            <input type="text"
                                   name="skills[0][skill_name]"
                                   placeholder="Ej: Laravel, React, Python..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                            <select name="skills[0][proficiency_level]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                                <option value="">Seleccione...</option>
                                <option value="beginner">Principiante</option>
                                <option value="intermediate">Intermedio</option>
                                <option value="advanced">Avanzado</option>
                                <option value="expert">Experto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Años Exp.</label>
                            <input type="number"
                                   name="skills[0][years_experience_skill]"
                                   min="0"
                                   max="50"
                                   step="0.5"
                                   placeholder="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                        </div>
                    </div>
                </div>

                <button type="button"
                        id="add-skill-btn"
                        class="mt-3 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors text-sm flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Agregar Habilidad
                </button>
                <p class="mt-2 text-xs text-gray-500">Agrega las tecnologías y herramientas que domina el técnico</p>
            </div>

            <!-- Tipo de Usuario -->
            @if(auth()->user()->isAdmin())
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user-shield mr-2 text-red-600"></i>
                    Tipo de Usuario
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Rol del Usuario -->
                    <div>
                        <label for="user_role" class="block text-sm font-medium text-gray-700 mb-2">
                            Rol de Usuario <span class="text-red-500">*</span>
                        </label>
                        <select name="user_role"
                                id="user_role"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('user_role') border-red-500 @enderror"
                                required>
                            <option value="user" {{ old('user_role', 'user') == 'user' ? 'selected' : '' }}>
                                Usuario Regular
                            </option>
                            <option value="technician" {{ old('user_role', 'technician') == 'technician' ? 'selected' : '' }}>
                                Técnico
                            </option>
                            <option value="admin" {{ old('user_role') == 'admin' ? 'selected' : '' }}>
                                Administrador
                            </option>
                        </select>
                        @error('user_role')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            <strong>Usuario:</strong> Sin permisos especiales | <strong>Técnico:</strong> Gestiona su propia agenda | <strong>Admin:</strong> Gestiona todo el sistema
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Estado y Disponibilidad -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-toggle-on mr-2 text-red-600"></i>
                    Estado y Disponibilidad
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Estado -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="status"
                                id="status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('status') border-red-500 @enderror"
                                required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Disponibilidad -->
                    <div>
                        <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-2">
                            Disponibilidad <span class="text-red-500">*</span>
                        </label>
                        <select name="availability_status"
                                id="availability_status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('availability_status') border-red-500 @enderror"
                                required>
                            <option value="available" {{ old('availability_status', 'available') == 'available' ? 'selected' : '' }}>Disponible</option>
                            <option value="busy" {{ old('availability_status') == 'busy' ? 'selected' : '' }}>Ocupado</option>
                            <option value="on_leave" {{ old('availability_status') == 'on_leave' ? 'selected' : '' }}>De Permiso</option>
                            <option value="unavailable" {{ old('availability_status') == 'unavailable' ? 'selected' : '' }}>No Disponible</option>
                        </select>
                        @error('availability_status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('technicians.index') }}"
                   class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Crear Técnico
                </button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
    let skillIndex = 1;

    document.getElementById('add-skill-btn').addEventListener('click', function() {
        const container = document.getElementById('skills-container');
        const newSkill = document.createElement('div');
        newSkill.className = 'skill-item grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 rounded-lg relative';
        newSkill.innerHTML = `
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Habilidad</label>
                <input type="text"
                       name="skills[${skillIndex}][skill_name]"
                       placeholder="Ej: Laravel, React, Python..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                <select name="skills[${skillIndex}][proficiency_level]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                    <option value="">Seleccione...</option>
                    <option value="beginner">Principiante</option>
                    <option value="intermediate">Intermedio</option>
                    <option value="advanced">Avanzado</option>
                    <option value="expert">Experto</option>
                </select>
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Años</label>
                    <input type="number"
                           name="skills[${skillIndex}][years_experience_skill]"
                           min="0"
                           max="50"
                           step="0.5"
                           placeholder="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                </div>
                <div class="flex items-end">
                    <button type="button"
                            class="remove-skill-btn px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors text-sm"
                            title="Eliminar habilidad">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(newSkill);
        skillIndex++;

        // Agregar evento de eliminar
        newSkill.querySelector('.remove-skill-btn').addEventListener('click', function() {
            newSkill.remove();
        });
    });

    // Agregar evento de eliminar a botones existentes
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-skill-btn')) {
            e.target.closest('.skill-item').remove();
        }
    });
</script>
@endsection
@endsection
