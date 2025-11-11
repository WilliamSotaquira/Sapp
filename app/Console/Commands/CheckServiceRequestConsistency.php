<?php
// app/Console/Commands/CheckServiceRequestConsistency.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceRequest;

class CheckServiceRequestConsistency extends Command
{
    protected $signature = 'service-requests:check-consistency';
    protected $description = 'Verifica la consistencia del flujo de trabajo de service requests';

    public function handle()
    {
        $this->info('🔍 Verificando consistencia de solicitudes de servicio...');

        // Verificar solicitudes EN_PROCESO sin asignación
        $inProgressWithoutAssignee = ServiceRequest::withInconsistencies()->count();

        if ($inProgressWithoutAssignee > 0) {
            $this->error("❌ Se encontraron {$inProgressWithoutAssignee} solicitudes EN_PROCESO sin técnico asignado");

            $requests = ServiceRequest::withInconsistencies()->get(['id', 'ticket_number', 'status', 'assigned_to']);
            $this->table(
                ['ID', 'Ticket', 'Estado', 'Técnico'],
                $requests->map(function($req) {
                    return [
                        'id' => $req->id,
                        'ticket_number' => $req->ticket_number,
                        'status' => $req->status,
                        'assigned_to' => $req->assigned_to ?: 'NULL'
                    ];
                })
            );
        } else {
            $this->info('✅ No se encontraron solicitudes EN_PROCESO sin técnico asignado');
        }

        // Verificar transiciones de estado válidas
        $this->info('');
        $this->info('📊 Estadísticas generales:');

        $stats = [
            'Total solicitudes' => ServiceRequest::count(),
            'PENDIENTE' => ServiceRequest::pending()->count(),
            'ACEPTADA' => ServiceRequest::where('status', ServiceRequest::STATUS_ACCEPTED)->count(),
            'EN_PROCESO' => ServiceRequest::inProgress()->count(),
            'RESUELTA' => ServiceRequest::resolved()->count(),
            'CERRADA' => ServiceRequest::closed()->count(),
            'Vencidas' => ServiceRequest::overdue()->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->line("  {$label}: {$count}");
        }

        // Verificar timestamps con las columnas reales de la base de datos
        $this->info('');
        $this->info('⏰ Verificando timestamps (con columnas existentes):');

        $timestampIssues = ServiceRequest::where(function($query) {
            // Usar solo las columnas que existen en la base de datos
            $query->whereNotNull('resolved_at')->whereNull('accepted_at')
                  ->orWhereNotNull('closed_at')->whereNull('resolved_at');
        })->count();

        if ($timestampIssues > 0) {
            $this->warn("⚠️  Se encontraron {$timestampIssues} solicitudes con timestamps inconsistentes");

            // Mostrar detalles
            $inconsistentRequests = ServiceRequest::where(function($query) {
                $query->whereNotNull('resolved_at')->whereNull('accepted_at')
                      ->orWhereNotNull('closed_at')->whereNull('resolved_at');
            })->get(['id', 'ticket_number', 'accepted_at', 'resolved_at', 'closed_at']);

            $this->table(
                ['ID', 'Ticket', 'Aceptada', 'Resuelta', 'Cerrada'],
                $inconsistentRequests->map(function($req) {
                    return [
                        'id' => $req->id,
                        'ticket_number' => $req->ticket_number,
                        'accepted_at' => $req->accepted_at ? $req->accepted_at->format('Y-m-d H:i') : 'NULL',
                        'resolved_at' => $req->resolved_at ? $req->resolved_at->format('Y-m-d H:i') : 'NULL',
                        'closed_at' => $req->closed_at ? $req->closed_at->format('Y-m-d H:i') : 'NULL'
                    ];
                })
            );
        } else {
            $this->info('✅ Timestamps consistentes');
        }

        // Verificar solicitudes con estados inválidos
        $this->info('');
        $this->info('🔄 Verificando estados válidos:');

        $invalidStatuses = ServiceRequest::whereNotIn('status', [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
            ServiceRequest::STATUS_CANCELLED
        ])->count();

        if ($invalidStatuses > 0) {
            $this->error("❌ Se encontraron {$invalidStatuses} solicitudes con estados inválidos");
        } else {
            $this->info('✅ Todos los estados son válidos');
        }

        $this->info('');
        $this->info('🎯 Verificación completada');
    }
}
