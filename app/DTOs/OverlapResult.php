<?php

namespace App\DTOs;

use App\Models\Cut;

class OverlapResult
{
    /**
     * @param bool $hasOverlap Whether the date range overlaps with an existing cut
     * @param Cut|null $conflictingCut The cut that conflicts, if any
     */
    public function __construct(
        public readonly bool $hasOverlap,
        public readonly ?Cut $conflictingCut = null,
    ) {
    }

    /**
     * Create a result indicating no overlap.
     */
    public static function noOverlap(): self
    {
        return new self(hasOverlap: false);
    }

    /**
     * Create a result indicating an overlap with a specific cut.
     */
    public static function overlaps(Cut $conflictingCut): self
    {
        return new self(hasOverlap: true, conflictingCut: $conflictingCut);
    }
}
