<?php

namespace App\Contracts;

use App\DTOs\ValidationResult;
use App\Models\ServiceRequest;

interface RequestTypeValidatorInterface
{
    /**
     * Validate a state transition for a service request based on its type-specific rules.
     *
     * @param ServiceRequest $sr The service request being transitioned
     * @param string $from The current status
     * @param string $to The target status
     * @return ValidationResult
     */
    public function validateTransition(ServiceRequest $sr, string $from, string $to): ValidationResult;

    /**
     * Get the list of required fields for creating a service request of this type.
     *
     * @return array<string>
     */
    public function getRequiredFieldsForCreation(): array;
}
