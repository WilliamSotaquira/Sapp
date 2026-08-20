@extends('layouts.app')

@section('title', 'Nueva Solicitud de Servicio')

@section('content')
<style>
    @keyframes scale-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-scale-in { animation: scale-in 0.2s ease-out; }

    @keyframes fade-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 0.25s ease-out; }

    @keyframes fade-slide-in {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .task-row-enter { animation: fade-slide-in 0.18s ease-out; }
    .task-row-leave {
        opacity: 0;
        transform: translateY(6px);
        transition: opacity 0.16s ease, transform 0.16s ease;
    }

    /* Parser pre-filled field highlighting */
    .parser-prefilled {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.2);
        background-color: rgba(238, 242, 255, 0.5);
    }
    .parser-prefilled-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        background-color: #eef2ff;
        border: 1px solid #c7d2fe;
        color: #4338ca;
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .parser-prefilled-badge i { font-size: 0.5rem; }

    .pending-requester-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        background-color: #fef3c7;
        border: 1px solid #fcd34d;
        color: #92400e;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Interpreting spinner pulse */
    @keyframes pulse-ring {
        0% { transform: scale(0.95); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.7; }
        100% { transform: scale(0.95); opacity: 1; }
    }
    .interpreting-pulse { animation: pulse-ring 1.5s ease-in-out infinite; }
