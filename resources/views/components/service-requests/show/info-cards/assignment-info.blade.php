@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-users text-green-600 mr-3"></i>
            Asignación y Responsables
        </h3>
    </div>

    <div class="p-6">
        <!-- Alertas de estado -->
        @if ($serviceRequest->status === 'EN_PROCESO' && !$serviceRequest->assigned_to)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    <div>
                        <p class="text-red-800 font-semibold">Inconsistencia detectada</p>
                        <p class="text-red-600 text-sm">La solicitud está en proceso pero no tiene técnico asignado.</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($serviceRequest->status === 'ACEPTADA' && !$serviceRequest->assigned_to)
            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-amber-500 mr-3"></i>
                    <div>
                        <p class="text-amber-800 font-semibold">Asignación pendiente</p>
                        <p class="text-amber-600 text-sm">La solicitud está aceptada pero requiere asignación de técnico
                            para iniciar el proceso.</p>
                        @if($serviceRequest->status !== 'CERRADA')
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                                class="inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-transparent text-xs font-medium rounded-full text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                                <i class="fas fa-edit mr-1"></i>
                                Editar Solicitud
                            </a>
                            <button type="button" data-request-id="{{ $serviceRequest->id }}"
                                class="quick-assign-btn inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-transparent text-xs font-medium rounded-full text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-user-plus mr-1"></i>
                                Asignar Técnico
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @php
            $availableRequesters = \App\Models\Requester::orderBy('name')
                ->where('is_active', true)
                ->get();
        @endphp
        <div class="space-y-6">
            <!-- Solicitante -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div
                    class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ substr($serviceRequest->requester->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0 w-full">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-500 block">Solicitante</label>
                            <p class="text-gray-900 font-semibold break-words">{{ $serviceRequest->requester->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 break-words">{{ $serviceRequest->requester->email ?? '' }}</p>
                        </div>
                        @if($serviceRequest->status !== 'CERRADA')
                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                            <button type="button" data-request-id="{{ $serviceRequest->id }}"
                                class="quick-requester-btn inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                <i class="fas fa-user-edit mr-1"></i>
                                <span>{{ $serviceRequest->requester ? 'Reasignar' : 'Asignar' }}</span>
                            </button>
                            <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                                class="inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-transparent text-xs font-medium rounded-full text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                <i class="fas fa-edit mr-1"></i>
                                Editar
                            </a>
                        </div>
                        @endif
                    </div>
                    @if(!$serviceRequest->requester)
                        <p class="text-sm text-amber-600 font-medium mt-2">
                            ⚠️ Asigna un solicitante para completar la información de contacto.
                        </p>
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Asignado a -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                @if ($serviceRequest->assigned_to)
                    <div
                        class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ substr($serviceRequest->assignee->name ?? 'T', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-500 block">Técnico Asignado</label>
                                <p class="text-gray-900 font-semibold break-words">{{ $serviceRequest->assignee->name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-500 break-words">{{ $serviceRequest->assignee->email ?? '' }}</p>
                            </div>
                            @if($serviceRequest->status !== 'CERRADA')
                            <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                                <button type="button" data-request-id="{{ $serviceRequest->id }}"
                                    class="quick-assign-btn inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <i class="fas fa-sync-alt mr-1"></i>
                                    <span>Reasignar</span>
                                </button>
                                <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                                    class="inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-transparent text-xs font-medium rounded-full text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <i class="fas fa-edit mr-1"></i>
                                    Editar
                                </a>
                            </div>
                            @endif
                        </div>

                        @if ($serviceRequest->status === 'ACEPTADA')
                            <div
                                class="mt-2 inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>
                                Listo para iniciar proceso
                            </div>
                        @endif
                    </div>
                @else
                    <div
                        class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600">
                        <i class="fas fa-user-plus text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-500 block">Técnico Asignado</label>
                            <p
                                class="text-gray-900 font-semibold @if ($serviceRequest->status === 'EN_PROCESO') text-red-600 @elseif($serviceRequest->status === 'ACEPTADA') text-amber-600 @endif">
                                No asignado
                                @if ($serviceRequest->status === 'EN_PROCESO')
                                    <i class="fas fa-exclamation-circle text-red-500 ml-2"></i>
                                @elseif($serviceRequest->status === 'ACEPTADA')
                                    <i class="fas fa-info-circle text-amber-500 ml-2"></i>
                                @endif
                            </p>
                        </div>
                        @if($serviceRequest->status !== 'CERRADA')
                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                            <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                                class="inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-edit mr-1"></i>
                                Editar Solicitud
                            </a>
                                <button type="button" data-request-id="{{ $serviceRequest->id }}"
                                    class="quick-assign-btn inline-flex items-center justify-center w-full sm:w-auto px-3 py-1.5 border border-transparent text-xs font-medium rounded-full text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <i class="fas fa-user-plus mr-1"></i>
                                    Asignar Técnico
                                </button>
                            </div>
                            @endif
                        </div>
                        <p
                            class="text-sm @if ($serviceRequest->status === 'EN_PROCESO') text-red-500 font-medium @elseif($serviceRequest->status === 'ACEPTADA') text-amber-600 font-medium @else text-gray-500 @endif mt-1">
                            @if ($serviceRequest->status === 'EN_PROCESO')
                                ⚠️ Asignación requerida para continuar
                            @elseif($serviceRequest->status === 'ACEPTADA')
                                📋 Asignación requerida para iniciar el proceso
                            @else
                                Este caso requiere asignación de técnico
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            @if ($serviceRequest->status === 'CERRADA')
                <div class="mt-4 p-3 bg-gray-100 border border-gray-300 rounded-lg">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-lock mr-2"></i>
                        <span class="text-sm font-medium">Esta solicitud está cerrada. No se pueden realizar cambios de asignación.</span>
                    </div>
                </div>
            @endif

            @if ($serviceRequest->status === 'ACEPTADA' && $serviceRequest->assigned_to)
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center text-blue-700">
                        <i class="fas fa-play-circle mr-2"></i>
                        <span class="text-sm font-medium">Listo para iniciar proceso de trabajo</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Asignación Rápida - CORREGIDO -->
<div id="quickAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Asignar Técnico</h3>
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
<div id="quickRequesterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Asignar Solicitante</h3>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });

    function setupQuickAssignModal({ modalId, formId, selectId, closeButtonId, buttonSelector, actionPath, emptySelectMessage }) {
        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        const closeButton = document.getElementById(closeButtonId);
        const assignButtons = document.querySelectorAll(buttonSelector);
        const selectField = document.getElementById(selectId);

        if (!modal || !form || !closeButton || !assignButtons.length || !selectField) {
            return;
        }

        function openModal(serviceRequestId) {
            form.action = actionPath(serviceRequestId);
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            form.reset();
        }

        assignButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const requestId = this.getAttribute('data-request-id');
                if (!requestId) {
                    alert('Error: No se pudo identificar la solicitud');
                    return;
                }
                openModal(requestId);
            });
        });

        closeButton.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const selectedValue = selectField.value;

            if (!selectedValue) {
                alert(emptySelectMessage);
                selectField.focus();
                return;
            }

            try {
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

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                    closeModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Error de conexión');
                console.error('Error:', error);
            }
        });
    }
</script>
