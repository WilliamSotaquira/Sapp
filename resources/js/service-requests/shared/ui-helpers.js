// Utilidades de UI reutilizables
export class UIHelpers {
    static toggleSection(showButton, hideButton, section) {
        if (showButton && hideButton && section) {
            showButton.addEventListener('click', () => {
                section.classList.remove('hidden');
                showButton.classList.add('hidden');
            });

            hideButton.addEventListener('click', () => {
                section.classList.add('hidden');
                showButton.classList.remove('hidden');
            });
        }
    }

    static formatTime(minutes) {
        minutes = parseInt(minutes);
        if (minutes < 60) return `${minutes} min`;
        if (minutes < 1440) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return mins > 0 ? `${hours}h ${mins}min` : `${hours} horas`;
        }
        const days = Math.floor(minutes / 1440);
        const hours = Math.floor((minutes % 1440) / 60);
        return hours > 0 ? `${days}d ${hours}h` : `${days} días`;
    }

    static showLoading(button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';
        button.disabled = true;
        return originalText;
    }

    static hideLoading(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
    }

    static isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
}
