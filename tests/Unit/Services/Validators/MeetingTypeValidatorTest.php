<?php

namespace Tests\Unit\Services\Validators;

use Tests\TestCase;
use App\Services\Validators\MeetingTypeValidator;
use App\DTOs\ValidationResult;
use App\Models\ServiceRequest;
use App\Models\MeetingDetail;
use App\Models\MeetingParticipant;
use App\Models\ServiceRequestEvidence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;

class MeetingTypeValidatorTest extends TestCase
{
    private MeetingTypeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MeetingTypeValidator();
    }

    // ==================== getRequiredFieldsForCreation ====================

    public function test_required_fields_returns_meeting_specific_fields(): void
    {
        $fields = $this->validator->getRequiredFieldsForCreation();

        $this->assertEquals(['scheduled_date', 'start_time', 'expected_duration_minutes'], $fields);
    }

    // ==================== PENDIENTE → ACEPTADA ====================

    public function test_pending_to_accepted_fails_without_meeting_detail(): void
    {
        $sr = $this->createServiceRequestMock(meetingDetail: null);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            "Se requiere al menos un participante con rol 'organizador' para continuar.",
            $result->errors
        );
    }

    public function test_pending_to_accepted_fails_without_organizer(): void
    {
        $meetingDetail = $this->createMeetingDetailMock(hasOrganizer: false);
        $sr = $this->createServiceRequestMock(meetingDetail: $meetingDetail);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            "Se requiere al menos un participante con rol 'organizador' para continuar.",
            $result->errors
        );
    }

    public function test_pending_to_accepted_passes_with_organizer(): void
    {
        $meetingDetail = $this->createMeetingDetailMock(hasOrganizer: true);
        $sr = $this->createServiceRequestMock(meetingDetail: $meetingDetail);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    // ==================== EN_PROCESO → RESUELTA ====================

    public function test_in_progress_to_resolved_fails_without_evidence(): void
    {
        $sr = $this->createServiceRequestMock(hasValidEvidence: false);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_RESOLVED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            'Se requiere al menos una evidencia (archivo, acta o enlace) para resolver una solicitud de reunión.',
            $result->errors
        );
    }

    public function test_in_progress_to_resolved_passes_with_valid_evidence(): void
    {
        $sr = $this->createServiceRequestMock(hasValidEvidence: true);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_RESOLVED);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    // ==================== RESUELTA → CERRADA ====================

    public function test_resolved_to_closed_fails_without_meeting_detail(): void
    {
        $sr = $this->createServiceRequestMock(meetingDetail: null);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_RESOLVED, ServiceRequest::STATUS_CLOSED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            'Se requiere que al menos un participante esté asignado a la reunión para su cierre.',
            $result->errors
        );
    }

    public function test_resolved_to_closed_fails_without_participants(): void
    {
        $meetingDetail = $this->createMeetingDetailMock(participantCount: 0, hasAttended: false);
        $sr = $this->createServiceRequestMock(meetingDetail: $meetingDetail);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_RESOLVED, ServiceRequest::STATUS_CLOSED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            'Se requiere que al menos un participante esté asignado a la reunión para su cierre.',
            $result->errors
        );
    }

    public function test_resolved_to_closed_fails_without_attendance(): void
    {
        $meetingDetail = $this->createMeetingDetailMock(participantCount: 2, hasAttended: false);
        $sr = $this->createServiceRequestMock(meetingDetail: $meetingDetail);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_RESOLVED, ServiceRequest::STATUS_CLOSED);

        $this->assertFalse($result->passed);
        $this->assertContains(
            'Se requiere que al menos un participante tenga asistencia confirmada para cerrar la reunión.',
            $result->errors
        );
    }

    public function test_resolved_to_closed_passes_with_attended_participant(): void
    {
        $meetingDetail = $this->createMeetingDetailMock(participantCount: 2, hasAttended: true);
        $sr = $this->createServiceRequestMock(meetingDetail: $meetingDetail);

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_RESOLVED, ServiceRequest::STATUS_CLOSED);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    // ==================== Non-validated transitions ====================

    public function test_non_validated_transitions_pass(): void
    {
        $sr = $this->createServiceRequestMock();

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_ACCEPTED, ServiceRequest::STATUS_IN_PROGRESS);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    public function test_same_status_transition_passes(): void
    {
        $sr = $this->createServiceRequestMock();

        $result = $this->validator->validateTransition($sr, ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_PENDING);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    // ==================== Helper Methods ====================

    private function createServiceRequestMock(
        ?object $meetingDetail = null,
        ?bool $hasValidEvidence = null,
    ): ServiceRequest {
        $sr = Mockery::mock(ServiceRequest::class)->makePartial();

        // meetingDetail property accessor
        $sr->shouldReceive('getAttribute')
            ->with('meetingDetail')
            ->andReturn($meetingDetail);

        // evidences relationship (for EN_PROCESO → RESUELTA)
        if ($hasValidEvidence !== null) {
            $evidenceQuery = Mockery::mock();
            $evidenceQuery->shouldReceive('whereIn')
                ->with('evidence_type', ['ARCHIVO', 'ACTA', 'ENLACE'])
                ->andReturnSelf();
            $evidenceQuery->shouldReceive('exists')
                ->andReturn($hasValidEvidence);

            $sr->shouldReceive('evidences')
                ->andReturn($evidenceQuery);
        }

        return $sr;
    }

    private function createMeetingDetailMock(
        ?bool $hasOrganizer = null,
        ?int $participantCount = null,
        ?bool $hasAttended = null,
    ): object {
        $meetingDetail = Mockery::mock(MeetingDetail::class)->makePartial();

        // participants() relationship mock
        $participantsQuery = Mockery::mock();

        if ($hasOrganizer !== null) {
            $organizerQuery = Mockery::mock();
            $organizerQuery->shouldReceive('exists')->andReturn($hasOrganizer);
            $participantsQuery->shouldReceive('organizers')->andReturn($organizerQuery);
        }

        if ($participantCount !== null) {
            $participantsQuery->shouldReceive('count')->andReturn($participantCount);
        }

        if ($hasAttended !== null) {
            $attendedQuery = Mockery::mock();
            $attendedQuery->shouldReceive('exists')->andReturn($hasAttended);
            $participantsQuery->shouldReceive('attended')->andReturn($attendedQuery);
        }

        $meetingDetail->shouldReceive('participants')->andReturn($participantsQuery);

        return $meetingDetail;
    }
}
