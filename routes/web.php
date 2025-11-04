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
    Route::prefix('service-requests/{serviceRequest}')->group(function () {
        Route::post('/accept', [ServiceRequestController::class, 'accept'])->name('service-requests.accept');
        Route::post('/start', [ServiceRequestController::class, 'start'])->name('service-requests.start');
        Route::post('/resolve', [ServiceRequestController::class, 'resolve'])->name('service-requests.resolve');
        Route::post('/close', [ServiceRequestController::class, 'close'])->name('service-requests.close');
        Route::post('/cancel', [ServiceRequestController::class, 'cancel'])->name('service-requests.cancel');
        Route::post('/pause', [ServiceRequestController::class, 'pause'])->name('service-requests.pause');
        Route::post('/resume', [ServiceRequestController::class, 'resume'])->name('service-requests.resume');

        // Timeline
        Route::get('/timeline', [ServiceRequestController::class, 'showTimeline'])
            ->name('service-requests.timeline');

        // Resolución con evidencias
        Route::get('/resolve-form', [ServiceRequestController::class, 'showResolveForm'])
            ->name('service-requests.resolve-form');
        Route::post('/resolve-with-evidence', [ServiceRequestController::class, 'resolveWithEvidence'])
            ->name('service-requests.resolve-with-evidence');

        // Evidencias
        Route::prefix('evidences')->group(function () {
            Route::get('/create', [ServiceRequestEvidenceController::class, 'create'])
                ->name('service-requests.evidences.create');
            Route::post('/', [ServiceRequestEvidenceController::class, 'store'])
                ->name('service-requests.evidences.store');
            Route::get('/{evidence}', [ServiceRequestEvidenceController::class, 'show'])
                ->name('service-requests.evidences.show');
            Route::delete('/{evidence}', [ServiceRequestEvidenceController::class, 'destroy'])
                ->name('service-requests.evidences.destroy');
            Route::get('/{evidence}/download', [ServiceRequestEvidenceController::class, 'download'])
                ->name('service-requests.evidences.download');
            Route::get('/{evidence}/view', [ServiceRequestEvidenceController::class, 'view'])
                ->name('service-requests.evidences.view');
            Route::get('/json/list', [ServiceRequestEvidenceController::class, 'getEvidences'])
                ->name('service-requests.evidences.json');
        });
    });

    // Rutas para AJAX
    Route::get('/service-families/{serviceFamily}/services', [ServiceFamilyController::class, 'getServices'])
        ->name('service-families.services');
    Route::get('/services/{service}/sub-services', [SubServiceController::class, 'getByService'])
        ->name('services.sub-services');
    Route::get('/sub-services/{subService}/slas', [ServiceRequestController::class, 'getSlas'])
        ->name('sub-services.slas');

    // =============================================
    // RUTAS OPTIMIZADAS PARA REPORTES
    // =============================================

    Route::prefix('reports')->name('reports.')->group(function () {
        // Dashboard de reportes
        Route::get('/', [ReportController::class, 'index'])->name('index');

        // Reportes de análisis
        Route::get('/sla-compliance', [ReportController::class, 'slaCompliance'])->name('sla-compliance');
        Route::get('/requests-by-status', [ReportController::class, 'requestsByStatus'])->name('requests-by-status');
        Route::get('/criticality-levels', [ReportController::class, 'criticalityLevels'])->name('criticality-levels');
        Route::get('/service-performance', [ReportController::class, 'servicePerformance'])->name('service-performance');
        Route::get('/monthly-trends', [ReportController::class, 'monthlyTrends'])->name('monthly-trends');

        // Línea de tiempo
        Route::prefix('timeline')->name('timeline.')->group(function () {
            Route::get('/', [ReportController::class, 'requestTimeline'])->name('index');
            Route::get('/detail/{id}', [ReportController::class, 'showTimeline'])->name('detail');
            Route::get('/export/{id}/{format}', [ReportController::class, 'exportTimeline'])->name('export');
        });

        // Exportaciones
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/{reportType}/pdf', [ReportController::class, 'exportPdf'])->name('pdf');
            Route::get('/{reportType}/excel', [ReportController::class, 'exportExcel'])->name('excel');

            // Nuevas rutas para reporte de resumen
            Route::post('/summary-pdf', [ReportController::class, 'exportSummaryPDF'])->name('summary-pdf');
            Route::post('/summary-excel', [ReportController::class, 'exportSummaryExcel'])->name('summary-excel');
        });

        // Generación de reportes
        Route::prefix('generate')->name('generate.')->group(function () {
            Route::post('/summary', [ReportController::class, 'generateSummary'])->name('summary');
        });
    });
});

require __DIR__ . '/auth.php';
