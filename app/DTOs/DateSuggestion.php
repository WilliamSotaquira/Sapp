<?php

namespace App\DTOs;

use Carbon\Carbon;

class DateSuggestion
{
    /**
     * @param Carbon $startDate Suggested start date for the new cut
     * @param Carbon $endDate Suggested end date for the new cut
     * @param string $format Display format for the dates
     */
    public function __construct(
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly string $format = 'Y-m-d H:i',
    ) {
    }

    /**
     * Get the formatted start date string.
     */
    public function formattedStartDate(): string
    {
        return $this->startDate->format($this->format);
    }

    /**
     * Get the formatted end date string.
     */
    public function formattedEndDate(): string
    {
        return $this->endDate->format($this->format);
    }
}
