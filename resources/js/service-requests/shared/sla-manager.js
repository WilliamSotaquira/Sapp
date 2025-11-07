import { UIHelpers } from './ui-helpers.js';

export class SLAManager {
    constructor() {
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
    }

    async loadSLAs() {
        const subServiceId = this.subServiceSelect.value;
        console.log('Cargando SLAs para sub-service:', subServiceId);

        if (!subServiceId) {
            this.slaSelect.innerHTML = '<option value="">Seleccione un sub-servicio primero</option>';
            this.hideSLAInfo();
            this.hideCreateButton();
            return;
        }

        this.slaSelect.innerHTML = '<option value="">Cargando SLAs...</option>';
        this.hideSLAInfo();

        try {
            const response = await fetch(`/api/sub-services/${subServiceId}/slas`);

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (!Array.isArray(data)) {
                throw new Error('Formato de respuesta inválido');
            }

            this.populateSLAs(data);

        } catch (error) {
            console.error('Error loading SLAs:', error);
            this.slaSelect.innerHTML = `<option value="">Error: ${error.message}</option>`;
            this.showCreateButton();
        }
    }

    populateSLAs(slas) {
        this.slaSelect.innerHTML = '';

        if (slas.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay SLAs disponibles';
            this.slaSelect.appendChild(option);
            this.showCreateButton();
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
            this.slaSelect.appendChild(option);
        });

        this.hideCreateButton();
    }

    showSLAInfo() {
        const selectedOption = this.slaSelect.options[this.slaSelect.selectedIndex];

        if (selectedOption.value && selectedOption.hasAttribute('data-acceptance')) {
            const acceptance = selectedOption.getAttribute('data-acceptance');
            const response = selectedOption.getAttribute('data-response');
            const resolution = selectedOption.getAttribute('data-resolution');

            document.getElementById('acceptance_time').textContent = UIHelpers.formatTime(acceptance);
            document.getElementById('response_time').textContent = UIHelpers.formatTime(response);
            document.getElementById('resolution_time').textContent = UIHelpers.formatTime(resolution);

            this.slaInfo.classList.remove('hidden');
        } else {
            this.hideSLAInfo();
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
        document.getElementById('modal_sub_service_id').value = subServiceId;

        if (this.createSlaModal) {
            this.createSlaModal.classList.remove('hidden');
        }
    }

    closeModal() {
        if (this.createSlaModal) {
            this.createSlaModal.classList.add('hidden');
            this.createSlaForm.reset();
        }
    }

    async createSLA(event) {
        event.preventDefault();

        const submitButton = this.createSlaForm.querySelector('button[type="submit"]');
        const originalText = UIHelpers.showLoading(submitButton);

        try {
            const formData = new FormData(this.createSlaForm);

            // Validación básica
            const name = document.getElementById('sla_name').value.trim();
            const criticality = document.getElementById('sla_criticality').value;

            if (!name || !criticality) {
                throw new Error('Por favor complete todos los campos requeridos');
            }

            const response = await fetch('/slas/create-from-modal', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.sla) {
                this.closeModal();

                // Recargar SLAs y seleccionar el nuevo
                await this.loadSLAs();

                setTimeout(() => {
                    this.slaSelect.value = data.sla.id;
                    this.slaSelect.dispatchEvent(new Event('change'));
                }, 500);

                alert('✅ SLA creado exitosamente');
            } else {
                throw new Error(data.message || 'Error desconocido');
            }

        } catch (error) {
            console.error('Error creating SLA:', error);
            alert('❌ Error al crear el SLA: ' + error.message);
        } finally {
            UIHelpers.hideLoading(submitButton, originalText);
        }
    }
}
