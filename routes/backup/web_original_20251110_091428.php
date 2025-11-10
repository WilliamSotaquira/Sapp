<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// RUTAS PÚBLICAS
// =============================================================================

Route::get('/', function () {
    return view('welcome');
});

// =============================================================================
// RUTAS AUTENTICADAS
// =============================================================================

Route::middleware('auth')->group(function () {

    // =========================================================================
    // DASHBOARD PRINCIPAL
    // =========================================================================

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('verified')->name('dashboard');

    // =========================================================================
    // FEATURES DEL SISTEMA
    // =========================================================================

    // Catálogo de servicios
    require __DIR__ . '/features/service-catalog/web.php';

    // Gestión de solicitudes de servicio
    require __DIR__ . '/features/service-requests/web.php';
    require __DIR__ . '/features/service-requests/workflow.php';

    // Gestión de SLAs
    require __DIR__ . '/features/sla-management/web.php';

    // Gestión de evidencias
    require __DIR__ . '/features/evidence-management/web.php';

    // Reportes y analytics
    require __DIR__ . '/features/reporting/web.php';
    require __DIR__ . '/features/reporting/exports.php';

    // Gestión de usuarios
    require __DIR__ . '/features/user-management/web.php';

    // =========================================================================
    // APIS PARA FORMULARIOS WEB
    // =========================================================================
    require __DIR__ . '/web-api.php';

    // =========================================================================
    // REQUIREMENTS
    // =========================================================================
    require __DIR__ . '/requirements.php';
});

// =============================================================================
// ARCHIVOS EXTERNOS
// =============================================================================

require __DIR__ . '/auth.php';
require __DIR__ . '/service-recuest.php';
