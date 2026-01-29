@props(['serviceRequest'])

@php
    $entryChannelOptions = \App\Models\ServiceRequest::getEntryChannelOptions();
    $selectedEntryChannel = $serviceRequest->entry_channel;
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-cogs text-blue-600 mr-3"></i>
            Información del Servicio
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500">Espacio de trabajo</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->company->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Familia de Servicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->service->family->name ?? 'N/A' }}
                </p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Servicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->service->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Subservicio</label>
                <p class="text-gray-900 font-semibold">{{ $serviceRequest->subService->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 block mb-2">Canal de ingreso</label>
                @if ($selectedEntryChannel && isset($entryChannelOptions[$selectedEntryChannel]))
                    @php
                        $selectedOption = $entryChannelOptions[$selectedEntryChannel];
                    @endphp
                    <span
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-sm font-semibold min-h-[2.5rem]">
                        <span class="text-lg">{{ $selectedOption['emoji'] ?? '📥' }}</span>
                        <span>{{ $selectedOption['label'] }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 text-sm">
                        No registrado
                    </span>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-500">Corte asociado</label>
                    <button type="button" data-edit-cut
                            class="inline-flex items-center justify-center h-6 px-2 rounded-full border border-indigo-100 bg-indigo-50 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        <i class="fas fa-edit mr-1 text-[10px]"></i>
                        {{ ($serviceRequest->cuts ?? collect())->isEmpty() ? 'Asignar' : 'Editar' }}
                    </button>
                </div>
                @php
                    $cuts = $serviceRequest->cuts ?? collect();
                @endphp
                <div id="cutAssociationContainer" class="mt-2">
                    @if ($cuts->isEmpty())
                        <p class="text-sm text-gray-500">Sin corte asociado.</p>
                    @else
                        @foreach ($cuts as $cut)
                            <a href="{{ route('reports.cuts.show', $cut) }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs font-semibold hover:bg-indigo-100 transition min-h-[2.5rem]">
                                <i class="fas fa-cut"></i>
                                <span class="truncate">{{ $cut->name }}</span>
                                <span class="text-[11px] text-indigo-500 font-medium whitespace-nowrap">{{ $cut->start_date->format('d/m/Y') }} — {{ $cut->end_date->format('d/m/Y') }}</span>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para editar corte -->
<div id="editCutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50" id="editCutModalBackdrop"></div>
    <div class="relative bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-sm mx-4">
        <!-- Cabecera del Modal -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Seleccionar Corte</h3>
            <button type="button" id="closeEditCutModal" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Cuerpo del Modal -->
        <form id="editCutForm" class="p-6 space-y-4">
            @csrf
            <div>
                <select id="cutSelect" name="cut_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm">
                    <option value="">Sin corte</option>
                    @forelse(\App\Models\Cut::orderBy('start_date', 'desc')->get() as $cut)
                        <option value="{{ $cut->id }}" {{ $cuts->contains($cut->id) ? 'selected' : '' }}>
                            {{ $cut->name }} — {{ $cut->start_date->format('d/m/Y') }} a {{ $cut->end_date->format('d/m/Y') }}
                        </option>
                    @empty
                        <option disabled>No hay cortes disponibles</option>
                    @endforelse
                </select>
            </div>

            <!-- Mensajes de estado -->
            <div id="editCutMessage" class="hidden p-3 rounded-lg text-sm"></div>

            <!-- Botones de acción -->
            <div class="flex gap-2 justify-end pt-2">
                <button type="button" id="cancelEditCutBtn" class="px-4 py-2 text-gray-700 hover:bg-gray-100 transition font-medium text-sm rounded-lg">
                    Cancelar
                </button>
                <button type="submit" id="saveCutBtn" class="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 transition font-medium text-sm rounded-lg">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editCutButtons = document.querySelectorAll('[data-edit-cut]');
    const editCutModal = document.getElementById('editCutModal');
    const closeEditCutModal = document.getElementById('closeEditCutModal');
    const cancelEditCutBtn = document.getElementById('cancelEditCutBtn');
    const editCutModalBackdrop = document.getElementById('editCutModalBackdrop');
    const editCutForm = document.getElementById('editCutForm');
    const editCutMessage = document.getElementById('editCutMessage');
    const saveCutBtn = document.getElementById('saveCutBtn');
    const serviceRequestId = {{ $serviceRequest->id }};

    // Abrir modal
    editCutButtons.forEach((btn) => {
        btn.addEventListener('click', function() {
            editCutModal.classList.remove('hidden');
        });
    });

    // Cerrar modal
    const closeModal = () => {
        editCutModal.classList.add('hidden');
        editCutMessage.classList.add('hidden');
    };

    closeEditCutModal.addEventListener('click', closeModal);
    cancelEditCutBtn.addEventListener('click', closeModal);
    editCutModalBackdrop.addEventListener('click', closeModal);

    // Enviar formulario
    editCutForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const cutId = document.getElementById('cutSelect').value;
        saveCutBtn.disabled = true;
        editCutMessage.classList.add('hidden');

        try {
            const response = await fetch(`/service-requests/${serviceRequestId}/update-cut`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    cut_id: cutId || null
                }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                editCutMessage.className = 'p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
                editCutMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                editCutMessage.classList.remove('hidden');

                const container = document.getElementById('cutAssociationContainer');
                if (container) {
                    if (!data.cut) {
                        container.innerHTML = '<p class="text-sm text-gray-500">Sin corte asociado.</p>';
                    } else {
                        const cut = data.cut;
                        const editBtn = document.querySelector('[data-edit-cut]');
                        if (editBtn) {
                            editBtn.innerHTML = '<i class="fas fa-edit mr-1 text-[10px]"></i>Editar';
                        }
                        container.innerHTML = `
                            <a href="/reports/cuts/${cut.id}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs font-semibold hover:bg-indigo-100 transition min-h-[2.5rem]">
                                <i class="fas fa-cut"></i>
                                <span class="truncate">${cut.name}</span>
                                <span class="text-[11px] text-indigo-500 font-medium whitespace-nowrap">${cut.start_date} — ${cut.end_date}</span>
                            </a>
                        `;
                    }
                }
            } else {
                editCutMessage.className = 'p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
                editCutMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || 'Error al actualizar');
                editCutMessage.classList.remove('hidden');
            }
        } catch (error) {
            editCutMessage.className = 'p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            editCutMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Error de conexión';
            editCutMessage.classList.remove('hidden');
        } finally {
            saveCutBtn.disabled = false;
        }
    });
});
</script>
