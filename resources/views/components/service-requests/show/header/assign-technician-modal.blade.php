<!-- Modal de Asignación de Técnico - VERSIÓN CORREGIDA -->
<div id="assign-technician-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="assign-technician-modal-title-{{ $serviceRequest->id }}"
     tabindex="-1">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mr-3">
                    <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                </div>
                <h3 id="assign-technician-modal-title-{{ $serviceRequest->id }}" class="text-lg font-medium text-gray-900">
                    Asignar Técnico
                </h3>
            </div>
            <button type="button"
                     onclick="closeModal('assign-technician-modal-{{ $serviceRequest->id }}')"
                     class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200"
                     aria-label="Cerrar diálogo">
                ✕
            </button>
        </div>

        <!-- Información de la solicitud -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Ticket: <strong>#{{ $serviceRequest->ticket_number }}</strong></span>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- FORMULARIO CORREGIDO -->
        <form action="{{ route('service-requests.quick-assign', $serviceRequest) }}"
              method="POST"
              id="assign-form-{{ $serviceRequest->id }}">
            @csrf
            @method('POST')

            <div class="space-y-4">
                <!-- Selección de técnico - NOMBRE CORREGIDO -->
                <div>
                    <label for="assigned_to_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                        Seleccionar Técnico *
                    </label>
                    <select name="assigned_to" {{-- ← CORREGIDO: technician_id → assigned_to --}}
                            id="assigned_to_{{ $serviceRequest->id }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                            required>
                        <option value="">Selecciona un técnico...</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}">
                                {{ $technician->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        onclick="closeModal('assign-technician-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        data-submit-mode="assign"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Asignar y Continuar
                </button>
                <button type="submit"
                        data-submit-mode="accept-start"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200">
                    <i class="fas fa-play mr-2"></i>
                    Aceptar e Iniciar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT CORREGIDO -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assign-form-{{ $serviceRequest->id }}');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
            const submitter = e.submitter;
            const submitMode = submitter && submitter.dataset ? submitter.dataset.submitMode : 'assign';
            const buttonSnapshots = submitButtons.map(btn => ({ btn, html: btn.innerHTML }));
            const technicianSelect = document.getElementById('assigned_to_{{ $serviceRequest->id }}');

            // Validar que se seleccionó un técnico
            if (!technicianSelect.value) {
                if (typeof window.srNotify === 'function') window.srNotify(false, 'Por favor selecciona un técnico.');
                return;
            }

            // Mostrar loading
            submitButtons.forEach(btn => btn.disabled = true);
            if (submitter) {
                submitter.innerHTML = submitMode === 'accept-start'
                    ? '<i class="fas fa-spinner fa-spin mr-2"></i>Aceptando e iniciando...'
                    : '<i class="fas fa-spinner fa-spin mr-2"></i>Asignando...';
            }
            form.setAttribute('aria-busy', 'true');

            const formData = new FormData(form);
            formData.set('accept_and_start', submitMode === 'accept-start' ? '1' : '0');

            // Enviar formulario
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json().then(data => {
                        if (typeof window.srNotify === 'function') window.srNotify(true, data.message || 'Técnico asignado.');
                        closeModal('assign-technician-modal-{{ $serviceRequest->id }}');

                        if (data && data.accepted_and_started) {
                            setTimeout(() => {
                                const targetUrl = `${window.location.pathname}${window.location.search}#tasks-panel-{{ $serviceRequest->id }}`;
                                window.location.assign(targetUrl);
                            }, 150);
                            return;
                        }

                        const selectedOption = technicianSelect.options[technicianSelect.selectedIndex];
                        const selectedText = selectedOption ? selectedOption.textContent.trim() : '';
                        const parts = selectedText.split('(');
                        const assigneeName = (parts[0] || '').trim() || 'Técnico asignado';
                        const assigneeEmail = (data && data.assigned_to_email) ? data.assigned_to_email : (parts.length > 1 ? parts[1].replace(')', '').trim() : '');

                        if (typeof window.updateServiceRequestAssignment === 'function') {
                            window.updateServiceRequestAssignment({
                                requestId: '{{ $serviceRequest->id }}',
                                type: 'technician',
                                name: assigneeName,
                                email: assigneeEmail,
                            });
                        }

                        const quickTaskButton = document.querySelector('div[data-service-request-id="{{ $serviceRequest->id }}"] .open-quick-task');
                        if (quickTaskButton) {
                            quickTaskButton.dataset.disabled = 'false';
                            const enabledClass = quickTaskButton.dataset.enabledClass || '';
                            const disabledClass = quickTaskButton.dataset.disabledClass || '';
                            if (disabledClass) {
                                disabledClass.split(' ').forEach(cls => cls && quickTaskButton.classList.remove(cls));
                            }
                            if (enabledClass) {
                                enabledClass.split(' ').forEach(cls => cls && quickTaskButton.classList.add(cls));
                            }
                        }

                        const warning = document.querySelector('div[data-service-request-id="{{ $serviceRequest->id }}"] [data-quick-task-warning]');
                        if (warning) {
                            warning.classList.add('hidden');
                        }

                        const workflowBtn = document.querySelector('[data-workflow-action="assign-technician"][data-service-request-id="{{ $serviceRequest->id }}"]');
                        if (workflowBtn) {
                            workflowBtn.setAttribute('data-workflow-action', 'accept');
                            workflowBtn.setAttribute('data-modal-id', 'accept-modal-{{ $serviceRequest->id }}');
                            workflowBtn.setAttribute('onclick', "openModal('accept-modal-{{ $serviceRequest->id }}', this)");
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
                                label.textContent = 'Aceptar Solicitud';
                            } else {
                                workflowBtn.textContent = 'Aceptar Solicitud';
                            }
                        }

                        // Abrir el modal de aceptación sin recargar
                        const acceptModal = document.getElementById('accept-modal-{{ $serviceRequest->id }}');
                        if (acceptModal) {
                            const assigneeTarget = acceptModal.querySelector('[data-accept-assignee]');
                            if (assigneeTarget && selectedText) {
                                assigneeTarget.textContent = selectedText;
                                assigneeTarget.classList.remove('text-red-600');
                                assigneeTarget.classList.add('text-green-600', 'font-medium');
                            }
                            openModal('accept-modal-{{ $serviceRequest->id }}', technicianSelect);
                        }
                    });
                } else {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Error en la asignación');
                    });
                }
            })
            .catch(error => {
                if (typeof window.srNotify === 'function') window.srNotify(false, 'Error al asignar técnico: ' + error.message);
            })
            .finally(() => {
                form.removeAttribute('aria-busy');
                buttonSnapshots.forEach(({ btn, html }) => {
                    btn.disabled = false;
                    btn.innerHTML = html;
                });
            });
        });
    }
});
</script>
