<script>
    // Modal manager para solicitudes de servicio
    class ServiceRequestModals {
        constructor() {
            this.modals = ['accept', 'pause', 'cancel', 'close', 'report'];
            this.init();
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
                    if (e.target === element) this.close(modal);
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

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        window.serviceRequestModals = new ServiceRequestModals();

        // Debug información
        console.log('🔧 Scripts cargados correctamente');
        console.log('Estado de la solicitud:', '{{ $serviceRequest->status }}');
        console.log('¿Puede aceptar?', '{{ $serviceRequest->status === '
            PENDIENTE ' }}');
        console.log('¿Puede agregar evidencias?', '{{ in_array($serviceRequest->status, ['
            ACEPTADA ', '
            EN_PROCESO ']) }}');
    });
</script>
