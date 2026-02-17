<?php

use App\Models\Service;
use App\Models\SubService;
use App\Models\Requester;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use App\Models\StandardTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// =============================================================================
// APIS PARA FORMULARIOS WEB
// =============================================================================

Route::prefix('api')->name('api.')->group(function () {

    // =========================================================================
    // SOLICITANTES (REQUESTERS)
    // =========================================================================

    // Crear solicitante rápido (para formularios) sin recargar la página
    Route::post('/requesters/quick-create', function (Request $request) {
        try {
            $currentCompanyId = $request->session()->get('current_company_id');
            if ($currentCompanyId) {
                $request->merge(['company_id' => $currentCompanyId]);
            }

            $companyRules = ['required', 'exists:companies,id'];
            if ($currentCompanyId) {
                $companyRules[] = Rule::in([$currentCompanyId]);
            }

            $validated = $request->validate([
                'company_id' => $companyRules,
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:requesters,email',
                'phone' => 'nullable|string|max:20',
                'department' => ['nullable', 'string', 'max:255', Rule::in(Requester::getDepartmentOptions((int) $currentCompanyId))],
                'position' => 'nullable|string|max:255',
            ]);

            $requester = Requester::create(array_merge($validated, [
                'is_active' => true,
            ]));

            $display = $requester->name;
            if ($requester->email) {
                $display .= ' - ' . $requester->email;
            }
            if ($requester->department) {
                $display .= ' (' . $requester->department . ')';
            }

            return response()->json([
                'id' => $requester->id,
                'name' => $requester->name,
                'email' => $requester->email,
                'department' => $requester->department,
                'display' => $display,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Error creando solicitante rápido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al crear el solicitante.',
            ], 500);
        }
    })->name('requesters.quick-create');

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

    // Obtener tareas predefinidas por subservicio
    Route::get('/sub-services/{subServiceId}/standard-tasks', function ($subServiceId) {
        try {
            \Log::info("Cargando tareas predefinidas para subservicio: " . $subServiceId);

            $tasks = StandardTask::with('standardSubtasks')
                ->where('sub_service_id', $subServiceId)
                ->active()
                ->ordered()
                ->get();

            \Log::info("Tareas predefinidas encontradas: " . $tasks->count());

            return response()->json($tasks);
        } catch (\Exception $e) {
            \Log::error('Error al cargar tareas predefinidas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar tareas predefinidas'], 500);
        }
    })->name('sub-services.standard-tasks');

    // Buscar subservicios (para Select2 / autocompletado)
    Route::get('/sub-services/search', function (Request $request) {
        try {
            $term = trim((string)($request->get('term', $request->get('q', ''))));
            $page = max(1, (int)$request->get('page', 1));
            $perPage = (int)$request->get('per_page', 20);
            $perPage = max(5, min(50, $perPage));
            $currentCompanyId = (int) session('current_company_id');
            $activeContractId = null;
            if ($currentCompanyId) {
                $activeContractId = \App\Models\Company::where('id', $currentCompanyId)->value('active_contract_id');
            }

            $query = SubService::query()
                ->where('is_active', true)
                ->with([
                    'service:id,name,service_family_id',
                    'service.family:id,name,contract_id',
                    'service.family.contract:id,number',
                    // Traer SLAs activos; usamos el primero como referencia
                    'slas'
                ]);

            if ($currentCompanyId) {
                $query->whereHas('service.family.contract', function ($q) use ($currentCompanyId) {
                    $q->where('company_id', $currentCompanyId);
                });
            }

            if (!empty($activeContractId)) {
                $query->whereHas('service.family', function ($q) use ($activeContractId) {
                    $q->where('contract_id', $activeContractId);
                });
            }

            if ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'LIKE', "%{$term}%")
                        ->orWhere('code', 'LIKE', "%{$term}%")
                        ->orWhereHas('service', function ($sq) use ($term) {
                            $sq->where('name', 'LIKE', "%{$term}%")
                                ->orWhereHas('family', function ($fq) use ($term) {
                                    $fq->where('name', 'LIKE', "%{$term}%");
                                });
                        });
                });
            }

            // Orden estable
            $query->orderBy('name');

            $items = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1)
                ->get();

            $hasMore = $items->count() > $perPage;
            $items = $items->take($perPage);

            $results = $items->map(function (SubService $subService) {
                $family = $subService->service?->family;
                $familyName = $family?->name ?? 'Sin Familia';
                $contractNumber = $family?->contract?->number;
                $familyLabel = $contractNumber ? "{$contractNumber} - {$familyName}" : $familyName;
                $serviceName = $subService->service?->name ?? 'Sin Servicio';
                $familyId = $family?->id;
                $serviceId = $subService->service?->id;

                $sla = $subService->relationLoaded('slas') ? $subService->slas->first() : null;
                $criticalityLevel = $sla?->criticality_level ?? 'MEDIA';
                $slaId = $sla?->id;

                return [
                    'id' => $subService->id,
                    'text' => $subService->name,
                    'familyName' => $familyLabel,
                    'serviceName' => $serviceName,
                    'familyId' => $familyId,
                    'serviceId' => $serviceId,
                    'criticalityLevel' => $criticalityLevel,
                    'slaId' => $slaId,
                ];
            });

            return response()->json([
                'results' => $results,
                'pagination' => [
                    'more' => $hasMore,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de subservicios: ' . $e->getMessage());
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'error' => 'Error al buscar subservicios'
            ], 500);
        }
    })->name('sub-services.search');

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

    // =========================================================================
    // CARGAR SOLICITUDES POR TÉCNICO
    // =========================================================================
    Route::get('/service-requests/by-technician/{technicianId}', function ($technicianId) {
        try {
            \Log::info("Cargando solicitudes para técnico: " . $technicianId);

            $technician = \App\Models\Technician::findOrFail($technicianId);
            $userId = $technician->user_id;

            $requests = \App\Models\ServiceRequest::with('sla')
                ->where('assigned_to', $userId)
                ->whereIn('status', ['ACEPTADA', 'PENDIENTE', 'EN_PROCESO'])
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info("Solicitudes encontradas: " . $requests->count());

            $formattedRequests = $requests->map(function ($request) use ($technicianId) {
                // Calcular duración estimada desde el SLA (en horas)
                $estimatedHours = 0;
                if ($request->sla && $request->sla->resolution_time_minutes) {
                    $estimatedHours = round($request->sla->resolution_time_minutes / 60, 1);
                }

                return [
                    'id' => $request->id,
                    'ticket_number' => $request->ticket_number,
                    'title' => $request->title,
                    'criticality_level' => $request->criticality_level,
                    'estimated_hours' => $estimatedHours,
                    'assigned_technician_id' => $technicianId,
                    'status' => $request->status
                ];
            });

            return response()->json($formattedRequests);
        } catch (\Exception $e) {
            \Log::error('Error cargando solicitudes por técnico: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno del servidor'
            ], 500);
        }
    })->name('service-requests.by-technician');
});
