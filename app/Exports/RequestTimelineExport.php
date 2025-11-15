<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestTimelineExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $request;

    public function __construct(ServiceRequest $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return collect($this->request->getTimelineEvents());
    }

    public function headings(): array
    {
        return [
            'Evento',
            'Fecha y Hora',
            'Usuario Responsable',
            'Descripción',
            'Tipo de Evento',
            'Duración en Estado',
            'Icono'
        ];
    }

    public function map($event): array
    {
        $timeInStatus = $this->getTimeInStatusForEvent($event);

        // Obtener el status/type del evento de manera segura
        $eventStatus = $event['status'] ?? $event['type'] ?? 'unknown';

        // Obtener el nombre del evento
        $eventName = $event['event'] ?? $event['title'] ?? 'Evento';

        return [
            $eventName,
            $event['timestamp']->format('d/m/Y H:i:s'),
            $event['user'] ? (is_object($event['user']) ? $event['user']->name : $event['user']) : 'Sistema',
            $event['description'] ?? 'Sin descripción',
            $this->getEventTypeLabel($eventStatus),
            $timeInStatus,
            $event['icon'] ?? 'N/A'
        ];
    }

    private function getTimeInStatusForEvent($event)
    {
        $eventStatus = $event['status'] ?? $event['type'] ?? 'unknown';
        $timeInStatus = $this->request->getTimeInEachStatus();
        return $timeInStatus[$eventStatus]['formatted'] ?? 'N/A';
    }

    private function getEventTypeLabel($status)
    {
        $labels = [
            'created' => 'Creación',
            'creation' => 'Creación',
            'assigned' => 'Asignación',
            'accepted' => 'Aceptación',
            'acceptance' => 'Aceptación',
            'responded' => 'Respuesta Inicial',
            'response' => 'Respuesta Inicial',
            'paused' => 'Pausa',
            'pause' => 'Pausa',
            'resumed' => 'Reanudación',
            'resolved' => 'Resolución',
            'resolution' => 'Resolución',
            'closed' => 'Cierre',
            'closure' => 'Cierre',
            'evidence' => 'Evidencia',
            'breach' => 'Incumplimiento SLA',
            'unknown' => 'Evento del Sistema'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    public function title(): string
    {
        return "Timeline {$this->request->ticket_number}";
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '2C3E50']
                ]
            ],

            // Estilo para las celdas
            'A:G' => [
                'alignment' => [
                    'wrapText' => true
                ]
            ],

            // Ancho de columnas
            'A' => ['width' => 25], // Evento
            'B' => ['width' => 20], // Fecha y Hora
            'C' => ['width' => 20], // Usuario Responsable
            'D' => ['width' => 40], // Descripción
            'E' => ['width' => 15], // Tipo de Evento
            'F' => ['width' => 15], // Duración en Estado
            'G' => ['width' => 10], // Icono
        ];
    }

    /**
     * Método adicional para generar CSV como fallback
     */
    public function toCsv()
    {
        $csv = "LÍNEA DE TIEMPO - SOLICITUD: {$this->request->ticket_number}\n";
        $csv .= "Título: {$this->request->title}\n";
        $csv .= "Solicitante: " . ($this->request->requester->name ?? 'N/A') . "\n";
        $csv .= "Asignado a: " . ($this->request->assignee->name ?? 'No asignado') . "\n";
        $csv .= "Estado: {$this->request->status}\n";
        $csv .= "Nivel de Criticidad: {$this->request->criticality_level}\n";
        $csv .= "Fecha de Creación: {$this->request->created_at->format('d/m/Y H:i')}\n";
        $csv .= "\n";

        $csv .= "Evento,Fecha y Hora,Usuario Responsable,Descripción,Tipo de Evento,Duración en Estado\n";

        foreach ($this->collection() as $event) {
            $timeInStatus = $this->getTimeInStatusForEvent($event);
            $userName = $event['user'] ? $event['user']->name : 'Sistema';
            $eventType = $this->getEventTypeLabel($event['status']);

            $csv .= "\"{$event['event']}\",";
            $csv .= "\"{$event['timestamp']->format('d/m/Y H:i:s')}\",";
            $csv .= "\"{$userName}\",";
            $csv .= "\"{$event['description']}\",";
            $csv .= "\"{$eventType}\",";
            $csv .= "\"{$timeInStatus}\"\n";
        }

        // Agregar estadísticas al final
        $stats = $this->request->getTimeStatistics();
        $csv .= "\n";
        $csv .= "ESTADÍSTICAS DE TIEMPO\n";
        $csv .= "Tiempo Total: {$stats['total_time']}\n";
        $csv .= "Tiempo Activo: {$stats['active_time']}\n";
        $csv .= "Tiempo Pausado: {$stats['paused_time']}\n";
        $csv .= "Eficiencia: {$stats['efficiency']}\n";

        return $csv;
    }
}
