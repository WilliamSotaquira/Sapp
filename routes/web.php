<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceFamilyController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubServiceController;
use App\Http\Controllers\SLAController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceRequestEvidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/requirements', function () {
        return view('requirements.index');
    })->name('requirements.index');

    // Rutas del módulo de servicios
    Route::resource('service-families', ServiceFamilyController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('sub-services', SubServiceController::class);
    Route::resource('slas', SLAController::class);
    Route::resource('service-requests', ServiceRequestController::class);

    // Rutas adicionales para ServiceRequest
    Route::post('/service-requests/{serviceRequest}/accept', [ServiceRequestController::class, 'accept'])->name('service-requests.accept');
    Route::post('/service-requests/{serviceRequest}/start', [ServiceRequestController::class, 'start'])->name('service-requests.start');
    Route::post('/service-requests/{serviceRequest}/resolve', [ServiceRequestController::class, 'resolve'])->name('service-requests.resolve');
    Route::post('/service-requests/{serviceRequest}/close', [ServiceRequestController::class, 'close'])->name('service-requests.close');
    Route::post('/service-requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])->name('service-requests.cancel');

    // Rutas para pausa/reanudar
    Route::post('/service-requests/{serviceRequest}/pause', [ServiceRequestController::class, 'pause'])->name('service-requests.pause');
    Route::post('/service-requests/{serviceRequest}/resume', [ServiceRequestController::class, 'resume'])->name('service-requests.resume');

    // Rutas de evidencias para solicitudes de servicio
    Route::prefix('service-requests/{serviceRequest}')->group(function () {
        // Evidencias
        Route::get('/evidences/create', [ServiceRequestEvidenceController::class, 'create'])
            ->name('service-requests.evidences.create');
        Route::post('/evidences', [ServiceRequestEvidenceController::class, 'store'])
            ->name('service-requests.evidences.store');
        Route::get('/evidences/{evidence}', [ServiceRequestEvidenceController::class, 'show'])
            ->name('service-requests.evidences.show');
        Route::delete('/evidences/{evidence}', [ServiceRequestEvidenceController::class, 'destroy'])
            ->name('service-requests.evidences.destroy');
        Route::get('/evidences/{evidence}/download', [ServiceRequestEvidenceController::class, 'download'])
            ->name('service-requests.evidences.download');

        // Ruta para visualizar archivos
        Route::get('/evidences/{evidence}/view', [ServiceRequestEvidenceController::class, 'view'])
            ->name('service-requests.evidences.view');

        // API para evidencias
        Route::get('/evidences-json', [ServiceRequestEvidenceController::class, 'getEvidences'])
            ->name('service-requests.evidences.json');
    });

    // Rutas específicas para resolución con evidencias
    Route::get('/service-requests/{serviceRequest}/resolve-form', [ServiceRequestController::class, 'showResolveForm'])
        ->name('service-requests.resolve-form');
    Route::post('/service-requests/{serviceRequest}/resolve-with-evidence', [ServiceRequestController::class, 'resolveWithEvidence'])
        ->name('service-requests.resolve-with-evidence');

    // Rutas para AJAX
    Route::get('/service-families/{serviceFamily}/services', [ServiceFamilyController::class, 'getServices'])->name('service-families.services');
    Route::get('/services/{service}/sub-services', [SubServiceController::class, 'getByService'])->name('services.sub-services');
    Route::get('/sub-services/{subService}/slas', [ServiceRequestController::class, 'getSlas'])->name('sub-services.slas');

    // =============================================
    // RUTAS PARA REPORTES DE LÍNEA DE TIEMPO
    // =============================================

    // Reporte de línea de tiempo
    Route::get('/reports/request-timeline', [ReportController::class, 'requestTimeline'])
        ->name('reports.request-timeline');

    Route::get('/reports/timeline-detail/{id}', [ReportController::class, 'showTimeline'])
        ->name('reports.timeline-detail');

    Route::get('/reports/export-timeline/{id}/{format}', [ReportController::class, 'exportTimeline'])
        ->name('reports.export-timeline');

    // Timeline desde el módulo de ServiceRequests
    Route::get('/service-requests/{serviceRequest}/timeline', [ServiceRequestController::class, 'showTimeline'])
        ->name('service-requests.timeline');

    // Rutas de reportes existentes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sla-compliance', [ReportController::class, 'slaCompliance'])->name('sla-compliance');
        Route::get('/requests-by-status', [ReportController::class, 'requestsByStatus'])->name('requests-by-status');
        Route::get('/criticality-levels', [ReportController::class, 'criticalityLevels'])->name('criticality-levels');
        Route::get('/service-performance', [ReportController::class, 'servicePerformance'])->name('service-performance');
        Route::get('/monthly-trends', [ReportController::class, 'monthlyTrends'])->name('monthly-trends');

        // Export routes
        Route::get('/export/{reportType}/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/{reportType}/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    });
});

require __DIR__ . '/auth.php';
