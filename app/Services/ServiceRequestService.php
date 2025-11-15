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
     * Obtener solicitudes con filtros y paginación optimizada
     */
    public function getFilteredServiceRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ServiceRequest::query()
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

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['criticality'])) {
            $query->where('criticality_level', $filters['criticality']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Obtener estadísticas del dashboard optimizada
     */
    public function getDashboardStats(): array
    {
        // Una sola consulta para obtener todas las estadísticas
        $stats = ServiceRequest::selectRaw("
            COUNT(CASE WHEN status = 'PENDIENTE' THEN 1 END) as pending_count,
            COUNT(CASE WHEN criticality_level = 'CRITICA' THEN 1 END) as critical_count,
            COUNT(CASE WHEN status = 'RESUELTA' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = 'CERRADA' THEN 1 END) as closed_count
        ")->first();

        return [
            'pendingCount' => $stats->pending_count ?? 0,
            'criticalCount' => $stats->critical_count ?? 0,
            'resolvedCount' => $stats->resolved_count ?? 0,
            'closedCount' => $stats->closed_count ?? 0,
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
        $criticalityLevels = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];

        return compact('subServices', 'users', 'criticalityLevels');
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
