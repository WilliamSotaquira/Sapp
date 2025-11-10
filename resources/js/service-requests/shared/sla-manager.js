// public/js/service-requests/shared/sla-manager.js
console.log('sla-manager.js loaded successfully');

class SLAManager {
    constructor() {
        console.log('SLAManager initialized');
        this.subServiceSelect = document.getElementById('sub_service_id');
        this.slaSelect = document.getElementById('sla_id');
        this.slaInfo = document.getElementById('sla_info');
        this.createSlaButton = document.getElementById('createSlaButton');
        this.createSlaModal = document.getElementById('createSlaModal');
        this.createSlaForm = document.getElementById('createSlaForm');
        this.closeSlaModal = document.getElementById('closeSlaModal');

        this.init();
    }

    init() {
        console.log('SLAManager init started');

        if (this.subServiceSelect && this.slaSelect) {
            this.subServiceSelect.addEventListener('change', () => this.loadSLAs());
        }

        if (this.slaSelect && this.slaInfo) {
            this.slaSelect.addEventListener('change', () => this.showSLAInfo());
        }

        if (this.createSlaButton) {
            this.createSlaButton.addEventListener('click', () => this.openSlaModal());
        }

        if (this.closeSlaModal) {
            this.closeSlaModal.addEventListener('click', () => this.closeModal());
        }

        if (this.createSlaForm) {
            this.createSlaForm.addEventListener('submit', (e) => this.createSLA(e));
        }

        console.log('SLAManager init completed');
    }

    async loadSLAs() {
        const subServiceId = this.subServiceSelect.value;
        console.log('Cargando SLAs para sub-service:', subServiceId);

        if (!subServiceId) {
            this.slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
            this.hideSLAInfo();
            this.hideCreateButton();
            this.clearSLAFields();
            return;
        }

        this.slaSelect.innerHTML = '<option value="">Cargando SLAs...</option>';
        this.hideSLAInfo();
        this.clearSLAFields();

        try {
            // Usar datos mock por ahora
            const mockSLAs = this.getMockSLAs();
            this.populateSLAs(mockSLAs);

        } catch (error) {
            console.error('Error loading SLAs:', error);
            this.slaSelect.innerHTML = `<option value="">Error: ${error.message}</option>`;
            this.showCreateButton();
            this.clearSLAFields();
        }
    }

    getMockSLAs() {
        return [
            {
                id: 1,
                name: 'SLA Básico',
                criticality_level: 'BAJA',
                acceptance_time_minutes: 30,
                response_time_minutes: 60,
                resolution_time_minutes: 240
            },
            {
                id: 2,
                name: 'SLA Estándar',
                criticality_level: 'MEDIA',
                acceptance_time_minutes: 15,
                response_time_minutes: 30,
                resolution_time_minutes: 120
            },
            {
                id: 3,
                name: 'SLA Crítico',
                criticality_level: 'ALTA',
                acceptance_time_minutes: 5,
                response_time_minutes: 15,
                resolution_time_minutes: 60
            }
        ];
    }

    populateSLAs(slas) {
        this.slaSelect.innerHTML = '';

        if (slas.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay SLAs disponibles';
            this.slaSelect.appendChild(option);
            this.showCreateButton();
            this.clearSLAFields();
            return;
        }

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Seleccione un SLA';
        this.slaSelect.appendChild(defaultOption);

        slas.forEach(sla => {
            const option = document.createElement('option');
            option.value = sla.id;
            option.textContent = `${sla.name} (${sla.criticality_level})`;
            option.setAttribute('data-acceptance', sla.acceptance_time_minutes);
            option.setAttribute('data-response', sla.response_time_minutes);
            option.setAttribute('data-resolution', sla.resolution_time_minutes);
            option.setAttribute('data-criticality', sla.criticality_level);
            option.setAttribute('data-sla-name', sla.name);
            this.slaSelect.appendChild(option);
        });

        this.hideCreateButton();
        console.log('SLAs populated:', slas.length, 'options');
    }

