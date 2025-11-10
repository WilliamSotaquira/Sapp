console.log('create.js loaded successfully');

class ServiceRequestCreator {
    constructor() {
        console.log('ServiceRequestCreator initialized');
        this.serviceFamilyFilter = document.getElementById('service_family_filter');
        this.subServiceSelect = document.getElementById('sub_service_id');
        this.init();
    }

    init() {
        console.log('ServiceRequestCreator init called');
        this.initializeServiceSelectors();
        this.initializeWebRoutes();
    }

    initializeServiceSelectors() {
        if (this.serviceFamilyFilter && this.subServiceSelect) {
            this.serviceFamilyFilter.addEventListener('change', (e) => {
                this.filterSubServices(e.target.value);
            });

            console.log('Service selectors initialized');
        }
    }

    filterSubServices(selectedFamily) {
        const options = this.subServiceSelect.querySelectorAll('option');

        options.forEach(option => {
            if (option.value === '' || option.dataset.family === selectedFamily || !selectedFamily) {
                option.style.display = '';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });

        // Mostrar/ocultar optgroups
        const optgroups = this.subServiceSelect.querySelectorAll('optgroup');
        optgroups.forEach(optgroup => {
            const family = optgroup.getAttribute('data-family');
            if (!selectedFamily || family === selectedFamily) {
                optgroup.style.display = '';
            } else {
                optgroup.style.display = 'none';
            }
        });

        // Resetear selección si es necesario
        if (this.subServiceSelect.value) {
            const selectedOption = this.subServiceSelect.options[this.subServiceSelect.selectedIndex];
            if (selectedOption && selectedOption.style.display === 'none') {
                this.subServiceSelect.value = '';
            }
        }

        console.log('Sub-services filtered for family:', selectedFamily);
    }

    initializeWebRoutes() {
        const addRouteButton = document.getElementById('add-route-btn');
        const routesContainer = document.getElementById('web-routes-container');

        if (addRouteButton && routesContainer) {
            addRouteButton.addEventListener('click', () => {
                this.addRouteField();
            });

            // Delegar eventos para eliminar rutas
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-route')) {
                    e.preventDefault();
                    e.target.closest('.route-item').remove();
                }
            });

            // Agregar campo inicial
            if (routesContainer.children.length === 0) {
                this.addRouteField();
            }

            console.log('Web routes initialized');
        }
    }

    addRouteField() {
        const routesContainer = document.getElementById('web-routes-container');
        const routeCount = routesContainer.querySelectorAll('.route-item').length;

        const routeHtml = `
            <div class="route-item flex gap-2 mb-2">
                <input type="text" name="web_routes[${routeCount}][name]"
                       placeholder="Nombre de la ruta"
                       class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
                <input type="text" name="web_routes[${routeCount}][url]"
                       placeholder="URL (ej: /api/users)"
                       class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
                <button type="button" class="remove-route bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition duration-200">
                    ×
                </button>
            </div>
        `;

        routesContainer.insertAdjacentHTML('beforeend', routeHtml);
        console.log('Route field added, total routes:', routeCount + 1);
    }
}

// Auto-inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.serviceRequestCreator = new ServiceRequestCreator();
    console.log('ServiceRequestCreator auto-initialized');
});
Google Meet
