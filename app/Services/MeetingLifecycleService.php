<?php

namespace App\Services;

use App\Models\MeetingDetail;
use App\Models\MeetingParticipant;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MeetingLifecycleService
{
    /**
     * Create meeting details linked to a service request.
     *
     * @param ServiceRequest $serviceRequest
     * @param array $data Expected keys: scheduled_date, start_time, expected_duration_minutes, location?, virtual_meeting_url?
     * @return MeetingDetail
     */
    public function createMeetingDetails(ServiceRequest $serviceRequest, array $data): MeetingDetail
    {
        return MeetingDetail::create([
            'service_request_id' => $serviceRequest->id,
            'scheduled_date' => $data['scheduled_date'],
            'start_time' => $data['start_time'],
            'expected_duration_minutes' => $data['expected_duration_minutes'],
            'location' => $data['location'] ?? null,
            'virtual_meeting_url' => $data['virtual_meeting_url'] ?? null,
        ]);
    }

    /**
     * Update meeting details — only if the associated service request is in PENDIENTE status.
     *
     * @param MeetingDetail $meetingDetail
     * @param array $data Fields to update (scheduled_date, start_time, expected_duration_minutes, location, virtual_meeting_url)
     * @return MeetingDetail
     *
     * @throws \InvalidArgumentException If the service request is not in PENDIENTE status.
     */
    public function updateMeetingDetails(MeetingDetail $meetingDetail, array $data): MeetingDetail
    {
        $serviceRequest = $meetingDetail->serviceRequest;

        if ($serviceRequest->status !== ServiceRequest::STATUS_PENDING) {
            throw new \InvalidArgumentException(
                'Los detalles de la reunión solo pueden editarse mientras la solicitud está en estado PENDIENTE.'
            );
        }

        $fillable = ['scheduled_date', 'start_time', 'expected_duration_minutes', 'location', 'virtual_meeting_url'];
        $updateData = array_intersect_key($data, array_flip($fillable));

        $meetingDetail->update($updateData);

        return $meetingDetail->fresh();
    }

    /**
     * Add a participant to a meeting detail.
     * Auto-links user_id if the email matches an existing user (case-insensitive).
     *
     * @param MeetingDetail $meetingDetail
     * @param array $data Expected keys: name, email, role, user_id?
     * @return MeetingParticipant
     */
    public function addParticipant(MeetingDetail $meetingDetail, array $data): MeetingParticipant
    {
        $email = $data['email'];

        // Auto-link user_id if email matches an existing user (case-insensitive)
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        return MeetingParticipant::create([
            'meeting_detail_id' => $meetingDetail->id,
            'name' => $data['name'],
            'email' => $email,
            'role' => $data['role'],
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove a participant from a meeting.
     *
     * @param MeetingParticipant $participant
     * @return void
     */
    public function removeParticipant(MeetingParticipant $participant): void
    {
        $participant->delete();
    }

    /**
     * Mark attendance for a participant.
     *
     * @param MeetingParticipant $participant
     * @param bool $attended
     * @return void
     */
    public function markAttendance(MeetingParticipant $participant, bool $attended): void
    {
        $participant->update(['attended' => $attended]);
    }

    /**
     * Get all commitments (tasks with type='impact') linked to a service request.
     *
     * @param ServiceRequest $serviceRequest
     * @return Collection
     */
    public function getCommitments(ServiceRequest $serviceRequest): Collection
    {
        return $serviceRequest->tasks()->where('type', 'impact')->get();
    }

    /**
     * Create a commitment (Task with type='impact') linked to the service request.
     * Resolves technician_id from the user_id in $data['assigned_to'] by looking up the Technician model.
     *
     * @param ServiceRequest $serviceRequest
     * @param array $data Expected keys: title, description, assigned_to (user_id), due_date, priority?
     * @return Task
     */
    public function createCommitment(ServiceRequest $serviceRequest, array $data): Task
    {
        // Resolve technician_id from assigned_to (user_id)
        $technicianId = null;
        if (!empty($data['assigned_to'])) {
            $technician = Technician::where('user_id', $data['assigned_to'])->first();
            if ($technician) {
                $technicianId = $technician->id;
            }
        }

        return Task::create([
            'type' => 'impact',
            'title' => $data['title'],
            'description' => $data['description'],
            'service_request_id' => $serviceRequest->id,
            'technician_id' => $technicianId,
            'due_date' => $data['due_date'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'pending',
        ]);
    }
}
