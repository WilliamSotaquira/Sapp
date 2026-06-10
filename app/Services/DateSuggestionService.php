<?php

namespace App\Services;

use App\DTOs\DateSuggestion;
use App\DTOs\OverlapResult;
use App\Models\Cut;
use Carbon\Carbon;

class DateSuggestionService
{
    /**
     * Calcula las fechas sugeridas para un nuevo corte.
     *
     * @param int $contractId - Contrato activo
     * @return DateSuggestion - start_date y end_date sugeridos
     */
    public function suggestDates(int $contractId): DateSuggestion
    {
        $latestCut = Cut::where('contract_id', $contractId)
            ->orderBy('end_date', 'desc')
            ->first();

        if ($latestCut) {
            $startDate = Carbon::parse($latestCut->end_date)
                ->addDay()
                ->startOfDay();
        } else {
            $startDate = Carbon::now()->startOfMinute();
        }

        $endDate = $startDate->copy()
            ->endOfMonth()
            ->setTime(23, 59);

        return new DateSuggestion(
            startDate: $startDate,
            endDate: $endDate,
        );
    }

    /**
     * Validate that a date range does not overlap with existing cuts for the same contract.
     *
     * Overlap condition: A_start <= B_end AND B_start <= A_end
     * Where A is an existing cut and B is the proposed range.
     *
     * @param int $contractId - Contract to check against
     * @param Carbon $start - Proposed start date
     * @param Carbon $end - Proposed end date
     * @param int|null $excludeCutId - Cut ID to exclude (for edit scenarios)
     * @return OverlapResult - Result with overlap status and conflicting cut details
     */
    public function validateNoOverlap(int $contractId, Carbon $start, Carbon $end, ?int $excludeCutId = null): OverlapResult
    {
        $query = Cut::where('contract_id', $contractId)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);

        if ($excludeCutId !== null) {
            $query->where('id', '!=', $excludeCutId);
        }

        $conflictingCut = $query->first();

        if ($conflictingCut) {
            return OverlapResult::overlaps($conflictingCut);
        }

        return OverlapResult::noOverlap();
    }
}
