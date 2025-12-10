<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceRequestService
{
    /**
     * Construir query base con los filtros aplicados
     */
    private function buildFilteredQuery(array $filters = [])
    {
        $query = ServiceRequest::query();

        // Búsqueda general
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Estado / abiertas
        if (!empty($filters['open'])) {
            $query->whereNotIn('status', ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA']);
        } elseif (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Criticidad
        if (!empty($filters['criticality'])) {
            $query->where('criticality_level', $filters['criticality']);
        }

        // Servicio
        if (!empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            if ($serviceId > 0) {
                $query->whereHas('subService.service', function ($q) use ($serviceId) {
                    $q->where('id', $serviceId);
                });
            }
        }

        // Solicitante (nombre o email parcial)
        if (!empty($filters['requester'])) {
            $term = trim($filters['requester']);
            $query->whereHas('requester', function($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        // Rango de fechas (creación)
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        if ($startDate || $endDate) {
            try {
                $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : null;
            } catch (\Exception $e) { $start = null; }
            try {
                $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : null;
            } catch (\Exception $e) { $end = null; }

            if ($start && $end) {
                $query->whereBetween('created_at', [$start, $end]);
            } elseif ($start) {
                $query->where('created_at', '>=', $start);
            } elseif ($end) {
                $query->where('created_at', '<=', $end);
            }
        }

        return $query;
    }

    /**
     * Obtener solicitudes con filtros y paginación optimizada
     */
    public function getFilteredServiceRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters)
            ->with([
                'subService:id,name,service_id',
                'subService.service:id,name,service_family_id',
                'subService.service.family:id,name',
                'requester:id,name,email'
            ])
            ->select([
                'id', 'ticket_number', 'title', 'description', 'status',
                'criticality_level', 'requester_id', 'sub_service_id',
                'created_at', 'updated_at'
            ]);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Obtener estadísticas del dashboard optimizada
     */
    public function getDashboardStats(): array
    {
        // Una sola consulta para obtener todas las estadísticas
        $stats = ServiceRequest::selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'PENDIENTE' THEN 1 END) as pending_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' THEN 1 END) as critical_count,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = 'CERRADA' THEN 1 END) as closed_count,
            COUNT(CASE WHEN status IN ('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA') THEN 1 END) as open_count
        ")->first();

        return [
            'totalCount' => $stats->total_count ?? 0,
            'pendingCount' => $stats->pending_count ?? 0,
            'criticalCount' => $stats->critical_count ?? 0,
            'resolvedCount' => $stats->resolved_count ?? 0,
            'closedCount' => $stats->closed_count ?? 0,
            'openCount' => $stats->open_count ?? 0,
        ];
    }

    /**
     * Obtener estadísticas basadas en los mismos filtros del listado
     */
    public function getFilteredStats(array $filters = []): array
    {
        $query = $this->buildFilteredQuery($filters);

        $stats = $query->selectRaw("
            COUNT(*) as total_count,
            COUNT(CASE WHEN status = 'PENDIENTE' THEN 1 END) as pending_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' THEN 1 END) as critical_count,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = 'CERRADA' THEN 1 END) as closed_count,
            COUNT(CASE WHEN status IN ('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA') THEN 1 END) as open_count
        ")->first();

        return [
            'totalCount' => $stats->total_count ?? 0,
            'pendingCount' => $stats->pending_count ?? 0,
            'criticalCount' => $stats->critical_count ?? 0,
            'resolvedCount' => $stats->resolved_count ?? 0,
            'closedCount' => $stats->closed_count ?? 0,
            'openCount' => $stats->open_count ?? 0,
        ];
    }

    /**
     * Crear nueva solicitud de servicio
     */
    public function createServiceRequest(array $data): ServiceRequest
    {
        Log::info('=== CREANDO NUEVA SOLICITUD ===', ['data' => $data]);

        try {
            // Procesar web_routes si existe
            if (!empty($data['web_routes'])) {
                $data['web_routes'] = is_string($data['web_routes'])
                    ? json_decode($data['web_routes'], true) ?? []
                    : $data['web_routes'];
            }

            $serviceRequest = ServiceRequest::create($data);

            Log::info('✅ Solicitud creada exitosamente', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
                'requester_id' => $serviceRequest->requester_id,
            ]);

            return $serviceRequest;
        } catch (\Exception $e) {
            Log::error('❌ Error al crear solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener datos para el formulario de creación
     */
    public function getCreateFormData(): array
    {
        return [
            'subServices' => SubService::with(['service.family', 'slas'])
                ->where('is_active', true)
                ->get(),
            'requesters' => \App\Models\Requester::active()->orderBy('name')->get(),
            'criticalityLevels' => ['BAJA', 'MEDIA', 'ALTA', 'URGENTE']
        ];
    }

    /**
     * Cargar solicitud con relaciones optimizadas
     */
    public function loadServiceRequestForShow(ServiceRequest $serviceRequest): ServiceRequest
    {
        return $serviceRequest->load([
            'subService:id,name,service_id',
            'subService.service:id,name,service_family_id',
            'subService.service.family:id,name',
            'sla:id,name,criticality_level,response_time_minutes,resolution_time_minutes',
            'requester:id,name,email,phone',
            'assignee:id,name,email',
            'breachLogs:id,service_request_id,breach_type,breach_minutes,created_at',
            'evidences' => function($query) {
                $query->with('user:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(50); // Limitar evidencias para mejor performance
            }
        ]);
    }

    /**
     * Obtener datos para el formulario de edición
     */
    public function getEditFormData(): array
    {
        $subServices = SubService::with(['service.family'])
            ->where('is_active', true)
            ->get();

        $users = User::select(['id', 'name', 'email'])->orderBy('name')->get();
        $requesters = \App\Models\Requester::active()->orderBy('name')->get();
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return compact('subServices', 'users', 'requesters', 'criticalityLevels');
    }

    /**
     * Actualizar solicitud de servicio
     */
    public function updateServiceRequest(ServiceRequest $serviceRequest, array $data): ServiceRequest
    {
        Log::info('=== ACTUALIZANDO SOLICITUD ===', [
            'id' => $serviceRequest->id,
            'data' => $data
        ]);

        try {
            $serviceRequest->update($data);

            Log::info('✅ Solicitud actualizada exitosamente', [
                'id' => $serviceRequest->id,
                'ticket_number' => $serviceRequest->ticket_number,
            ]);

            return $serviceRequest;
        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar solicitud de servicio
     */
    public function deleteServiceRequest(ServiceRequest $serviceRequest): bool
    {
        Log::info('=== ELIMINANDO SOLICITUD ===', [
            'id' => $serviceRequest->id,
            'ticket_number' => $serviceRequest->ticket_number,
        ]);

        try {
            $deleted = $serviceRequest->delete();

            Log::info('✅ Solicitud eliminada exitosamente', [
                'id' => $serviceRequest->id,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }
}
