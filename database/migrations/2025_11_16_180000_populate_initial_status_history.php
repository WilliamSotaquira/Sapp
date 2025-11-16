<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusHistory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Poblar historial inicial para todas las solicitudes existentes
        ServiceRequest::withoutEvents(function () {
            ServiceRequest::chunk(100, function ($serviceRequests) {
                foreach ($serviceRequests as $request) {
                    // Solo crear historial si no existe ya uno para esta solicitud
                    $existingHistory = ServiceRequestStatusHistory::where('service_request_id', $request->id)->count();

                    if ($existingHistory === 0) {
                        // Verificar que el usuario existe antes de asignarlo
                        $changedBy = null;
                        if ($request->requester_id) {
                            $userExists = DB::table('users')->where('id', $request->requester_id)->exists();
                            if ($userExists) {
                                $changedBy = $request->requester_id;
                            }
                        }

                        ServiceRequestStatusHistory::create([
                            'service_request_id' => $request->id,
                            'status' => $request->status,
                            'previous_status' => null,
                            'comments' => 'Estado inicial - Historial generado automáticamente',
                            'changed_by' => $changedBy,
                            'ip_address' => null,
                            'user_agent' => null,
                            'metadata' => [
                                'migration' => true,
                                'created_via' => 'populate_initial_status_history'
                            ],
                            'created_at' => $request->created_at,
                            'updated_at' => $request->created_at,
                        ]);
                    }
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar solo los registros de historial creados por esta migración
        ServiceRequestStatusHistory::whereJsonContains('metadata->migration', true)->delete();
    }
};
