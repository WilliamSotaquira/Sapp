<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceRequestWorkflowService
{
    /**
     * Aceptar solicitud con transacción
     */
    public function acceptRequest(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return [
                'success' => false,
                'message' => 'Esta solicitud ya no puede ser aceptada. Estado actual: ' . $serviceRequest->status
            ];
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $serviceRequest->update([
                    'status' => 'ACEPTADA',
                    'accepted_at' => now(),
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Aceptada',
                    'description' => 'La solicitud fue aceptada por ' . auth()->user()->name,
                    'action' => 'ACCEPTED',
                    'previous_status' => 'PENDIENTE',
                    'new_status' => 'ACEPTADA',
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud aceptada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al aceptar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al aceptar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Rechazar solicitud
     */
    public function rejectRequest(ServiceRequest $serviceRequest, string $rejectionReason): array
    {
        if ($serviceRequest->status !== 'PENDIENTE') {
            return [
                'success' => false,
                'message' => 'La solicitud debe estar en estado PENDIENTE para ser rechazada.'
            ];
        }

        try {
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $rejectionReason) {
                $serviceRequest->update([
                    'status' => 'RECHAZADA',
                    'rejection_reason' => $rejectionReason,
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                ]);
            });

            $this->createSystemEvidence($serviceRequest, [
                'title' => 'Solicitud Rechazada',
                'description' => $rejectionReason,
                'action' => 'REJECTED',
                'previous_status' => 'PENDIENTE',
                'new_status' => 'RECHAZADA',
                'rejection_reason' => $rejectionReason,
            ]);

            return ['success' => true, 'message' => 'Solicitud rechazada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al rechazar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Iniciar procesamiento
     */
    public function startProcessing(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->status !== 'ACEPTADA') {
            return ['success' => false, 'message' => 'La solicitud debe estar ACEPTADA para iniciar.'];
        }

        if (!$serviceRequest->assigned_to) {
            return ['success' => false, 'message' => 'Asigna un técnico antes de iniciar.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $previousStatus = $serviceRequest->status;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'started_at' => now(),
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Procesamiento Iniciado',
                    'description' => "Inicio de trabajo - Técnico: {$serviceRequest->assignee->name}",
                    'action' => 'STARTED',
                    'previous_status' => $previousStatus,
                    'new_status' => 'EN_PROCESO',
                    'assigned_technician' => $serviceRequest->assigned_to,
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud marcada como en proceso.'];
        } catch (\Exception $e) {
            Log::error('Error al iniciar procesamiento: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al iniciar el procesamiento.'];
        }
    }

    /**
     * Resolver solicitud
     */
    public function resolveRequest(ServiceRequest $serviceRequest, array $data = []): array
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return ['success' => false, 'message' => 'La solicitud debe estar en estado EN PROCESO.'];
        }

        try {
            ServiceRequest::withoutEvents(function () use ($serviceRequest, $data) {
                $serviceRequest->update([
                    'status' => 'RESUELTA',
                    'resolution_notes' => $data['resolution_notes'] ?? 'Resolución completada',
                    'actual_resolution_time' => $data['actual_resolution_time'] ?? 60,
                    'resolved_at' => now(),
                ]);
            });

            return ['success' => true, 'message' => '¡Solicitud resuelta correctamente!'];
        } catch (\Exception $e) {
            Log::error('Error al resolver solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Pausar solicitud
     */
    public function pauseRequest(ServiceRequest $serviceRequest, string $pauseReason): array
    {
        if ($serviceRequest->status !== 'EN_PROCESO') {
            return ['success' => false, 'message' => 'Solo se pueden pausar solicitudes en proceso.'];
        }

        if ($serviceRequest->is_paused) {
            return ['success' => false, 'message' => 'La solicitud ya está pausada.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest, $pauseReason) {
                $serviceRequest->update([
                    'status' => 'PAUSADA',
                    'paused_at' => now(),
                    'is_paused' => true,
                    'pause_reason' => $pauseReason,
                    'paused_by' => auth()->id(),
                    'total_paused_minutes' => $serviceRequest->total_paused_minutes ?? 0,
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Pausada',
                    'description' => $pauseReason,
                    'action' => 'PAUSED',
                    'previous_status' => 'EN_PROCESO',
                    'new_status' => 'PAUSADA',
                    'pause_reason' => $pauseReason,
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud pausada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al pausar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al pausar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Reanudar solicitud pausada
     */
    public function resumeRequest(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->status !== 'PAUSADA') {
            return ['success' => false, 'message' => 'Solo se pueden reanudar solicitudes pausadas.'];
        }

        try {
            DB::transaction(function () use ($serviceRequest) {
                $currentPauseMinutes = 0;
                if ($serviceRequest->paused_at) {
                    $currentPauseMinutes = $serviceRequest->paused_at->diffInMinutes(now());
                }

                $totalPausedMinutes = ($serviceRequest->total_paused_minutes ?? 0) + $currentPauseMinutes;

                $serviceRequest->update([
                    'status' => 'EN_PROCESO',
                    'is_paused' => false,
                    'resumed_at' => now(),
                    'pause_reason' => null,
                    'paused_by' => null,
                    'total_paused_minutes' => $totalPausedMinutes,
                ]);

                $this->createSystemEvidence($serviceRequest, [
                    'title' => 'Solicitud Reanudada',
                    'description' => "La solicitud fue reanudada después de {$currentPauseMinutes} minutos en pausa.",
                    'action' => 'RESUMED',
                    'previous_status' => 'PAUSADA',
                    'new_status' => 'EN_PROCESO',
                    'pause_duration_minutes' => $currentPauseMinutes,
                    'total_pause_minutes' => $totalPausedMinutes,
                ]);
            });

            return ['success' => true, 'message' => 'Solicitud reanudada correctamente.'];
        } catch (\Exception $e) {
            Log::error('Error al reanudar solicitud: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al reanudar la solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Crear evidencia del sistema de forma consistente
     */
    private function createSystemEvidence(ServiceRequest $serviceRequest, array $data): void
    {
        ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'evidence_type' => 'SISTEMA',
            'created_by' => auth()->id(),
            'evidence_data' => array_merge([
                'action' => $data['action'],
                'performed_by' => auth()->id(),
                'performed_at' => now()->toISOString(),
            ], $data),
        ]);
    }
}
