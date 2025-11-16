<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusHistory;
use Illuminate\Support\Facades\Auth;

class ServiceRequestObserver
{
    /**
     * Handle the ServiceRequest "created" event.
     */
    public function created(ServiceRequest $serviceRequest): void
    {
        // Registrar el estado inicial
        $this->logStatusChange($serviceRequest, null, $serviceRequest->status, 'Solicitud creada');
    }

    /**
     * Handle the ServiceRequest "updating" event.
     */
    public function updating(ServiceRequest $serviceRequest): void
    {
        // Detectar cambio de estado
        if ($serviceRequest->isDirty('status')) {
            $oldStatus = $serviceRequest->getOriginal('status');
            $newStatus = $serviceRequest->status;

            $this->logStatusChange($serviceRequest, $oldStatus, $newStatus);
        }
    }

    /**
     * Registrar cambio de estado en el historial
     */
    protected function logStatusChange(ServiceRequest $serviceRequest, $previousStatus, $newStatus, $comments = null)
    {
        $user = Auth::user();

        ServiceRequestStatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'comments' => $comments,
            'changed_by' => $user ? $user->id : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'route' => request()->route() ? request()->route()->getName() : null,
                'method' => request()->method(),
            ]
        ]);
    }
}
