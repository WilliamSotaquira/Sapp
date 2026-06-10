<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Services\DateSuggestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property tests for DateSuggestionService: date suggestion and overlap detection.
 *
 * - Property 14: Date suggestion from previous cut
 * - Property 15: End-date defaults to last day of month
 * - Property 16: Overlap detection correctness
 *
 * **Validates: Requirements 5.1, 5.2, 5.4, 5.5**
 *
 * @group pbt Feature: evidence-file-organization, Property 14: Date suggestion from previous cut
 * @group pbt Feature: evidence-file-organization, Property 15: End-date defaults to last day of month
 * @group pbt Feature: evidence-file-organization, Property 16: Overlap detection correctness
 */
class DateSuggestionPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected DateSuggestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DateSuggestionService();
    }

    protected function createContract(array $overrides = []): Contract
    {
        $company = Company::create(['name' => 'Company ' . uniqid()]);

        return Contract::create(array_merge([
            'company_id' => $company->id,
            'number' => 'C-' . uniqid(),
            'name' => 'Test Contract',
            'is_active' => true,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Property 14: Date suggestion from previous cut
    // For any contract with at least one existing cut, the suggested
    // start_date for a new cut SHALL equal the end_date of the most
    // recent cut plus exactly one calendar day, at time 00:00.
    //
    // Validates: Requirements 5.1, 5.2
    // ---------------------------------------------------------------

    /**
     * @group pbt Feature: evidence-file-organization, Property 14: Date suggestion from previous cut
     */
    public function test_start_date_equals_previous_end_date_plus_one_day_at_midnight(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random end_date for the previous cut
            $year = random_int(2020, 2030);
            $month = random_int(1, 12);
            $day = random_int(1, 28);
            $hour = random_int(0, 23);
            $minute = random_int(0, 59);

            $prevEndDate = Carbon::create($year, $month, $day, $hour, $minute, 0);

            // Create a fresh contract and cut for each iteration
            $contract = $this->createContract();
            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Cut iteration {$i}",
                'start_date' => $prevEndDate->copy()->subDays(random_int(1, 30))->startOfDay()->format('Y-m-d H:i:s'),
                'end_date' => $prevEndDate->format('Y-m-d H:i:s'),
            ]);

            $suggestion = $this->service->suggestDates($contract->id);

            // The start_date should be the next day at 00:00
            $expectedStart = $prevEndDate->copy()->addDay()->startOfDay();

            $this->assertTrue(
                $suggestion->startDate->equalTo($expectedStart),
                "Iteration {$i}: Expected start_date={$expectedStart->format('Y-m-d H:i:s')} "
                . "but got {$suggestion->startDate->format('Y-m-d H:i:s')} "
                . "(prev end_date={$prevEndDate->format('Y-m-d H:i:s')})"
            );

            // Verify time is exactly 00:00:00
            $this->assertEquals(0, $suggestion->startDate->hour, "Iteration {$i}: Hour should be 0");
            $this->assertEquals(0, $suggestion->startDate->minute, "Iteration {$i}: Minute should be 0");
            $this->assertEquals(0, $suggestion->startDate->second, "Iteration {$i}: Second should be 0");
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 14: Date suggestion from previous cut
     */
    public function test_start_date_uses_most_recent_cut_when_multiple_exist(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $contract = $this->createContract();

            // Generate 2-5 random cuts with non-overlapping dates
            $numCuts = random_int(2, 5);
            $baseDate = Carbon::create(random_int(2020, 2028), random_int(1, 6), 1);
            $latestEndDate = null;

            for ($j = 0; $j < $numCuts; $j++) {
                $startDate = $baseDate->copy()->addMonths($j)->startOfDay();
                $endDate = $startDate->copy()->addDays(random_int(15, 28));

                Cut::create([
                    'contract_id' => $contract->id,
                    'name' => "Cut {$j} iteration {$i}",
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                ]);

                if ($latestEndDate === null || $endDate->greaterThan($latestEndDate)) {
                    $latestEndDate = $endDate->copy();
                }
            }

            $suggestion = $this->service->suggestDates($contract->id);

            $expectedStart = $latestEndDate->copy()->addDay()->startOfDay();

            $this->assertTrue(
                $suggestion->startDate->equalTo($expectedStart),
                "Iteration {$i}: With {$numCuts} cuts, expected start={$expectedStart->format('Y-m-d H:i:s')} "
                . "but got {$suggestion->startDate->format('Y-m-d H:i:s')} "
                . "(latest end_date={$latestEndDate->format('Y-m-d H:i:s')})"
            );
        }
    }

    // ---------------------------------------------------------------
    // Property 15: End-date defaults to last day of month
    // For any suggested start_date, the default end_date SHALL be the
    // last calendar day of the month containing that start_date, at 23:59.
    //
    // Validates: Requirements 5.4
    // ---------------------------------------------------------------

    /**
     * @group pbt Feature: evidence-file-organization, Property 15: End-date defaults to last day of month
     */
    public function test_end_date_is_last_day_of_month_at_2359(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random end_date for previous cut that will produce a known start_date
            $year = random_int(2020, 2030);
            $month = random_int(1, 12);
            $day = random_int(1, 28);
            $prevEndDate = Carbon::create($year, $month, $day, random_int(0, 23), random_int(0, 59), 0);

            $contract = $this->createContract();
            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Cut iteration {$i}",
                'start_date' => $prevEndDate->copy()->subDays(10)->format('Y-m-d H:i:s'),
                'end_date' => $prevEndDate->format('Y-m-d H:i:s'),
            ]);

            $suggestion = $this->service->suggestDates($contract->id);

            // The end_date should be last day of the month of start_date at 23:59
            $startMonth = $suggestion->startDate->month;
            $startYear = $suggestion->startDate->year;
            $lastDayOfMonth = Carbon::create($startYear, $startMonth, 1)->endOfMonth()->day;

            $this->assertEquals(
                $startYear,
                $suggestion->endDate->year,
                "Iteration {$i}: End date year should match start date year"
            );
            $this->assertEquals(
                $startMonth,
                $suggestion->endDate->month,
                "Iteration {$i}: End date month should match start date month"
            );
            $this->assertEquals(
                $lastDayOfMonth,
                $suggestion->endDate->day,
                "Iteration {$i}: End date day should be last day of month ({$lastDayOfMonth}) "
                . "but got {$suggestion->endDate->day} for start={$suggestion->startDate->format('Y-m-d')}"
            );
            $this->assertEquals(23, $suggestion->endDate->hour, "Iteration {$i}: End hour should be 23");
            $this->assertEquals(59, $suggestion->endDate->minute, "Iteration {$i}: End minute should be 59");
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 15: End-date defaults to last day of month
     */
    public function test_end_date_handles_leap_years_and_varying_month_lengths(): void
    {
        // Test specific months with known last days across various years
        $testCases = [
            // Leap years: February has 29 days
            ['year' => 2024, 'month' => 2, 'expectedLastDay' => 29],
            ['year' => 2028, 'month' => 2, 'expectedLastDay' => 29],
            // Non-leap years: February has 28 days
            ['year' => 2023, 'month' => 2, 'expectedLastDay' => 28],
            ['year' => 2025, 'month' => 2, 'expectedLastDay' => 28],
            // 30-day months
            ['year' => 2024, 'month' => 4, 'expectedLastDay' => 30],
            ['year' => 2024, 'month' => 6, 'expectedLastDay' => 30],
            ['year' => 2024, 'month' => 9, 'expectedLastDay' => 30],
            ['year' => 2024, 'month' => 11, 'expectedLastDay' => 30],
            // 31-day months
            ['year' => 2024, 'month' => 1, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 3, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 5, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 7, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 8, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 10, 'expectedLastDay' => 31],
            ['year' => 2024, 'month' => 12, 'expectedLastDay' => 31],
        ];

        foreach ($testCases as $idx => $case) {
            // Create a previous cut ending the day before the 1st of the target month
            $prevEndDate = Carbon::create($case['year'], $case['month'], 1)->subDay()
                ->setTime(23, 59, 0);

            $contract = $this->createContract();
            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Cut case {$idx}",
                'start_date' => $prevEndDate->copy()->subDays(10)->format('Y-m-d H:i:s'),
                'end_date' => $prevEndDate->format('Y-m-d H:i:s'),
            ]);

            $suggestion = $this->service->suggestDates($contract->id);

            // The start should fall on the 1st of target month (prev end was last day of prior month)
            // Actually start_date = prevEndDate + 1 day at 00:00 = 1st of target month
            $this->assertEquals($case['month'], $suggestion->startDate->month, "Case {$idx}: Start month mismatch");
            $this->assertEquals($case['year'], $suggestion->startDate->year, "Case {$idx}: Start year mismatch");

            // End date should be last day of that month at 23:59
            $this->assertEquals(
                $case['expectedLastDay'],
                $suggestion->endDate->day,
                "Case {$idx}: For {$case['year']}-{$case['month']}, expected last day {$case['expectedLastDay']} "
                . "but got {$suggestion->endDate->day}"
            );
            $this->assertEquals(23, $suggestion->endDate->hour, "Case {$idx}: Hour should be 23");
            $this->assertEquals(59, $suggestion->endDate->minute, "Case {$idx}: Minute should be 59");
        }
    }

    // ---------------------------------------------------------------
    // Property 16: Overlap detection correctness
    // For any two date ranges (A_start, A_end) and (B_start, B_end)
    // belonging to the same contract, the overlap detection SHALL
    // return true if and only if A_start <= B_end AND B_start <= A_end.
    //
    // Validates: Requirements 5.5
    // ---------------------------------------------------------------

    /**
     * @group pbt Feature: evidence-file-organization, Property 16: Overlap detection correctness
     */
    public function test_overlap_detection_matches_mathematical_formula(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $contract = $this->createContract();

            // Generate random existing cut (A)
            $aStartYear = random_int(2020, 2028);
            $aStartMonth = random_int(1, 12);
            $aStartDay = random_int(1, 28);
            $aStart = Carbon::create($aStartYear, $aStartMonth, $aStartDay, 0, 0, 0);
            $aEnd = $aStart->copy()->addDays(random_int(1, 60));

            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Existing cut {$i}",
                'start_date' => $aStart->format('Y-m-d H:i:s'),
                'end_date' => $aEnd->format('Y-m-d H:i:s'),
            ]);

            // Generate random proposed range (B)
            $bStartOffset = random_int(-90, 90);
            $bStart = $aStart->copy()->addDays($bStartOffset);
            $bEnd = $bStart->copy()->addDays(random_int(1, 60));

            // Mathematical overlap formula: A_start <= B_end AND B_start <= A_end
            $expectedOverlap = $aStart->lte($bEnd) && $bStart->lte($aEnd);

            $result = $this->service->validateNoOverlap($contract->id, $bStart, $bEnd);

            $this->assertEquals(
                $expectedOverlap,
                $result->hasOverlap,
                "Iteration {$i}: Overlap mismatch. "
                . "A=[{$aStart->format('Y-m-d H:i')}, {$aEnd->format('Y-m-d H:i')}], "
                . "B=[{$bStart->format('Y-m-d H:i')}, {$bEnd->format('Y-m-d H:i')}]. "
                . "Expected overlap=" . ($expectedOverlap ? 'true' : 'false')
                . " but got " . ($result->hasOverlap ? 'true' : 'false')
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 16: Overlap detection correctness
     */
    public function test_no_overlap_when_ranges_are_disjoint(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $contract = $this->createContract();

            // Generate existing cut
            $aStart = Carbon::create(random_int(2020, 2028), random_int(1, 12), random_int(1, 28), 0, 0, 0);
            $aEnd = $aStart->copy()->addDays(random_int(1, 30));

            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Existing cut {$i}",
                'start_date' => $aStart->format('Y-m-d H:i:s'),
                'end_date' => $aEnd->format('Y-m-d H:i:s'),
            ]);

            // Generate proposed range strictly AFTER existing cut (guaranteed no overlap)
            $gap = random_int(1, 60);
            $bStart = $aEnd->copy()->addDays($gap);
            $bEnd = $bStart->copy()->addDays(random_int(1, 30));

            $result = $this->service->validateNoOverlap($contract->id, $bStart, $bEnd);

            $this->assertFalse(
                $result->hasOverlap,
                "Iteration {$i}: Should NOT overlap. "
                . "A=[{$aStart->format('Y-m-d')}, {$aEnd->format('Y-m-d')}], "
                . "B=[{$bStart->format('Y-m-d')}, {$bEnd->format('Y-m-d')}] "
                . "(gap={$gap} days)"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 16: Overlap detection correctness
     */
    public function test_overlap_when_ranges_intersect(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $contract = $this->createContract();

            // Generate existing cut
            $aStart = Carbon::create(random_int(2020, 2028), random_int(1, 12), random_int(1, 28), 0, 0, 0);
            $duration = random_int(10, 40);
            $aEnd = $aStart->copy()->addDays($duration);

            Cut::create([
                'contract_id' => $contract->id,
                'name' => "Existing cut {$i}",
                'start_date' => $aStart->format('Y-m-d H:i:s'),
                'end_date' => $aEnd->format('Y-m-d H:i:s'),
            ]);

            // Generate proposed range that OVERLAPS (starts within existing range)
            $overlapOffset = random_int(0, $duration);
            $bStart = $aStart->copy()->addDays($overlapOffset);
            $bEnd = $bStart->copy()->addDays(random_int(1, 30));

            $result = $this->service->validateNoOverlap($contract->id, $bStart, $bEnd);

            $this->assertTrue(
                $result->hasOverlap,
                "Iteration {$i}: Should overlap. "
                . "A=[{$aStart->format('Y-m-d')}, {$aEnd->format('Y-m-d')}], "
                . "B=[{$bStart->format('Y-m-d')}, {$bEnd->format('Y-m-d')}]"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 16: Overlap detection correctness
     */
    public function test_overlap_detection_is_contract_scoped(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $contract1 = $this->createContract();
            $contract2 = $this->createContract();

            // Create a cut on contract1
            $aStart = Carbon::create(random_int(2020, 2028), random_int(1, 12), random_int(1, 28), 0, 0, 0);
            $aEnd = $aStart->copy()->addDays(random_int(10, 30));

            Cut::create([
                'contract_id' => $contract1->id,
                'name' => "Cut on contract1 iter {$i}",
                'start_date' => $aStart->format('Y-m-d H:i:s'),
                'end_date' => $aEnd->format('Y-m-d H:i:s'),
            ]);

            // Check overlap on contract2 with the same range - should NOT overlap
            $result = $this->service->validateNoOverlap($contract2->id, $aStart, $aEnd);

            $this->assertFalse(
                $result->hasOverlap,
                "Iteration {$i}: Cuts on different contracts should not conflict. "
                . "Range=[{$aStart->format('Y-m-d')}, {$aEnd->format('Y-m-d')}]"
            );
        }
    }
}
