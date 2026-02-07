@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden" data-service-request-id="{{ $serviceRequest->id }}" data-company-id="{{ $serviceRequest->company_id ? (int) $serviceRequest->company_id : '' }}">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-100' }} px-6 py-3 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-users {{ $isDead ? 'text-gray-500' : 'text-green-600' }} mr-3"></i>
            Asignación y Responsables
        </h3>
    </div>

    <div class="p-4">
        @if ($serviceRequest->status === 'EN_PROCESO' && !$serviceRequest->assigned_to)
            <div class="mb-4 px-4 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                Falta asignar técnico para continuar.
            </div>
        @elseif ($serviceRequest->status === 'ACEPTADA' && !$serviceRequest->assigned_to)
            <div class="mb-4 px-4 py-2.5 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                Asignación pendiente para iniciar el proceso.
            </div>
        @endif

        @php
            $availableRequesters = \App\Models\Requester::orderBy('name')
                ->where('is_active', true)
                ->get();
        @endphp

        <div class="grid grid-cols-1 gap-2.5">
            <!-- Solicitante -->
            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                <div class="grid grid-cols-[auto,1fr,auto] gap-2.5 items-start">
                    <div class="flex-shrink-0 w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <span data-requester-initial>{{ substr($serviceRequest->requester->name ?? 'U', 0, 1) }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-medium uppercase tracking-normal text-gray-500">Solicitante</div>
                        <div class="font-semibold text-gray-900 leading-snug mt-0.5 text-sm" data-requester-name>{{ $serviceRequest->requester->name ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-600 mt-0.5 leading-snug truncate" data-requester-email title="{{ $serviceRequest->requester->email ?? '' }}">{{ $serviceRequest->requester->email ?? '' }}</div>
                    </div>
                @if($serviceRequest->status !== 'CERRADA')
                    <div class="self-start">
                        <button type="button" data-request-id="{{ $serviceRequest->id }}"
                            class="quick-requester-btn inline-flex items-center justify-center px-2.5 py-1.5 border border-gray-300 text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-user-edit mr-1"></i>
                            <span data-requester-action-label>{{ $serviceRequest->requester ? 'Cambiar' : 'Asignar' }}</span>
                        </button>
                    </div>
                @endif
                </div>
            </div>

            <!-- Técnico -->
            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                <div class="grid grid-cols-[auto,1fr,auto] gap-2.5 items-start">
                    <div data-assignee-avatar class="flex-shrink-0 w-9 h-9 {{ $serviceRequest->assigned_to ? 'bg-gradient-to-br from-blue-500 to-cyan-500 text-white' : 'bg-amber-100 text-amber-700' }} rounded-full flex items-center justify-center font-bold text-sm">
                        <span data-assignee-initial>{{ $serviceRequest->assigned_to ? substr($serviceRequest->assignee->name ?? 'T', 0, 1) : '?' }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-medium uppercase tracking-normal text-gray-500">Técnico asignado</div>
                        <div class="font-semibold text-gray-900 leading-snug mt-0.5 text-sm" data-assignee-name>
                            {{ $serviceRequest->assignee->name ?? 'Sin asignar' }}
                        </div>
                        <div class="text-sm text-gray-600 mt-0.5 leading-snug truncate" data-assignee-email title="{{ $serviceRequest->assignee->email ?? '' }}">{{ $serviceRequest->assignee->email ?? '' }}</div>
                    </div>
                @if($serviceRequest->status !== 'CERRADA')
                    <div class="self-start">
                        <button type="button" data-request-id="{{ $serviceRequest->id }}"
                            class="quick-assign-btn inline-flex items-center justify-center px-2.5 py-1.5 border border-gray-300 text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-user-plus mr-1"></i>
                            <span data-assignee-action-label>{{ $serviceRequest->assigned_to ? 'Reasignar' : 'Asignar' }}</span>
                        </button>
                    </div>
                @endif
                </div>
            </div>
        </div>

        @if ($serviceRequest->status === 'CERRADA')
            <div class="mt-3 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600">
                Solicitud cerrada. No se permiten cambios de asignación.
            </div>
        @endif
    </div>
</div>

<!-- Modal de Asignación Rápida - CORREGIDO -->
<div id="quickAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="quickAssignTitle"
     tabindex="-1">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 id="quickAssignTitle" class="text-lg font-medium text-gray-900 mb-4">Asignar Técnico</h3>
                <form id="quickAssignForm" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="quick_assign_assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Técnico
                        </label>
                        <select name="assigned_to" id="quick_assign_assigned_to"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="">Selecciona un técnico</option>
                            @foreach (\App\Models\User::all() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" id="closeModalButton"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Asignación de Solicitante -->
<div id="quickRequesterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="quickRequesterTitle"
     tabindex="-1">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 id="quickRequesterTitle" class="text-lg font-medium text-gray-900 mb-4">Asignar Solicitante</h3>
                <form id="quickRequesterForm" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="quick_assign_requester" class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Solicitante
                        </label>
                        <select name="requester_id" id="quick_assign_requester"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                            required>
                            <option value="">Selecciona un solicitante</option>
                            @foreach ($availableRequesters as $requester)
                                <option value="{{ $requester->id }}">
                                    {{ $requester->name }}{{ $requester->email ? " - {$requester->email}" : '' }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-2 flex justify-end">
                            <button type="button" id="openRequesterQuickCreateFromAssign"
                                class="inline-flex items-center gap-2 text-sm font-medium text-purple-700 hover:text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 rounded">
                                <i class="fas fa-user-plus"></i>
                                <span>Crear</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" id="closeRequesterModalButton"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors">
                            Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Crear solicitante rápido (desde asignación) -->
<div id="requesterQuickCreateFromAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="requesterQuickCreateFromAssignTitle"
     tabindex="-1">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="p-6 max-h-[75vh] overflow-y-auto">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 id="requesterQuickCreateFromAssignTitle" class="text-lg font-medium text-gray-900">Crear solicitante</h3>
                    <button type="button" id="closeRequesterQuickCreateFromAssign"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 rounded">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="requesterQuickCreateFromAssignErrors" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-sm font-medium text-red-800 mb-1">Revisa los campos:</p>
                    <ul class="text-sm text-red-700 list-disc list-inside" data-errors-list></ul>
                </div>

                <form id="requesterQuickCreateFromAssignForm" data-url="{{ route('api.requesters.quick-create') }}" class="space-y-4">
                    <div>
                        <label for="quickAssignRequesterName" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" id="quickAssignRequesterName" name="name" maxlength="255" data-quick-requester-assign-field disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="quickAssignRequesterEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="quickAssignRequesterEmail" name="email" maxlength="255" data-quick-requester-assign-field disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" />
                        </div>
                        <div>
                            <label for="quickAssignRequesterPhone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="text" id="quickAssignRequesterPhone" name="phone" maxlength="20" data-quick-requester-assign-field disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="quickAssignRequesterDepartment" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                            @php
                                $departmentOptions = \App\Models\Requester::getDepartmentOptions();
                            @endphp
                            <select id="quickAssignRequesterDepartment" name="department" data-quick-requester-assign-field disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                <option value="">Seleccione un departamento</option>
                                @foreach ($departmentOptions as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="quickAssignRequesterPosition" class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                            <input type="text" id="quickAssignRequesterPosition" name="position" maxlength="255" data-quick-requester-assign-field disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 mt-8">
                        <button type="button" id="cancelRequesterQuickCreateFromAssign"
                            class="w-full sm:w-auto px-4 py-2.5 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" id="submitRequesterQuickCreateFromAssign"
                            class="w-full sm:w-auto px-5 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors">
                            Crear y seleccionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--open {
                z-index: 99999;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single {
                height: 40px;
                border-radius: 0.375rem;
                border-color: #d1d5db;
                padding: 0.35rem 0.75rem;
                display: flex;
                align-items: center;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single .select2-selection__rendered {
                line-height: 24px;
                padding-left: 0;
                padding-right: 2.25rem;
            }

            .select2-container--default .s2-modal-selection.select2-selection--single .select2-selection__arrow {
                height: 38px;
                right: 0.75rem;
            }

            .select2-dropdown.s2-modal-dropdown {
                border-radius: 0.75rem;
                overflow: hidden;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    @endpush
@endonce

<script>
    function runWhenReady(cb) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', cb);
        } else {
            cb();
        }
    }

    window.updateServiceRequestAssignment = function(payload) {
        if (!payload) return;
        var requestId = payload.requestId;
        var type = payload.type;
        var name = payload.name || '';
        var email = payload.email || '';
        var card = payload.cardEl || null;
        if (!card && requestId) {
            card = document.querySelector('[data-service-request-id="' + requestId + '"]');
        }
        if (!card) return;

        if (type === 'requester') {
            var requesterNameEl = card.querySelector('[data-requester-name]');
            var requesterEmailEl = card.querySelector('[data-requester-email]');
            var requesterInitialEl = card.querySelector('[data-requester-initial]');
            var requesterLabel = card.querySelector('[data-requester-action-label]');

            if (requesterNameEl) requesterNameEl.textContent = name || 'Solicitante';
            if (requesterEmailEl) requesterEmailEl.textContent = email || '';
            if (requesterInitialEl && name) requesterInitialEl.textContent = name.charAt(0).toUpperCase();
            if (requesterLabel) requesterLabel.textContent = 'Reasignar';
            return;
        }

        var nameEl = card.querySelector('[data-assignee-name]');
        var emailEl = card.querySelector('[data-assignee-email]');
        var initialEl = card.querySelector('[data-assignee-initial]');
        var avatarEl = card.querySelector('[data-assignee-avatar]');
        var actionLabel = card.querySelector('[data-assignee-action-label]');

        if (nameEl) nameEl.textContent = name || 'Sin asignar';
        if (emailEl) emailEl.textContent = email || '';
        if (initialEl) initialEl.textContent = (name && name.length) ? name.charAt(0).toUpperCase() : '?';
        if (actionLabel) actionLabel.textContent = (name && name.length) ? 'Reasignar' : 'Asignar';

        if (avatarEl) {
            avatarEl.classList.remove('bg-amber-100', 'text-amber-700', 'bg-gradient-to-br', 'from-blue-500', 'to-cyan-500', 'text-white');
            if (name && name.length) {
                avatarEl.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-cyan-500', 'text-white');
            } else {
                avatarEl.classList.add('bg-amber-100', 'text-amber-700');
            }
        }
    };

    function initAssignmentCard() {
        setupQuickAssignModal({
            modalId: 'quickAssignModal',
            formId: 'quickAssignForm',
            selectId: 'quick_assign_assigned_to',
            closeButtonId: 'closeModalButton',
            buttonSelector: '.quick-assign-btn',
            actionPath: (requestId) => `/service-requests/${requestId}/quick-assign`,
            emptySelectMessage: 'Por favor selecciona un técnico antes de asignar.'
        });

        setupQuickAssignModal({
            modalId: 'quickRequesterModal',
            formId: 'quickRequesterForm',
            selectId: 'quick_assign_requester',
            closeButtonId: 'closeRequesterModalButton',
            buttonSelector: '.quick-requester-btn',
            actionPath: (requestId) => `/service-requests/${requestId}/quick-assign-requester`,
            emptySelectMessage: 'Por favor selecciona un solicitante antes de asignar.'
        });

        // Crear solicitante sin recargar (desde el modal de asignación)
        (function setupRequesterQuickCreateFromAssign() {
            const assignModalId = 'quickRequesterModal';
            const modalId = 'requesterQuickCreateFromAssignModal';
            const openBtn = document.getElementById('openRequesterQuickCreateFromAssign');
            const modal = document.getElementById(modalId);
            const closeBtn = document.getElementById('closeRequesterQuickCreateFromAssign');
            const cancelBtn = document.getElementById('cancelRequesterQuickCreateFromAssign');
            const form = document.getElementById('requesterQuickCreateFromAssignForm');
            const errorsBox = document.getElementById('requesterQuickCreateFromAssignErrors');
            const assignSelect = document.getElementById('quick_assign_requester');
            const submitBtn = document.getElementById('submitRequesterQuickCreateFromAssign');

            if (!openBtn || !modal || !closeBtn || !cancelBtn || !form || !assignSelect) {
                return;
            }
            if (modal.dataset.bound) return;
            modal.dataset.bound = '1';

            const errorsList = errorsBox ? errorsBox.querySelector('[data-errors-list]') : null;
            const nameInput = document.getElementById('quickAssignRequesterName');

            // Estado inicial seguro (por si el modal vive dentro de otro form)
            modal.querySelectorAll('[data-quick-requester-assign-field]').forEach((el) => {
                el.disabled = true;
            });

            let lastFocusEl = null;

            function openCreateModal() {
                lastFocusEl = document.activeElement;
                window.openModal ? window.openModal(modalId, openBtn) : modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');

                modal.querySelectorAll('[data-quick-requester-assign-field]').forEach((el) => {
                    el.disabled = false;
                });
                if (errorsBox) errorsBox.classList.add('hidden');
                if (errorsList) errorsList.innerHTML = '';

                const emailInput = document.getElementById('quickAssignRequesterEmail');
                const phoneInput = document.getElementById('quickAssignRequesterPhone');
                const deptInput = document.getElementById('quickAssignRequesterDepartment');
                const posInput = document.getElementById('quickAssignRequesterPosition');

                if (nameInput) nameInput.value = '';
                if (emailInput) emailInput.value = '';
                if (phoneInput) phoneInput.value = '';
                if (deptInput) {
                    deptInput.value = '';
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && window.jQuery(deptInput).data('select2')) {
                        window.jQuery(deptInput).val(null).trigger('change');
                    }
                }
                if (posInput) posInput.value = '';

                // Select2: Departamento dentro del modal
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && deptInput) {
                    const $dept = window.jQuery(deptInput);
                    if (!$dept.data('select2')) {
                        $dept.select2({
                            width: '100%',
                            placeholder: 'Seleccione un departamento',
                            allowClear: true,
                            dropdownParent: window.jQuery(modal),
                            selectionCssClass: 's2-modal-selection',
                            dropdownCssClass: 's2-modal-dropdown'
                        });

                        $dept.on('select2:open', function () {
                            const search = document.querySelector('.select2-container--open .select2-search__field');
                            if (search) search.focus();
                        });
                    }
                }

                setTimeout(() => { if (nameInput) nameInput.focus(); }, 0);
            }

            function closeCreateModal() {
                window.closeModal ? window.closeModal(modalId) : modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');

                modal.querySelectorAll('[data-quick-requester-assign-field]').forEach((el) => {
                    el.disabled = true;
                });

                const target = lastFocusEl || openBtn;
                if (target && typeof target.focus === 'function') {
                    setTimeout(() => target.focus(), 0);
                }
            }

            openBtn.addEventListener('click', openCreateModal);
            closeBtn.addEventListener('click', closeCreateModal);
            cancelBtn.addEventListener('click', closeCreateModal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeCreateModal();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeCreateModal();
                }
            });

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (errorsBox) errorsBox.classList.add('hidden');
                if (errorsList) errorsList.innerHTML = '';

                const url = form.dataset.url;
                if (!url) {
                    if (errorsBox) {
                        errorsBox.classList.remove('hidden');
                        if (errorsList) errorsList.innerHTML = '<li>No se configuró la URL del endpoint.</li>';
                    }
                    return;
                }

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
                const rootCard = document.querySelector('[data-service-request-id="' + requestId + '"]');
                const companyId = rootCard ? rootCard.getAttribute('data-company-id') : '';
                const payload = {
                    name: (document.getElementById('quickAssignRequesterName') ? document.getElementById('quickAssignRequesterName').value : '').trim(),
                    email: (document.getElementById('quickAssignRequesterEmail') ? document.getElementById('quickAssignRequesterEmail').value : '').trim() || null,
                    phone: (document.getElementById('quickAssignRequesterPhone') ? document.getElementById('quickAssignRequesterPhone').value : '').trim() || null,
                    department: (document.getElementById('quickAssignRequesterDepartment') ? document.getElementById('quickAssignRequesterDepartment').value : '').trim() || null,
                    position: (document.getElementById('quickAssignRequesterPosition') ? document.getElementById('quickAssignRequesterPosition').value : '').trim() || null,
                    company_id: companyId ? parseInt(companyId, 10) : null,
                };

                if (!payload.name) {
                    if (errorsBox) errorsBox.classList.remove('hidden');
                    if (errorsList) errorsList.innerHTML = '<li>El nombre es obligatorio.</li>';
                    setTimeout(() => { if (nameInput) nameInput.focus(); }, 0);
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75');
                }

                try {
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    };
                    if (csrf) {
                        headers['X-CSRF-TOKEN'] = csrf;
                    }

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify(payload),
                    });

                    const data = await res.json().catch(() => null);
                    if (!res.ok) {
                        const messages = [];
                        if (data && data.errors && typeof data.errors === 'object') {
                            for (const key of Object.keys(data.errors)) {
                                const arr = data.errors[key];
                                if (Array.isArray(arr)) {
                                    for (const msg of arr) messages.push(String(msg));
                                }
                            }
                        }
                        if (!messages.length) {
                            messages.push(data && data.message ? String(data.message) : 'No se pudo crear el solicitante.');
                        }
                        if (errorsBox) {
                            errorsBox.classList.remove('hidden');
                            if (errorsList) {
                                errorsList.innerHTML = messages
                                    .map(m => `<li>${String(m).replace(/</g,'&lt;').replace(/>/g,'&gt;')}</li>`)
                                    .join('');
                            }
                        }
                        return;
                    }

                    const requesterId = data ? data.id : null;
                    const display = (data && data.display) ? data.display : ((data && data.name) ? data.name : 'Solicitante');
                    if (!requesterId) {
                        if (errorsBox) {
                            errorsBox.classList.remove('hidden');
                            if (errorsList) errorsList.innerHTML = '<li>Respuesta inválida del servidor.</li>';
                        }
                        return;
                    }

                    const option = new Option(display, String(requesterId), true, true);
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && window.jQuery(assignSelect).data('select2')) {
                        window.jQuery(assignSelect).append(option).trigger('change');
                    } else {
                        assignSelect.appendChild(option);
                        assignSelect.value = String(requesterId);
                        assignSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    closeCreateModal();

                    // Dejar el modal de asignación abierto y enfocar el selector
                    const assignModal = document.getElementById(assignModalId);
                    if (assignModal && !assignModal.classList.contains('hidden')) {
                        setTimeout(() => assignSelect.focus(), 0);
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-75');
                    }
                }
            });
        })();
    }

    runWhenReady(initAssignmentCard);
    document.addEventListener('turbo:load', initAssignmentCard);
    document.addEventListener('livewire:navigated', initAssignmentCard);

    function getFocusableElements(container) {
        if (!container) return [];
        const selectors = [
            'a[href]',
            'area[href]',
            'input:not([disabled]):not([type="hidden"])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            'button:not([disabled])',
            '[contenteditable="true"]',
            '[tabindex]:not([tabindex="-1"])'
        ].join(',');

        return Array.from(container.querySelectorAll(selectors)).filter((el) => {
            if (!(el instanceof HTMLElement)) return false;
            if (el.hasAttribute('disabled')) return false;
            if (el.getAttribute('aria-hidden') === 'true') return false;
            // Visible (incluye select2 y modales)
            return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
        });
    }

    function bindFocusTrap(modalEl, isOpenFn) {
        if (!modalEl || modalEl.dataset.focusTrapBound) return;
        modalEl.dataset.focusTrapBound = '1';

        modalEl.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            if (typeof isOpenFn === 'function' && !isOpenFn()) return;

            const focusables = getFocusableElements(modalEl);
            if (!focusables.length) {
                e.preventDefault();
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (!(active instanceof HTMLElement) || !modalEl.contains(active)) {
                e.preventDefault();
                first.focus();
                return;
            }

            if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        });
    }

    function setupQuickAssignModal({ modalId, formId, selectId, closeButtonId, buttonSelector, actionPath, emptySelectMessage }) {
        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        const closeButton = document.getElementById(closeButtonId);
        const assignButtons = document.querySelectorAll(buttonSelector);
        const selectField = document.getElementById(selectId);
        let lastTrigger = null;

        if (!modal || !form || !closeButton || !assignButtons.length || !selectField) {
            return;
        }
        if (modal.dataset.bound === '1') {
            return;
        }
        modal.dataset.bound = '1';

        bindFocusTrap(modal, () => !modal.classList.contains('hidden'));

        function openQuickModal(serviceRequestId) {
            form.action = actionPath(serviceRequestId);
            form.dataset.requestId = serviceRequestId;
            modal.dataset.requestId = serviceRequestId;
            window.openModal ? window.openModal(modalId, lastTrigger) : modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(() => selectField.focus(), 0);
        }

        function closeQuickModal() {
            window.closeModal ? window.closeModal(modalId) : modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            form.reset();

            const target = lastTrigger;
            if (target && typeof target.focus === 'function') {
                setTimeout(() => target.focus(), 0);
            }
        }

        assignButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                lastTrigger = this;
                const requestId = this.getAttribute('data-request-id');
                if (!requestId) {
                    if (typeof window.srNotify === 'function') window.srNotify(false, 'No se pudo identificar la solicitud.');
                    return;
                }
                openQuickModal(requestId);
            });
        });

        closeButton.addEventListener('click', closeQuickModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeQuickModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeQuickModal();
            }
        });

        function parseAssignee(text) {
            const raw = (text || '').trim();
            if (!raw) return { name: '', email: '' };
            if (raw.includes('(') && raw.includes(')')) {
                const parts = raw.split('(');
                const name = (parts[0] ? parts[0].trim() : '') || raw;
                const email = (parts[1] ? parts[1].replace(')', '').trim() : '') || '';
                return { name: name, email: email };
            }
            if (raw.includes(' - ')) {
                const parts = raw.split(' - ');
                const name = (parts[0] ? parts[0].trim() : '') || raw;
                const email = parts.slice(1).join(' - ').trim();
                return { name: name, email: email };
            }
            return { name: raw, email: '' };
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const selectedValue = selectField.value;

            if (!selectedValue) {
                if (typeof window.srNotify === 'function') window.srNotify(false, emptySelectMessage);
                selectField.focus();
                return;
            }

            try {
                form.setAttribute('aria-busy','true');
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: new FormData(this)
                });

                let result = null;
                try {
                    result = await response.json();
                } catch (err) {
                    result = null;
                }

                if (response.ok && (!result || result.success)) {
                    if (typeof window.srNotify === 'function') window.srNotify(true, result.message || 'Actualizado.');
                    const requestId = form.dataset.requestId || modal.dataset.requestId || (function() {
                        const requestMatch = form.action.match(/service-requests\/(\d+)/);
                        return requestMatch ? requestMatch[1] : null;
                    })();
                    const selectedOption = selectField.options[selectField.selectedIndex];
                    const selectedText = selectedOption && selectedOption.textContent ? selectedOption.textContent.trim() : '';
                    const parsed = parseAssignee(selectedText);
                    closeQuickModal();

                    var targetCard = lastTrigger ? lastTrigger.closest('[data-service-request-id]') : null;

                    if (requestId || targetCard) {
                        if (modalId === 'quickRequesterModal') {
                            const requesterName = (result && (result.requester_name || result.name)) || parsed.name || 'Solicitante';
                            const requesterEmail = (result && (result.requester_email || result.email)) || parsed.email || '';
                            if (typeof window.updateServiceRequestAssignment === 'function') {
                                window.updateServiceRequestAssignment({
                                requestId,
                                cardEl: targetCard,
                                type: 'requester',
                                name: requesterName,
                                email: requesterEmail,
                                });
                            }
                        } else {
                            const assigneeName = (result && result.assigned_to) || parsed.name || 'Técnico asignado';
                            if (typeof window.updateServiceRequestAssignment === 'function') {
                                window.updateServiceRequestAssignment({
                                requestId,
                                cardEl: targetCard,
                                type: 'technician',
                                name: assigneeName,
                                email: parsed.email,
                                });
                            }
                        }

                        if (modalId !== 'quickRequesterModal') {
                            const tasksPanel = document.querySelector(`div[data-service-request-id="${requestId}"] .open-quick-task`);
                            if (tasksPanel) {
                                tasksPanel.dataset.disabled = 'false';
                                const enabledClass = tasksPanel.dataset.enabledClass || '';
                                const disabledClass = tasksPanel.dataset.disabledClass || '';
                                if (disabledClass) {
                                    disabledClass.split(' ').forEach(cls => cls && tasksPanel.classList.remove(cls));
                                }
                                if (enabledClass) {
                                    enabledClass.split(' ').forEach(cls => cls && tasksPanel.classList.add(cls));
                                }
                            }

                            const warning = document.querySelector(`div[data-service-request-id="${requestId}"] [data-quick-task-warning]`);
                            if (warning) {
                                warning.classList.add('hidden');
                            }

                            const workflowBtn = document.querySelector(`[data-workflow-action="assign-technician"][data-service-request-id="${requestId}"]`);
                            if (workflowBtn) {
                                const newLabel = 'Aceptar Solicitud';
                                workflowBtn.setAttribute('data-workflow-action', 'accept');
                                workflowBtn.setAttribute('data-modal-id', `accept-modal-${requestId}`);
                                workflowBtn.setAttribute('onclick', `openModal('accept-modal-${requestId}', this)`);
                                workflowBtn.className = workflowBtn.className
                                    .replace('bg-blue-600', 'bg-emerald-600')
                                    .replace('hover:bg-blue-700', 'hover:bg-emerald-700')
                                    .replace('active:bg-blue-800', 'active:bg-emerald-800')
                                    .replace('border-blue-700', 'border-emerald-700')
                                    .replace('hover:border-blue-800', 'hover:border-emerald-800')
                                    .replace('focus:ring-blue-500', 'focus:ring-emerald-500');

                                const icon = workflowBtn.querySelector('i');
                                if (icon) {
                                    icon.className = icon.className.replace(/fa-user-plus/g, 'fa-handshake');
                                }

                                const label = workflowBtn.querySelector('span');
                                if (label) {
                                    label.textContent = newLabel;
                                } else {
                                    workflowBtn.textContent = newLabel;
                                }
                            }
                        }
                    }

                    // Abrir el modal de aceptación si existe (sin forzar)
                    if (requestId && modalId !== 'quickRequesterModal') {
                        const acceptModal = document.getElementById(`accept-modal-${requestId}`);
                        if (acceptModal) {
                            const assigneeTarget = acceptModal.querySelector('[data-accept-assignee]');
                            if (assigneeTarget && selectedText) {
                                assigneeTarget.textContent = selectedText;
                                assigneeTarget.classList.remove('text-red-600');
                                assigneeTarget.classList.add('text-green-600', 'font-medium');
                            }
                            window.openModal ? window.openModal(`accept-modal-${requestId}`, lastTrigger) : acceptModal.classList.remove('hidden');
                        }
                    }
                } else {
                    if (typeof window.srNotify === 'function') window.srNotify(false, (result && result.message) || 'No se pudo completar la acción.');
                }
            } catch (error) {
                if (typeof window.srNotify === 'function') window.srNotify(false, 'Error de conexión.');
            } finally {
                form.removeAttribute('aria-busy');
            }
        });
    }
</script>
