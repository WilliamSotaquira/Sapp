<?php

use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =============================================================================
// APIS PARA FORMULARIOS WEB
// =============================================================================

Route::prefix('api')->name('api.')->group(function () {

    // =========================================================================
    // CARGAR DATOS JERÁRQUICOS
    // =========================================================================

    // Cargar servicios por familia
    Route::get('/service-families/{familyId}/services', function ($familyId) {
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
    })->name('service-families.services');

    // Cargar subservicios por servicio
    Route::get('/services/{serviceId}/sub-services', function ($serviceId) {
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
    })->name('services.sub-services');

    // =========================================================================
    // SERVICE SUB SERVICE - FIND OR CREATE
    // =========================================================================

    Route::post('/service-subservices/find-or-create', function (Request $request) {
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
    })->name('service-subservices.find-or-create');

    // ServiceSubservice - Obtener por ID
    Route::get('/service-subservices/{id}', function ($id) {
        try {
            $serviceSubservice = ServiceSubservice::find($id);

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
    })->name('service-subservices.show');

    // =========================================================================
    // SLAS POR SUB-SERVICIO - RUTA MEJORADA
    // =========================================================================

    Route::get('/sub-services/{subService}/slas', function ($subService) {
        try {
            \Log::info("=== CARGANDO SLAS PARA SUB-SERVICE ===");
            \Log::info("Sub-service ID recibido: " . $subService);

            // Si se pasa el modelo directamente (route model binding)
            if ($subService instanceof \App\Models\SubService) {
                $subServiceId = $subService->id;
                \Log::info("Route model binding detectado, ID: " . $subServiceId);
            } else {
                $subServiceId = (int)$subService;
            }

            // Resto del código se mantiene igual...
            if (!is_numeric($subServiceId)) {
                \Log::warning("Sub-service ID no es numérico: " . $subServiceId);
                return response()->json([], 400);
            }

            $subServiceModel = \App\Models\SubService::find($subServiceId);
            if (!$subServiceModel) {
                \Log::warning("Sub-service no encontrado con ID: " . $subServiceId);
                return response()->json([]);
            }

            \Log::info("Sub-service encontrado: " . $subServiceModel->name);

            // Buscar SLAs a través de ServiceSubservice
            $serviceSubservices = ServiceSubservice::where('sub_service_id', $subServiceId)->get();
            \Log::info("ServiceSubservices encontrados: " . $serviceSubservices->count());

            if ($serviceSubservices->isEmpty()) {
                \Log::info("No se encontraron ServiceSubservices para el sub-service");
                return response()->json([]);
            }

            $serviceSubserviceIds = $serviceSubservices->pluck('id')->toArray();
            \Log::info("IDs de ServiceSubservice: ", $serviceSubserviceIds);

            $slas = \App\Models\ServiceLevelAgreement::whereIn('service_subservice_id', $serviceSubserviceIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'criticality_level', 'acceptance_time_minutes', 'response_time_minutes', 'resolution_time_minutes']);

            \Log::info("SLAs encontrados: " . $slas->count());

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
            \Log::error('Error cargando SLAs: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno del servidor'
            ], 500);
        }
    })->name('sub-services.slas.get');
});
