<?php

namespace App\DTOs;

class ValidationResult
{
    /**
     * @param bool $passed Whether the validation passed
     * @param array<string> $errors List of error messages when validation failed
     */
    public function __construct(
        public readonly bool $passed,
        public readonly array $errors = [],
    ) {
    }

    /**
     * Create a passing validation result.
     */
    public static function pass(): self
    {
        return new self(passed: true, errors: []);
    }

    /**
     * Create a failing validation result with error messages.
     *
     * @param array<string> $errors
     */
    public static function fail(array $errors): self
    {
        return new self(passed: false, errors: $errors);
    }
}
