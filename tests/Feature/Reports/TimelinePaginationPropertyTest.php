<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 2: Timeline pagination respects page size and date range
 *
 * For any set of service requests and any date range filter, the unified timeline list
 * SHALL return at most 10 items per page, and every returned item SHALL have a
 * `created_at` within the specified date range.
 *
 * **Validates: Requirements 2.3**
 */
class TimelinePaginationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);

        $company->users()->attach($user->id);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-PROP2-001',
            'name' => 'Contrato Property 2',
            'description' => 'Contrato para property test',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $company->refresh();

        return compact('user', 'company', 'contract');
    }

    private function createFamily(Contract $contract): array
    {
        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Timeline Test Family',
            'code' => 'TLF',
            'description' => 'Familia para test de timeline',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Timeline Test Service',
            'code' => 'S_TLF',
            'description' => 'Servicio para test de timeline',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Timeline Test SubService',
            'code' => 'SS_TLF',
            'description' => 'SubServicio para test de timeline',
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA Timeline Test',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 2,
            'resolution_time_hours' => 6,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 60,
            'response_time_minutes' => 120,
            'resolution_time_minutes' => 360,
            'conditions' => 'Test',
            'is_active' => true,
        ]);

        return compact('family', 'service', 'subService', 'sla');
    }

    private function createServiceRequest(
        Company $company,
        User $user,
        SubService $subService,
        ServiceLevelAgreement $sla,
        Requester $requester,
        string $title,
        string $createdAt
    ): ServiceRequest {
        session(['current_company_id' => $company->id]);

        return ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'requester_id' => $requester->id,
            'title' => $title,
            'description' => "Descripción de $title",
            'sub_service_id' => $subService->id,
            'sla_id' => $sla->id,
            'requested_by' => $user->id,
            'assigned_to' => $user->id,
            'technician_assigned_at' => $createdAt,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'ACEPTADA',
            'created_at' => $createdAt,
        ]);
    }

    /**
     * Property 2 - Iteration 1: Page size never exceeds 10 items.
     *
     * For any number of service requests within a date range, the timeline index
     * returns at most 10 items per page.
     *
     * @dataProvider requestCountDataProvider
     */
    public function test_timeline_pagination_returns_at_most_10_items_per_page(int $totalRequests): void
    {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract']);

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        // Create service requests all within the same date range (March 2026)
        for ($i = 0; $i < $totalRequests; $i++) {
            $day = str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Timeline Request $i",
                "2026-03-{$day} 10:00:00"
            );
        }

        // Request the timeline index with the date range covering all requests
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index', [
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
            ]));

        $response->assertStatus(200);

        // Get the paginated results from the view data
        $requests = $response->viewData('requests');

        // Property: page size is at most 10
        $this->assertLessThanOrEqual(10, $requests->count(),
            "Page should contain at most 10 items, got {$requests->count()}");

        // Verify expected count on first page
        $expectedOnPage = min($totalRequests, 10);
        $this->assertEquals($expectedOnPage, $requests->count(),
            "First page should have $expectedOnPage items when total is $totalRequests");
    }

    public static function requestCountDataProvider(): array
    {
        return [
            '5 requests (less than page size)' => [5],
            '10 requests (exactly page size)' => [10],
            '15 requests (exceeds one page)' => [15],
            '25 requests (multiple pages)' => [25],
        ];
    }

    /**
     * Property 2 - Iteration 2: All returned items have created_at within the date range.
     *
     * For any date range filter, every item returned by the timeline index has a
     * created_at timestamp within the specified start and end dates.
     *
     * @dataProvider dateRangeDataProvider
     */
    public function test_all_returned_items_fall_within_specified_date_range(
        string $startDate,
        string $endDate,
        int $requestsInRange,
        int $requestsOutsideRange
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract']);

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $rangeStart = Carbon::parse($startDate);
        $rangeEnd = Carbon::parse($endDate);

        // Create service requests WITHIN the date range
        for ($i = 0; $i < $requestsInRange; $i++) {
            // Distribute requests evenly within the range
            $daysInRange = $rangeStart->diffInDays($rangeEnd);
            $dayOffset = $daysInRange > 0 ? ($i % $daysInRange) : 0;
            $createdAt = $rangeStart->copy()->addDays($dayOffset)->setTime(10, 0, 0);

            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "In Range Request $i",
                $createdAt->format('Y-m-d H:i:s')
            );
        }

        // Create service requests OUTSIDE the date range
        for ($i = 0; $i < $requestsOutsideRange; $i++) {
            // Place them before the range start
            $outsideDate = $rangeStart->copy()->subMonths(2)->addDays($i);

            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Outside Range Request $i",
                $outsideDate->format('Y-m-d H:i:s')
            );
        }

        // Request the timeline index with the specified date range
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(200);

        $requests = $response->viewData('requests');

        // Property: every returned item has created_at within the date range
        $filterStart = Carbon::parse($startDate)->startOfDay();
        $filterEnd = Carbon::parse($endDate)->endOfDay();

        foreach ($requests as $request) {
            $createdAt = Carbon::parse($request->created_at);
            $this->assertTrue(
                $createdAt->gte($filterStart) && $createdAt->lte($filterEnd),
                "Service request '{$request->title}' created_at ({$createdAt}) should be within " .
                "range [{$filterStart} - {$filterEnd}]"
            );
        }

        // Property: no outside-range requests should appear
        foreach ($requests as $request) {
            $this->assertStringNotContainsString('Outside Range', $request->title,
                "Request outside the date range should not appear in results");
        }
    }

    public static function dateRangeDataProvider(): array
    {
        return [
            'one week range, 5 in, 3 out' => ['2026-04-01', '2026-04-07', 5, 3],
            'one month range, 10 in, 5 out' => ['2026-05-01', '2026-05-31', 10, 5],
            'two week range, 15 in, 8 out' => ['2026-06-01', '2026-06-14', 15, 8],
            'one month range, 25 in, 10 out' => ['2026-07-01', '2026-07-31', 25, 10],
        ];
    }

    /**
     * Property 2 - Iteration 3: Pagination across multiple pages respects both constraints.
     *
     * When navigating to page 2, the page size constraint (max 10) still holds
     * and all items still fall within the date range.
     *
     * @dataProvider multiPageDataProvider
     */
    public function test_second_page_also_respects_page_size_and_date_range(int $totalRequests): void
    {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract']);

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        // Create requests within August 2026
        for ($i = 0; $i < $totalRequests; $i++) {
            $day = str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Page2 Request $i",
                "2026-08-{$day} 10:00:00"
            );
        }

        // Request page 2
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index', [
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'page' => 2,
            ]));

        $response->assertStatus(200);

        $requests = $response->viewData('requests');

        // Property: page 2 also has at most 10 items
        $this->assertLessThanOrEqual(10, $requests->count(),
            "Page 2 should contain at most 10 items, got {$requests->count()}");

        // Property: expected items on page 2
        $expectedOnPage2 = max(0, min(10, $totalRequests - 10));
        $this->assertEquals($expectedOnPage2, $requests->count(),
            "Page 2 should have $expectedOnPage2 items when total is $totalRequests");

        // Property: all items on page 2 are within the date range
        $filterStart = Carbon::parse('2026-08-01')->startOfDay();
        $filterEnd = Carbon::parse('2026-08-31')->endOfDay();

        foreach ($requests as $request) {
            $createdAt = Carbon::parse($request->created_at);
            $this->assertTrue(
                $createdAt->gte($filterStart) && $createdAt->lte($filterEnd),
                "Service request on page 2 '{$request->title}' created_at ({$createdAt}) " .
                "should be within range [{$filterStart} - {$filterEnd}]"
            );
        }
    }

    public static function multiPageDataProvider(): array
    {
        return [
            '15 requests (5 on page 2)' => [15],
            '20 requests (10 on page 2)' => [20],
            '25 requests (10 on page 2)' => [25],
        ];
    }

    /**
     * Property 2 - Iteration 4: Default date range (current month) is applied
     * when no date range is specified, and pagination still holds.
     */
    public function test_default_date_range_uses_current_month_with_pagination(): void
    {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract']);

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();

        // Create 15 requests in the current month
        for ($i = 0; $i < 15; $i++) {
            $day = str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);
            $createdAt = $currentMonthStart->copy()->addDays($i % 28)->setTime(10, 0, 0);

            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Current Month Request $i",
                $createdAt->format('Y-m-d H:i:s')
            );
        }

        // Create 5 requests outside the current month (2 months ago)
        for ($i = 0; $i < 5; $i++) {
            $outsideDate = $currentMonthStart->copy()->subMonths(2)->addDays($i);

            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Old Month Request $i",
                $outsideDate->format('Y-m-d H:i:s')
            );
        }

        // Request without specifying date range (should default to current month)
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index'));

        $response->assertStatus(200);

        $requests = $response->viewData('requests');

        // Property: at most 10 items per page
        $this->assertLessThanOrEqual(10, $requests->count(),
            "Default page should contain at most 10 items");

        // Property: all items are within the current month
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        foreach ($requests as $request) {
            $createdAt = Carbon::parse($request->created_at);
            $this->assertTrue(
                $createdAt->gte($monthStart) && $createdAt->lte($monthEnd),
                "Service request '{$request->title}' created_at ({$createdAt}) " .
                "should be within current month [{$monthStart} - {$monthEnd}]"
            );
        }

        // Verify no old requests appear
        foreach ($requests as $request) {
            $this->assertStringNotContainsString('Old Month', $request->title,
                "Requests from outside the current month should not appear");
        }
    }
}