    showSLAInfo() {
        const selectedOption = this.slaSelect.options[this.slaSelect.selectedIndex];

        if (selectedOption.value && selectedOption.hasAttribute('data-acceptance')) {
            const acceptance = selectedOption.getAttribute('data-acceptance');
            const response = selectedOption.getAttribute('data-response');
            const resolution = selectedOption.getAttribute('data-resolution');
            const criticality = selectedOption.getAttribute('data-criticality');
            const slaName = selectedOption.getAttribute('data-sla-name');

            document.getElementById('acceptance_time').textContent = this.formatTime(acceptance);
            document.getElementById('response_time').textContent = this.formatTime(response);
            document.getElementById('resolution_time').textContent = this.formatTime(resolution);

            this.syncSLAFields({
                criticality_level: criticality,
                response_time: Math.round(response / 60 * 100) / 100,
                resolution_time: Math.round(resolution / 60 * 100) / 100,
                name: slaName
            });

            this.slaInfo.classList.remove('hidden');
            console.log('SLA info shown for:', slaName);
        } else {
            this.hideSLAInfo();
            this.clearSLAFields();
        }
    }

    syncSLAFields(slaData) {
        const criticalityLevel = document.getElementById('criticality_level');
        const responseTime = document.getElementById('response_time');
        const resolutionTime = document.getElementById('resolution_time');
        const slaNameField = document.getElementById('sla_name');

        if (criticalityLevel) criticalityLevel.value = slaData.criticality_level || '';
        if (responseTime) responseTime.value = slaData.response_time || '';
        if (resolutionTime) resolutionTime.value = slaData.resolution_time || '';
        if (slaNameField) slaNameField.value = slaData.name || '';

        console.log('SLA fields synchronized');
    }

    clearSLAFields() {
        const criticalityLevel = document.getElementById('criticality_level');
        const responseTime = document.getElementById('response_time');
        const resolutionTime = document.getElementById('resolution_time');
        const slaNameField = document.getElementById('sla_name');

        if (criticalityLevel) criticalityLevel.value = '';
        if (responseTime) responseTime.value = '';
        if (resolutionTime) resolutionTime.value = '';
        if (slaNameField) slaNameField.value = '';

        console.log('SLA fields cleared');
    }

    formatTime(minutes) {
        const mins = parseInt(minutes);
        if (mins < 60) {
            return `${mins} min`;
        } else if (mins < 1440) {
            const hours = (mins / 60).toFixed(1);
            return `${hours} h`;
        } else {
            const days = (mins / 1440).toFixed(1);
            return `${days} d`;
        }
    }

    hideSLAInfo() {
        if (this.slaInfo) {
            this.slaInfo.classList.add('hidden');
        }
    }

    showCreateButton() {
        if (this.createSlaButton) {
            this.createSlaButton.classList.remove('hidden');
        }
    }

    hideCreateButton() {
        if (this.createSlaButton) {
            this.createSlaButton.classList.add('hidden');
        }
    }

    openSlaModal() {
        const subServiceId = this.subServiceSelect.value;
        if (subServiceId) {
            document.getElementById('modal_sub_service_id').value = subServiceId;
        }

        if (this.createSlaModal) {
            this.createSlaModal.classList.remove('hidden');
            console.log('SLA modal opened');
        }
    }

    closeModal() {
        if (this.createSlaModal) {
            this.createSlaModal.classList.add('hidden');
            if (this.createSlaForm) {
                this.createSlaForm.reset();
            }
            console.log('SLA modal closed');
        }
    }

    async createSLA(event) {
        event.preventDefault();
        console.log('Creating SLA...');

        const submitButton = this.createSlaForm.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;

        try {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando...';
            submitButton.disabled = true;

            const formData = new FormData(this.createSlaForm);
            const name = document.getElementById('sla_name').value.trim();
            const criticality = document.getElementById('sla_criticality').value;

            if (!name || !criticality) {
                throw new Error('Por favor complete todos los campos requeridos');
            }

            // Simular creación
            setTimeout(() => {
                this.handleSLACreationSuccess();
            }, 1000);

        } catch (error) {
            console.error('Error creating SLA:', error);
            this.showNotification('❌ Error: ' + error.message, 'error');
        } finally {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    }

    handleSLACreationSuccess() {
        this.closeModal();
        this.showNotification('✅ SLA creado exitosamente', 'success');
        this.loadSLAs();
        console.log('SLA created successfully');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `<div class="flex items-center"><span class="text-sm">${message}</span></div>`;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Auto-inicialización
document.addEventListener('DOMContentLoaded', function() {
    window.slaManager = new SLAManager();
    console.log('SLAManager auto-initialized');
});
