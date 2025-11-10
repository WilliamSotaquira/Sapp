class FormValidator {
    static validateServiceRequestForm() {
        const errors = [];

        // Validar campos requeridos
        const requiredFields = [
            { id: 'sub_service_id', name: 'Sub-servicio' },
            { id: 'criticality_level', name: 'Nivel de criticidad' },
            { id: 'title', name: 'Título' },
            { id: 'description', name: 'Descripción' }
        ];

        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (!element || !element.value.trim()) {
                errors.push(`${field.name} es requerido`);
            }
        });

        // Validar rutas web si existen
        const routeInputs = document.querySelectorAll('input[name^="web_routes"]');
        routeInputs.forEach(input => {
            if (input.value.trim() && !this.validateURL(input.value)) {
                errors.push(`La URL "${input.value}" no tiene un formato válido`);
            }
        });

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    static validateURL(url) {
        if (!url) return true; // URLs vacías son válidas (no requeridas)
        try {
            // Validar formato básico de URL
            return /^(\/|https?:\/\/)/.test(url);
        } catch {
            return false;
        }
    }

    static showValidationErrors(errors) {
        // Limpiar errores anteriores
        this.clearValidationErrors();

        // Mostrar nuevos errores
        errors.forEach(error => {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-50 border border-red-200 rounded-md p-4 mb-4';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <span class="text-red-700">${error}</span>
                </div>
            `;

            const form = document.getElementById('serviceRequestForm');
            if (form) {
                form.insertBefore(errorDiv, form.firstChild);
            }
        });

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    static clearValidationErrors() {
        const errorElements = document.querySelectorAll('.bg-red-50.border-red-200');
        errorElements.forEach(element => element.remove());
    }
}
