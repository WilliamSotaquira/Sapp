<?php

namespace App\Http\Controllers;

use App\Models\OperationalAlert;
use App\Services\OperationalAlertService;
use Illuminate\Http\Request;

class OperationalAlertController extends Controller
{
    /**
     * Panel principal de alertas operativas.
     */
    public function index(Request $request)
    {
        $query = OperationalAlert::with('alertable')
            ->orderByRaw("FIELD(severity, 'critica', 'alta', 'media', 'baja')")
            ->orderBy('alert_at', 'desc');

        // Filtro por estado
        $status = $request->input('status', 'active');
        switch ($status) {
            case 'active':
                $query->active();
                break;
            case 'read':
                $query->where('is_read', true)->where('is_resolved', false)->where('is_dismissed', false);
                break;
            case 'resolved':
                $query->where('is_resolved', true);
                break;
            case 'dismissed':
                $query->where('is_dismissed', true);
                break;
            case 'all':
                break;
        }

        // Filtro por severidad
        if ($severity = $request->input('severity')) {
            $query->ofSeverity($severity);
        }

        // Filtro por tipo
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        $alerts = $query->paginate(25)->withQueryString();

        // Resumen rápido
        $summary = app(OperationalAlertService::class)->getActiveSummary(
            (int) session('current_company_id') ?: null
        );

        return view('operational-alerts.index', compact('alerts', 'summary', 'status'));
    }

    /**
     * Marcar una alerta como leída.
     */
    public function markAsRead(OperationalAlert $alert)
    {
        $alert->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Alerta marcada como leída.');
    }

    /**
     * Descartar una alerta.
     */
    public function dismiss(OperationalAlert $alert)
    {
        $alert->dismiss();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Alerta descartada.');
    }

    /**
     * Resolver una alerta manualmente.
     */
    public function resolve(Request $request, OperationalAlert $alert)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:500',
        ]);

        $alert->resolve(auth()->id(), $request->input('resolution_notes'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Alerta resuelta.');
    }

    /**
     * Marcar todas las alertas visibles como leídas.
     */
    public function markAllAsRead()
    {
        OperationalAlert::active()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'Todas las alertas marcadas como leídas.');
    }

    /**
     * API: obtener conteo de alertas no leídas (para badge en navegación).
     */
    public function unreadCount()
    {
        $count = OperationalAlert::active()->unread()->count();
        $critical = OperationalAlert::active()->critical()->count();

        return response()->json([
            'unread' => $count,
            'critical' => $critical,
        ]);
    }

    /**
     * API: obtener las 5 alertas más recientes sin leer (para dropdown de campana).
     */
    public function recent()
    {
        $alerts = OperationalAlert::active()
            ->unread()
            ->with('alertable')
            ->orderByRaw("FIELD(severity, 'critica', 'alta', 'media', 'baja')")
            ->orderBy('alert_at', 'desc')
            ->limit(5)
            ->get();

        $items = $alerts->map(function ($alert) {
            $severityColors = [
                'critica' => 'border-l-red-600',
                'alta' => 'border-l-orange-500',
                'media' => 'border-l-yellow-500',
                'baja' => 'border-l-blue-400',
            ];
            $url = null;
            if ($alert->alertable_type === \App\Models\ServiceRequest::class) {
                $url = route('service-requests.show', $alert->alertable_id);
            }

            return [
                'id' => $alert->id,
                'title' => $alert->title,
                'message' => \Illuminate\Support\Str::limit($alert->message, 80),
                'severity' => $alert->severity,
                'border_class' => $severityColors[$alert->severity] ?? 'border-l-gray-300',
                'time' => $alert->alert_at->diffForHumans(short: true),
                'url' => $url,
            ];
        });

        return response()->json(['alerts' => $items]);
    }

    /**
     * Crear un recordatorio manual (con o sin solicitud vinculada).
     */
    public function createReminder(Request $request)
    {
        $request->validate([
            'service_request_id' => 'nullable|exists:service_requests,id',
            'reminder_date' => 'required|date|after_or_equal:today',
            'reminder_note' => 'required|string|min:3|max:500',
        ], [
            'reminder_date.required' => 'Selecciona una fecha para el recordatorio.',
            'reminder_date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'reminder_note.required' => 'Escribe una nota para el recordatorio.',
            'reminder_note.min' => 'La nota debe tener al menos 3 caracteres.',
        ]);

        $srId = $request->input('service_request_id');
        $sr = $srId ? \App\Models\ServiceRequest::find($srId) : null;

        $message = $request->input('reminder_note');
        if ($sr) {
            $message .= " — {$sr->ticket_number}";
        }

        $data = [
            'alert_type' => OperationalAlert::TYPE_REMINDER,
            'severity' => OperationalAlert::SEVERITY_LOW,
            'title' => 'Recordatorio',
            'message' => $message,
            'metadata' => [
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name,
                'note' => $request->input('reminder_note'),
                'ticket' => $sr?->ticket_number,
            ],
            'alert_at' => \Carbon\Carbon::parse($request->input('reminder_date'))->startOfDay()->setHour(7),
        ];

        if ($sr) {
            $data['alertable_type'] = \App\Models\ServiceRequest::class;
            $data['alertable_id'] = $sr->id;
        } else {
            // Recordatorio general: vinculado al usuario como referencia
            $data['alertable_type'] = \App\Models\User::class;
            $data['alertable_id'] = auth()->id();
        }

        OperationalAlert::create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Recordatorio creado.']);
        }

        return back()->with('success', 'Recordatorio programado para ' . \Carbon\Carbon::parse($request->input('reminder_date'))->format('d/m/Y') . '.');
    }

    /**
     * Vista de recordatorios (activos + futuros programados).
     */
    public function reminders()
    {
        $active = OperationalAlert::reminders()
            ->active()
            ->where('alert_at', '<=', now())
            ->orderBy('alert_at', 'desc')
            ->get();

        $scheduled = OperationalAlert::reminders()
            ->scheduledFuture()
            ->orderBy('alert_at', 'asc')
            ->get();

        return view('operational-alerts.reminders', compact('active', 'scheduled'));
    }

    /**
     * Eliminar/cancelar un recordatorio pendiente.
     */
    public function destroyReminder(OperationalAlert $alert)
    {
        if ($alert->alert_type !== OperationalAlert::TYPE_REMINDER) {
            return back()->with('error', 'Solo se pueden eliminar recordatorios.');
        }

        $alert->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Recordatorio eliminado.');
    }
}
