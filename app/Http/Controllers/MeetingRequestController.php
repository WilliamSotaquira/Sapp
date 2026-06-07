<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommitmentRequest;
use App\Http\Requests\StoreMeetingParticipantRequest;
use App\Models\MeetingParticipant;
use App\Models\ServiceRequest;
use App\Services\MeetingLifecycleService;
use Illuminate\Http\Request;

class MeetingRequestController extends Controller
{
    protected MeetingLifecycleService $meetingLifecycleService;

    public function __construct(MeetingLifecycleService $meetingLifecycleService)
    {
        $this->meetingLifecycleService = $meetingLifecycleService;
    }

    /**
     * Update meeting scheduling details (only if PENDIENTE).
     */
    public function updateDetails(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'expected_duration_minutes' => 'required|integer|min:5|max:480',
            'location' => 'nullable|string|max:255',
            'virtual_meeting_url' => 'nullable|string|max:2048',
        ]);

        $meetingDetail = $serviceRequest->meetingDetail;

        if (!$meetingDetail) {
            return redirect()->back()->with('error', 'No se encontró información de reunión para esta solicitud.');
        }

        try {
            $this->meetingLifecycleService->updateMeetingDetails($meetingDetail, $validated);

            return redirect()->back()->with('success', 'Detalles de la reunión actualizados correctamente.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add a participant to the meeting.
     */
    public function addParticipant(StoreMeetingParticipantRequest $request, ServiceRequest $serviceRequest)
    {
        $meetingDetail = $serviceRequest->meetingDetail;

        if (!$meetingDetail) {
            return redirect()->back()->with('error', 'No se encontró información de reunión para esta solicitud.');
        }

        $this->meetingLifecycleService->addParticipant($meetingDetail, $request->validated());

        return redirect()->back()->with('success', 'Participante agregado correctamente.');
    }

    /**
     * Remove a participant from the meeting.
     */
    public function removeParticipant(ServiceRequest $serviceRequest, MeetingParticipant $participant)
    {
        $meetingDetail = $serviceRequest->meetingDetail;

        if (!$meetingDetail || $participant->meeting_detail_id !== $meetingDetail->id) {
            return redirect()->back()->with('error', 'El participante no pertenece a esta reunión.');
        }

        $this->meetingLifecycleService->removeParticipant($participant);

        return redirect()->back()->with('success', 'Participante eliminado correctamente.');
    }

    /**
     * Bulk mark attendance for meeting participants.
     */
    public function markAttendance(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*' => 'boolean',
        ]);

        $meetingDetail = $serviceRequest->meetingDetail;

        if (!$meetingDetail) {
            return redirect()->back()->with('error', 'No se encontró información de reunión para esta solicitud.');
        }

        $attendance = $validated['attendance'];

        foreach ($attendance as $participantId => $attended) {
            $participant = MeetingParticipant::where('id', $participantId)
                ->where('meeting_detail_id', $meetingDetail->id)
                ->first();

            if ($participant) {
                $this->meetingLifecycleService->markAttendance($participant, (bool) $attended);
            }
        }

        return redirect()->back()->with('success', 'Asistencia registrada correctamente.');
    }

    /**
     * Store a commitment (impact task) for the meeting.
     */
    public function storeCommitment(StoreCommitmentRequest $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'assigned_to' => $validated['technician_id'],
            'due_date' => $validated['due_date'],
        ];

        try {
            $this->meetingLifecycleService->createCommitment($serviceRequest, $data);

            return redirect()->back()->with('success', 'Compromiso creado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
