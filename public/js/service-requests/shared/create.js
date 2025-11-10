console.log('create.js loaded successfully');

class ServiceRequestCreator {
    constructor() {
        console.log('ServiceRequestCreator initialized');
        this.init();
    }

    init() {
        console.log('ServiceRequestCreator init called');
        // La funcionalidad principal ya está en el script inline
    }
}

// Auto-inicialización
document.addEventListener('DOMContentLoaded', function() {
    window.serviceRequestCreator = new ServiceRequestCreator();
});
