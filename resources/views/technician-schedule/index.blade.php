@extends('layouts.app')

@section('title', 'Calendario de Técnicos')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-calendar-alt"></i>
            <span class="ml-1">Calendario de Técnicos</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto">
    <!-- Encabezado -->
    <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
        <div>
            <p class="text-gray-600 text-sm sm:text-base">Gestión de tiempos y capacidad del equipo</p>
        </div>
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
            <a href="{{ route('tasks.create') }}" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Nueva Tarea</span><span class="sm:hidden">Tarea</span>
            </a>
            <a href="{{ route('technician-schedule.team-capacity') }}" class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-chart-bar"></i> <span class="hidden sm:inline">Capacidad</span><span class="sm:hidden">Cap.</span>
            </a>
        </div>
    </div>

    <!-- Filtros y Controles -->
    <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 mb-4 sm:mb-6">
        <div class="space-y-3 sm:space-y-4">
            <!-- Fila 1: Selectores -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- Selector de vista -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">Vista</label>
                    <select id="viewSelector" class="w-full text-sm sm:text-base border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="day" {{ $view === 'day' ? 'selected' : '' }}>Día</option>
                        <option value="week" {{ $view === 'week' ? 'selected' : '' }}>Semana</option>
                        <option value="month" {{ $view === 'month' ? 'selected' : '' }}>Mes</option>
                    </select>
                </div>

                <!-- Selector de técnico -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">Técnico</label>
                    <select id="technicianSelector" class="w-full text-sm sm:text-base border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los técnicos</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ $technicianId == $tech->id ? 'selected' : '' }}>
                                {{ $tech->user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Selector de fecha -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">Fecha</label>
                    <input type="date" id="dateSelector" value="{{ $date }}" class="w-full text-sm sm:text-base border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Fila 2: Botones de navegación -->
            <div class="flex justify-center sm:justify-start gap-2">
                <button onclick="navigateDate('prev')" class="bg-gray-200 hover:bg-gray-300 px-3 sm:px-4 py-2 rounded-lg transition-colors text-sm sm:text-base">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="navigateDate('today')" class="bg-gray-200 hover:bg-gray-300 px-3 sm:px-4 py-2 rounded-lg transition-colors text-sm sm:text-base font-medium">
                    Hoy
                </button>
                <button onclick="navigateDate('next')" class="bg-gray-200 hover:bg-gray-300 px-3 sm:px-4 py-2 rounded-lg transition-colors text-sm sm:text-base">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Contenido del calendario -->
    @if($view === 'day')
        @include('technician-schedule.partials.day-view')
    @elseif($view === 'week')
        @include('technician-schedule.partials.week-view')
    @elseif($view === 'month')
        @include('technician-schedule.partials.month-view')
    @endif
</div>

<script>
    // Cambiar vista
    document.getElementById('viewSelector').addEventListener('change', function() {
        updateCalendar();
    });

    // Cambiar técnico
    document.getElementById('technicianSelector').addEventListener('change', function() {
        updateCalendar();
    });

    // Cambiar fecha
    document.getElementById('dateSelector').addEventListener('change', function() {
        updateCalendar();
    });

    // Navegar fechas
    function navigateDate(direction) {
        const dateInput = document.getElementById('dateSelector');
        const currentDate = new Date(dateInput.value);
        const view = document.getElementById('viewSelector').value;

        let newDate;
        if (direction === 'prev') {
            newDate = new Date(currentDate);
            if (view === 'day') newDate.setDate(currentDate.getDate() - 1);
            else if (view === 'week') newDate.setDate(currentDate.getDate() - 7);
            else if (view === 'month') newDate.setMonth(currentDate.getMonth() - 1);
        } else if (direction === 'next') {
            newDate = new Date(currentDate);
            if (view === 'day') newDate.setDate(currentDate.getDate() + 1);
            else if (view === 'week') newDate.setDate(currentDate.getDate() + 7);
            else if (view === 'month') newDate.setMonth(currentDate.getMonth() + 1);
        } else if (direction === 'today') {
            newDate = new Date();
        }

        dateInput.value = newDate.toISOString().split('T')[0];
        updateCalendar();
    }

    // Actualizar calendario
    function updateCalendar() {
        const view = document.getElementById('viewSelector').value;
        const technicianId = document.getElementById('technicianSelector').value;
        const date = document.getElementById('dateSelector').value;

        const params = new URLSearchParams();
        params.append('view', view);
        params.append('date', date);
        if (technicianId) params.append('technician_id', technicianId);

        window.location.href = `{{ route('technician-schedule.index') }}?${params.toString()}`;
    }

    // ======================================
    // DRAG & DROP FUNCTIONALITY
    // ======================================
    let draggedTask = null;
    let isDragging = false;
    let isResizing = false;
    let resizingTask = null;
    let startY = 0;
    let startHeight = 0;
    let initialDuration = 0;
    let justResized = false; // Nueva bandera para prevenir click después de resize
    let justDragged = false; // Nueva bandera para prevenir click después de drag

    // Función para corregir duraciones a múltiplos de 5 minutos
    function correctTaskDurations() {
        document.querySelectorAll('.duration-display').forEach(display => {
            const taskCard = display.closest('.task-card');
            if (taskCard && taskCard.dataset.duration) {
                const rawDuration = parseFloat(taskCard.dataset.duration);
                const totalMins = Math.round(rawDuration * 60);
                const hours = Math.floor(totalMins / 60);
                const mins = totalMins % 60;

                if (hours > 0 && mins > 0) {
                    display.innerHTML = `<i class="fas fa-clock"></i> ${hours}h ${mins}min`;
                } else if (hours > 0) {
                    display.innerHTML = `<i class="fas fa-clock"></i> ${hours}h`;
                } else {
                    display.innerHTML = `<i class="fas fa-clock"></i> ${mins}min`;
                }
            }
        });
    }

    // Inicializar drag & drop en todas las tareas
    function initializeDragAndDrop() {
        const taskCards = document.querySelectorAll('[data-task-id]');

        taskCards.forEach(card => {
            card.setAttribute('draggable', 'true');

            card.addEventListener('dragstart', function(e) {
                isDragging = true;
                justDragged = true;
                draggedTask = {
                    id: this.dataset.taskId,
                    element: this
                };
                this.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            });

            card.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
                isDragging = false;

                // Remover highlights de todas las celdas
                document.querySelectorAll('.drop-target').forEach(cell => {
                    cell.classList.remove('bg-green-100', 'border-2', 'border-green-500');
                });

                // Reset la bandera después de un pequeño delay
                setTimeout(() => {
                    justDragged = false;
                }, 300);
            });

            // Prevenir click cuando se está arrastrando
            card.addEventListener('click', function(e) {
                if (isDragging || isResizing) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        });

        // Hacer que las celdas del calendario sean drop targets
        const calendarCells = document.querySelectorAll('[data-date][data-hour]');

        calendarCells.forEach(cell => {
            cell.classList.add('drop-target');

            cell.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('bg-green-100', 'border-2', 'border-green-500');
            });

            cell.addEventListener('dragleave', function(e) {
                this.classList.remove('bg-green-100', 'border-2', 'border-green-500');
            });

            cell.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();

                this.classList.remove('bg-green-100', 'border-2', 'border-green-500');

                if (draggedTask) {
                    const newDate = this.dataset.date;
                    const newHour = this.dataset.hour;

                    // Validar que la fecha y hora no sean del pasado
                    const scheduledDateTime = new Date(`${newDate} ${newHour}`);
                    const now = new Date();

                    if (scheduledDateTime < now) {
                        alert('No se puede asignar una tarea en una fecha y hora pasadas.');
                        return;
                    }

                    // Validar horario laboral (6:00 - 18:00)
                    const hour = parseInt(newHour.split(':')[0]);
                    if (hour < 6 || hour >= 18) {
                        alert('La hora debe estar dentro del horario laboral (6:00 - 18:00).');
                        return;
                    }

                    // Advertencias para horarios no hábiles
                    const selectedDate = new Date(newDate);
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
                    let confirmMessage = `¿Mover la tarea a ${newDate} a las ${newHour}?`;
                    if (warnings.length > 0) {
                        confirmMessage = '⚠️ ADVERTENCIA DE HORARIO NO HÁBIL:\n\n' + warnings.join('\n') + '\n\n' + confirmMessage;
                    }

                    // Confirmar el movimiento
                    if (confirm(confirmMessage)) {
                        updateTaskSchedule(draggedTask.id, newDate, newHour);
                    }
                }
            });
        });
    }

    // ======================================
    // RESIZE FUNCTIONALITY
    // ======================================
    function initializeResize() {
        const resizeHandles = document.querySelectorAll('.resize-handle');

        resizeHandles.forEach(handle => {
            // Prevenir click en el handle
            handle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });

            handle.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();

                isResizing = true;
                resizingTask = this.closest('.task-card');
                startY = e.clientY;
                startHeight = resizingTask.offsetHeight;

                // Duración inicial exacta
                const rawDuration = parseFloat(resizingTask.dataset.duration) || 0.5;
                initialDuration = rawDuration;

                // Deshabilitar draggable mientras se redimensiona
                resizingTask.setAttribute('draggable', 'false');

                // Añadir clase visual
                resizingTask.style.border = '2px dashed #3b82f6';

                document.addEventListener('mousemove', handleResize);
                document.addEventListener('mouseup', stopResize);
            });
        });
    }

    function handleResize(e) {
        if (!isResizing || !resizingTask) return;

        const deltaY = e.clientY - startY;

        // Calcular duración en minutos con precisión de 1 minuto
        const totalMinutes = initialDuration * 60 + (deltaY / 5); // 5px = 1 minuto
        const newDurationMinutes = Math.round(totalMinutes); // Redondear a minutos completos
        const newDuration = Math.max(1, Math.min(1440, newDurationMinutes)) / 60; // Min 1 min, max 24h

        // Calcular altura correspondiente a la nueva duración (60px por hora)
        const calculatedHeight = newDuration * 60;

        // Aplicar límites de altura
        const newHeight = Math.max(40, calculatedHeight);

        // Solo actualizar si hay cambio real
        if (Math.abs(newHeight - resizingTask.offsetHeight) >= 1) {
            // Actualizar visualmente con transición suave
            resizingTask.style.height = newHeight + 'px';

            // Actualizar display de duración usando minutos ya redondeados
            const durationDisplay = resizingTask.querySelector('.duration-display');
            if (durationDisplay) {
                const totalMins = Math.round(newDuration * 60);
                const hours = Math.floor(totalMins / 60);
                const mins = totalMins % 60;
                if (hours > 0 && mins > 0) {
                    durationDisplay.innerHTML = `<i class="fas fa-clock"></i> ${hours}h ${mins}min`;
                } else if (hours > 0) {
                    durationDisplay.innerHTML = `<i class="fas fa-clock"></i> ${hours}h`;
                } else {
                    durationDisplay.innerHTML = `<i class="fas fa-clock"></i> ${mins}min`;
                }
            }
        }
    }

    function stopResize(e) {
        if (!isResizing || !resizingTask) return;

        document.removeEventListener('mousemove', handleResize);
        document.removeEventListener('mouseup', stopResize);

        // Marcar que acabamos de hacer resize
        justResized = true;

        // Calcular duración final exacta
        const deltaY = e.clientY - startY;
        const totalMinutes = initialDuration * 60 + (deltaY / 5);
        const newDurationMinutes = Math.round(totalMinutes);
        const newDuration = Math.max(1, Math.min(1440, newDurationMinutes)) / 60;

        // Restaurar border
        resizingTask.style.border = '';

        // Confirmar cambio si hay diferencia de al menos 1 minuto
        if (Math.abs(newDuration - initialDuration) >= (1/60)) {
            const taskId = resizingTask.dataset.taskId;
            const durationText = formatDuration(newDuration);

            if (confirm(`¿Cambiar duración de ${formatDuration(initialDuration)} a ${durationText}?`)) {
                updateTaskDuration(taskId, newDuration);
            } else {
                // Revertir cambios visuales
                resizingTask.style.height = '';
                const durationDisplay = resizingTask.querySelector('.duration-display');
                if (durationDisplay) {
                    durationDisplay.innerHTML = `<i class="fas fa-clock"></i> ${formatDuration(initialDuration)}`;
                }
            }
        } else {
            // Revertir si el cambio es muy pequeño
            resizingTask.style.height = '';
        }

        // Restaurar draggable
        resizingTask.setAttribute('draggable', 'true');

        isResizing = false;
        resizingTask = null;

        // Reset la bandera después de un pequeño delay
        setTimeout(() => {
            justResized = false;
        }, 300);
    }

    function formatDuration(hours) {
        const totalMins = Math.round(hours * 60);
        const h = Math.floor(totalMins / 60);
        const m = totalMins % 60;
        if (h > 0 && m > 0) {
            return `${h}h ${m}min`;
        } else if (h > 0) {
            return `${h}h`;
        } else {
            return `${m}min`;
        }
    }

    function updateTaskDuration(taskId, newDuration) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando duración...';
        document.body.appendChild(loadingDiv);

        fetch(`/tasks/${taskId}/update-duration`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                estimated_hours: newDuration
            })
        })
        .then(response => response.json())
        .then(data => {
            document.body.removeChild(loadingDiv);

            if (data.success) {
                const successDiv = document.createElement('div');
                successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ¡Duración actualizada!';
                document.body.appendChild(successDiv);

                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showError(data.message || 'Error al actualizar duración');
            }
        })
        .catch(error => {
            document.body.removeChild(loadingDiv);
            console.error('Error:', error);
            showError('Error al actualizar duración. Intenta de nuevo.');
        });
    }

    // Actualizar horario de tarea vía AJAX
    function updateTaskSchedule(taskId, newDate, newHour) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Mostrar indicador de carga
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moviendo tarea...';
        document.body.appendChild(loadingDiv);

        fetch(`/tasks/${taskId}/reschedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                scheduled_date: newDate,
                scheduled_start_time: newHour + ':00'
            })
        })
        .then(response => {
            // Manejar respuestas de error (422, etc.)
            if (!response.ok) {
                return response.json().then(errData => {
                    throw new Error(errData.message || 'Error al mover la tarea');
                });
            }
            return response.json();
        })
        .then(data => {
            document.body.removeChild(loadingDiv);

            if (data.success) {
                // Mostrar mensaje de éxito
                const successDiv = document.createElement('div');
                successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ¡Tarea movida exitosamente!';
                document.body.appendChild(successDiv);

                // Recargar después de 1 segundo
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showError(data.message || 'Error al mover la tarea');
            }
        })
        .catch(error => {
            document.body.removeChild(loadingDiv);
            console.error('Error:', error);
            showError(error.message || 'Error al mover la tarea. Por favor, intenta de nuevo.');
        });
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        document.body.appendChild(errorDiv);

        setTimeout(() => {
            document.body.removeChild(errorDiv);
        }, 4000);
    }

    // Inicializar cuando la página cargue
    document.addEventListener('DOMContentLoaded', function() {
        correctTaskDurations(); // Corregir duraciones al cargar
        initializeDragAndDrop();
        initializeResize();
    });
</script>
</script>
@endsection