</style>

    @php
        $plainTextImportValue = old('plain_text_import_text', '');
        $shouldOpenPlainTextImport = (bool) old('__open_plain_text_import', false) || session()->has('plain_text_import_error');
        $pendingRequesterName = old('__pending_requester_name', '');
        $pendingRequesterEmail = old('__pending_requester_email', '');

        // Determine which fields were pre-filled by the parser
        $parserPrefilledFields = [];
        if (session('success') && str_contains(session('success') ?? '', 'Texto interpretado')) {
            $checkFields = ['title', 'description', 'entry_channel', 'sub_service_id', 'requester_id', 'created_at', 'due_date', 'criticality_level', 'web_routes'];
            foreach ($checkFields as $field) {
                if (old($field) !== null && old($field) !== '') {
                    $parserPrefilledFields[] = $field;
                }
            }
        }

        // Determine initial step
        $selectedRequestTypeId = old('request_type_id', '');
        $selectedSlug = '';
        if ($selectedRequestTypeId) {
            $selectedType = ($requestTypes ?? collect())->firstWhere('id', (int) $selectedRequestTypeId);
            $selectedSlug = $selectedType ? $selectedType->slug : '';
        }
        $startAtStep2 = $errors->any() || $selectedRequestTypeId || !empty($parserPrefilledFields) || old('title');
    @endphp

    @if ($errors->any())
        <div class="mb-6 max-w-4xl mx-auto p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-sm font-semibold text-red-800 mb-1">Revisa los campos obligatorios</h3>
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 max-w-4xl mx-auto p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 max-w-4xl mx-auto p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Workspace switch form (if needed from interpreter error) --}}
    @if (session('plain_text_import_suggested_workspace_id'))
        <form method="POST" action="{{ route('workspaces.switch') }}" id="switchWorkspaceForm" class="hidden">
            @csrf
            <input type="hidden" name="company_id" value="{{ session('plain_text_import_suggested_workspace_id') }}">
            <input type="hidden" name="redirect_to" value="/service-requests/create">
            <input type="hidden" name="preserve_text" value="{{ $plainTextImportValue }}">
        </form>
    @endif

    {{-- ===== MAIN CONTENT WITH ALPINE.JS — AI-FIRST FLOW ===== --}}
    <div x-data='{"step":{{ $startAtStep2 ? 2 : 1 }},"interpreting":false,"pasteText":{{ json_encode($plainTextImportValue ?: '') }},"operatorNotes":"","selectedTypeId":{{ json_encode($selectedRequestTypeId ?: '') }},"selectedTypeSlug":{{ json_encode($selectedSlug ?: '') }}}' class="max-w-4xl mx-auto">

        {{-- ===== STATE 1: PASTE & INTERPRET (AI-first hero) ===== --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex flex-col items-center justify-center min-h-[60vh] py-12">
                {{-- Hero header --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 mb-4">
                        <i class="fas fa-clipboard-list text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Nueva Solicitud</h1>
                    <p class="mt-2 text-gray-500 max-w-md mx-auto">
                        Pega el texto de la solicitud y la IA se encargará de clasificar y llenar los campos automáticamente.
                    </p>
                </div>

                {{-- Paste form --}}
                <div class="w-full max-w-2xl">
                    <form action="{{ route('service-requests.prefill-from-text') }}" method="POST" id="aiInterpreterForm" x-on:submit="interpreting = true">
                        @csrf

                        @if (session('plain_text_import_error'))
                            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-exclamation-circle mt-0.5 text-red-500"></i>
                                    <span>{{ session('plain_text_import_error') }}</span>
                                </div>
                                @if (session('plain_text_import_suggested_workspace_id'))
                                    <div class="mt-3">
                                        <button
                                            type="button"
                                            id="switchWorkspaceBtn"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-red-700 transition"
                                        >
                                            <i class="fas fa-exchange-alt"></i>
                                            Cambiar a {{ session('plain_text_import_suggested_workspace_name') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="relative">
                            <textarea
                                name="plain_text"
                                id="plain_text"
                                rows="10"
                                x-model="pasteText"
                                x-on:keydown.ctrl.enter.prevent="if(!$event.shiftKey && pasteText.length >= 20) { interpreting = true; $el.closest('form').submit(); }"
                                x-on:keydown.ctrl.shift.enter.prevent="if(pasteText.length >= 20) { interpreting = true; $refs.fastCreateForm.submit(); }"
                                x-on:keydown.meta.enter.prevent="if(!$event.shiftKey && pasteText.length >= 20) { interpreting = true; $el.closest('form').submit(); }"
                                x-on:keydown.meta.shift.enter.prevent="if(pasteText.length >= 20) { interpreting = true; $refs.fastCreateForm.submit(); }"
                                class="w-full rounded-2xl border-2 border-gray-200 px-5 py-4 text-base text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 transition-all duration-200 resize-none"
                                placeholder="Pega aquí el correo, mensaje de WhatsApp o texto de la solicitud..."
                                required
                                autofocus
                            ></textarea>

                            {{-- Interpreting overlay --}}
                            <div x-show="interpreting" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 rounded-2xl backdrop-blur-sm">
                                <svg class="animate-spin h-8 w-8 text-blue-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium text-blue-700 interpreting-pulse">Interpretando texto...</span>
                            </div>
                        </div>

                        {{-- Operator instructions (optional, collapsible) --}}
                        <div class="mt-3" x-show="pasteText.length >= 20" x-cloak x-transition>
                            <button type="button"
                                    @click="$refs.operatorNotesField.classList.toggle('hidden'); if(!$refs.operatorNotesField.classList.contains('hidden')) $refs.operatorNotesInput.focus()"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-blue-600 transition-colors">
                                <i class="fas fa-comment-dots text-[10px]"></i>
                                <span>Agregar indicaciones para la IA</span>
                                <i class="fas fa-chevron-down text-[8px]"></i>
                            </button>
                            <div x-ref="operatorNotesField" class="{{ old('operator_notes') ? '' : 'hidden' }} mt-2">
                                <textarea
                                    name="operator_notes"
                                    x-ref="operatorNotesInput"
                                    x-model="operatorNotes"
                                    rows="2"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none"
                                    placeholder="Ej: &quot;Ya fue resuelto, solo verificar publicación&quot; o &quot;Tomar solo el segundo correo del hilo&quot;"
                                ></textarea>
                                <p class="mt-1 text-[11px] text-gray-400">Estas indicaciones guían a la IA al interpretar el texto.</p>
                            </div>
                        </div>

                        {{-- Character count + submit --}}
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-sm tabular-nums transition-colors"
                                  :class="pasteText.length < 20 ? 'text-amber-600' : 'text-green-600'">
                                <span x-text="pasteText.length"></span> caracteres
                            </span>

                            <div class="flex items-center gap-2">
                                <button
                                    type="submit"
                                    :disabled="pasteText.length < 20"
                                    x-ref="interpretBtn"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white border-2 border-blue-300 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50 hover:border-blue-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                                >
                                    <i class="fas fa-eye" x-show="!interpreting"></i>
                                    <span x-text="interpreting ? '...' : 'Revisar'"></span>
                                    <kbd x-show="!interpreting" class="ml-0.5 hidden sm:inline-flex items-center gap-0.5 rounded border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-500">Ctrl+↵</kbd>
                                </button>

                                <button
                                    type="button"
                                    :disabled="pasteText.length < 20"
                                    x-ref="fastCreateBtn"
                                    @click="if(pasteText.length >= 20) { interpreting = true; $refs.fastCreateForm.submit(); }"
                                    class="inline-flex items-center gap-2.5 rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-green-600 transition-all duration-200 shadow-md hover:shadow-lg"
                                >
                                    <i class="fas fa-bolt" x-show="!interpreting"></i>
                                    <svg x-show="interpreting" x-cloak class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="interpreting ? 'Creando...' : 'Interpretar y Crear'"></span>
                                    <kbd x-show="!interpreting" class="ml-1 hidden sm:inline-flex items-center gap-0.5 rounded border border-green-400/50 bg-green-500/20 px-1.5 py-0.5 text-[10px] font-medium text-green-100">Ctrl+Shift+↵</kbd>
                                </button>
                            </div>
                        </div>

                        {{-- Min length warning --}}
                        <div x-show="pasteText.length > 0 && pasteText.length < 20" x-cloak
                             class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se requieren al menos 20 caracteres para interpretar el texto.
                        </div>
                    </form>

                    {{-- Hidden fast-create form --}}
                    <form action="{{ route('service-requests.interpret-and-store') }}" method="POST" x-ref="fastCreateForm" class="hidden">
                        @csrf
                        <input type="hidden" name="plain_text" :value="pasteText">
                        <input type="hidden" name="operator_notes" :value="operatorNotes">
                    </form>

                    {{-- Manual link --}}
                    <div class="mt-6 text-center">
                        <button
                            type="button"
                            @click="step = 2"
                            class="text-sm text-gray-500 hover:text-blue-700 transition-colors"
                        >
                            O crear manualmente →
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== STATE 2: THE FORM ===== --}}
        <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            {{-- Back to paste + re-interpret button --}}
            <div class="mb-6 flex items-center justify-between">
                <button
                    type="button"
                    @click="step = 1"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-blue-700 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    ← Volver a pegar texto
                </button>

                <button
                    type="button"
                    id="openPlainTextImportModalStep2"
                    class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <i class="fas fa-paste"></i>
                    Pegar e interpretar
                </button>
            </div>

            {{-- Hidden spans for type names (used for display) --}}
            @foreach (($requestTypes ?? collect()) as $type)
                <span data-type-name-{{ $type->slug }} class="hidden">{{ $type->name }}</span>
            @endforeach

            <form action="{{ route('service-requests.store') }}" method="POST">
                @csrf

                {{-- Hidden input for request_type_id --}}
                <input type="hidden" name="request_type_id" :value="selectedTypeId">

                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                        <h2 class="text-xl font-bold text-gray-800">Datos de la solicitud</h2>
                        <p class="text-sm text-gray-600 mt-1">Los campos marcados con * son obligatorios.</p>
                    </div>
                    <div class="p-6">
                        @include('components.service-requests.forms.basic-fields', [
                            'subServices' => $subServices,
                            'selectedSubService' => $selectedSubService ?? null,
                            'requesters' => $requesters,
                            'companies' => $companies ?? [],
                            'errors' => $errors,
                            'mode' => 'create',
                        ])

                        {{-- Meeting-specific fields (shown when type = "reunion") --}}
                        <div class="mt-6" x-show="selectedTypeSlug === 'reunion'" x-cloak>
                            @include('service-requests.partials._meeting-details', ['errors' => $errors])
                        </div>
                    </div>
                </div>

                <!-- Tareas (opcional, collapsed by default) -->
                <div class="mt-6 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <button type="button" id="toggleTasksSection" class="group w-full rounded-2xl px-6 py-4 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 transition border-b border-blue-100 focus:outline-none focus:bg-blue-100 focus:border-blue-600 focus:ring-4 focus:ring-inset focus:ring-blue-600/35 focus:shadow-md focus-visible:bg-blue-100 focus-visible:border-blue-600 focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-600/35">
                        <div class="text-left">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 group-focus:text-blue-900 group-focus-visible:text-blue-900">Opcional</p>
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
                        <!-- Botón Cancelar -->
                        <a href="{{ route('service-requests.index') }}"
                            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Cancelar
                        </a>

                        <!-- Botón Crear -->
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

                    {{-- Panel de confirmación inline --}}
                    <div id="confirmationPanel" class="hidden mt-4 border border-blue-200 bg-blue-50 rounded-xl p-4 shadow-sm animate-fade-in">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-blue-800 mb-2">Confirmar creación de solicitud</h4>
                                <div id="confirmationSummary" class="text-sm text-blue-700 space-y-1 mb-3">
                                    {{-- Se llena dinámicamente --}}
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btnConfirmCreate"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Confirmar
                                        <kbd class="ml-2 px-1.5 py-0.5 bg-blue-500 text-blue-100 rounded text-[10px] font-bold">Enter</kbd>
                                    </button>
                                    <button type="button" id="btnCancelCreate"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- AI Interpreter (State 1) ---
        const aiForm = document.getElementById('aiInterpreterForm');
        const plainTextInput = document.getElementById('plain_text');
        const openPlainTextImportModalStep2Btn = document.getElementById('openPlainTextImportModalStep2');

        // Auto-paste from clipboard on page load (if step 1 is active)
        (async function autoClipboardPaste() {
            const stepEl = document.querySelector('[x-data]');
            if (!stepEl || !plainTextInput) return;
            // Only attempt auto-paste if textarea is empty and we're on step 1
            if (plainTextInput.value.trim()) return;

            if (!navigator.clipboard || typeof navigator.clipboard.readText !== 'function' || !window.isSecureContext) return;

            try {
                const clipboardText = await navigator.clipboard.readText();
                if (clipboardText && clipboardText.trim() && clipboardText.trim().length >= 20) {
                    plainTextInput.value = clipboardText.replace(/\r\n/g, '\n');
                    plainTextInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            } catch (e) {
                // Clipboard permission denied, that's fine — user will paste manually
            }
        })();

        // "Pegar e interpretar" button in step 2 — clipboard auto-submit
        async function importPlainTextFromClipboard() {
            if (!plainTextInput || !aiForm) return;

            const triggerBtn = openPlainTextImportModalStep2Btn;
            if (triggerBtn) {
                triggerBtn.disabled = true;
                triggerBtn.setAttribute('aria-busy', 'true');
                triggerBtn.classList.add('cursor-wait', 'opacity-80');
            }

            try {
                if (!navigator.clipboard || typeof navigator.clipboard.readText !== 'function' || !window.isSecureContext) {
                    // Fall back: switch to step 1
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl.__x) alpineEl.__x.$data.step = 1;
                    else if (alpineEl && window.Alpine) window.Alpine.$data(alpineEl).step = 1;
                    plainTextInput.focus();
                    return;
                }

                const clipboardText = await navigator.clipboard.readText();

                if (!clipboardText || !clipboardText.trim()) {
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl.__x) alpineEl.__x.$data.step = 1;
                    else if (alpineEl && window.Alpine) window.Alpine.$data(alpineEl).step = 1;
                    plainTextInput.focus();
                    return;
                }

                plainTextInput.value = clipboardText.replace(/\r\n/g, '\n');
                plainTextInput.dispatchEvent(new Event('input', { bubbles: true }));

                if (plainTextInput.value.length >= 20) {
                    // Set interpreting state via Alpine
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl.__x) alpineEl.__x.$data.interpreting = true;
                    else if (alpineEl && window.Alpine) window.Alpine.$data(alpineEl).interpreting = true;

                    if (typeof aiForm.requestSubmit === 'function') {
                        aiForm.requestSubmit();
                    } else {
                        aiForm.submit();
                    }
                } else {
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl.__x) alpineEl.__x.$data.step = 1;
                    else if (alpineEl && window.Alpine) window.Alpine.$data(alpineEl).step = 1;
                    plainTextInput.focus();
                }
            } catch (error) {
                const alpineEl = document.querySelector('[x-data]');
                if (alpineEl && alpineEl.__x) alpineEl.__x.$data.step = 1;
                else if (alpineEl && window.Alpine) window.Alpine.$data(alpineEl).step = 1;
                plainTextInput.focus();
            } finally {
                if (triggerBtn) {
                    triggerBtn.disabled = false;
                    triggerBtn.removeAttribute('aria-busy');
                    triggerBtn.classList.remove('cursor-wait', 'opacity-80');
                }
            }
        }

        openPlainTextImportModalStep2Btn?.addEventListener('click', importPlainTextFromClipboard);

        // Workspace switch button
        const switchWorkspaceBtn = document.getElementById('switchWorkspaceBtn');
        const switchWorkspaceForm = document.getElementById('switchWorkspaceForm');
        if (switchWorkspaceBtn && switchWorkspaceForm) {
            switchWorkspaceBtn.addEventListener('click', function() {
                switchWorkspaceForm.submit();
            });
        }

        // Auto-crear solicitante pendiente (diferido desde interpretación de texto)
        const pendingRequesterName = @json($pendingRequesterName);
        const pendingRequesterEmail = @json($pendingRequesterEmail);

        if (pendingRequesterName) {
            (async function createPendingRequester() {
                const url = @json(route('api.requesters.quick-create'));
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const companyId = (document.getElementById('company_id')?.value || '').trim();

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        body: JSON.stringify({
                            name: pendingRequesterName,
                            email: pendingRequesterEmail || null,
                            company_id: companyId || null,
                        }),
                    });

                    const data = await res.json();
                    if (res.ok && data?.id) {
                        const select = document.getElementById('requester_id');
                        if (select) {
                            const display = data.display || pendingRequesterName;
                            const newOption = new Option(display, String(data.id), true, true);
                            if (companyId) {
                                newOption.setAttribute('data-company-id', companyId);
                            }
                            if (window.jQuery && window.jQuery.fn?.select2 && window.jQuery(select).data('select2')) {
                                window.jQuery(select).append(newOption).trigger('change');
                            } else {
                                select.appendChild(newOption);
                                select.value = String(data.id);
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    }
                } catch (e) {
                    console.warn('No se pudo crear el solicitante pendiente:', e);
                }
            })();
        }

        // --- Parser pre-filled field highlighting ---
        const parserPrefilledFields = @json($parserPrefilledFields);

        if (parserPrefilledFields && parserPrefilledFields.length > 0) {
            const fieldIdMap = {
                'title': 'title',
                'description': 'description',
                'entry_channel': 'entry_channel',
                'sub_service_id': 'sub_service_id',
                'requester_id': 'requester_id',
                'created_at': 'created_at',
                'due_date': 'due_date',
                'criticality_level': 'criticality-level-container',
                'web_routes': null,
            };

            parserPrefilledFields.forEach(function(fieldName) {
                const elementId = fieldIdMap[fieldName];

                let el = null;
                if (fieldName === 'web_routes') {
                    el = document.querySelector('.web-route-input');
                } else if (elementId) {
                    el = document.getElementById(elementId);
                }

                if (!el) return;

                if (fieldName === 'criticality_level') {
                    el.classList.add('parser-prefilled');
                    el.style.padding = '0.5rem';
                    el.style.borderRadius = '0.5rem';
                } else {
                    el.classList.add('parser-prefilled');
                }

                const fieldContainer = el.closest('div');
                if (fieldContainer) {
                    const label = fieldContainer.querySelector('label');
                    if (label && !label.querySelector('.parser-prefilled-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'parser-prefilled-badge ml-2';
                        badge.innerHTML = '<i class="fas fa-magic"></i> Auto';
                        label.appendChild(badge);
                    }
                }
            });
        }

        // --- Pending requester warning badge ---
        if (pendingRequesterName) {
            const requesterContainer = document.getElementById('requester_id')?.closest('div');
            if (requesterContainer) {
                const existingBadge = requesterContainer.querySelector('.pending-requester-badge');
                if (!existingBadge) {
                    const warningBadge = document.createElement('div');
                    warningBadge.className = 'pending-requester-badge mt-2';
                    warningBadge.innerHTML = '<i class="fas fa-user-clock"></i> <span>Solicitante "<strong>' +
                        pendingRequesterName.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                        '</strong>" pendiente de creación — se creará al enviar el formulario.</span>';
                    const selectEl = document.getElementById('requester_id');
                    if (selectEl) {
                        selectEl.parentNode.insertBefore(warningBadge, selectEl.nextSibling);
                    }
                }
            }
        }

        const formEl = document.querySelector('form[action="{{ route('service-requests.store') }}"]');
        const inlineErrorEl = document.getElementById('createFormInlineError');
        let createConfirmed = false;

        // Focus positioning
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

            setTimeout(() => {
                try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); }
                try { target.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
            }, 0);
        })();

        // --- Tasks section logic ---
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
            const createdAt = document.getElementById('created_at');

            const checks = [
                { el: title, ok: !!title?.value?.trim(), label: 'Título' },
                { el: description, ok: !!description?.value?.trim(), label: 'Descripción' },
                { el: requester, ok: !!requester?.value, label: 'Solicitante' },
                { el: subService, ok: !!subService?.value, label: 'Subservicio' },
                { el: entryChannel, ok: !!entryChannel?.value, label: 'Canal de ingreso' },
                { el: createdAt, ok: !!createdAt?.value, label: 'Fecha y hora de la solicitud' },
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
            const createdAt = document.getElementById('created_at')?.value || 'Sin fecha';
            const dueDate = document.getElementById('due_date')?.value || 'Sin vencimiento';
            const tasksCount = document.querySelectorAll('#tasksList [data-task-row]').length;

            const requesterText = requester?.selectedOptions?.[0]?.textContent?.trim() || 'Sin solicitante';
            const subServiceText = subService?.selectedOptions?.[0]?.textContent?.trim() || 'Sin subservicio';
            const channelText = channel?.selectedOptions?.[0]?.textContent?.trim() || 'Sin canal';

            return [
                'Resumen de la solicitud:',
                `- Título: ${title}`,
                `- Solicitante: ${requesterText}`,
                `- Subservicio: ${subServiceText}`,
                `- Canal: ${channelText}`,
                `- Fecha solicitud: ${createdAt.replace('T', ' ')}`,
                `- Vencimiento: ${dueDate}`,
                `- Tareas: ${tasksCount}`,
                '',
                '¿Deseas crear la solicitud?'
            ].join('\n');
        }

        function setNotice(message) {
            if (!notice) return;
            if (!message) {
                notice.classList.add('hidden');
                notice.textContent = '';
                return;
            }
            notice.textContent = message;
            notice.classList.remove('hidden');
        }

        function updateTaskSummary() {
            if (!tasksList) return;
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
                created_at: document.getElementById('created_at')?.value ?? '',
                due_date: document.getElementById('due_date')?.value ?? '',
                tasks_template: templateSelect?.value ?? 'none',
                tasks: rows,
                saved_at: new Date().toISOString(),
            };
        }

        function saveDraftNow() {
            try {
                const payload = collectDraftState();
                localStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
            } catch (e) {}
        }

        function scheduleDraftSave() {
            if (autosaveTimer) clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(saveDraftNow, 2200);
        }

        function clearDraft() {
            try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
        }

        function readDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (e) { return null; }
        }

        function isOpen() {
            return body && !body.classList.contains('hidden');
        }

        function openSection() {
            if (!body) return;
            body.classList.remove('hidden');
            if (chevron) chevron.textContent = '▴';
        }

        function closeSection() {
            if (!body) return;
            body.classList.add('hidden');
            if (chevron) chevron.textContent = '▾';
        }

        function clearTaskRowErrors() {
            if (!tasksList) return;
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

        ['title', 'description', 'requester_id', 'sub_service_id', 'entry_channel', 'created_at', 'due_date'].forEach((id) => {
            const field = document.getElementById(id);
            if (!field) return;
            field.addEventListener('input', validateMainFields);
            field.addEventListener('change', validateMainFields);
            field.addEventListener('input', scheduleDraftSave);
            field.addEventListener('change', scheduleDraftSave);
        });

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

            if (locked && Number.isFinite(Number(totalMinutes)) && Number(totalMinutes) > 0) {
                minutesEl.value = String(Math.round(Number(totalMinutes) / 5) * 5);
                hoursEl.value = formatHoursFromMinutes(minutesEl.value);
            }
        }

        function parseDurationToMinutes(rawValue) {
            const raw = String(rawValue ?? '').trim().toLowerCase();
            if (!raw) return null;

            const normalized = raw.replace(',', '.').replace(/\s+/g, ' ');

            const hmMatch = normalized.match(/^(\d{1,2})\s*:\s*(\d{1,2})$/);
            if (hmMatch) {
                const hh = Number(hmMatch[1]);
                const mm = Number(hmMatch[2]);
                if (Number.isFinite(hh) && Number.isFinite(mm) && hh >= 0 && mm >= 0) {
                    return Math.round((hh * 60 + mm) / 5) * 5;
                }
            }

            let total = 0;
            let hasToken = false;
            const hourToken = normalized.match(/(\d+(?:\.\d+)?)\s*(h|hr|hrs|hora|horas)\b/);
            if (hourToken) {
                const h = Number(hourToken[1]);
                if (Number.isFinite(h) && h >= 0) { total += h * 60; hasToken = true; }
            }
            const minuteToken = normalized.match(/(\d+(?:\.\d+)?)\s*(m|min|mins|minuto|minutos)\b/);
            if (minuteToken) {
                const m = Number(minuteToken[1]);
                if (Number.isFinite(m) && m >= 0) { total += m; hasToken = true; }
            }
            if (hasToken) return total > 0 ? (Math.round(total / 5) * 5) : null;

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

            const inside = String(matches[matches.length - 1][1] ?? '').trim().toLowerCase();
            if (!inside) return null;

            const text = inside.replace(/\s+/g, ' ');

            let totalMinutes = 0;
            let hasAny = false;

            const hourMatch = text.match(/(\d+(?:[\.,]\d+)?)\s*(h|hr|hrs|hora|horas)\b/);
            if (hourMatch) {
                const hours = Number(String(hourMatch[1]).replace(',', '.'));
                if (Number.isFinite(hours) && hours > 0) { totalMinutes += hours * 60; hasAny = true; }
            }

            const minuteMatch = text.match(/(\d+(?:[\.,]\d+)?)\s*(m|min|mins|minuto|minutos)\b/);
            if (minuteMatch) {
                const mins = Number(String(minuteMatch[1]).replace(',', '.'));
                if (Number.isFinite(mins) && mins > 0) { totalMinutes += mins; hasAny = true; }
            }

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
            return Math.round(totalMinutes / 5) * 5;
        }

        function extractSubtaskTitleAndMinutes(title) {
            const rawTitle = String(title ?? '').trim();
            if (!rawTitle) return null;

            const matches = Array.from(rawTitle.matchAll(/\(([^()]*)\)/g));
            if (!matches.length) return null;

            const lastMatch = matches[matches.length - 1];
            const parsedMinutes = parseMinutesFromSubtaskTitle(rawTitle);
            if (parsedMinutes === null || typeof lastMatch.index !== 'number') return null;

            const cleanTitle = `${rawTitle.slice(0, lastMatch.index)}${rawTitle.slice(lastMatch.index + lastMatch[0].length)}`.trim().replace(/\s{2,}/g, ' ');
            if (!cleanTitle) return null;

            return { cleanTitle, minutes: parsedMinutes };
        }

        function bindTaskEstimateSync(row) {
            const displayEl = row.querySelector('[data-field="estimated_display"]');
            const minutesEl = row.querySelector('[data-field="estimated_minutes"]');
            const hoursEl = row.querySelector('[data-field="estimated_hours"]');
            const unitEl = row.querySelector('[data-estimate-unit]');
            if (!displayEl || !minutesEl || !hoursEl || !unitEl) return;

            function setHoursFromMinutes(minutes) {
                const raw = String(minutes ?? '').trim();
                if (raw === '') { hoursEl.value = ''; return; }
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
                        ? formatHoursFromMinutes(minutes) : '';
                } else {
                    displayEl.step = '5';
                    displayEl.placeholder = 'Minutos (Ej: 75)';
                    displayEl.value = (minutes !== null && Number.isFinite(minutes))
                        ? String(Math.round(minutes)) : '';
                }
            }

            function parseDisplayToMinutes() {
                const raw = String(displayEl.value || '').trim();
                if (!raw) return null;
                if (unitEl.value === 'hours') return parseDurationToMinutes(raw);
                const parsed = Number(raw);
                if (!Number.isFinite(parsed) || parsed < 0) return null;
                return Math.round(parsed / 5) * 5;
            }

            if (!String(minutesEl.value || '').trim() && String(hoursEl.value || '').trim()) {
                const m = parseDurationToMinutes(hoursEl.value);
                if (m !== null) minutesEl.value = String(m);
            }

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

                let minutes = 25;
                if (raw !== '') {
                    const parsed = Number(raw);
                    if (Number.isFinite(parsed)) minutes = parsed;
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
                if (minutesEl.dataset.programmatic === '1') {
                    delete minutesEl.dataset.programmatic;
                } else {
                    minutesEl.dataset.touched = '1';
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

            const subtaskTitleEl = el.querySelector('input[type="text"]');
            const subtaskMinutesEl = el.querySelector('[data-subtask-field="estimated_minutes"]');

            if (subtaskTitleEl && subtaskMinutesEl) {
                const initialParsed = extractSubtaskTitleAndMinutes(subtaskTitleEl.value);
                if (initialParsed !== null) {
                    subtaskTitleEl.value = initialParsed.cleanTitle;
                    if (subtaskMinutesEl.dataset.touched !== '1') {
                        subtaskMinutesEl.value = String(initialParsed.minutes);
                    }
                    setSubtaskTabOrderFromAutoMinutes(el, { parsedMinutes: initialParsed.minutes });
                }
            }

            subtaskTitleEl?.addEventListener('input', function() {
                const extracted = extractSubtaskTitleAndMinutes(subtaskTitleEl.value);
                if (extracted !== null) subtaskTitleEl.value = extracted.cleanTitle;

                if (subtaskMinutesEl && subtaskMinutesEl.dataset.touched !== '1' && extracted !== null) {
                    subtaskMinutesEl.value = String(extracted.minutes);
                    subtaskMinutesEl.dataset.programmatic = '1';
                    subtaskMinutesEl.dispatchEvent(new Event('input', { bubbles: true }));
                    setSubtaskTabOrderFromAutoMinutes(el, { parsedMinutes: extracted.minutes });
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
                        <textarea data-field="description" data-name-template="tasks[__INDEX__][description]" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Descripción detallada de la tarea...">${(task.description ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                    </div>
                </div>

                <input type="hidden" data-field="standard_task_id" data-name-template="tasks[__INDEX__][standard_task_id]" value="${task.standard_task_id ?? ''}" />

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select data-field="type" data-name-template="tasks[__INDEX__][type]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="regular" ${(task.type ?? 'regular') === 'regular' ? 'selected' : ''}>Regular</option>
                            <option value="verification" ${task.type === 'verification' ? 'selected' : ''}>Verificación</option>
                            <option value="approval" ${task.type === 'approval' ? 'selected' : ''}>Aprobación</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                        <select data-field="priority" data-name-template="tasks[__INDEX__][priority]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            <option value="high" ${task.priority === 'high' ? 'selected' : ''}>Alta</option>
                            <option value="medium" ${(task.priority ?? 'medium') === 'medium' ? 'selected' : ''}>Media</option>
                            <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Baja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estimado</label>
                        <div class="flex gap-1 items-center">
                            <input type="number" min="0" step="5" data-field="estimated_display" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Minutos (Ej: 75)" />
                            <select data-estimate-unit class="px-2 py-2.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="minutes">min</option>
                                <option value="hours">hrs</option>
                            </select>
                        </div>
                        <input type="hidden" data-field="estimated_minutes" data-name-template="tasks[__INDEX__][estimated_minutes]" value="${task.estimated_minutes ?? ''}" />
                        <input type="hidden" data-field="estimated_hours" data-name-template="tasks[__INDEX__][estimated_hours]" value="${task.estimated_hours ?? ''}" />
                        <div class="mt-1.5 flex flex-wrap gap-1">
                            <button type="button" tabindex="-1" data-estimate-chip="15" class="px-2 py-0.5 text-xs rounded border border-gray-200 hover:bg-blue-50 hover:border-blue-300 text-gray-600">+15m</button>
                            <button type="button" tabindex="-1" data-estimate-chip="30" class="px-2 py-0.5 text-xs rounded border border-gray-200 hover:bg-blue-50 hover:border-blue-300 text-gray-600">+30m</button>
                            <button type="button" tabindex="-1" data-estimate-chip="60" class="px-2 py-0.5 text-xs rounded border border-gray-200 hover:bg-blue-50 hover:border-blue-300 text-gray-600">+1h</button>
                            <button type="button" tabindex="-1" data-estimate-chip="clear" class="px-2 py-0.5 text-xs rounded border border-gray-200 hover:bg-red-50 hover:border-red-300 text-gray-600">✕</button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-t border-gray-100 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Subtareas</span>
                        <div class="flex items-center gap-2">
                            <input type="number" min="1" max="10" value="1" class="w-14 px-2 py-1 text-xs border border-gray-300 rounded-lg" data-subtask-count placeholder="Nº" />
                            <button type="button" tabindex="-1" class="text-sm font-medium text-blue-600 hover:text-blue-800" data-add-subtask>+ Agregar</button>
                        </div>
                    </div>
                    <div class="mt-2 hidden space-y-2" data-subtasks-section>
                        <div class="space-y-2" data-subtasks-list></div>
                    </div>
                </div>
            `;

            // Description toggle
            const toggleDescBtn = row.querySelector('[data-toggle-description]');
            const descSection = row.querySelector('[data-description-section]');
            const descEl = row.querySelector('[data-field="description"]');
            if (task.description) {
                descSection?.classList.remove('hidden');
                if (toggleDescBtn) toggleDescBtn.textContent = 'Ocultar descripción';
            }
            toggleDescBtn?.addEventListener('click', function() {
                if (!descSection) return;
                const isHidden = descSection.classList.toggle('hidden');
                toggleDescBtn.textContent = isHidden ? 'Agregar descripción' : 'Ocultar descripción';
                if (!isHidden) setTimeout(() => descEl?.focus(), 0);
            });

            // Remove row
            row.querySelector('[data-remove-row]')?.addEventListener('click', function() {
                row.classList.add('task-row-leave');
                setTimeout(() => {
                    row.remove();
                    reindexRows();
                    updateTaskSummary();
                    scheduleDraftSave();
                }, 160);
            });

            // Estimate sync
            bindTaskEstimateSync(row);
            bindEstimateChips(row);

            // Subtasks
            const subtasksList = row.querySelector('[data-subtasks-list]');
            const subtasksSection = row.querySelector('[data-subtasks-section]');
            const addSubtaskBtn = row.querySelector('[data-add-subtask]');
            const subtaskCountEl = row.querySelector('[data-subtask-count]');
            const titleEl = row.querySelector('[data-field="title"]');
            const priorityEl = row.querySelector('[data-field="priority"]');

            function openSubtasks() {
                subtasksSection?.classList.remove('hidden');
            }

            function extractSubtaskCountFromTaskTitle(title) {
                const raw = String(title ?? '').trim();
                if (!raw) return null;

                const matches = Array.from(raw.matchAll(/\((\d+)\s*(sub|subtarea|subtareas|st)\)/gi));
                if (!matches.length) return null;

                const lastMatch = matches[matches.length - 1];
                const count = parseInt(lastMatch[1], 10);
                if (!Number.isFinite(count) || count < 1 || count > 10) return null;

                const cleanTitle = `${raw.slice(0, lastMatch.index)}${raw.slice(lastMatch.index + lastMatch[0].length)}`.trim().replace(/\s{2,}/g, ' ');
                return { cleanTitle: cleanTitle || raw, count };
            }

            function normalizeTaskPriorityToSubtaskPriority(taskPriority) {
                const normalized = String(taskPriority ?? '').toLowerCase();
                if (normalized === 'urgent' || normalized === 'critical' || normalized === 'high') return 'high';
                if (normalized === 'low') return 'low';
                return 'medium';
            }

            function appendAutomaticSubtasks(count) {
                openSubtasks();
                const safeCount = Math.max(1, Math.min(10, parseInt(String(count ?? '1'), 10) || 1));
                const currentSubtasks = Array.from(subtasksList?.querySelectorAll('[data-subtask-row]') ?? []);
                const baseIndex = currentSubtasks.length;
                const subtaskPriority = normalizeTaskPriorityToSubtaskPriority(priorityEl?.value);

                for (let i = 0; i < safeCount; i++) {
                    const stRow = createSubtaskRow({ title: `Subtarea ${baseIndex + i + 1}`, priority: subtaskPriority });
                    subtasksList?.appendChild(stRow);
                }

                if (subtaskCountEl) subtaskCountEl.value = String(safeCount);
                reindexRows();
                recalcTaskEstimateFromSubtasks(row);
                scheduleDraftSave();
            }

            function maybeCreateSubtasksFromTaskTitle() {
                const parsed = extractSubtaskCountFromTaskTitle(titleEl?.value);
                if (!parsed) return;
                if (titleEl) titleEl.value = parsed.cleanTitle;
                appendAutomaticSubtasks(parsed.count);
            }

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

            titleEl?.addEventListener('input', function() {
                maybeCreateSubtasksFromTaskTitle();
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

            maybeCreateSubtasksFromTaskTitle();

            row.querySelectorAll('input, textarea, select').forEach((inputEl) => {
                inputEl.addEventListener('input', scheduleDraftSave);
                inputEl.addEventListener('change', scheduleDraftSave);
            });

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
            if (focusTitle) setTimeout(() => row.querySelector('[data-field="title"]')?.focus(), 0);
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
            if (templateSelect) templateSelect.value = 'none';
            setNotice('');
        });

        formEl?.addEventListener('submit', function(e) {
            const hasRows = tasksList?.querySelector('[data-task-row]');
            if (!hasRows) return;

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
                if (!ok) { templateSelect.value = 'none'; return; }
            }

            if (templateSelect.value === 'subservice_standard') {
                await loadTemplateSubServiceStandard();
            } else {
                setNotice('');
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
            setValue('created_at', draft.created_at);
            setValue('due_date', draft.due_date);
            if (templateSelect) setValue('tasks_template', draft.tasks_template || 'none');

            clearAllRows();
            if (Array.isArray(draft.tasks)) {
                draft.tasks.forEach((t) => addRow(t));
            }
            if (Array.isArray(draft.tasks) && draft.tasks.length > 0) openSection();

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

        // Render old() tasks if present
        if (Array.isArray(initialTasks) && initialTasks.length > 0) {
            openSection();
            initialTasks.forEach((t) => addRow(t));
        } else if (initialTemplate && initialTemplate !== 'none') {
            openSection();
        }

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

                // Mostrar panel de confirmación inline
                const panel = document.getElementById('confirmationPanel');
                const summaryEl = document.getElementById('confirmationSummary');
                const btnConfirm = document.getElementById('btnConfirmCreate');
                const btnCancel = document.getElementById('btnCancelCreate');

                if (!panel || !summaryEl || !btnConfirm || !btnCancel) {
                    // Fallback si no existe el panel
                    createConfirmed = true;
                    clearDraft();
                    formEl.submit();
                    return;
                }

                // Construir resumen visual
                const title = document.getElementById('title')?.value?.trim() || '(sin título)';
                const requester = document.getElementById('requester_id');
                const subService = document.getElementById('sub_service_id');
                const channel = document.getElementById('entry_channel');
                const tasksCount = document.querySelectorAll('#tasksList [data-task-row]').length;

                const requesterText = requester?.selectedOptions?.[0]?.textContent?.trim() || 'Sin solicitante';
                const subServiceText = subService?.selectedOptions?.[0]?.textContent?.trim() || 'Sin subservicio';
                const channelText = channel?.selectedOptions?.[0]?.textContent?.trim() || 'Sin canal';

                summaryEl.innerHTML = `
                    <div class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1">
                        <span class="text-blue-500 font-medium">Título:</span><span class="truncate">${title}</span>
                        <span class="text-blue-500 font-medium">Solicitante:</span><span class="truncate">${requesterText}</span>
                        <span class="text-blue-500 font-medium">Subservicio:</span><span class="truncate">${subServiceText}</span>
                        <span class="text-blue-500 font-medium">Canal:</span><span>${channelText}</span>
                        <span class="text-blue-500 font-medium">Tareas:</span><span>${tasksCount}</span>
                    </div>
                `;

                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                btnConfirm.focus();

                // Handler para confirmar
                function handleConfirm() {
                    cleanup();
                    createConfirmed = true;
                    clearDraft();
                    formEl.submit();
                }

                // Handler para cancelar
                function handleCancel() {
                    cleanup();
                    panel.classList.add('hidden');
                }

                // Handler para teclas
                function handleKeydown(evt) {
                    if (evt.key === 'Enter' || evt.key === 'Tab') {
                        evt.preventDefault();
                        handleConfirm();
                    } else if (evt.key === 'Escape') {
                        evt.preventDefault();
                        handleCancel();
                    }
                }

                function cleanup() {
                    btnConfirm.removeEventListener('click', handleConfirm);
                    btnCancel.removeEventListener('click', handleCancel);
                    document.removeEventListener('keydown', handleKeydown);
                }

                btnConfirm.addEventListener('click', handleConfirm);
                btnCancel.addEventListener('click', handleCancel);
                document.addEventListener('keydown', handleKeydown);
            });
        }

        // Don't show draft notice for fresh forms
        tasksDraftNotice?.classList.add('hidden');

        window.addEventListener('beforeunload', saveDraftNow);
        updateTaskSummary();

    });
    </script>

    {{-- Context Menu --}}
    <div id="sr-create-ctx" class="hidden fixed z-[9999] min-w-[200px] max-w-[260px] bg-white border border-gray-200 rounded-xl shadow-lg p-1" role="menu" style="animation: ctx-scale-in 0.12s ease-out;">
        <div class="px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Acciones rápidas</div>
        <button type="button" class="sr-create-ctx__item sr-create-ctx__item--primary" role="menuitem" data-ctx-action="paste-and-create">
            <i class="fas fa-paste"></i>
            <span>Pegar e interpretar</span>
            <kbd class="ml-auto px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-bold">↵</kbd>
        </button>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="paste-only">
            <i class="fas fa-clipboard"></i>
            <span>Pegar texto</span>
        </button>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="paste-and-review">
            <i class="fas fa-eye"></i>
            <span>Pegar y revisar</span>
        </button>
        <div class="my-1 border-t border-gray-100"></div>
        <div class="px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Formulario</div>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="submit">
            <i class="fas fa-paper-plane"></i>
            <span>Crear solicitud</span>
            <kbd class="ml-auto px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] font-bold">Tab</kbd>
        </button>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="interpret">
            <i class="fas fa-magic"></i>
            <span>Ir al campo de texto</span>
        </button>
        <div class="my-1 border-t border-gray-100"></div>
        <div class="px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Ir a</div>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="focus-title">
            <i class="fas fa-heading"></i>
            <span>Título</span>
        </button>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="focus-description">
            <i class="fas fa-align-left"></i>
            <span>Descripción</span>
        </button>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="focus-service">
            <i class="fas fa-concierge-bell"></i>
            <span>Servicio</span>
        </button>
        <div class="my-1 border-t border-gray-100"></div>
        <button type="button" class="sr-create-ctx__item" role="menuitem" data-ctx-action="clear">
            <i class="fas fa-eraser"></i>
            <span>Limpiar formulario</span>
        </button>
        <a href="{{ route('service-requests.index') }}" class="sr-create-ctx__item" role="menuitem">
            <i class="fas fa-list"></i>
            <span>Ver solicitudes</span>
        </a>
    </div>

    <style>
        @keyframes ctx-scale-in {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .sr-create-ctx__item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 7px 10px;
            border-radius: 6px;
            border: none;
            background: none;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            text-decoration: none;
            cursor: pointer;
            text-align: left;
            transition: background 0.1s ease;
        }
        .sr-create-ctx__item:hover,
        .sr-create-ctx__item:focus {
            background: #f1f5f9;
            color: #0f172a;
            outline: none;
        }
        .sr-create-ctx__item i {
            width: 14px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        .sr-create-ctx__item:hover i { color: #3b82f6; }
        .sr-create-ctx__item--primary {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            color: #166534;
        }
        .sr-create-ctx__item--primary:hover,
        .sr-create-ctx__item--primary:focus { background: #dcfce7; border-color: #86efac; }
        .sr-create-ctx__item--primary i { color: #16a34a; }
    </style>

    <script>
    (function() {
        var menu = document.getElementById('sr-create-ctx');
        if (!menu) return;

        var isOpen = false;
        var storeForm = document.querySelector('form[action$="/service-requests"]') ||
                        document.querySelector('form[action*="service-requests"][method="POST"]:not(#aiInterpreterForm):not(#switchWorkspaceForm)');

        function show(x, y) {
            menu.classList.remove('hidden');
            menu.style.animation = 'scale-in 0.12s ease-out';
            isOpen = true;

            // Posicionar fuera de vista para medir
            menu.style.left = '-9999px';
            menu.style.top = '0px';

            var vw = window.innerWidth, vh = window.innerHeight;
            var mw = menu.offsetWidth, mh = menu.offsetHeight;

            // Calcular offset del item primario relativo al menú
            var primary = menu.querySelector('.sr-create-ctx__item--primary');
            var py = y;
            var px = x;

            if (primary) {
                // Centrar verticalmente el cursor sobre el botón primario
                var primaryCenterY = primary.offsetTop + Math.round(primary.offsetHeight / 2);
                py = y - primaryCenterY;

                // Mover el menú a la izquierda para que el cursor quede sobre el texto del botón
                px = x - 40;
            }

            // Ajustar si se sale del viewport
            if (px + mw > vw) px = vw - mw - 8;
            if (px < 4) px = 4;
            if (py < 4) py = 4;
            if (py + mh > vh) py = vh - mh - 8;

            menu.style.left = px + 'px';
            menu.style.top = py + 'px';

            setTimeout(function() {
                if (primary) primary.focus();
            }, 60);
        }

        function hide() {
            menu.classList.add('hidden');
            isOpen = false;
        }

        function executeAction(action) {
            hide();
            switch (action) {
                case 'paste-and-create':
                    pasteFromClipboard(function() {
                        // Después de pegar, ejecutar "Interpretar y Crear"
                        var fastForm = document.querySelector('form[action$="interpret-and-store"]');
                        if (fastForm) {
                            var hiddenInput = fastForm.querySelector('input[name="plain_text"]');
                            var textarea = document.getElementById('plain_text');
                            if (hiddenInput && textarea) {
                                hiddenInput.value = textarea.value;
                            }
                            fastForm.submit();
                        }
                    });
                    break;
                case 'paste-only':
                    pasteFromClipboard();
                    break;
                case 'paste-and-review':
                    pasteFromClipboard(function() {
                        // Después de pegar, ejecutar "Revisar"
                        var form = document.getElementById('aiInterpreterForm');
                        if (form) form.submit();
                    });
                    break;
                case 'submit':
                    if (storeForm) {
                        var submitBtn = storeForm.querySelector('button[type="submit"]');
                        if (submitBtn) submitBtn.click();
                    }
                    break;
                case 'interpret':
                    var textarea = document.getElementById('plain_text');
                    if (textarea) {
                        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        textarea.focus();
                    }
                    break;
                case 'focus-title':
                    var el = document.getElementById('title') || document.querySelector('[name="title"]');
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(function(){ el.focus(); }, 300); }
                    break;
                case 'focus-description':
                    var el = document.getElementById('description') || document.querySelector('[name="description"]');
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(function(){ el.focus(); }, 300); }
                    break;
                case 'focus-service':
                    var el = document.getElementById('sub_service_id') || document.querySelector('[name="sub_service_id"]');
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(function(){ el.focus(); }, 300); }
                    break;
                case 'clear':
                    if (confirm('¿Limpiar todos los campos del formulario?')) {
                        if (storeForm) storeForm.reset();
                        var textarea = document.getElementById('plain_text');
                        if (textarea) textarea.value = '';
                    }
                    break;
            }
        }

        /**
         * Pega texto del portapapeles en el textarea y ejecuta la acción.
         * Con HTTPS, la Clipboard API funciona directamente con un solo clic.
         */
        function pasteFromClipboard(onSuccess) {
            // Asegurar step 1
            var alpineRoot = document.querySelector('[x-data]');
            if (alpineRoot) {
                var alpineData = Alpine.$data(alpineRoot);
                if (alpineData) alpineData.step = 1;
            }

            requestAnimationFrame(function() {
                var textarea = document.getElementById('plain_text');
                if (!textarea) return;

                if (navigator.clipboard && navigator.clipboard.readText) {
                    navigator.clipboard.readText().then(function(text) {
                        if (text && text.trim().length >= 5) {
                            applyPastedText(textarea, text.trim(), alpineRoot, onSuccess);
                        } else {
                            showToast('El portapapeles está vacío o tiene muy poco texto.', 'warning');
                        }
                    }).catch(function() {
                        // Fallback si el usuario deniega el permiso
                        textarea.focus();
                        showToast('Ctrl+V para pegar y se ejecutará automáticamente', 'info');
                        listenForPaste(textarea, alpineRoot, onSuccess);
                    });
                } else {
                    textarea.focus();
                    showToast('Ctrl+V para pegar y se ejecutará automáticamente', 'info');
                    listenForPaste(textarea, alpineRoot, onSuccess);
                }
            });
        }

        function listenForPaste(textarea, alpineRoot, onSuccess) {
            function onPaste(e) {
                textarea.removeEventListener('paste', onPaste);
                var text = (e.clipboardData || window.clipboardData).getData('text');
                if (text && text.trim().length >= 5) {
                    e.preventDefault();
                    applyPastedText(textarea, text.trim(), alpineRoot, onSuccess);
                }
            }
            textarea.addEventListener('paste', onPaste);
            setTimeout(function() { textarea.removeEventListener('paste', onPaste); }, 15000);
        }

        /**
         * Aplica el texto pegado al textarea y ejecuta la acción.
         */
        function applyPastedText(textarea, text, alpineRoot, onSuccess) {
            textarea.value = text;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));

            if (alpineRoot) {
                var alpineData = Alpine.$data(alpineRoot);
                if (alpineData) {
                    alpineData.pasteText = text;
                    if (onSuccess) alpineData.interpreting = true;
                }
            }

            if (onSuccess) {
                showToast('Texto pegado. Procesando...', 'success');
                setTimeout(onSuccess, 200);
            } else {
                showToast('Texto pegado (' + text.length + ' caracteres)', 'success');
            }
        }

        function showToast(message, type) {
            var toast = document.createElement('div');
            var colors = { success: 'bg-green-600', warning: 'bg-amber-600', info: 'bg-blue-600' };
            toast.className = 'fixed top-4 right-4 z-[99999] px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-lg ' + (colors[type] || colors.info);
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function() { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; }, 2500);
            setTimeout(function() { toast.remove(); }, 2900);
        }

        // Right-click on the page
        document.addEventListener('contextmenu', function(e) {
            // Allow native menu inside modals
            if (e.target.closest('[role="dialog"]')) return;
            // Allow native menu on inputs in step 2 (the form) so user can paste normally
            var inStep2 = document.querySelector('[x-data]');
            var isStep2Active = false;
            if (inStep2 && inStep2.__x) {
                isStep2Active = inStep2.__x.$data.step === 2;
            }
            if (isStep2Active && e.target.closest('input[type="text"], input[type="url"], input[type="email"], textarea, select')) return;

            e.preventDefault();
            show(e.clientX, e.clientY);
        });

        // Close
        document.addEventListener('mousedown', function(e) {
            if (isOpen && !menu.contains(e.target)) hide();
        });
        document.addEventListener('keydown', function(e) {
            if (!isOpen) return;
            if (e.key === 'Escape') { hide(); return; }
            // Tab executes the primary action
            if (e.key === 'Tab') {
                e.preventDefault();
                var focused = document.activeElement;
                if (focused && focused.closest('#sr-create-ctx') && focused.dataset.ctxAction) {
                    executeAction(focused.dataset.ctxAction);
                } else {
                    executeAction('submit');
                }
                return;
            }
        });
        window.addEventListener('scroll', function() { if (isOpen) hide(); }, { passive: true });

        // Click on items
        menu.addEventListener('click', function(e) {
            var item = e.target.closest('[data-ctx-action]');
            if (item) executeAction(item.dataset.ctxAction);
            // Links (<a>) handle themselves
            if (e.target.closest('a[href]')) hide();
        });

        // Keyboard navigation within menu
        menu.addEventListener('keydown', function(e) {
            var items = Array.from(menu.querySelectorAll('.sr-create-ctx__item'));
            var idx = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[(idx + 1) % items.length].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); items[(idx - 1 + items.length) % items.length].focus(); }
            else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var item = document.activeElement.closest('[data-ctx-action]');
                if (item) executeAction(item.dataset.ctxAction);
                else if (document.activeElement.closest('a[href]')) { document.activeElement.click(); hide(); }
            }
        });
    })();
    </script>
@endsection
