<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate; // ✅ AGREGAR ESTA LÍNEA

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // ========== DEFINICIÓN DE GATES/POLÍTICAS ==========

        // ✅ AGREGAR ESTE GATE PARA ASIGNAR SOLICITUDES
        Gate::define('assign-service-requests', function ($user) {
            // ✅ PERMITIR TEMPORALMENTE A TODOS LOS USUARIOS
            return true;

            // ✅ DESPUÉS PUEDES RESTRINGIR POR:
            // - ID de usuario específico
            // return in_array($user->id, [1, 2, 3]);

            // - O por cualquier lógica que necesites
            // return $user->department === 'soporte';
        });

        // ========== ALIASES PARA COMPATIBILIDAD ==========

        // Componentes UI básicos
        Blade::component('components.ui.buttons.primary-button', 'primary-button');
        Blade::component('components.ui.buttons.secondary-button', 'secondary-button');
        Blade::component('components.ui.buttons.danger-button', 'danger-button');
        Blade::component('components.ui.forms.text-input', 'text-input');
        Blade::component('components.ui.forms.input-label', 'input-label');
        Blade::component('components.ui.forms.input-error', 'input-error');

        // Componentes de layout y navegación
        Blade::component('components.core.layout.application-logo', 'application-logo');
        Blade::component('components.core.navigation.nav-link', 'nav-link');
        Blade::component('components.core.navigation.responsive-nav-link', 'responsive-nav-link');
        Blade::component('components.core.feedback.auth-session-status', 'auth-session-status');
        Blade::component('components.ui.overlays.modal', 'modal');
        Blade::component('components.ui.overlays.dropdown', 'dropdown');
        Blade::component('components.ui.overlays.dropdown-link', 'dropdown-link');

        // Componentes de alerts
        Blade::component('components.core.feedback.alerts', 'alerts');

        // ========== ALIASES PARA SERVICE-REQUESTS ==========

        // Componentes de display/view
        Blade::component('components.modules.service-requests.features.view-request.general-info', 'service-requests.display.general-info');
        Blade::component('components.modules.service-requests.features.view-request.assignment-info', 'service-requests.display.assignment-info');
        Blade::component('components.modules.service-requests.features.view-request.service-details', 'service-requests.display.service-details');
        Blade::component('components.modules.service-requests.features.view-request.sla-info', 'service-requests.display.sla-info');
        Blade::component('components.modules.service-requests.features.view-request.evidences-section', 'service-requests.display.evidences-section');
        Blade::component('components.modules.service-requests.features.view-request.history-timeline', 'service-requests.display.history-timeline');
        Blade::component('components.modules.service-requests.features.view-request.pause-info', 'service-requests.display.pause-info');
        Blade::component('components.modules.service-requests.features.view-request.resolution-notes', 'service-requests.display.resolution-notes');
        Blade::component('components.modules.service-requests.features.view-request.satisfaction-score', 'service-requests.display.satisfaction-score');
        Blade::component('components.modules.service-requests.features.view-request.web-routes-info', 'service-requests.display.web-routes-info');

        // Componentes de formulario
        Blade::component('components.modules.service-requests.features.create-request.basic-fields', 'service-requests.form.fields.basic-fields');
        Blade::component('components.modules.service-requests.features.create-request.assignment-fields', 'service-requests.form.fields.assignment-fields');
        Blade::component('components.modules.service-requests.features.create-request.service-family-filter', 'service-requests.form.fields.service-family-filter');
        Blade::component('components.modules.service-requests.features.create-request.sub-service-select', 'service-requests.form.fields.sub-service-select');
        Blade::component('components.modules.service-requests.features.create-request.web-routes', 'service-requests.form.fields.web-routes');
        Blade::component('components.modules.service-requests.features.create-request.description', 'service-requests.form.sections.description');
        Blade::component('components.modules.service-requests.features.create-request.evidences-section', 'service-requests.form.sections.evidences-section');
        Blade::component('components.modules.service-requests.features.create-request.sla-fields', 'service-requests.form.sla.sla-fields');
        Blade::component('components.modules.service-requests.features.create-request.sla-timers', 'service-requests.form.sla.sla-timers');

        // Modales - CORREGIR ESTOS ALIASES
        Blade::component('components.modules.service-requests.modals.accept-modal', 'service-requests.modals.accept-modal');
        Blade::component('components.modules.service-requests.modals.cancel-modal', 'service-requests.modals.cancel-modal');
        Blade::component('components.modules.service-requests.modals.close-modal', 'service-requests.modals.close-modal');
        Blade::component('components.modules.service-requests.modals.pause-modal', 'service-requests.modals.pause-modal');
        Blade::component('components.modules.service-requests.modals.report-modal', 'service-requests.modals.report-modal');
        Blade::component('components.modules.service-requests.modals.sla-create', 'service-requests.modals.sla-create');
        Blade::component('components.modules.service-requests.modals.all', 'service-requests.modals.all');

        // Layout
        Blade::component('components.modules.service-requests.features.list-requests.breadcrumb', 'service-requests.layout.breadcrumb');
        Blade::component('components.modules.service-requests.features.view-request.header', 'service-requests.layout.header');
        Blade::component('components.modules.service-requests.features.view-request.scripts', 'service-requests.layout.scripts');
        Blade::component('components.modules.service-requests._config', 'service-requests._config');
    }
}
