<?php

namespace App\DTOs;

class OrganizationResult
{
    /**
     * @param array<int> $succeeded Evidence IDs that were moved successfully
     * @param array<array{evidence_id: int, reason: string}> $failed Evidence IDs that failed with reasons
     * @param int $successCount Number of successfully moved files
     * @param int $failureCount Number of failed files
     */
    public function __construct(
        public readonly array $succeeded = [],
        public readonly array $failed = [],
        public readonly int $successCount = 0,
        public readonly int $failureCount = 0,
    ) {
    }

    /**
     * Create an OrganizationResult from succeeded and failed arrays.
     *
     * @param array<int> $succeeded
     * @param array<array{evidence_id: int, reason: string}> $failed
     */
    public static function fromArrays(array $succeeded, array $failed): self
    {
        return new self(
            succeeded: $succeeded,
            failed: $failed,
            successCount: count($succeeded),
            failureCount: count($failed),
        );
    }
}
