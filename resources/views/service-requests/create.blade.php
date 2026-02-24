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
    @keyframes fade-slide-in {
        from {
            opacity: 0;
            transform: translateY(6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .task-row-enter {
        animation: fade-slide-in 0.18s ease-out;
    }
    .task-row-leave {
        opacity: 0;
        transform: translateY(6px);
        transition: opacity 0.16s ease, transform 0.16s ease;
    }
</style>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-sm font-semibold text-red-800 mb-1">Revisa los campos obligatorios</h3>
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Paso 1 de 2</p>
                    <h2 class="text-xl font-bold text-gray-800">Datos de la solicitud</h2>
                    <p class="text-sm text-gray-600 mt-1">Los campos marcados con * son obligatorios.</p>
                </div>
                <div class="p-6">
                    @include('components.service-requests.forms.basic-fields', [
                        'subServices' => $subServices,
                        'selectedSubService' => $selectedSubService ?? null,
                        'requesters' => $requesters,
                        'companies' => $companies ?? [],
                        'cuts' => $cuts ?? [],
                        'errors' => $errors,
                        'mode' => 'create',
                    ])
                </div>
            </div>

            <!-- Tareas (opcional) -->
            <div class="mt-6 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <button type="button" id="toggleTasksSection" class="group w-full rounded-2xl px-6 py-4 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 transition border-b border-blue-100 focus:outline-none focus:bg-blue-100 focus:border-blue-600 focus:ring-4 focus:ring-inset focus:ring-blue-600/35 focus:shadow-md focus-visible:bg-blue-100 focus-visible:border-blue-600 focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-600/35">
                    <div class="text-left">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 group-focus:text-blue-900 group-focus-visible:text-blue-900">Paso 2 de 2</p>
                        <div class="text-lg font-bold text-gray-800 group-focus:text-blue-950 group-focus-visible:text-blue-950">Tareas</div>
                        <div class="text-gray-600 text-sm group-focus:text-blue-800 group-focus-visible:text-blue-800">Agrega tareas ahora o deja la solicitud solo con descripción.</div>
                    </div>
                    <span id="tasksChevron" class="text-gray-500 group-focus:text-blue-900 group-focus-visible:text-blue-900">▾</span>
                </button>

                <div id="tasksSectionBody" class="hidden p-6">
                    <div class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1">
                        <div class="flex items-center gap-2 min-w-max">
                            <label for="tasks_template" class="text-sm font-medium text-gray-700 whitespace-nowrap">Plantilla</label>
                            <div class="relative group">
                                <button type="button"
                                        tabindex="-1"
                                        class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 text-xs text-gray-500 hover:text-blue-700 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        aria-label="Ayuda sobre plantillas"
                                        title="Si eliges una plantilla, se cargarán tareas sugeridas que podrás editar.">
                                    <i class="fas fa-question"></i>
                                </button>
                                <div class="pointer-events-none absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-72 rounded-lg bg-gray-900 text-white text-xs px-3 py-2 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity shadow-lg z-20">
                                    Si eliges una plantilla, se cargarán tareas sugeridas que podrás editar.
                                </div>
                            </div>
                            <select id="tasks_template" name="tasks_template" tabindex="-1" class="w-[280px] px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="none" {{ old('tasks_template', 'none') === 'none' ? 'selected' : '' }}>Ninguna (manual)</option>
                                <option value="subservice_standard" {{ old('tasks_template') === 'subservice_standard' ? 'selected' : '' }}>Tareas predefinidas del subservicio</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 min-w-max ml-auto">
                            <button type="button" id="addTaskRow" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold whitespace-nowrap">
                                + Agregar tarea
                            </button>
                            <button type="button" id="clearTasks" class="px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold whitespace-nowrap">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div id="tasksDraftNotice" class="hidden mt-3 p-3 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-900 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span>Se encontró un borrador guardado automáticamente.</span>
                            <div class="flex items-center gap-2">
                                <button type="button" id="restoreTasksDraft" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 font-semibold">Recuperar</button>
                                <button type="button" id="discardTasksDraft" class="px-3 py-1.5 rounded-md border border-indigo-300 text-indigo-800 hover:bg-indigo-100 font-semibold">Descartar</button>
                            </div>
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
                <p id="createFormInlineError" class="hidden mt-2 text-sm text-red-600 font-medium"></p>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formEl = document.querySelector('form[action="{{ route('service-requests.store') }}"]');
        const inlineErrorEl = document.getElementById('createFormInlineError');
        let createConfirmed = false;

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
        const tasksDraftNotice = document.getElementById('tasksDraftNotice');
        const restoreTasksDraftBtn = document.getElementById('restoreTasksDraft');
        const discardTasksDraftBtn = document.getElementById('discardTasksDraft');
        const tasksSummaryCount = document.querySelector('[data-summary-count]');
        const tasksSummaryTime = document.querySelector('[data-summary-time]');
        const subServiceIdInput = document.getElementById('sub_service_id');
        const DRAFT_KEY = 'sr_create_tasks_draft_v1';
        let autosaveTimer = null;

        const initialTasks = @json(old('tasks', []));
        const initialTemplate = @json(old('tasks_template', 'none'));

        function setFieldValidity(el, ok) {
            if (!el) return;
            el.classList.remove('border-red-500');
            if (!ok) el.classList.add('border-red-500');
        }

        function validateMainFields() {
            const title = document.getElementById('title');
            const description = document.getElementById('description');
            const requester = document.getElementById('requester_id');
            const subService = document.getElementById('sub_service_id');
            const entryChannel = document.getElementById('entry_channel');

            const checks = [
                { el: title, ok: !!title?.value?.trim(), label: 'Título' },
                { el: description, ok: !!description?.value?.trim(), label: 'Descripción' },
                { el: requester, ok: !!requester?.value, label: 'Solicitante' },
                { el: subService, ok: !!subService?.value, label: 'Subservicio' },
                { el: entryChannel, ok: !!entryChannel?.value, label: 'Canal de ingreso' },
            ];

            checks.forEach(({ el, ok }) => setFieldValidity(el, ok));
            const missing = checks.filter(c => !c.ok).map(c => c.label);
            return { valid: missing.length === 0, missing };
        }

        function buildSummaryText() {
            const title = document.getElementById('title')?.value?.trim() || '(sin título)';
            const requester = document.getElementById('requester_id');
            const subService = document.getElementById('sub_service_id');
            const channel = document.getElementById('entry_channel');
            const cut = document.getElementById('cut_id');
            const tasksCount = document.querySelectorAll('#tasksList [data-task-row]').length;

            const requesterText = requester?.selectedOptions?.[0]?.textContent?.trim() || 'Sin solicitante';
            const subServiceText = subService?.selectedOptions?.[0]?.textContent?.trim() || 'Sin subservicio';
            const channelText = channel?.selectedOptions?.[0]?.textContent?.trim() || 'Sin canal';
            const cutText = cut?.selectedOptions?.[0]?.textContent?.trim() || 'Sin corte';

            return [
                'Resumen de la solicitud:',
                `- Título: ${title}`,
                `- Solicitante: ${requesterText}`,
                `- Subservicio: ${subServiceText}`,
                `- Canal: ${channelText}`,
                `- Corte: ${cutText}`,
                `- Tareas: ${tasksCount}`,
                '',
                '¿Deseas crear la solicitud?'
            ].join('\n');
        }

        function setNotice(message) {
            if (!message) {
                notice.classList.add('hidden');
                notice.textContent = '';
                return;
            }
            notice.textContent = message;
            notice.classList.remove('hidden');
        }

        function updateTaskSummary() {
            const rows = Array.from(tasksList.querySelectorAll('[data-task-row]'));
            let totalMinutes = 0;

            rows.forEach((row) => {
                const rawMinutes = String(row.querySelector('[data-field="estimated_minutes"]')?.value ?? '').trim();
                const rawHours = String(row.querySelector('[data-field="estimated_hours"]')?.value ?? '').trim();
                const parsedMinutes = rawMinutes !== ''
                    ? Number(rawMinutes)
                    : parseDurationToMinutes(rawHours);

                if (Number.isFinite(parsedMinutes) && parsedMinutes > 0) {
                    totalMinutes += parsedMinutes;
                }
            });

            if (tasksSummaryCount) tasksSummaryCount.textContent = String(rows.length);
            if (tasksSummaryTime) tasksSummaryTime.textContent = formatHumanDuration(totalMinutes) || '0m';
        }

        function collectDraftState() {
            const rows = Array.from(tasksList.querySelectorAll('[data-task-row]')).map((row) => getRowData(row));
            return {
                title: document.getElementById('title')?.value ?? '',
                description: document.getElementById('description')?.value ?? '',
                requester_id: document.getElementById('requester_id')?.value ?? '',
                sub_service_id: document.getElementById('sub_service_id')?.value ?? '',
                entry_channel: document.getElementById('entry_channel')?.value ?? '',
                cut_id: document.getElementById('cut_id')?.value ?? '',
                tasks_template: templateSelect?.value ?? 'none',
                tasks: rows,
                saved_at: new Date().toISOString(),
            };
        }

        function saveDraftNow() {
            try {
                const payload = collectDraftState();
                localStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
            } catch (e) {
                // no-op
            }
        }

        function scheduleDraftSave() {
            if (autosaveTimer) clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(saveDraftNow, 2200);
        }

        function clearDraft() {
            try {
                localStorage.removeItem(DRAFT_KEY);
            } catch (e) {
                // no-op
            }
        }

        function readDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (e) {
                return null;
            }
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

        function normalizeTaskDurationsBeforeSubmit() {
            const rows = Array.from(tasksList.querySelectorAll('[data-task-row]'));
            rows.forEach((row) => {
                const minutesEl = row.querySelector('[data-field="estimated_minutes"]');
                const hoursEl = row.querySelector('[data-field="estimated_hours"]');
                if (!minutesEl || !hoursEl) return;

                let minutes = Number(String(minutesEl.value ?? '').trim());
                if (!Number.isFinite(minutes) || minutes < 0) {
                    minutes = parseDurationToMinutes(hoursEl.value);
                }

                if (Number.isFinite(minutes) && minutes > 0) {
                    const rounded = Math.round(minutes / 5) * 5;
                    minutesEl.value = String(rounded);
                    hoursEl.value = formatHoursFromMinutes(rounded);
                } else {
                    minutesEl.value = '';
                    hoursEl.value = '';
                }
            });
        }

        toggleBtn?.addEventListener('click', function() {
            isOpen() ? closeSection() : openSection();
        });

        ['title', 'description', 'requester_id', 'sub_service_id', 'entry_channel'].forEach((id) => {
            const field = document.getElementById(id);
            if (!field) return;
            field.addEventListener('input', validateMainFields);
            field.addEventListener('change', validateMainFields);
            field.addEventListener('input', scheduleDraftSave);
            field.addEventListener('change', scheduleDraftSave);
        });
        document.getElementById('cut_id')?.addEventListener('change', scheduleDraftSave);

        function getRowData(rowEl) {
            const subtasks = Array.from(rowEl.querySelectorAll('[data-subtask-row]')).map((stRow) => ({
                title: stRow.querySelector('input[type="text"]')?.value ?? '',
                estimated_minutes: stRow.querySelector('[data-subtask-field="estimated_minutes"]')?.value ?? '',
                priority: stRow.querySelector('select')?.value ?? 'medium',
                notes: stRow.querySelector('[data-subtask-notes]')?.value ?? '',
            }));

            return {
                title: rowEl.querySelector('[data-field="title"]')?.value ?? '',
                description: rowEl.querySelector('[data-field="description"]')?.value ?? '',
                type: rowEl.querySelector('[data-field="type"]')?.value ?? 'regular',
                priority: rowEl.querySelector('[data-field="priority"]')?.value ?? 'medium',
                estimated_minutes: rowEl.querySelector('[data-field="estimated_minutes"]')?.value ?? '',
                estimated_hours: rowEl.querySelector('[data-field="estimated_hours"]')?.value ?? '',
                standard_task_id: rowEl.querySelector('[data-field="standard_task_id"]')?.value ?? '',
                subtasks,
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
            if (!minutesEl || !hoursEl) return;

            // Sin bloquear inputs: solo mantener sincronía entre minutos y horas.
            if (locked && Number.isFinite(Number(totalMinutes)) && Number(totalMinutes) > 0) {
                minutesEl.value = String(Math.round(Number(totalMinutes) / 5) * 5);
                hoursEl.value = formatHoursFromMinutes(minutesEl.value);
            }
        }

        function parseDurationToMinutes(rawValue) {
            const raw = String(rawValue ?? '').trim().toLowerCase();
            if (!raw) return null;

            const normalized = raw.replace(',', '.').replace(/\s+/g, ' ');

            // 1:30
            const hmMatch = normalized.match(/^(\d{1,2})\s*:\s*(\d{1,2})$/);
            if (hmMatch) {
                const hh = Number(hmMatch[1]);
                const mm = Number(hmMatch[2]);
                if (Number.isFinite(hh) && Number.isFinite(mm) && hh >= 0 && mm >= 0) {
                    return Math.round((hh * 60 + mm) / 5) * 5;
                }
            }

            // 90m | 1.5h | 1h 30m
            let total = 0;
            let hasToken = false;
            const hourToken = normalized.match(/(\d+(?:\.\d+)?)\s*(h|hr|hrs|hora|horas)\b/);
            if (hourToken) {
                const h = Number(hourToken[1]);
                if (Number.isFinite(h) && h >= 0) {
                    total += h * 60;
                    hasToken = true;
                }
            }
            const minuteToken = normalized.match(/(\d+(?:\.\d+)?)\s*(m|min|mins|minuto|minutos)\b/);
            if (minuteToken) {
                const m = Number(minuteToken[1]);
                if (Number.isFinite(m) && m >= 0) {
                    total += m;
                    hasToken = true;
                }
            }
            if (hasToken) {
                return total > 0 ? (Math.round(total / 5) * 5) : null;
            }

            // Valor decimal sin sufijo = horas.
            const asNumber = Number(normalized);
            if (Number.isFinite(asNumber) && asNumber >= 0) {
                const minutes = Math.round(asNumber * 60);
                return Math.round(minutes / 5) * 5;
            }

            return null;
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
            const displayEl = row.querySelector('[data-field="estimated_display"]');
            const minutesEl = row.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = row.querySelector('[data-field="estimated_hours"]');
            const unitEl = row.querySelector('[data-estimate-unit]');
            if (!displayEl || !minutesEl || !hoursEl || !unitEl) return;

            function setHoursFromMinutes(minutes) {
                const raw = String(minutes ?? '').trim();
                if (raw === '') {
                    hoursEl.value = '';
                    return;
                }
                const m = Number(raw);
                if (!Number.isFinite(m) || m < 0) return;
                hoursEl.value = formatHoursFromMinutes(m);
            }

            function renderDisplayFromMinutes() {
                const unit = unitEl.value;
                const rawMinutes = String(minutesEl.value || '').trim();
                const minutes = rawMinutes === '' ? null : Number(rawMinutes);

                if (unit === 'hours') {
                    displayEl.step = '0.25';
                    displayEl.placeholder = 'Horas (Ej: 1.5)';
                    displayEl.value = (minutes !== null && Number.isFinite(minutes))
                        ? formatHoursFromMinutes(minutes)
                        : '';
                } else {
                    displayEl.step = '5';
                    displayEl.placeholder = 'Minutos (Ej: 75)';
                    displayEl.value = (minutes !== null && Number.isFinite(minutes))
                        ? String(Math.round(minutes))
                        : '';
                }
            }

            function parseDisplayToMinutes() {
                const raw = String(displayEl.value || '').trim();
                if (!raw) return null;

                if (unitEl.value === 'hours') {
                    return parseDurationToMinutes(raw);
                }

                const parsed = Number(raw);
                if (!Number.isFinite(parsed) || parsed < 0) return null;
                return Math.round(parsed / 5) * 5;
            }

            if (!String(minutesEl.value || '').trim() && String(hoursEl.value || '').trim()) {
                const m = parseDurationToMinutes(hoursEl.value);
                if (m !== null) {
                    minutesEl.value = String(m);
                }
            }

            // Estado inicial (manual)
            setEstimateUiState(row, { locked: false });
            setHoursFromMinutes(minutesEl.value);
            renderDisplayFromMinutes();

            displayEl.addEventListener('input', function() {
                const m = parseDisplayToMinutes();
                minutesEl.value = m === null ? '' : String(m);
                setHoursFromMinutes(minutesEl.value);
                setEstimateUiState(row, { locked: false });
                updateTaskSummary();
                scheduleDraftSave();
            });

            unitEl.addEventListener('change', function() {
                const m = parseDisplayToMinutes();
                minutesEl.value = m === null ? (String(minutesEl.value || '').trim() || '') : String(m);
                setHoursFromMinutes(minutesEl.value);
                renderDisplayFromMinutes();
                updateTaskSummary();
                scheduleDraftSave();
            });

            // Permite refrescar visual desde otras funciones (ej. subtareas)
            row.__renderEstimateDisplay = renderDisplayFromMinutes;
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
                        updateTaskSummary();
                        scheduleDraftSave();
                        hoursEl.focus();
                        return;
                    }

                    const delta = Number(action);
                    if (!Number.isFinite(delta) || delta <= 0) return;

                    const next = getMinutesValue() + delta;
                    // redondear a 5 por consistencia
                    const rounded = Math.round(next / 5) * 5;
                    setMinutesValue(rounded);
                    hoursEl.focus();
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
            if (typeof taskRow.__renderEstimateDisplay === 'function') {
                taskRow.__renderEstimateDisplay();
            }

            updateTaskSummary();
            scheduleDraftSave();
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
            el.className = 'rounded-lg border border-gray-200 bg-[#f5f5f5] p-3.5';

            const title = (subtask.title ?? '').toString().replace(/\"/g, '&quot;');
            const notes = (subtask.notes ?? '').toString();
            const hasNotes = notes.trim().length > 0;
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

                <div class="mt-2.5 border-t border-gray-100"></div>

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

                <div class="mt-3 flex items-center justify-end">
                    <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-subtask-toggle-notes>${hasNotes ? 'Ocultar notas' : 'Agregar notas'}</button>
                </div>

                <div class="mt-2 ${hasNotes ? '' : 'hidden'}" data-subtask-notes-section>
                    <textarea rows="2" data-subtask-notes data-subtask-name-template="tasks[__INDEX__][subtasks][__SINDEX__][notes]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Detalles o pasos para completar esta subtarea...">${notes}</textarea>
                </div>
            `;

            el.querySelector('[data-remove-subtask]')?.addEventListener('click', function() {
                const taskRow = el.closest('[data-task-row]');
                el.remove();
                reindexRows();
                recalcTaskEstimateFromSubtasks(taskRow);
            });

            bindSubtaskMinutes(el);

            const toggleNotesBtn = el.querySelector('[data-subtask-toggle-notes]');
            const notesSection = el.querySelector('[data-subtask-notes-section]');
            toggleNotesBtn?.addEventListener('click', function() {
                if (!notesSection) return;
                const isHidden = notesSection.classList.toggle('hidden');
                toggleNotesBtn.textContent = isHidden ? 'Agregar notas' : 'Ocultar notas';
            });

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
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <button type="button" tabindex="-1" class="px-4 py-3 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50 font-semibold" data-remove-row>Eliminar</button>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-end">
                        <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-toggle-description>Agregar descripción</button>
                    </div>
                    <div class="mt-2 hidden" data-description-section>
                        <textarea data-field="description" data-name-template="tasks[__INDEX__][description]" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Descripción de la tarea (opcional)">${(task.description ?? '')}</textarea>
                    </div>
                </div>
                <div class="mt-4 border-t border-gray-200"></div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">Subtareas (opcional)</label>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Cantidad</label>
                                <select class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-subtask-count>
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
                            <button type="button" class="px-3 py-2 rounded-lg border border-gray-300 text-blue-700 hover:bg-blue-50 font-semibold" data-add-subtask>+ Agregar</button>
                            <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-toggle-subtasks>Ver subtareas</button>
                        </div>
                    </div>
                    <div class="mt-2 hidden" data-subtasks-section>
                        <div class="space-y-2.5 rounded-xl border border-gray-100 bg-[#f5f5f5] p-2.5" data-subtasks-list></div>
                    </div>
                </div>

                <div class="mt-4 border-t border-gray-200"></div>

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
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_7rem] gap-2 min-w-0">
                                <input type="number" min="0" step="5" inputmode="decimal" data-field="estimated_display" value="${task.estimated_minutes ?? ''}" class="w-full min-w-0 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="75" />
                                <select data-estimate-unit class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="minutes" selected>Min</option>
                                    <option value="hours">Horas</option>
                                </select>
                            </div>
                            <input type="hidden" data-field="estimated_minutes" data-name-template="tasks[__INDEX__][estimated_minutes]" value="${task.estimated_minutes ?? ''}" />
                            <input type="hidden" data-field="estimated_hours" data-name-template="tasks[__INDEX__][estimated_hours]" value="${task.estimated_hours ?? ''}" />
                        </div>

                    </div>
                </div>

                <input type="hidden" data-field="standard_task_id" data-name-template="tasks[__INDEX__][standard_task_id]" value="${task.standard_task_id ?? ''}" />
            `;

            row.querySelector('[data-remove-row]')?.addEventListener('click', function() {
                row.classList.add('task-row-leave');
                setTimeout(() => {
                    row.remove();
                    reindexRows();
                    updateTaskSummary();
                    scheduleDraftSave();
                }, 160);
            });

            bindTaskEstimateSync(row);
            bindEstimateChips(row);

            // Descripción opcional (sin título visible)
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

            if (String(task.description ?? '').trim()) {
                openDescription();
            }

            const subtasksSection = row.querySelector('[data-subtasks-section]');
            const subtasksToggle = row.querySelector('[data-toggle-subtasks]');
            const subtasksList = row.querySelector('[data-subtasks-list]');
            const addSubtaskBtn = row.querySelector('[data-add-subtask]');
            const subtaskCountEl = row.querySelector('[data-subtask-count]');
            const titleEl = row.querySelector('[data-field="title"]');

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

            titleEl?.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.ctrlKey) {
                    e.preventDefault();
                    setTimeout(() => row.querySelector('[data-field="type"]')?.focus(), 0);
                    return;
                }
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    addRow({}, { focusTitle: true });
                }
            });

            subtaskCountEl?.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                addSubtaskBtn?.click();
            });

            row.querySelector('[data-field="type"]')?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    setTimeout(() => row.querySelector('[data-field="priority"]')?.focus(), 0);
                }
            });

            row.querySelector('[data-field="priority"]')?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    setTimeout(() => row.querySelector('[data-field="estimated_display"]')?.focus(), 0);
                }
            });

            row.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    addRow({}, { focusTitle: true });
                }
            });

            if (Array.isArray(task.subtasks) && task.subtasks.length > 0) {
                openSubtasks();
                task.subtasks.forEach((st) => {
                    const stRow = createSubtaskRow(st || {});
                    subtasksList?.appendChild(stRow);
                });
            }

            row.querySelectorAll('input, textarea, select').forEach((inputEl) => {
                inputEl.addEventListener('input', scheduleDraftSave);
                inputEl.addEventListener('change', scheduleDraftSave);
            });

            // Autocalcular estimado si hay subtareas con minutos
            recalcTaskEstimateFromSubtasks(row);

            return row;
        }

        function addRow(task = {}, { focusTitle = false } = {}) {
            const row = createRow(task);
            row.classList.add('task-row-enter');
            tasksList.appendChild(row);
            reindexRows();
            updateTaskSummary();
            scheduleDraftSave();
            if (focusTitle) {
                setTimeout(() => row.querySelector('[data-field="title"]')?.focus(), 0);
            }
        }

        function clearAllRows() {
            tasksList.innerHTML = '';
            reindexRows();
            updateTaskSummary();
            scheduleDraftSave();
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
            scheduleDraftSave();
        });

        function applyDraftState(draft) {
            if (!draft || typeof draft !== 'object') return;

            const setValue = (id, value) => {
                const el = document.getElementById(id);
                if (!el || value === undefined || value === null) return;
                el.value = String(value);
                el.dispatchEvent(new Event('change', { bubbles: true }));
            };

            setValue('title', draft.title);
            setValue('description', draft.description);
            setValue('requester_id', draft.requester_id);
            setValue('sub_service_id', draft.sub_service_id);
            setValue('entry_channel', draft.entry_channel);
            setValue('cut_id', draft.cut_id);
            setValue('tasks_template', draft.tasks_template || 'none');

            clearAllRows();
            if (Array.isArray(draft.tasks)) {
                draft.tasks.forEach((t) => addRow(t));
            }
            if (Array.isArray(draft.tasks) && draft.tasks.length > 0) {
                openSection();
            }

            updateTaskSummary();
            setNotice('Borrador recuperado correctamente.');
        }

        restoreTasksDraftBtn?.addEventListener('click', function() {
            const draft = readDraft();
            if (!draft) return;
            applyDraftState(draft);
            tasksDraftNotice?.classList.add('hidden');
        });

        discardTasksDraftBtn?.addEventListener('click', function() {
            clearDraft();
            tasksDraftNotice?.classList.add('hidden');
        });

        document.addEventListener('keydown', function(e) {
            if (!(e.ctrlKey && e.key === 'Enter')) return;
            const target = e.target;
            const insideForm = target instanceof Element && formEl?.contains(target);
            if (!insideForm) return;
            e.preventDefault();
            openSection();
            addRow({}, { focusTitle: true });
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

        if (formEl) {
            formEl.addEventListener('submit', function(e) {
                if (createConfirmed) return;
                normalizeTaskDurationsBeforeSubmit();

                const validation = validateMainFields();
                if (!validation.valid) {
                    e.preventDefault();
                    if (inlineErrorEl) {
                        inlineErrorEl.textContent = `Completa los campos obligatorios: ${validation.missing.join(', ')}.`;
                        inlineErrorEl.classList.remove('hidden');
                    }

                    const firstInvalid = formEl.querySelector('.border-red-500');
                    firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid?.focus();
                    return;
                }

                if (inlineErrorEl) {
                    inlineErrorEl.classList.add('hidden');
                    inlineErrorEl.textContent = '';
                }

                e.preventDefault();
                const confirmed = window.confirm(buildSummaryText());
                if (!confirmed) return;

                createConfirmed = true;
                clearDraft();
                formEl.submit();
            });
        }

        // No mostrar aviso de borrador en solicitudes nuevas.
        tasksDraftNotice?.classList.add('hidden');

        window.addEventListener('beforeunload', saveDraftNow);
        updateTaskSummary();

    });
    </script>
@endsection
