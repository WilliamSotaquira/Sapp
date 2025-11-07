export class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.init();
    }

    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }
    }

    validateForm(event) {
        const subServiceId = document.getElementById('sub_service_id')?.value;
        const slaId = document.getElementById('sla_id')?.value;
        const title = document.getElementById('title')?.value;
        const description = document.getElementById('description')?.value;

        const errors = [];

        if (!subServiceId) {
            errors.push('Debe seleccionar un sub-servicio');
        }

        if (!slaId) {
            errors.push('Debe seleccionar un SLA');
        }

        if (!title?.trim()) {
            errors.push('El título es requerido');
        }

        if (!description?.trim()) {
            errors.push('La descripción es requerida');
        }

        if (errors.length > 0) {
            event.preventDefault();
            this.showErrors(errors);
            return false;
        }

        return true;
    }

    showErrors(errors) {
        // Puedes usar un modal, toast, o alert simple
        alert('Por favor corrija los siguientes errores:\n\n• ' + errors.join('\n• '));

        // Opcional: Scroll al primer error
        const firstErrorField = this.form.querySelector('[required]');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }
    }
}
