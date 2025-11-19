@extends('layouts.app')

@section('title', 'Nueva Solicitud de Servicio')

@section('content')
<style>
    @keyframes scale-in {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    .animate-scale-in {
        animation: scale-in 0.2s ease-out;
    }
</style>

    {{-- Mostrar todos los errores de validación --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-lg font-medium text-red-800 mb-2">Errores de validación:</h3>
            <ul class="list-disc list-inside text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            {{-- Mostrar datos enviados --}}
            <div class="mt-4 p-3 bg-red-100 rounded">
                <h4 class="font-medium text-red-800">Datos enviados:</h4>
                <pre class="text-sm text-red-700 mt-2">{{ json_encode(old(), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    <form action="{{ route('service-requests.store') }}" method="POST">
        @csrf

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                    <h2 class="text-xl font-bold text-gray-800">Nueva Solicitud de Servicio</h2>
                </div>
                <div class="p-6">
                    @include('components.service-requests.forms.basic-fields', [
                        'subServices' => $subServices,
                        'requesters' => $requesters,
                        'errors' => $errors,
                        'mode' => 'create',
                    ])
                </div>
            </div>

            <!-- Tareas Predefinidas -->
            <div id="standardTasksSection" class="hidden mt-6 bg-white rounded-2xl shadow-lg border-2 border-purple-200 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <i class="fas fa-tasks text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Tareas Predefinidas Disponibles</h3>
                                <p class="text-purple-100 text-sm">Este subservicio tiene tareas preconfiguradas</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-gradient-to-br from-purple-50 to-indigo-50">
                    <div id="standardTasksCount" class="mb-4 text-sm font-medium text-purple-700">
                        <!-- Contador de tareas -->
                    </div>
                    <div id="standardTasksList" class="space-y-4">
                        <!-- Las tareas se cargarán dinámicamente -->
                    </div>
                    <div id="noStandardTasks" class="hidden text-center py-8 text-gray-500">
                        <i class="fas fa-info-circle text-4xl mb-3"></i>
                        <p>Este subservicio no tiene tareas predefinidas configuradas</p>
                    </div>
                </div>
            </div>

            <!-- Modal de Confirmación de Tareas Predefinidas -->
            <div id="standardTasksModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4" style="display: none;">
                <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden animate-scale-in">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-3 rounded-xl">
                                <i class="fas fa-tasks text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">¿Deseas crear las tareas predefinidas?</h3>
                                <p class="text-purple-100 text-sm mt-1">Este subservicio tiene tareas preconfiguradas disponibles</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <div id="modalTasksCount" class="mb-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
                            <!-- Contador de tareas en modal -->
                        </div>

                        <div id="modalTasksList" class="space-y-3">
                            <!-- Las tareas se mostrarán aquí -->
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row gap-3 justify-end">
                        <button type="button" onclick="submitWithoutTasks()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-times-circle"></i>
                            No, crear solo la solicitud
                        </button>
                        <button type="button" onclick="submitWithTasks()" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2 shadow-lg">
                            <i class="fas fa-check-circle"></i>
                            Sí, crear con tareas predefinidas
                        </button>
                    </div>
                </div>
            </div>

            <input type="hidden" id="use_standard_tasks_hidden" name="use_standard_tasks" value="0">

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-end gap-3">
                    <!-- Botón Cancelar - Simplificado -->
                    <a href="{{ route('service-requests.index') }}"
                        class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Cancelar
                    </a>

                    <!-- Botón Crear - Simplificado -->
                    <button type="submit"
                        class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Crear Solicitud
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
    let availableStandardTasks = [];
    let hasStandardTasks = false;

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const subServiceIdInput = document.getElementById('sub_service_id');
        const standardTasksSection = document.getElementById('standardTasksSection');
        const standardTasksList = document.getElementById('standardTasksList');
        const standardTasksCount = document.getElementById('standardTasksCount');
        const noStandardTasks = document.getElementById('noStandardTasks');
        const standardTasksModal = document.getElementById('standardTasksModal');
        const modalTasksList = document.getElementById('modalTasksList');
        const modalTasksCount = document.getElementById('modalTasksCount');
        const useStandardTasksHidden = document.getElementById('use_standard_tasks_hidden');

        // Interceptar envío del formulario
        form.addEventListener('submit', function(e) {
            if (hasStandardTasks && !form.dataset.confirmed) {
                e.preventDefault();
                showTasksModal();
                return false;
            }
        });

        // Escuchar cambios en el subservicio seleccionado
        if (subServiceIdInput) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        loadStandardTasks();
                    }
                });
            });

            observer.observe(subServiceIdInput, { attributes: true });

            // También escuchar el evento change
            subServiceIdInput.addEventListener('change', loadStandardTasks);
        }

        function loadStandardTasks() {
            const subServiceId = subServiceIdInput.value;

            if (!subServiceId) {
                standardTasksSection.classList.add('hidden');
                return;
            }

            fetch(`/api/sub-services/${subServiceId}/standard-tasks`)
                .then(response => response.json())
                .then(tasks => {
                    availableStandardTasks = tasks;
                    if (tasks.length > 0) {
                        hasStandardTasks = true;
                        displayStandardTasks(tasks);
                        standardTasksSection.classList.remove('hidden');
                        noStandardTasks.classList.add('hidden');
                    } else {
                        hasStandardTasks = false;
                        standardTasksList.innerHTML = '';
                        standardTasksSection.classList.remove('hidden');
                        noStandardTasks.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error cargando tareas predefinidas:', error);
                    hasStandardTasks = false;
                    standardTasksSection.classList.add('hidden');
                });
        }

        function showTasksModal() {
            // Llenar el modal con las tareas
            const totalSubtasks = availableStandardTasks.reduce((sum, task) => sum + (task.standard_subtasks?.length || 0), 0);

            modalTasksCount.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-purple-600 text-lg"></i>
                        <span class="font-semibold text-purple-900">
                            Se crearán <strong class="text-xl">${availableStandardTasks.length}</strong> tarea${availableStandardTasks.length !== 1 ? 's' : ''} predefinida${availableStandardTasks.length !== 1 ? 's' : ''}
                        </span>
                    </div>
                    ${totalSubtasks > 0 ? `
                        <div class="flex items-center gap-2 text-purple-600">
                            <i class="fas fa-tasks"></i>
                            <span class="font-medium">${totalSubtasks} subtarea${totalSubtasks !== 1 ? 's' : ''}</span>
                        </div>
                    ` : ''}
                </div>
            `;

            modalTasksList.innerHTML = availableStandardTasks.map((task, index) => `
                <div class="border-2 border-purple-200 rounded-lg p-4 bg-white">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 bg-purple-100 text-purple-700 font-bold rounded-lg w-8 h-8 flex items-center justify-center text-sm">
                            ${index + 1}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-bold rounded ${getPriorityClass(task.priority)}">
                                    ${task.priority.toUpperCase()}
                                </span>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-bold rounded bg-blue-100 text-blue-800">
                                    <i class="fas fa-clock mr-1"></i>${task.estimated_hours} hrs
                                </span>
                                ${task.type === 'impact' ? '<span class="inline-flex items-center px-2 py-1 text-xs font-bold rounded bg-gradient-to-r from-purple-500 to-pink-500 text-white"><i class="fas fa-star mr-1"></i>IMPACTO</span>' : ''}
                            </div>
                            <h4 class="font-bold text-gray-900 mb-1">${task.title}</h4>
                            ${task.description ? `<p class="text-sm text-gray-600 mb-2">${task.description}</p>` : ''}
                            ${task.standard_subtasks && task.standard_subtasks.length > 0 ? `
                                <div class="mt-2 text-xs text-gray-600">
                                    <i class="fas fa-tasks mr-1"></i>${task.standard_subtasks.length} subtarea${task.standard_subtasks.length !== 1 ? 's' : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            standardTasksModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        window.submitWithTasks = function() {
            useStandardTasksHidden.value = '1';
            form.dataset.confirmed = 'true';
            standardTasksModal.style.display = 'none';
            document.body.style.overflow = '';
            form.submit();
        };

        window.submitWithoutTasks = function() {
            useStandardTasksHidden.value = '0';
            form.dataset.confirmed = 'true';
            standardTasksModal.style.display = 'none';
            document.body.style.overflow = '';
            form.submit();
        };

        function displayStandardTasks(tasks) {
            // Actualizar contador
            const totalSubtasks = tasks.reduce((sum, task) => sum + (task.standard_subtasks?.length || 0), 0);
            standardTasksCount.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas fa-layer-group text-purple-600"></i>
                    <span><strong>${tasks.length}</strong> tarea${tasks.length !== 1 ? 's' : ''} predefinida${tasks.length !== 1 ? 's' : ''}</span>
                    ${totalSubtasks > 0 ? `<span class="text-purple-500">• <strong>${totalSubtasks}</strong> subtarea${totalSubtasks !== 1 ? 's' : ''} total${totalSubtasks !== 1 ? 'es' : ''}</span>` : ''}
                </div>
            `;

            standardTasksList.innerHTML = tasks.map((task, index) => `
                <div class="border-2 border-purple-200 rounded-xl p-5 bg-white shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 bg-purple-100 text-purple-700 font-bold rounded-lg w-8 h-8 flex items-center justify-center text-sm">
                            ${index + 1}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg ${getPriorityClass(task.priority)}">
                                    ${task.priority.toUpperCase()}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-800">
                                    <i class="fas fa-clock mr-1"></i>${task.estimated_hours} hrs
                                </span>
                                ${task.type === 'impact' ? '<span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-purple-500 to-pink-500 text-white"><i class="fas fa-star mr-1"></i>IMPACTO</span>' : ''}
                            </div>
                            <h4 class="font-bold text-gray-900 mb-1 text-lg">${task.title}</h4>
                            ${task.description ? `<p class="text-sm text-gray-600 mb-3">${task.description}</p>` : ''}
                            ${task.standard_subtasks && task.standard_subtasks.length > 0 ? `
                                <div class="mt-3 bg-purple-50 rounded-lg p-3 border border-purple-100">
                                    <p class="text-xs font-bold text-purple-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-tasks"></i>
                                        Subtareas incluidas (${task.standard_subtasks.length}):
                                    </p>
                                    <ul class="space-y-1.5">
                                        ${task.standard_subtasks.map(subtask => `
                                            <li class="flex items-start gap-2 text-sm text-gray-700">
                                                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                                                <span>${subtask.title}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getPriorityClass(priority) {
            const classes = {
                'critical': 'bg-red-100 text-red-800',
                'high': 'bg-orange-100 text-orange-800',
                'medium': 'bg-yellow-100 text-yellow-800',
                'low': 'bg-green-100 text-green-800'
            };
            return classes[priority] || 'bg-gray-100 text-gray-800';
        }
    });
    </script>
@endsection
