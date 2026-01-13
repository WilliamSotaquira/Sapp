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

    @if (session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('service-requests.store') }}" method="POST">
        @csrf

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                    <h2 class="text-xl font-bold text-gray-800">Datos de la solicitud</h2>
                </div>
                <div class="p-6">
                    @include('components.service-requests.forms.basic-fields', [
                        'subServices' => $subServices,
                        'selectedSubService' => $selectedSubService ?? null,
                        'requesters' => $requesters,
                        'errors' => $errors,
                        'mode' => 'create',
                    ])
                </div>
            </div>

            <!-- Tareas (opcional) -->
            <div class="mt-6 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <button type="button" id="toggleTasksSection" class="w-full px-6 py-4 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 transition border-b border-blue-100">
                    <div class="text-left">
                        <div class="text-lg font-bold text-gray-800">Tareas (opcional)</div>
                        <div class="text-gray-600 text-sm">Agrega tareas ahora o deja la solicitud solo con descripción.</div>
                    </div>
                    <span id="tasksChevron" class="text-gray-500">▾</span>
                </button>

                <div id="tasksSectionBody" class="hidden p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div>
                            <label for="tasks_template" class="block text-sm font-medium text-gray-700 mb-2">Plantilla</label>
                            <select id="tasks_template" name="tasks_template" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="none" {{ old('tasks_template', 'none') === 'none' ? 'selected' : '' }}>Ninguna (manual)</option>
                                <option value="subservice_standard" {{ old('tasks_template') === 'subservice_standard' ? 'selected' : '' }}>Tareas predefinidas del subservicio</option>
                            </select>

                        </div>

                        <div class="flex gap-3 justify-start sm:justify-end">
                            <button type="button" id="addTaskRow" class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold">
                                + Agregar tarea
                            </button>
                            <button type="button" id="clearTasks" class="w-full sm:w-auto px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div id="tasksNotice" class="hidden mt-4 p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-sm"></div>

                    <div id="tasksList" class="mt-4 space-y-3"></div>
                </div>
            </div>

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
    document.addEventListener('DOMContentLoaded', function() {
        const formEl = document.querySelector('form[action="{{ route('service-requests.store') }}"]');

            // Posicionar foco correctamente:
            // - Si hay errores de validación, enfocar el primer campo con error.
            // - Si no hay errores, enfocar el título.
            (function positionInitialFocus() {
                if (!formEl) return;

                const isVisible = (el) => {
                    if (!el) return false;
                    if (el.disabled) return false;
                    if (el.type === 'hidden') return false;
                    const style = window.getComputedStyle(el);
                    if (style.visibility === 'hidden' || style.display === 'none') return false;
                    const rect = el.getBoundingClientRect();
                    return rect.width > 0 && rect.height > 0;
                };

                const errorField = formEl.querySelector('input.border-red-500, select.border-red-500, textarea.border-red-500');
                const titleField = document.getElementById('title');

                const target = (errorField && isVisible(errorField)) ? errorField : (titleField && isVisible(titleField) ? titleField : null);
                if (!target) return;

                // Esperar un tick por si hay scripts que re-renderizan/selectores.
                setTimeout(() => {
                    try {
                        target.focus({ preventScroll: true });
                    } catch (e) {
                        target.focus();
                    }

                    try {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (e) {
                        // no-op
                    }
                }, 0);
            })();

        const toggleBtn = document.getElementById('toggleTasksSection');
        const body = document.getElementById('tasksSectionBody');
        const chevron = document.getElementById('tasksChevron');
        const tasksList = document.getElementById('tasksList');
        const addRowBtn = document.getElementById('addTaskRow');
        const clearBtn = document.getElementById('clearTasks');
        const templateSelect = document.getElementById('tasks_template');
        const notice = document.getElementById('tasksNotice');
        const subServiceIdInput = document.getElementById('sub_service_id');

        const initialTasks = @json(old('tasks', []));
        const initialTemplate = @json(old('tasks_template', 'none'));

        function setNotice(message) {
            if (!message) {
                notice.classList.add('hidden');
                notice.textContent = '';
                return;
            }
            notice.textContent = message;
            notice.classList.remove('hidden');
        }

        function isOpen() {
            return !body.classList.contains('hidden');
        }

        function openSection() {
            body.classList.remove('hidden');
            chevron.textContent = '▴';
        }

        function closeSection() {
            body.classList.add('hidden');
            chevron.textContent = '▾';
        }

        function clearTaskRowErrors() {
            tasksList.querySelectorAll('[data-task-desc-error]').forEach((el) => el.remove());
            tasksList.querySelectorAll('[data-field="description"]').forEach((el) => {
                el.classList.remove('border-red-500');
            });
        }

        function validateTaskDescriptionsMinLen() {
            clearTaskRowErrors();

            const rows = Array.from(tasksList.querySelectorAll('[data-task-row]'));
            let isValid = true;

            rows.forEach((row) => {
                const descEl = row.querySelector('[data-field="description"]');
                const stdIdEl = row.querySelector('[data-field="standard_task_id"]');

                const description = String(descEl?.value ?? '').trim();
                const standardTaskId = String(stdIdEl?.value ?? '').trim();

                // Consistente con otras pantallas: mínimo 10 caracteres.
                // Solo aplica a tareas manuales (sin standard_task_id) y cuando se llena descripción.
                if (!standardTaskId && description.length > 0 && description.length < 10) {
                    isValid = false;
                    descEl?.classList.add('border-red-500');

                    const error = document.createElement('p');
                    error.setAttribute('data-task-desc-error', '1');
                    error.className = 'mt-1 text-sm text-red-600';
                    error.textContent = 'La descripción debe tener al menos 10 caracteres.';
                    descEl?.insertAdjacentElement('afterend', error);
                }
            });

            return isValid;
        }

        toggleBtn?.addEventListener('click', function() {
            isOpen() ? closeSection() : openSection();
        });

        function getRowData(rowEl) {
            return {
                title: rowEl.querySelector('[data-field="title"]')?.value ?? '',
                description: rowEl.querySelector('[data-field="description"]')?.value ?? '',
                type: rowEl.querySelector('[data-field="type"]')?.value ?? 'regular',
                priority: rowEl.querySelector('[data-field="priority"]')?.value ?? 'medium',
                estimated_minutes: rowEl.querySelector('[data-field="estimated_minutes"]')?.value ?? '',
                estimated_hours: rowEl.querySelector('[data-field="estimated_hours"]')?.value ?? '',
                standard_task_id: rowEl.querySelector('[data-field="standard_task_id"]')?.value ?? '',
            };
        }

        function reindexRows() {
            const rows = Array.from(tasksList.querySelectorAll('[data-task-row]'));
            rows.forEach((row, index) => {
                row.querySelectorAll('[data-name-template]').forEach((input) => {
                    const tpl = input.getAttribute('data-name-template');
                    input.setAttribute('name', tpl.replace('__INDEX__', index));
                });

                reindexSubtasks(row, index);
            });
        }

        function formatHoursFromMinutes(minutes) {
            const m = Number(minutes);
            if (!Number.isFinite(m) || m < 0) return '';
            const hours = m / 60;
            return String(hours.toFixed(2)).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
        }

        function formatHumanDuration(minutes) {
            const m = Number(minutes);
            if (!Number.isFinite(m) || m <= 0) return '';
            const h = Math.floor(m / 60);
            const mm = Math.round(m % 60);
            if (h > 0 && mm > 0) return `${h}h ${mm}m`;
            if (h > 0) return `${h}h`;
            return `${mm}m`;
        }

        function setEstimateUiState(taskRow, { locked, totalMinutes } = {}) {
            if (!taskRow) return;
            const minutesEl = taskRow.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = taskRow.querySelector('[data-field="estimated_hours"]');
            const hintEl = taskRow.querySelector('[data-estimate-hint]');
            if (!minutesEl || !hoursEl) return;

            const chipButtons = Array.from(taskRow.querySelectorAll('[data-estimate-chip]'));

            const lockedClasses = ['bg-gray-50', 'text-gray-500', 'cursor-not-allowed'];

            if (locked) {
                minutesEl.readOnly = true;
                hoursEl.readOnly = true;

                // No tabular hacia campos calculados (pero se siguen enviando en el POST)
                minutesEl.setAttribute('tabindex', '-1');
                hoursEl.setAttribute('tabindex', '-1');

                minutesEl.classList.add(...lockedClasses);
                hoursEl.classList.add(...lockedClasses);

                chipButtons.forEach((btn) => {
                    btn.disabled = true;
                    btn.setAttribute('tabindex', '-1');
                    btn.classList.add('opacity-60', 'cursor-not-allowed');
                });

                const human = formatHumanDuration(totalMinutes);
                if (hintEl) {
                    hintEl.textContent = `Calculado por subtareas: ${totalMinutes} min${human ? ` (${human})` : ''}. Edita subtareas para cambiar.`;
                }
            } else {
                minutesEl.readOnly = false;
                hoursEl.readOnly = false;

                minutesEl.removeAttribute('tabindex');
                hoursEl.removeAttribute('tabindex');

                minutesEl.classList.remove(...lockedClasses);
                hoursEl.classList.remove(...lockedClasses);

                chipButtons.forEach((btn) => {
                    btn.disabled = false;
                    btn.removeAttribute('tabindex');
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                });

                const rawMinutes = String(minutesEl.value || '').trim();
                const m = rawMinutes !== '' ? Number(rawMinutes) : null;
                const human = m !== null && Number.isFinite(m) ? formatHumanDuration(m) : '';
                if (hintEl) {
                    hintEl.textContent = human
                        ? `Equivale a ${human}. Puedes escribir horas con coma (ej: 1,5).`
                        : 'Puedes escribir horas con coma (ej: 1,5).';
                }
            }
        }

        function parseHoursToMinutes(rawHours) {
            const raw = String(rawHours ?? '').trim();
            if (!raw) return null;
            const normalized = raw.replace(',', '.');
            const hours = Number(normalized);
            if (!Number.isFinite(hours) || hours < 0) return null;
            const minutes = Math.round(hours * 60);
            return Math.round(minutes / 5) * 5;
        }

        function parseMinutesFromSubtaskTitle(title) {
            const rawTitle = String(title ?? '').trim();
            if (!rawTitle) return null;

            const matches = Array.from(rawTitle.matchAll(/\(([^()]*)\)/g));
            if (!matches.length) return null;

            // Tomar el último paréntesis (lo más común es que la duración vaya al final)
            const inside = String(matches[matches.length - 1][1] ?? '').trim().toLowerCase();
            if (!inside) return null;

            const text = inside.replace(/\s+/g, ' ');

            let totalMinutes = 0;
            let hasAny = false;

            // Soportar formatos: "1 hora", "1,5 horas", "2h", "30 min", "1 hora 30 min"
            const hourMatch = text.match(/(\d+(?:[\.,]\d+)?)\s*(h|hr|hrs|hora|horas)\b/);
            if (hourMatch) {
                const hours = Number(String(hourMatch[1]).replace(',', '.'));
                if (Number.isFinite(hours) && hours > 0) {
                    totalMinutes += hours * 60;
                    hasAny = true;
                }
            }

            const minuteMatch = text.match(/(\d+(?:[\.,]\d+)?)\s*(m|min|mins|minuto|minutos)\b/);
            if (minuteMatch) {
                const mins = Number(String(minuteMatch[1]).replace(',', '.'));
                if (Number.isFinite(mins) && mins > 0) {
                    totalMinutes += mins;
                    hasAny = true;
                }
            }

            // Soportar formato "1:30" => 1h30m
            if (!hasAny) {
                const hm = text.match(/(\d{1,2})\s*:\s*(\d{1,2})/);
                if (hm) {
                    const hh = Number(hm[1]);
                    const mm = Number(hm[2]);
                    if (Number.isFinite(hh) && Number.isFinite(mm) && hh >= 0 && mm >= 0) {
                        totalMinutes = hh * 60 + mm;
                        hasAny = true;
                    }
                }
            }

            if (!hasAny || !Number.isFinite(totalMinutes) || totalMinutes <= 0) return null;

            // Redondeo consistente (pasos de 5)
            return Math.round(totalMinutes / 5) * 5;
        }

        function bindTaskEstimateSync(row) {
            const minutesEl = row.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = row.querySelector('[data-field="estimated_hours"]');
            if (!minutesEl || !hoursEl) return;

            function setFromMinutes(minutes) {
                const raw = String(minutes ?? '').trim();
                if (raw === '') {
                    hoursEl.value = '';
                    return;
                }
                const m = Number(raw);
                if (!Number.isFinite(m) || m < 0) return;
                hoursEl.value = formatHoursFromMinutes(m);
            }

            // Inicializar: si hay horas y no hay minutos, derivar minutos
            if (!String(minutesEl.value || '').trim() && String(hoursEl.value || '').trim()) {
                const m = parseHoursToMinutes(hoursEl.value);
                if (m !== null) {
                    minutesEl.value = String(m);
                }
            }

            if (String(minutesEl.value || '').trim()) {
                setFromMinutes(minutesEl.value);
            }

            // Evitar cambios accidentales por rueda del mouse
            minutesEl.addEventListener('wheel', function(e) {
                if (document.activeElement === minutesEl) {
                    e.preventDefault();
                }
            }, { passive: false });

            minutesEl.addEventListener('input', function() {
                setFromMinutes(minutesEl.value);
                setEstimateUiState(row, { locked: false });
            });

            hoursEl.addEventListener('input', function() {
                const m = parseHoursToMinutes(hoursEl.value);
                if (m === null) return;
                minutesEl.value = String(m);
                setEstimateUiState(row, { locked: false });
            });

            // Estado inicial (manual)
            setEstimateUiState(row, { locked: false });
        }

        function bindEstimateChips(row) {
            const minutesEl = row.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = row.querySelector('[data-field="estimated_hours"]');
            if (!minutesEl || !hoursEl) return;

            const chips = Array.from(row.querySelectorAll('[data-estimate-chip]'));
            if (!chips.length) return;
            if (row.dataset.estimateChipsBound) return;
            row.dataset.estimateChipsBound = '1';

            function getMinutesValue() {
                const raw = String(minutesEl.value || '').trim();
                if (!raw) return 0;
                const m = Number(raw);
                return Number.isFinite(m) && m > 0 ? m : 0;
            }

            function setMinutesValue(value) {
                const m = Number(value);
                if (!Number.isFinite(m) || m < 0) return;
                minutesEl.value = m === 0 ? '' : String(m);
                minutesEl.dispatchEvent(new Event('input', { bubbles: true }));
                // mantener hint actualizado
                setEstimateUiState(row, { locked: false });
            }

            chips.forEach((btn) => {
                btn.addEventListener('click', function () {
                    if (btn.disabled) return;
                    const action = btn.getAttribute('data-estimate-chip');

                    if (action === 'clear') {
                        minutesEl.value = '';
                        hoursEl.value = '';
                        setEstimateUiState(row, { locked: false });
                        minutesEl.focus();
                        return;
                    }

                    const delta = Number(action);
                    if (!Number.isFinite(delta) || delta <= 0) return;

                    const next = getMinutesValue() + delta;
                    // redondear a 5 por consistencia
                    const rounded = Math.round(next / 5) * 5;
                    setMinutesValue(rounded);
                    minutesEl.focus();
                });
            });
        }

        function recalcTaskEstimateFromSubtasks(taskRow) {
            if (!taskRow) return;

            const minutesEl = taskRow.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = taskRow.querySelector('[data-field="estimated_hours"]');
            if (!minutesEl || !hoursEl) return;

            const subtaskRows = Array.from(taskRow.querySelectorAll('[data-subtask-row]'));
            let hasTitledSubtasks = false;
            let totalMinutes = 0;

            subtaskRows.forEach((stRow) => {
                const titleEl = stRow.querySelector('input[type="text"]');
                const title = String(titleEl?.value ?? '').trim();
                if (!title) return;

                hasTitledSubtasks = true;

                const stMinutesEl = stRow.querySelector('[data-subtask-field="estimated_minutes"]');
                const raw = String(stMinutesEl?.value ?? '').trim();

                // Consistente con el modelo: default 25 si está vacío
                let minutes = 25;
                if (raw !== '') {
                    const parsed = Number(raw);
                    if (Number.isFinite(parsed)) {
                        minutes = parsed;
                    }
                }

                if (minutes > 0) totalMinutes += minutes;
            });

            if (hasTitledSubtasks && totalMinutes > 0) {
                minutesEl.value = String(totalMinutes);
                hoursEl.value = formatHoursFromMinutes(totalMinutes);
                setEstimateUiState(taskRow, { locked: true, totalMinutes });
            } else {
                setEstimateUiState(taskRow, { locked: false });
            }
        }

        function bindSubtaskMinutes(subtaskRow) {
            const minutesEl = subtaskRow.querySelector('[data-subtask-field="estimated_minutes"]');
            if (!minutesEl) return;

            minutesEl.addEventListener('input', function() {
                // Marcar como editado manualmente (evitar pisar con autocompletado desde el título)
                if (minutesEl.dataset.programmatic === '1') {
                    delete minutesEl.dataset.programmatic;
                } else {
                    minutesEl.dataset.touched = '1';
                    // Si el usuario lo editó manualmente, permitir tabular (si aplica)
                    minutesEl.removeAttribute('tabindex');
                }
                const taskRow = subtaskRow.closest('[data-task-row]');
                recalcTaskEstimateFromSubtasks(taskRow);
            });
        }

        function setSubtaskTabOrderFromAutoMinutes(subtaskRow, { parsedMinutes } = {}) {
            if (!subtaskRow) return;
            const minutesEl = subtaskRow.querySelector('[data-subtask-field="estimated_minutes"]');
            if (!minutesEl) return;

            // Si fue autocompletado y el usuario aún no lo tocó, sacarlo del orden de tabulación
            if (minutesEl.dataset.touched !== '1' && Number.isFinite(Number(parsedMinutes))) {
                const current = Number(String(minutesEl.value ?? '').trim());
                if (Number.isFinite(current) && current === Number(parsedMinutes)) {
                    minutesEl.setAttribute('tabindex', '-1');
                }
            }
        }

        function createSubtaskRow(subtask = {}) {
            const el = document.createElement('div');
            el.setAttribute('data-subtask-row', '1');
            el.className = 'rounded-lg border border-gray-200 bg-gray-50 p-4';

            const title = (subtask.title ?? '').toString().replace(/\"/g, '&quot;');
            const notes = (subtask.notes ?? '').toString();
            const priority = (subtask.priority ?? 'medium');
            const estimatedMinutes = (subtask.estimated_minutes ?? 25);

            el.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                        <input type="text" data-subtask-name-template="tasks[__INDEX__][subtasks][__SINDEX__][title]" value="${title}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Ej: Validar accesos, revisar logs..." />
                    </div>
                    <button type="button" tabindex="-1" class="px-4 py-2.5 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50 font-semibold" data-remove-subtask>Eliminar</button>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Minutos estimados</label>
                        <div class="flex gap-2">
                            <input type="number" min="0" step="5" data-subtask-field="estimated_minutes" data-subtask-name-template="tasks[__INDEX__][subtasks][__SINDEX__][estimated_minutes]" value="${estimatedMinutes}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                        <select tabindex="-1" data-subtask-name-template="tasks[__INDEX__][subtasks][__SINDEX__][priority]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="high" ${priority === 'high' ? 'selected' : ''}>Alta</option>
                            <option value="medium" ${priority === 'medium' ? 'selected' : ''}>Media</option>
                            <option value="low" ${priority === 'low' ? 'selected' : ''}>Baja</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">Notas (opcional)</label>
                        <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-subtask-toggle-notes>Agregar notas</button>
                    </div>
                    <div class="mt-2 hidden" data-subtask-notes-section>
                        <textarea rows="2" data-subtask-notes data-subtask-name-template="tasks[__INDEX__][subtasks][__SINDEX__][notes]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Detalles o pasos para completar esta subtarea...">${notes}</textarea>
                    </div>
                </div>
            `;

            el.querySelector('[data-remove-subtask]')?.addEventListener('click', function() {
                const taskRow = el.closest('[data-task-row]');
                el.remove();
                reindexRows();
                recalcTaskEstimateFromSubtasks(taskRow);
            });

            bindSubtaskMinutes(el);

            // Solo contar subtareas con título; si cambia, recalcular
            const subtaskTitleEl = el.querySelector('input[type="text"]');
            const subtaskMinutesEl = el.querySelector('[data-subtask-field="estimated_minutes"]');

            // Si el título ya trae una duración y coincide con minutos, sacar el input del tab order
            if (subtaskTitleEl && subtaskMinutesEl) {
                const initialParsed = parseMinutesFromSubtaskTitle(subtaskTitleEl.value);
                if (initialParsed !== null) {
                    setSubtaskTabOrderFromAutoMinutes(el, { parsedMinutes: initialParsed });
                }
            }

            subtaskTitleEl?.addEventListener('input', function() {
                // Si el título trae duración entre paréntesis, usarla para minutos (si no se ha editado manualmente)
                if (subtaskMinutesEl && subtaskMinutesEl.dataset.touched !== '1') {
                    const parsed = parseMinutesFromSubtaskTitle(subtaskTitleEl.value);
                    if (parsed !== null) {
                        subtaskMinutesEl.value = String(parsed);
                        // disparar recálculo sin marcar touched
                        subtaskMinutesEl.dataset.programmatic = '1';
                        subtaskMinutesEl.dispatchEvent(new Event('input', { bubbles: true }));

                        // Si fue autocompletado, evitar tabular hacia este campo
                        setSubtaskTabOrderFromAutoMinutes(el, { parsedMinutes: parsed });
                    }
                }
                const taskRow = el.closest('[data-task-row]');
                recalcTaskEstimateFromSubtasks(taskRow);
            });

            // Notas opcionales (sutilmente ocultas)
            const notesSection = el.querySelector('[data-subtask-notes-section]');
            const notesToggle = el.querySelector('[data-subtask-toggle-notes]');
            const notesEl = el.querySelector('[data-subtask-notes]');

            function openNotes() {
                notesSection?.classList.remove('hidden');
                if (notesToggle) notesToggle.textContent = 'Ocultar notas';
            }

            function closeNotes() {
                notesSection?.classList.add('hidden');
                if (notesToggle) notesToggle.textContent = 'Agregar notas';
            }

            notesToggle?.addEventListener('click', function() {
                const isHidden = notesSection?.classList.contains('hidden');
                if (isHidden) {
                    openNotes();
                    setTimeout(() => notesEl?.focus(), 0);
                } else {
                    closeNotes();
                }
            });

            if (String(notes ?? '').trim()) {
                openNotes();
            }

            return el;
        }

        function reindexSubtasks(taskRow, taskIndex) {
            const subtaskRows = Array.from(taskRow.querySelectorAll('[data-subtask-row]'));
            subtaskRows.forEach((subtaskRow, subIndex) => {
                subtaskRow.querySelectorAll('[data-subtask-name-template]').forEach((input) => {
                    const tpl = input.getAttribute('data-subtask-name-template');
                    input.setAttribute('name', tpl.replace('__INDEX__', taskIndex).replace('__SINDEX__', subIndex));
                });
            });
        }

        function createRow(task = {}) {
            const row = document.createElement('div');
            row.setAttribute('data-task-row', '1');
            row.className = 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm';

            row.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                        <input type="text" data-field="title" data-name-template="tasks[__INDEX__][title]" value="${(task.title ?? '').replace(/\"/g, '&quot;')}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Ej: Revisar acceso, Configurar usuario, Validar evidencia" />
                    </div>
                    <button type="button" tabindex="-1" class="px-4 py-3 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50 font-semibold" data-remove-row>Eliminar</button>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
                        <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-toggle-description>Agregar descripción</button>
                    </div>
                    <div class="mt-2 hidden" data-description-section>
                        <textarea data-field="description" data-name-template="tasks[__INDEX__][description]" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Opcional (mín. 10 caracteres si se llena)">${(task.description ?? '')}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">Subtareas (opcional)</label>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Cantidad</label>
                                <select tabindex="-1" class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-subtask-count>
                                    <option value="1" selected>1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <button type="button" tabindex="-1" class="px-3 py-2 rounded-lg border border-gray-300 text-blue-700 hover:bg-blue-50 font-semibold" data-add-subtask>+ Agregar</button>
                            <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-toggle-subtasks>Ver subtareas</button>
                        </div>
                    </div>
                    <div class="mt-2 hidden" data-subtasks-section>
                        <div class="space-y-3" data-subtasks-list></div>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select data-field="type" data-name-template="tasks[__INDEX__][type]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="regular" ${(task.type ?? 'regular') === 'regular' ? 'selected' : ''}>Regular</option>
                            <option value="impact" ${(task.type ?? '') === 'impact' ? 'selected' : ''}>Impacto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                        <select data-field="priority" data-name-template="tasks[__INDEX__][priority]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="urgent" ${(task.priority ?? '') === 'urgent' ? 'selected' : ''}>Urgente</option>
                            <option value="high" ${(task.priority ?? '') === 'high' ? 'selected' : ''}>Alta</option>
                            <option value="medium" ${(task.priority ?? 'medium') === 'medium' ? 'selected' : ''}>Media</option>
                            <option value="low" ${(task.priority ?? '') === 'low' ? 'selected' : ''}>Baja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estimado</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <input type="number" min="0" step="5" data-field="estimated_minutes" data-name-template="tasks[__INDEX__][estimated_minutes]" value="${task.estimated_minutes ?? ''}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Minutos (Ej: 75)" />
                                <p class="mt-1 text-xs text-gray-500">Minutos (base)</p>
                            </div>
                            <div>
                                <input type="text" inputmode="decimal" data-field="estimated_hours" data-name-template="tasks[__INDEX__][estimated_hours]" value="${task.estimated_hours ?? ''}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Horas (Ej: 1.25 o 1,25)" />
                                <p class="mt-1 text-xs text-gray-500">Horas (opcional)</p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2" aria-label="Atajos de estimación">
                            <span class="text-[11px] text-gray-500">Atajos:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" data-estimate-chip="15" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Sumar 15 minutos">+15</button>
                                <button type="button" data-estimate-chip="30" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Sumar 30 minutos">+30</button>
                                <button type="button" data-estimate-chip="60" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Sumar 60 minutos">+60</button>
                                <button type="button" data-estimate-chip="120" class="px-2 py-1 rounded-md text-[11px] font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Sumar 120 minutos">+120</button>
                                <button type="button" data-estimate-chip="clear" class="ml-1 px-2 py-1 rounded-md text-[11px] font-semibold border border-gray-300 text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500" aria-label="Limpiar estimación">Limpiar</button>
                            </div>
                        </div>

                        <p class="mt-1 text-xs text-gray-500">Si hay subtareas con título, se calcula como suma (25 min por defecto si está vacío).</p>
                        <p class="mt-1 text-xs text-gray-600" data-estimate-hint></p>
                    </div>
                </div>

                <input type="hidden" data-field="standard_task_id" data-name-template="tasks[__INDEX__][standard_task_id]" value="${task.standard_task_id ?? ''}" />
            `;

            row.querySelector('[data-remove-row]')?.addEventListener('click', function() {
                row.remove();
                reindexRows();
            });

            bindTaskEstimateSync(row);
            bindEstimateChips(row);

            // Descripción opcional (toggle)
            const descSection = row.querySelector('[data-description-section]');
            const descToggle = row.querySelector('[data-toggle-description]');
            const descEl = row.querySelector('[data-field="description"]');

            function openDescription() {
                descSection?.classList.remove('hidden');
                if (descToggle) descToggle.textContent = 'Ocultar descripción';
            }

            function closeDescription() {
                descSection?.classList.add('hidden');
                if (descToggle) descToggle.textContent = 'Agregar descripción';
            }

            descToggle?.addEventListener('click', function() {
                const isHidden = descSection?.classList.contains('hidden');
                if (isHidden) {
                    openDescription();
                    setTimeout(() => descEl?.focus(), 0);
                } else {
                    closeDescription();
                }
            });

            // Si viene con descripción (old() o plantilla), mostrarla
            if (String(task.description ?? '').trim()) {
                openDescription();
            }

            const subtasksSection = row.querySelector('[data-subtasks-section]');
            const subtasksToggle = row.querySelector('[data-toggle-subtasks]');
            const subtasksList = row.querySelector('[data-subtasks-list]');
            const addSubtaskBtn = row.querySelector('[data-add-subtask]');
            const subtaskCountEl = row.querySelector('[data-subtask-count]');

            function openSubtasks() {
                subtasksSection?.classList.remove('hidden');
                if (subtasksToggle) subtasksToggle.textContent = 'Ocultar subtareas';
            }

            function closeSubtasks() {
                subtasksSection?.classList.add('hidden');
                if (subtasksToggle) subtasksToggle.textContent = 'Ver subtareas';
            }

            subtasksToggle?.addEventListener('click', function() {
                const isHidden = subtasksSection?.classList.contains('hidden');
                if (isHidden) {
                    openSubtasks();
                } else {
                    closeSubtasks();
                }
            });

            addSubtaskBtn?.addEventListener('click', function() {
                openSubtasks();
                const count = Math.max(1, Math.min(10, parseInt(String(subtaskCountEl?.value ?? '1'), 10) || 1));
                let firstRow = null;
                for (let i = 0; i < count; i++) {
                    const stRow = createSubtaskRow({});
                    if (!firstRow) firstRow = stRow;
                    subtasksList?.appendChild(stRow);
                }
                reindexRows();
                recalcTaskEstimateFromSubtasks(row);
                setTimeout(() => firstRow?.querySelector('input')?.focus(), 0);
            });

            if (Array.isArray(task.subtasks) && task.subtasks.length > 0) {
                openSubtasks();
                task.subtasks.forEach((st) => {
                    const stRow = createSubtaskRow(st || {});
                    subtasksList?.appendChild(stRow);
                });
            }

            // Autocalcular estimado si hay subtareas con minutos
            recalcTaskEstimateFromSubtasks(row);

            return row;
        }

        function addRow(task = {}, { focusTitle = false } = {}) {
            const row = createRow(task);
            tasksList.appendChild(row);
            reindexRows();
            if (focusTitle) {
                setTimeout(() => row.querySelector('[data-field="title"]')?.focus(), 0);
            }
        }

        function clearAllRows() {
            tasksList.innerHTML = '';
            reindexRows();
        }

        addRowBtn?.addEventListener('click', function() {
            openSection();
            addRow({}, { focusTitle: true });
        });

        clearBtn?.addEventListener('click', function() {
            clearAllRows();
            templateSelect.value = 'none';
            setNotice('');
        });

        formEl?.addEventListener('submit', function(e) {
            // Si hay tareas en pantalla, validar descripciones manuales.
            // (No bloquea tareas predefinidas con standard_task_id)
            const hasRows = tasksList.querySelector('[data-task-row]');
            if (!hasRows) {
                return;
            }

            const ok = validateTaskDescriptionsMinLen();
            if (!ok) {
                e.preventDefault();
                openSection();
                setNotice('Revisa los errores en las tareas antes de guardar.');
            }
        });

        async function loadTemplateSubServiceStandard() {
            const subServiceId = subServiceIdInput?.value;
            if (!subServiceId) {
                setNotice('Selecciona un subservicio para cargar la plantilla.');
                return;
            }

            setNotice('Cargando tareas predefinidas del subservicio...');
            try {
                const res = await fetch(`/api/sub-services/${subServiceId}/standard-tasks`);
                const data = await res.json();

                if (!Array.isArray(data) || data.length === 0) {
                    clearAllRows();
                    setNotice('Este subservicio no tiene tareas predefinidas configuradas.');
                    return;
                }

                clearAllRows();
                data.forEach((t) => {
                    const stdSubtasks = Array.isArray(t.standard_subtasks)
                        ? t.standard_subtasks
                        : (Array.isArray(t.standardSubtasks) ? t.standardSubtasks : []);

                    addRow({
                        title: t.title,
                        description: t.description,
                        type: t.type,
                        priority: t.priority,
                        estimated_hours: t.estimated_hours,
                        standard_task_id: t.id,
                        subtasks: Array.isArray(stdSubtasks)
                            ? stdSubtasks.map((sst) => ({
                                title: sst.title,
                                notes: sst.description,
                                priority: sst.priority,
                                estimated_minutes: 25,
                            }))
                            : [],
                    });
                });

                setNotice(`Plantilla cargada: ${data.length} tarea(s). Puedes editar o eliminar.`);
            } catch (e) {
                console.error(e);
                setNotice('No se pudo cargar la plantilla. Intenta nuevamente.');
            }
        }

        templateSelect?.addEventListener('change', async function() {
            openSection();
            const currentRows = Array.from(tasksList.querySelectorAll('[data-task-row]'));
            if (currentRows.length > 0) {
                const ok = confirm('Esto reemplazará las tareas actuales. ¿Continuar?');
                if (!ok) {
                    // revertir al valor anterior
                    templateSelect.value = 'none';
                    return;
                }
            }

            if (templateSelect.value === 'subservice_standard') {
                await loadTemplateSubServiceStandard();
            } else {
                setNotice('');
                // no borra automáticamente en manual; solo cambia plantilla
            }
        });

        // Si viene old() con tareas, renderizarlas y abrir sección
        if (Array.isArray(initialTasks) && initialTasks.length > 0) {
            openSection();
            initialTasks.forEach((t) => addRow(t));
        } else if (initialTemplate && initialTemplate !== 'none') {
            openSection();
        }

        // Si subservicio cambia y la plantilla actual es del subservicio, avisar.
        subServiceIdInput?.addEventListener('change', function() {
            if (templateSelect?.value === 'subservice_standard') {
                setNotice('El subservicio cambió. Vuelve a cargar la plantilla para actualizar las tareas.');
            }
        });

    });
    </script>
@endsection
