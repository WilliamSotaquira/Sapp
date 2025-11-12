<!-- Modal de Asignación de Técnico - VERSIÓN CORREGIDA -->
<div id="assign-technician-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mr-3">
                    <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Asignar Técnico
                </h3>
            </div>
            <button type="button"
                    onclick="document.getElementById('assign-technician-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200">
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
                                @if($technician->email)
                                    ({{ $technician->email }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        onclick="document.getElementById('assign-technician-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Asignar y Continuar
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

            console.log('🔍 Iniciando asignación de técnico...');

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const technicianSelect = document.getElementById('assigned_to_{{ $serviceRequest->id }}');

            // Validar que se seleccionó un técnico
            if (!technicianSelect.value) {
                alert('❌ Por favor selecciona un técnico');
                return;
            }

            console.log('✅ Técnico seleccionado:', technicianSelect.value);

            // Mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Asignando...';

            // Enviar formulario
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('📨 Respuesta del servidor:', response.status);

                if (response.ok) {
                    return response.json().then(data => {
                        console.log('✅ Asignación exitosa:', data);
                        // Cerrar modal y recargar
                        document.getElementById('assign-technician-modal-{{ $serviceRequest->id }}').classList.add('hidden');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    });
                } else {
                    return response.json().then(errorData => {
                        console.error('❌ Error del servidor:', errorData);
                        throw new Error(errorData.message || 'Error en la asignación');
                    });
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                alert('❌ Error al asignar técnico: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>
