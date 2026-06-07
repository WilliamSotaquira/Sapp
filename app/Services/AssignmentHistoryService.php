<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignmentHistory;
use App\Models\ServiceRequestEvidence;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentHistoryService
{
    /**
     * Allowed statuses for reassignment.
     */
    private const REASSIGNABLE_STATUSES = [
        ServiceRequest::STATUS_PENDING,
        ServiceRequest::STATUS_ACCEPTED,
        ServiceRequest::STATUS_IN_PROGRESS,
        ServiceRequest::STATUS_PAUSED,
    ];

    /**
     * Task statuses that should be transferred to the new technician.
     */
    private const TRANSFERABLE_TASK_STATUSES = [
        'pending',
        'in_progress',
        'blocked',
        'in_review',
    ];

    /**
     * Record an assignment/reassignment event for a service request.
     *
     * The operation is wrapped in a DB transaction with a lock on the service
     * request row to prevent race conditions during concurrent reassignments.
     */
    public function recordAssignment(
        ServiceRequest $sr,
        ?int $previousId,
        int $newId,
        string $reason,
        int $changedBy
    ): ServiceRequestAssignmentHistory {
        return DB::transaction(function () use ($sr, $previousId, $newId, $reason, $changedBy) {
            // Lock the service request row to prevent concurrent reassignments
            ServiceRequest::withoutGlobalScopes()
                ->where('id', $sr->id)
                ->lockForUpdate()
                ->first();

            // Validate status allows reassignment
            $this->validateReassignableStatus($sr);

            // Create assignment history record
            $history = ServiceRequestAssignmentHistory::create([
                'service_request_id' => $sr->id,
                'previous_assignee_id' => $previousId,
                'new_assignee_id' => $newId,
                'reason' => $reason,
                'changed_by' => $changedBy,
            ]);

            // Create system evidence record documenting the reassignment
            ServiceRequestEvidence::create([
                'service_request_id' => $sr->id,
                'title' => 'Técnico Reasignado',
                'description' => $reason,
                'evidence_type' => 'SISTEMA',
                'evidence_data' => [
                    'action' => 'REASSIGNED',
                    'reassigned_by' => $changedBy,
                    'reassigned_at' => now()->toISOString(),
                    'previous_technician' => $previousId,
                    'new_technician' => $newId,
                    'reassignment_reason' => $reason,
                ],
            ]);

            return $history;
        });
    }

    /**
     * Get the full assignment history for a service request, sorted oldest first.
     * Eager loads previousAssignee, newAssignee, and changedBy relationships.
     */
    public function getHistory(ServiceRequest $sr): Collection
    {
        return $sr->assignmentHistories()
            ->with(['previousAssignee', 'newAssignee', 'changedBy'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Transfer tasks from one technician to another for a given service request.
     *
     * Only tasks with transferable statuses (pending, in_progress, blocked, in_review)
     * are moved. Tasks with terminal statuses (completed, cancelled, rescheduled) remain
     * unchanged.
     *
     * @return int Number of tasks transferred
     */
    public function transferTasks(ServiceRequest $sr, int $fromTechnicianId, int $toTechnicianId): int
    {
        return $sr->tasks()
            ->where('technician_id', $fromTechnicianId)
            ->whereIn('status', self::TRANSFERABLE_TASK_STATUSES)
            ->update(['technician_id' => $toTechnicianId]);
    }

    /**
     * Validate that the service request is in a status that allows reassignment.
     *
     * @throws \InvalidArgumentException
     */
    private function validateReassignableStatus(ServiceRequest $sr): void
    {
        if (!in_array($sr->status, self::REASSIGNABLE_STATUSES, true)) {
            throw new \InvalidArgumentException(
                "No se puede reasignar una solicitud en estado {$sr->status}."
            );
        }
    }
}
