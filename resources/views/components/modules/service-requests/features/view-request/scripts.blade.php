@props([
    'webRoutes' => false,
    'slaManagement' => false,
    'formValidation' => false,
    'serviceRequest' => null
])

<script>
    // Definir la clase globalmente inmediatamente
    class ServiceRequestModals {
        constructor() {
            this.modals = ['accept', 'pause', 'cancel', 'close', 'report', 'sla-create'];
            // NO inicializar aquí, se hará después
        }

        init() {
            this.bindEvents();
            console.log('🔧 ServiceRequestModals inicializado correctamente');
        }

        bindEvents() {
            // Cerrar con Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeAll();
            });

            // Cerrar haciendo click fuera
            document.addEventListener('click', (e) => {
                this.modals.forEach(modal => {
                    const element = document.getElementById(`${modal}Modal`);
                    if (element && e.target === element) this.close(modal);
                });
            });
        }

        open(modalName) {
            const modal = document.getElementById(`${modalName}Modal`);
            if (modal) {
                modal.classList.remove('hidden');
                console.log(`📂 Modal ${modalName} abierto`);
            }
        }

        close(modalName) {
            const modal = document.getElementById(`${modalName}Modal`);
            if (modal) {
                modal.classList.add('hidden');
                console.log(`📂 Modal ${modalName} cerrado`);
            }
        }

        closeAll() {
            this.modals.forEach(modal => this.close(modal));
            console.log('📂 Todos los modales cerrados');
        }
    }

    // Crear instancia global INMEDIATAMENTE
    window.serviceRequestModals = new ServiceRequestModals();

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        window.serviceRequestModals.init();

        // Debug información - CON VERIFICACIÓN SEGURA
        console.log('🔧 Scripts cargados correctamente');

        @if(isset($serviceRequest) && $serviceRequest)
            console.log('Estado de la solicitud:', '{{ $serviceRequest->status }}');
            console.log('¿Puede aceptar?', '{{ $serviceRequest->status === "PENDIENTE" ? "true" : "false" }}');
            console.log('¿Puede agregar evidencias?', '{{ in_array($serviceRequest->status, ["ACEPTADA", "EN_PROCESO"]) ? "true" : "false" }}');
        @else
            console.log('✅ Modo creación - No hay solicitud existente');
            console.log('Estado de la solicitud:', 'No aplica');
            console.log('¿Puede aceptar?', 'false');
            console.log('¿Puede agregar evidencias?', 'false');
        @endif
    });
</script>

@if($webRoutes)
<script>
    console.log('🌐 Scripts de rutas web cargados');
</script>
@endif

@if($slaManagement)
<script>
    console.log('⏱️ Scripts de gestión SLA cargados');
</script>
@endif

@if($formValidation)
<script>
    console.log('📝 Scripts de validación de formularios cargados');
</script>
@endif
