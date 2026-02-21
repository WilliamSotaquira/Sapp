@extends('layouts.app')

@section('title', 'Editar Técnico')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-xs"></i></li>
        <li>
            <a href="{{ route('technicians.index') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-users-cog"></i> Técnicos
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-xs"></i></li>
        <li>
            <a href="{{ route('technicians.show', $technician) }}" class="hover:text-blue-600 transition-colors">
                {{ $technician->user->name }}
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-edit"></i> Editar
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">

        <form action="{{ route('technicians.update', $technician) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Usuario Asociado -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-yellow-600"></i>
                    Usuario Asociado
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name', $technician->user->name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Correo Electronico <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email', $technician->user->email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('email') border-red-500 @enderror"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Entidades Asociadas -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-building mr-2 text-yellow-600"></i>
                    Entidades Asociadas
                </h3>

                <p class="text-xs text-gray-500 mb-3">Seleccione una o varias entidades para este tecnico.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($companies as $company)
                        <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <input type="checkbox"
                                   name="company_ids[]"
                                   value="{{ $company->id }}"
                                   class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                   {{ in_array((string) $company->id, array_map('strval', $selectedCompanyIds ?? []), true) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">{{ $company->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('company_ids')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
                @error('company_ids.*')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Información Profesional -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('specialization') border-red-500 @enderror"
                                required>
                            <option value="frontend" {{ old('specialization', $technician->specialization) == 'frontend' ? 'selected' : '' }}>Frontend</option>
                            <option value="backend" {{ old('specialization', $technician->specialization) == 'backend' ? 'selected' : '' }}>Backend</option>
                            <option value="fullstack" {{ old('specialization', $technician->specialization) == 'fullstack' ? 'selected' : '' }}>Full Stack</option>
                            <option value="devops" {{ old('specialization', $technician->specialization) == 'devops' ? 'selected' : '' }}>DevOps</option>
                            <option value="support" {{ old('specialization', $technician->specialization) == 'support' ? 'selected' : '' }}>Soporte Técnico</option>
                            <option value="qa" {{ old('specialization', $technician->specialization) == 'qa' ? 'selected' : '' }}>QA/Testing</option>
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
                               value="{{ old('years_experience', $technician->years_experience) }}"
                               min="0"
                               max="50"
                               step="0.5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('years_experience') border-red-500 @enderror"
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('skill_level') border-red-500 @enderror"
                                required>
                            <option value="junior" {{ old('skill_level', $technician->skill_level) == 'junior' ? 'selected' : '' }}>Junior</option>
                            <option value="mid" {{ old('skill_level', $technician->skill_level) == 'mid' ? 'selected' : '' }}>Mid-Level</option>
                            <option value="senior" {{ old('skill_level', $technician->skill_level) == 'senior' ? 'selected' : '' }}>Senior</option>
                            <option value="lead" {{ old('skill_level', $technician->skill_level) == 'lead' ? 'selected' : '' }}>Lead</option>
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
                               value="{{ old('max_daily_capacity_hours', $technician->max_daily_capacity_hours) }}"
                               min="1"
                               max="12"
                               step="0.5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('max_daily_capacity_hours') border-red-500 @enderror"
                               required>
                        @error('max_daily_capacity_hours')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Habilidades Técnicas -->
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-code mr-2 text-yellow-600"></i>
                    Habilidades Técnicas
                </h3>

                <div id="skills-container" class="space-y-3">
                    @forelse($technician->skills as $index => $skill)
                        <div class="skill-item grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 rounded-lg">
                            <input type="hidden" name="skills[{{ $index }}][id]" value="{{ $skill->id }}">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Habilidad</label>
                                <input type="text"
                                       name="skills[{{ $index }}][skill_name]"
                                       value="{{ $skill->skill_name }}"
                                       placeholder="Ej: Laravel, React, Python..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                                <select name="skills[{{ $index }}][proficiency_level]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                                    <option value="">Seleccione...</option>
                                    <option value="beginner" {{ $skill->proficiency_level == 'beginner' ? 'selected' : '' }}>Principiante</option>
                                    <option value="intermediate" {{ $skill->proficiency_level == 'intermediate' ? 'selected' : '' }}>Intermedio</option>
                                    <option value="advanced" {{ $skill->proficiency_level == 'advanced' ? 'selected' : '' }}>Avanzado</option>
                                    <option value="expert" {{ $skill->proficiency_level == 'expert' ? 'selected' : '' }}>Experto</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Años</label>
                                    <input type="number"
                                           name="skills[{{ $index }}][years_experience_skill]"
                                           value="{{ $skill->years_experience }}"
                                           min="0"
                                           max="50"
                                           step="0.5"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                                </div>
                                <div class="flex items-end">
                                    <button type="button"
                                            class="remove-skill-btn px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors text-sm"
                                            data-skill-id="{{ $skill->id }}"
                                            title="Eliminar habilidad">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="skill-item grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Habilidad</label>
                                <input type="text"
                                       name="skills[0][skill_name]"
                                       placeholder="Ej: Laravel, React, Python..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                                <select name="skills[0][proficiency_level]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
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
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                    @endforelse
                </div>

                <button type="button"
                        id="add-skill-btn"
                        class="mt-3 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors text-sm flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Agregar Habilidad
                </button>
                <p class="mt-2 text-xs text-gray-500">Gestiona las habilidades técnicas del profesional</p>
            </div>

            <!-- Tipo de Usuario -->
            @if(auth()->user()->isAdmin())
            <div class="border-b pb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-user-shield mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('user_role') border-red-500 @enderror"
                                {{ auth()->id() === $technician->user_id ? 'disabled' : '' }}
                                required>
                            <option value="user" {{ old('user_role', $technician->user->role ?? 'user') == 'user' ? 'selected' : '' }}>
                                Usuario Regular
                            </option>
                            <option value="technician" {{ old('user_role', $technician->user->role ?? 'user') == 'technician' ? 'selected' : '' }}>
                                Técnico
                            </option>
                            <option value="admin" {{ old('user_role', $technician->user->role ?? 'user') == 'admin' ? 'selected' : '' }}>
                                Administrador
                            </option>
                        </select>
                        @if(auth()->id() === $technician->user_id)
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i> No puedes cambiar tu propio rol
                            </p>
                        @endif
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
                    <i class="fas fa-toggle-on mr-2 text-yellow-600"></i>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('status') border-red-500 @enderror"
                                required>
                            <option value="active" {{ old('status', $technician->status) == 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status', $technician->status) == 'inactive' ? 'selected' : '' }}>Inactivo</option>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('availability_status') border-red-500 @enderror"
                                required>
                            <option value="available" {{ old('availability_status', $technician->availability_status) == 'available' ? 'selected' : '' }}>Disponible</option>
                            <option value="busy" {{ old('availability_status', $technician->availability_status) == 'busy' ? 'selected' : '' }}>Ocupado</option>
                            <option value="on_leave" {{ old('availability_status', $technician->availability_status) == 'on_leave' ? 'selected' : '' }}>De Permiso</option>
                            <option value="unavailable" {{ old('availability_status', $technician->availability_status) == 'unavailable' ? 'selected' : '' }}>No Disponible</option>
                        </select>
                        @error('availability_status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('technicians.show', $technician) }}"
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
    let skillIndex = {{ $technician->skills->count() }};
    const deletedSkills = [];

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
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                <select name="skills[${skillIndex}][proficiency_level]"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
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
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
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
    });

    // Manejar eliminación de habilidades
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-skill-btn')) {
            const skillItem = e.target.closest('.skill-item');
            const skillId = e.target.closest('.remove-skill-btn').dataset.skillId;

            if (skillId) {
                // Marcar para eliminar en el servidor
                if (confirm('¿Estás seguro de eliminar esta habilidad?')) {
                    deletedSkills.push(skillId);
                    skillItem.remove();
                }
            } else {
                // Nueva habilidad, simplemente eliminar del DOM
                skillItem.remove();
            }
        }
    });
</script>
@endsection
@endsection
