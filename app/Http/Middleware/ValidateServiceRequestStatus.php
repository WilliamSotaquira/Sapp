<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateServiceRequestStatus
{
    /**
     * Validar que la solicitud esté en un estado permitido para cierta acción
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $serviceRequest = $request->route('serviceRequest') ?? $request->route('service_request');

        if (!$serviceRequest) {
            abort(404, 'Solicitud no encontrada');
        }

        $allowedStatuses = $this->getAllowedStatuses($action);

        if (!in_array($serviceRequest->status, $allowedStatuses)) {
            $message = $this->getErrorMessage($action, $serviceRequest->status);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 422);
            }

            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', $message);
        }

        return $next($request);
    }

    /**
     * Obtener estados permitidos según la acción
     */
    private function getAllowedStatuses(string $action): array
    {
        return match ($action) {
            'edit', 'update' => ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'],
            'delete' => ['PENDIENTE', 'CANCELADA'],
            'accept' => ['PENDIENTE'],
            'reject' => ['PENDIENTE'],
            'start' => ['ACEPTADA'],
            'resolve' => ['EN_PROCESO'],
            'pause' => ['EN_PROCESO'],
            'resume' => ['PAUSADA'],
            'close' => ['RESUELTA', 'PAUSADA'],
            'reopen' => ['RESUELTA', 'CERRADA'],
            'cancel' => ['PENDIENTE', 'ACEPTADA'],
            'reassign' => ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA'],
            default => []
        };
    }

    /**
     * Obtener mensaje de error según la acción
     */
    private function getErrorMessage(string $action, string $currentStatus): string
    {
        $messages = [
            'edit' => 'No se pueden editar solicitudes en estado: ' . $currentStatus,
            'update' => 'No se pueden actualizar solicitudes en estado: ' . $currentStatus,
            'delete' => 'Solo se pueden eliminar solicitudes en estado PENDIENTE o CANCELADA',
            'accept' => 'Esta solicitud ya no puede ser aceptada. Estado actual: ' . $currentStatus,
            'reject' => 'La solicitud debe estar en estado PENDIENTE para ser rechazada',
            'start' => 'La solicitud debe estar ACEPTADA para iniciar',
            'resolve' => 'La solicitud debe estar en estado EN PROCESO',
            'pause' => 'Solo se pueden pausar solicitudes en proceso',
            'resume' => 'Solo se pueden reanudar solicitudes pausadas',
            'close' => 'Solo se pueden cerrar solicitudes RESUELTAS o PAUSADAS por vencimiento',
            'reopen' => 'La solicitud no puede ser reabierta desde el estado actual',
            'cancel' => 'Solo se pueden cancelar solicitudes en estado PENDIENTE o ACEPTADA',
            'reassign' => 'No se puede reasignar una solicitud en estado: ' . $currentStatus,
        ];

        return $messages[$action] ?? 'Acción no permitida en el estado actual: ' . $currentStatus;
    }
}
