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

    // =============================================================================
    // RUTAS PARA CREACIÓN DE SLA DESDE MODAL EN SOLICITUDES DE SERVICIO
    // =============================================================================

    // Obtener SLAs por sub-servicio - RUTA TEMPORAL MEJORADA
    Route::get('/sub-services/{subService}/slas', function ($subServiceId) {
        try {
            \Log::info("=== RUTA TEMPORAL MEJORADA - OBTENIENDO SLAS ===");
            \Log::info("Sub-service ID recibido: " . $subServiceId);

            // Validar que el ID sea numérico
            if (!is_numeric($subServiceId)) {
                \Log::warning("Sub-service ID no es numérico: " . $subServiceId);
                return response()->json([], 400);
            }

            $subServiceId = (int)$subServiceId;

            // Verificar que el sub-servicio existe
            $subService = \App\Models\SubService::find($subServiceId);
            if (!$subService) {
                \Log::warning("Sub-service no encontrado con ID: " . $subServiceId);
                return response()->json([]);
            }

            \Log::info("Sub-service encontrado: " . $subService->name);

            // Verificar estructura de la tabla
            if (!\Schema::hasTable('service_level_agreements')) {
                \Log::error("La tabla service_level_agreements no existe");
                return response()->json([], 200);
            }

            $columns = \Schema::getColumnListing('service_level_agreements');
            \Log::info("Columnas disponibles:", $columns);

            $slas = collect([]);

            // Método 1: Buscar por service_subservice_id
            if (in_array('service_subservice_id', $columns)) {
                \Log::info("Buscando SLAs por service_subservice_id para sub_service: " . $subServiceId);

                $serviceSubservices = \App\Models\ServiceSubservice::where('sub_service_id', $subServiceId)->get();
                \Log::info("ServiceSubservices encontrados: " . $serviceSubservices->count());

                if ($serviceSubservices->isNotEmpty()) {
                    $serviceSubserviceIds = $serviceSubservices->pluck('id')->toArray();
                    \Log::info("IDs de ServiceSubservice: ", $serviceSubserviceIds);

                    $slas = \App\Models\ServiceLevelAgreement::whereIn('service_subservice_id', $serviceSubserviceIds)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

                    \Log::info("SLAs encontrados por service_subservice_id: " . $slas->count());
                }
            }

            // Si no se encontraron SLAs, retornar array vacío
            if ($slas->isEmpty()) {
                \Log::info("No se encontraron SLAs para el sub-service: " . $subServiceId);
                return response()->json([]);
            }

            \Log::info("Total SLAs a retornar: " . $slas->count());

            // Formatear respuesta
            $formattedSlas = $slas->map(function ($sla) {
                return [
                    'id' => $sla->id,
                    'name' => $sla->name,
                    'criticality_level' => $sla->criticality_level,
                    'acceptance_time_minutes' => $sla->acceptance_time_minutes,
                    'response_time_minutes' => $sla->response_time_minutes,
                    'resolution_time_minutes' => $sla->resolution_time_minutes
                ];
            });

            return response()->json($formattedSlas);
        } catch (\Exception $e) {
            \Log::error('Error CRÍTICO en ruta temporal de SLAs: ' . $e->getMessage());
            \Log::error('Stack trace completo: ' . $e->getTraceAsString());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());

            // Retornar error en formato JSON
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    })->name('sub-services.slas.get');

    // Crear SLA desde el modal en solicitudes de servicio
    Route::post('/slas/create-from-modal', [SLAController::class, 'storeFromModal'])
        ->name('slas.create-from-modal');

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
                    'service_id' => $validated['service_id'],
                    'sub_service_id' => $validated['sub_service_id'],
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

    // Obtener datos de service_subservice por ID
    Route::get('/api/service-subservices/{id}', function ($id) {
        try {
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
