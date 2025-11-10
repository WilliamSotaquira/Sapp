{{-- resources/views/components/service-requests/show/history/helpers/get-event-title.blade.php --}}
@php
    protected function getEventTitle($history)
    {
        return match($history->event_type) {
            'status_change' => 'Cambio de Estado',
            'assignment' => 'Asignación',
            'evidence_added' => 'Evidencia Agregada',
            'comment' => 'Comentario',
            'sla_updated' => 'SLA Actualizado',
            'priority_change' => 'Cambio de Prioridad',
            'deadline_updated' => 'Fecha Límite Actualizada',
            default => ucfirst(str_replace('_', ' ', $history->event_type))
        };
    }
@endphp
