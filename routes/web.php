<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceFamilyController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubServiceController;
use App\Http\Controllers\SLAController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceRequestEvidenceController;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use Illuminate\Http\Request;
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

    // =============================================================================
    // RUTAS PARA FORMULARIO DE SLA - CARGAR DATOS DINÁMICOS
    // =============================================================================

    // Cargar servicios por familia
    Route::get('/api/service-families/{familyId}/services', function ($familyId) {
        try {
            \Log::info("Cargando servicios para familia: " . $familyId);

            $services = Service::where('service_family_id', $familyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            \Log::info("Servicios encontrados: " . $services->count());

            return response()->json($services);
        } catch (\Exception $e) {
            \Log::error('Error al cargar servicios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar servicios'], 500);
        }
    })->name('api.service-families.services');

    // Cargar subservicios por servicio
    Route::get('/api/services/{serviceId}/sub-services', function ($serviceId) {
        try {
            \Log::info("Cargando subservicios para servicio: " . $serviceId);

            $subServices = SubService::where('service_id', $serviceId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            \Log::info("Subservicios encontrados: " . $subServices->count());

            return response()->json($subServices);
        } catch (\Exception $e) {
            \Log::error('Error al cargar subservicios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar subservicios'], 500);
        }
    })->name('api.services.sub-services');

    // Buscar o crear service_subservice
    // Buscar o crear service_subservice
    Route::post('/api/service-subservices/find-or-create', function (Request $request) {
        try {
            \Log::info("=== INICIANDO FIND-OR-CREATE ===");
            \Log::info("Datos recibidos:", $request->all());

            $validated = $request->validate([
                'service_family_id' => 'required|exists:service_families,id',
                'service_id' => 'required|exists:services,id',
                'sub_service_id' => 'required|exists:sub_services,id'
            ]);

            \Log::info("Datos validados:", $validated);

            // Buscar si ya existe
            $serviceSubservice = ServiceSubservice::where([
                'service_family_id' => $validated['service_family_id'],
                'service_id' => $validated['service_id'],
                'sub_service_id' => $validated['sub_service_id']
            ])->first();

            \Log::info("ServiceSubservice encontrado:", [$serviceSubservice ? $serviceSubservice->toArray() : 'No encontrado']);

            // Si no existe, crear uno nuevo
            if (!$serviceSubservice) {
                \Log::info("Creando nuevo service subservice");

                // Obtener nombres para crear un nombre descriptivo
                $serviceFamily = ServiceFamily::find($validated['service_family_id']);
                $service = Service::find($validated['service_id']);
                $subService = SubService::find($validated['sub_service_id']);

                \Log::info("Modelos encontrados:", [
                    'service_family' => $serviceFamily ? $serviceFamily->name : 'No encontrado',
                    'service' => $service ? $service->name : 'No encontrado',
                    'sub_service' => $subService ? $subService->name : 'No encontrado'
                ]);

                if (!$serviceFamily || !$service || !$subService) {
                    throw new \Exception('No se pudieron encontrar los modelos relacionados');
                }

                $serviceSubservice = ServiceSubservice::create([
                    'service_family_id' => $validated['service_family_id'],
                    'service_id' => $validated['service_id'], // Asegurar que service_id se incluya
                    'sub_service_id' => $validated['sub_service_id'], // Asegurar que sub_service_id se incluya
                    'name' => $serviceFamily->name . ' - ' . $service->name . ' - ' . $subService->name,
                    'description' => 'Combinación automática: ' . $serviceFamily->name . ', ' . $service->name . ', ' . $subService->name,
                    'is_active' => true
                ]);

                \Log::info("Service subservice creado:", $serviceSubservice->toArray());
            } else {
                \Log::info("Service subservice encontrado con ID: " . $serviceSubservice->id);
            }

            return response()->json([
                'id' => $serviceSubservice->id,
                'name' => $serviceSubservice->name
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en find-or-create service subservice: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    })->name('api.service-subservices.find-or-create');

    // Obtener datos de service_subservice por ID - VERSIÓN SIMPLIFICADA
Route::get('/api/service-subservices/{id}', function ($id) {
    try {
        // Buscar directamente sin relaciones complejas
        $serviceSubservice = \App\Models\ServiceSubservice::find($id);

        if (!$serviceSubservice) {
            return response()->json(['error' => 'Service subservice not found'], 404);
        }

        return response()->json([
            'id' => $serviceSubservice->id,
            'service_family_id' => $serviceSubservice->service_family_id,
            'service_id' => $serviceSubservice->service_id,
            'sub_service_id' => $serviceSubservice->sub_service_id
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en API service-subservices: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error'], 500);
    }
})->name('api.service-subservices.show');

});

require __DIR__ . '/auth.php';
