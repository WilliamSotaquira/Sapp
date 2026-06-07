<?php

namespace App\Services\Validators;

use App\Contracts\RequestTypeValidatorInterface;
use App\DTOs\ValidationResult;
use App\Models\ServiceRequest;

class GeneralTypeValidator implements RequestTypeValidatorInterface
{
    /**
     * Validate a state transition for a general/null-type service request.
     *
     * General type has no additional type-specific validations — all transitions are allowed.
     */
    public function validateTransition(ServiceRequest $sr, string $from, string $to): ValidationResult
    {
        return ValidationResult::pass();
    }

    /**
     * Get the list of required fields for creating a general service request.
     *
     * General type has no additional required fields beyond the base service request fields.
     *
     * @return array<string>
     */
    public function getRequiredFieldsForCreation(): array
    {
        return [];
    }
}
