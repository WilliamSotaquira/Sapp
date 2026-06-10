<?php

namespace Tests\Unit\Services;

use App\DTOs\OverlapResult;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Services\DateSuggestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateSuggestionServiceTest extends TestCase
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
    // validateNoOverlap() tests
    // ---------------------------------------------------------------

    public function test_validate_no_overlap_returns_no_overlap_when_no_cuts_exist(): void
    {
        $contract = $this->createContract();

        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-03-01'),
            Carbon::parse('2024-03-31')
        );

        $this->assertInstanceOf(OverlapResult::class, $result);
        $this->assertFalse($result->hasOverlap);
        $this->assertNull($result->conflictingCut);
    }

    public function test_validate_no_overlap_detects_overlap_when_ranges_intersect(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Proposed range overlaps with existing: starts during existing cut
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-15'),
            Carbon::parse('2024-02-15')
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertNotNull($result->conflictingCut);
        $this->assertEquals($existingCut->id, $result->conflictingCut->id);
    }

    public function test_validate_no_overlap_detects_overlap_when_proposed_contains_existing(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-10 00:00:00',
            'end_date' => '2024-01-20 23:59:00',
        ]);

        // Proposed range fully contains existing
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31')
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertEquals($existingCut->id, $result->conflictingCut->id);
    }

    public function test_validate_no_overlap_detects_overlap_when_existing_contains_proposed(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Proposed range is fully within existing
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-10'),
            Carbon::parse('2024-01-20')
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertEquals($existingCut->id, $result->conflictingCut->id);
    }

    public function test_validate_no_overlap_detects_overlap_on_boundary_start(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Proposed starts exactly when existing ends (A_start <= B_end AND B_start <= A_end)
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-31 23:59:00'),
            Carbon::parse('2024-02-28 23:59:00')
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertEquals($existingCut->id, $result->conflictingCut->id);
    }

    public function test_validate_no_overlap_detects_overlap_on_boundary_end(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-15 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Proposed ends exactly when existing starts (boundary overlap)
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-01 00:00:00'),
            Carbon::parse('2024-01-15 00:00:00')
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertEquals($existingCut->id, $result->conflictingCut->id);
    }

    public function test_validate_no_overlap_no_overlap_when_ranges_are_adjacent(): void
    {
        $contract = $this->createContract();

        Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Proposed starts after existing ends (no overlap)
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-02-01 00:00:00'),
            Carbon::parse('2024-02-29 23:59:00')
        );

        $this->assertFalse($result->hasOverlap);
        $this->assertNull($result->conflictingCut);
    }

    public function test_validate_no_overlap_no_overlap_when_proposed_is_before_existing(): void
    {
        $contract = $this->createContract();

        Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut March',
            'start_date' => '2024-03-01 00:00:00',
            'end_date' => '2024-03-31 23:59:00',
        ]);

        // Proposed is entirely before existing
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-01 00:00:00'),
            Carbon::parse('2024-01-31 23:59:00')
        );

        $this->assertFalse($result->hasOverlap);
        $this->assertNull($result->conflictingCut);
    }

    public function test_validate_no_overlap_excludes_specified_cut_id(): void
    {
        $contract = $this->createContract();

        $existingCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Same range as existing cut, but excluding it (edit scenario)
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-01-01 00:00:00'),
            Carbon::parse('2024-01-31 23:59:00'),
            $existingCut->id
        );

        $this->assertFalse($result->hasOverlap);
        $this->assertNull($result->conflictingCut);
    }

    public function test_validate_no_overlap_does_not_detect_overlap_with_other_contracts(): void
    {
        $contract1 = $this->createContract(['number' => 'C-001', 'name' => 'Contract 1']);
        $contract2 = $this->createContract(['number' => 'C-002', 'name' => 'Contract 2']);

        // Cut exists on contract 1
        Cut::create([
            'contract_id' => $contract1->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        // Check overlap for contract 2 - should not conflict
        $result = $this->service->validateNoOverlap(
            $contract2->id,
            Carbon::parse('2024-01-01 00:00:00'),
            Carbon::parse('2024-01-31 23:59:00')
        );

        $this->assertFalse($result->hasOverlap);
        $this->assertNull($result->conflictingCut);
    }

    public function test_validate_no_overlap_excludes_only_specified_cut_detects_others(): void
    {
        $contract = $this->createContract();

        $cut1 = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut January',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-31 23:59:00',
        ]);

        $cut2 = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Cut February',
            'start_date' => '2024-02-01 00:00:00',
            'end_date' => '2024-02-29 23:59:00',
        ]);

        // Proposed overlaps with cut2, excluding cut1
        $result = $this->service->validateNoOverlap(
            $contract->id,
            Carbon::parse('2024-02-15 00:00:00'),
            Carbon::parse('2024-03-15 23:59:00'),
            $cut1->id
        );

        $this->assertTrue($result->hasOverlap);
        $this->assertEquals($cut2->id, $result->conflictingCut->id);
    }
}
