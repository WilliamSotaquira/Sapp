<?php

namespace App\Http\Controllers;

use App\Models\TaskAlert;
use Illuminate\Http\Request;

class TaskAlertController extends Controller
{
    /**
     * Lista de alertas del usuario
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = TaskAlert::with('task.technician.user');
        
        if (!$user->isAdmin()) {
            // Técnico solo ve sus alertas
            $query->whereHas('task', function ($q) use ($user) {
                $technicianId = $user->technician?->id;
                if ($technicianId) {
                    $q->where('technician_id', $technicianId);
                }
            });
        }
        
        $alerts = $query->orderBy('alert_at', 'desc')
            ->paginate(20);
            
        return view('task-alerts.index', compact('alerts'));
    }

    /**
     * Marcar alerta como leída
     */
    public function markAsRead(TaskAlert $alert)
    {
        $alert->markAsRead();
        return back()->with('success', 'Alerta marcada como leída.');
    }

    /**
     * Descartar alerta
     */
    public function dismiss(TaskAlert $alert)
    {
        $alert->dismiss();
        return back()->with('success', 'Alerta descartada.');
    }

    /**
     * Marcar todas como leídas
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        
        $query = TaskAlert::unread();
        
        if (!$user->isAdmin()) {
            $query->whereHas('task', function ($q) use ($user) {
                $technicianId = $user->technician?->id;
                if ($technicianId) {
                    $q->where('technician_id', $technicianId);
                }
            });
        }
        
        $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return back()->with('success', 'Todas las alertas marcadas como leídas.');
    }

    /**
     * API: Obtener conteo de alertas no leídas
     */
    public function getUnreadCount()
    {
        $user = auth()->user();
        
        $query = TaskAlert::unread();
        
        if (!$user->isAdmin()) {
            $query->whereHas('task', function ($q) use ($user) {
                $technicianId = $user->technician?->id;
                if ($technicianId) {
                    $q->where('technician_id', $technicianId);
                }
            });
        }
        
        return response()->json([
            'count' => $query->count(),
        ]);
    }

    /**
     * Generar alertas manualmente (para admin)
     */
    public function generate()
    {
        $this->authorize('admin');
        
        $service = app(\App\Services\SmartSchedulingService::class);
        $count = $service->generateCriticalTaskAlerts();
        
        return back()->with('success', "Se generaron {$count} nuevas alertas.");
    }
}
