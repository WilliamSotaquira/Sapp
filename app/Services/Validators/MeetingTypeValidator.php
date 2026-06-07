<?php

namespace App\Services\Validators;

use App\Contracts\RequestTypeValidatorInterface;
use App\DTOs\ValidationResult;
use App\Models\ServiceRequest;

class MeetingTypeValidator implements RequestTypeValidatorInterface
{
    /**
     * Validate a state transition for a meeting-type service request.
     *
     * Rules:
     * - PENDIENTE → ACEPTADA: at least one participant with role "organizador"
     * - EN_PROCESO → RESUELTA: at least one evidence record (ARCHIVO, ACTA, or ENLACE)
     * - RESUELTA → CERRADA: at least one participant with attended=true AND at least one participant exists
     */
    public function validateTransition(ServiceRequest $sr, string $from, string $to): ValidationResult
    {
        if ($from === ServiceRequest::STATUS_PENDING && $to === ServiceRequest::STATUS_ACCEPTED) {
            return $this->validatePendingToAccepted($sr);
        }

        if ($from === ServiceRequest::STATUS_IN_PROGRESS && $to === ServiceRequest::STATUS_RESOLVED) {
            return $this->validateInProgressToResolved($sr);
        }

        if ($from === ServiceRequest::STATUS_RESOLVED && $to === ServiceRequest::STATUS_CLOSED) {
            return $this->validateResolvedToClosed($sr);
        }

        return ValidationResult::pass();
    }

    /**
     * Get the list of required fields for creating a meeting service request.
     *
     * @return array<string>
     */
    public function getRequiredFieldsForCreation(): array
    {
        return ['scheduled_date', 'start_time', 'expected_duration_minutes'];
    }

    /**
     * PENDIENTE → ACEPTADA: verify at least one participant with role "organizador".
     */
    private function validatePendingToAccepted(ServiceRequest $sr): ValidationResult
    {
        $meetingDetail = $sr->meetingDetail;

        if (!$meetingDetail) {
            return ValidationResult::fail([
                "Se requiere al menos un participante con rol 'organizador' para continuar.",
            ]);
        }

        $hasOrganizer = $meetingDetail->participants()->organizers()->exists();

        if (!$hasOrganizer) {
            return ValidationResult::fail([
                "Se requiere al menos un participante con rol 'organizador' para continuar.",
            ]);
        }

        return ValidationResult::pass();
    }

    /**
     * EN_PROCESO → RESUELTA: verify at least one evidence record with type ARCHIVO, ACTA, or ENLACE.
     */
    private function validateInProgressToResolved(ServiceRequest $sr): ValidationResult
    {
        $hasEvidence = $sr->evidences()
            ->whereIn('evidence_type', ['ARCHIVO', 'ACTA', 'ENLACE'])
            ->exists();

        if (!$hasEvidence) {
            return ValidationResult::fail([
                'Se requiere al menos una evidencia (archivo, acta o enlace) para resolver una solicitud de reunión.',
            ]);
        }

        return ValidationResult::pass();
    }

    /**
     * RESUELTA → CERRADA: verify at least one participant exists AND at least one has attended=true.
     */
    private function validateResolvedToClosed(ServiceRequest $sr): ValidationResult
    {
        $meetingDetail = $sr->meetingDetail;
        $errors = [];

        if (!$meetingDetail || $meetingDetail->participants()->count() === 0) {
            $errors[] = 'Se requiere que al menos un participante esté asignado a la reunión para su cierre.';
        }

        if ($meetingDetail) {
            $hasAttendance = $meetingDetail->participants()->attended()->exists();

            if (!$hasAttendance) {
                $errors[] = 'Se requiere que al menos un participante tenga asistencia confirmada para cerrar la reunión.';
            }
        }

        if (!empty($errors)) {
            return ValidationResult::fail($errors);
        }

        return ValidationResult::pass();
    }
}
