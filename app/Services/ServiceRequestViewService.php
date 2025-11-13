<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestViewService
{
    public function getStatusColors(): array
    {
        return [
            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
            'ACEPTADA' => 'bg-blue-100 text-blue-800',
            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
            'PAUSADA' => 'bg-orange-100 text-orange-800',
            'RESUELTA' => 'bg-green-100 text-green-800',
            'CERRADA' => 'bg-gray-100 text-gray-800',
            'CANCELADA' => 'bg-red-100 text-red-800'
        ];
    }

    public function getCriticalityColors(): array
    {
        return [
            'BAJA' => 'bg-green-100 text-green-800',
            'MEDIA' => 'bg-yellow-100 text-yellow-800',
            'ALTA' => 'bg-orange-100 text-orange-800',
            'URGENTE' => 'bg-red-100 text-red-800',
            'CRITICA' => 'bg-red-100 text-red-800'
        ];
    }

    public function getTimeSlots(ServiceRequest $request): array
    {
        return [
            [
                'type' => 'acceptance',
                'label' => 'Aceptación',
                'minutes' => $request->sla->acceptance_time_minutes,
                'completed_at' => $request->accepted_at,
                'deadline' => $request->acceptance_deadline,
                'icon' => 'fa-user-check'
            ],
            [
                'type' => 'response',
                'label' => 'Respuesta',
                'minutes' => $request->sla->response_time_minutes,
                'completed_at' => $request->responded_at,
                'deadline' => $request->response_deadline,
                'icon' => 'fa-play'
            ],
            [
                'type' => 'resolution',
                'label' => 'Resolución',
                'minutes' => $request->sla->resolution_time_minutes,
                'completed_at' => $request->resolved_at,
                'deadline' => $request->resolution_deadline,
                'icon' => 'fa-flag-checkered'
            ]
        ];
    }

    public function canShowResolveButton(ServiceRequest $request): bool
    {
        return $request->status === 'EN_PROCESO';
    }

    public function canResolve(ServiceRequest $request): bool
    {
        return $request->status === 'EN_PROCESO' &&
               (($request->stepByStepEvidences->count() ?? 0) > 0 ||
                ($request->fileEvidences->count() ?? 0) > 0);
    }

    public function getResolveButtonData(ServiceRequest $request): array
    {
        $hasStepEvidences = ($request->stepByStepEvidences->count() ?? 0) > 0;
        $hasFileEvidences = ($request->fileEvidences->count() ?? 0) > 0;
        $canResolve = $hasStepEvidences || $hasFileEvidences;

        return [
            'can_resolve' => $canResolve,
            'has_step_evidences' => $hasStepEvidences,
            'has_file_evidences' => $hasFileEvidences,
            'step_evidences_count' => $request->stepByStepEvidences->count() ?? 0,
            'file_evidences_count' => $request->fileEvidences->count() ?? 0
        ];
    }

    public function shouldShowPauseInfo(ServiceRequest $request): bool
    {
        return $request->isPaused() || ($request->total_paused_minutes ?? 0) > 0;
    }

    public function getWebRoutesData(ServiceRequest $request): array
    {
        $routes = [];

        // Ruta principal
        if ($request->main_web_route) {
            $mainUrl = trim($request->main_web_route);
            $routes[] = [
                'type' => 'main',
                'url' => $mainUrl,
                'description' => 'Ruta Principal',
                'is_valid' => filter_var($mainUrl, FILTER_VALIDATE_URL) !== false,
                'icon' => 'fa-star text-yellow-500'
            ];
        }

        // Rutas adicionales
        if ($request->hasWebRoutes()) {
            foreach ($request->web_routes as $index => $route) {
                $url = '';
                $description = '';

                if (is_array($route)) {
                    $url = $route['route'] ?? '';
                    $description = $route['description'] ?? '';
                } else {
                    $url = $route;
                }

                $url = trim($url);
                $routes[] = [
                    'type' => 'additional',
                    'url' => $url,
                    'description' => $description ?: "Ruta " . ($index + 1),
                    'is_valid' => filter_var($url, FILTER_VALIDATE_URL) !== false,
                    'icon' => 'fa-link text-gray-500'
                ];
            }
        }

        return $routes;
    }

    public function getEvidencesSummary(ServiceRequest $request): array
    {
        return [
            'step_by_step' => $request->stepByStepEvidences->count() ?? 0,
            'files' => $request->fileEvidences->count() ?? 0,
            'comments' => $request->commentEvidences->count() ?? 0,
            'total' => $request->evidences_count ?? 0
        ];
    }
}
