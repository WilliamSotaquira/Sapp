<!-- Modal de Inicio de Servicio -->
<div id="start-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-cyan-100 rounded-full mr-3">
                    <i class="fas fa-play text-cyan-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Iniciar Servicio
                </h3>
            </div>
            <button type="button"
                    onclick="document.getElementById('start-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200">
                ✕
            </button>
        </div>

        <!-- Información del servicio -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Preparación para iniciar servicio</span>
            </div>
            <div class="mt-2 text-sm text-blue-700">
                <p>Confirma que estás listo para comenzar el trabajo en esta solicitud.</p>
            </div>
        </div>

        <!-- Detalles del servicio -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 rounded-md p-3">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Detalles:</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ticket:</span>
                        <span class="font-mono text-gray-900">{{ $serviceRequest->ticket_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Técnico:</span>
                        <span>
                            @if($serviceRequest->assignee)
                                <span class="text-green-600 font-medium">{{ $serviceRequest->assignee->name }}</span>
                            @else
                                <span class="text-red-600">Sin asignar</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Estado:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                            ACEPTADA
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tareas Predefinidas -->
            @php
                $standardTasks = $serviceRequest->subService->standardTasks()->active()->ordered()->get();
            @endphp
            <div class="bg-purple-50 rounded-md p-3">
                <h4 class="text-sm font-medium text-purple-700 mb-2 flex items-center">
                    <i class="fas fa-tasks mr-2"></i>
                    Tareas Predefinidas
                </h4>
                @if($standardTasks->count() > 0)
                    <p class="text-xs text-purple-600 mb-2">
                        Este subservicio tiene {{ $standardTasks->count() }} tarea(s) predefinida(s):
                    </p>
                    <ul class="text-xs text-purple-700 space-y-1 max-h-32 overflow-y-auto">
                        @foreach($standardTasks as $task)
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mr-1 mt-0.5 flex-shrink-0"></i>
                                <span>{{ $task->title }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-gray-500">
                        No hay tareas predefinidas para este subservicio.
                    </p>
                @endif
            </div>
        </div>

        <!-- Información importante -->
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-md mb-4">
            <div class="flex items-start">
                <i class="fas fa-clock text-amber-500 mt-0.5 mr-2 flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-medium text-amber-800">Registro de Tiempo</p>
                    <p class="text-xs text-amber-700 mt-1">
                        Al iniciar el servicio, el sistema comenzará a registrar el tiempo empleado en la resolución.
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulario de inicio -->
        <form id="start-service-form-{{ $serviceRequest->id }}"
              action="{{ route('service-requests.start', $serviceRequest) }}"
              method="POST">
            @csrf
            @method('PATCH')

            <input type="hidden" name="use_standard_tasks" id="use-standard-tasks-{{ $serviceRequest->id }}" value="0">

            @if($standardTasks->count() > 0)
                <!-- Pregunta sobre tareas predefinidas -->
                <div class="mb-4 p-4 bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-200 rounded-lg">
                    <p class="text-sm font-medium text-gray-900 mb-3">
                        ¿Deseas crear las tareas predefinidas automáticamente?
                    </p>
                    <div class="flex gap-3">
                        <button type="button"
                                onclick="submitStartWithTasks{{ $serviceRequest->id }}(true)"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                            <i class="fas fa-check-double mr-2"></i>
                            Sí, usar tareas predefinidas
                        </button>
                        <button type="button"
                                onclick="submitStartWithTasks{{ $serviceRequest->id }}(false)"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                            <i class="fas fa-plus-circle mr-2"></i>
                            No, crear tareas manualmente
                        </button>
                    </div>
                </div>
            @endif

            <div class="flex justify-end space-x-3">
                <button type="button"
                        onclick="document.getElementById('start-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                @if($standardTasks->count() == 0)
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 border border-transparent rounded-md hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-colors duration-200">
                        <i class="fas fa-play mr-2"></i>
                        Iniciar Servicio
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
function submitStartWithTasks{{ $serviceRequest->id }}(useTasks) {
    const form = document.getElementById('start-service-form-{{ $serviceRequest->id }}');
    const input = document.getElementById('use-standard-tasks-{{ $serviceRequest->id }}');
    input.value = useTasks ? '1' : '0';
    form.submit();
}
</script>
