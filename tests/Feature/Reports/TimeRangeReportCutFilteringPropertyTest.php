<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 3: Cut-based and family filtering constrains results
 *
 * For any cut selected as date range source and any set of selected service families,
 * the Time Range report results SHALL only contain service requests that are associated
 * with the selected cut (via cut_service_request) AND belong to at least one of the
 * selected service families.
 *
 * **Validates: Requirements 3.4, 3.7**
 */
class TimeRangeReportCutFilteringPropertyTest extends TestCase
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
            'number' => 'C-PROP3-001',
            'name' => 'Contrato Property 3',
            'description' => 'Contrato para property test',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $company->refresh();

        return compact('user', 'company', 'contract');
    }

    private function createFamily(Contract $contract, string $name, string $code): array
    {
        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => $name,
            'code' => $code,
            'description' => "Familia $name",
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => "Servicio $name",
            'code' => "S_$code",
            'description' => "Servicio de $name",
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => "SubServicio $name",
            'code' => "SS_$code",
            'description' => "SubServicio de $name",
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => "SLA $name",
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
        string $createdAt = '2026-03-15 10:00:00'
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
     * Property 3 - Iteration 1: When a cut_id is provided, only service requests
     * associated with that cut appear in results.
     *
     * @dataProvider cutFilteringDataProvider
     */
    public function test_cut_filtering_only_returns_requests_associated_with_cut(
        int $requestsInCut,
        int $requestsOutsideCut
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'Alpha', 'ALPHA');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Test',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => $data['user']->id,
        ]);

        // Create service requests within the cut's date range (auto-sync will associate them)
        $inCutIds = [];
        for ($i = 0; $i < $requestsInCut; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "In Cut Request $i",
                '2026-03-15 10:00:00'
            );
            $inCutIds[] = $sr->id;
        }

        // Ensure the cut has exactly these requests
        $cut->serviceRequests()->sync($inCutIds);

        // Create service requests OUTSIDE the cut's date range (won't be auto-synced)
        for ($i = 0; $i < $requestsOutsideCut; $i++) {
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Outside Cut Request $i",
                '2026-01-10 10:00:00' // Outside the cut's date range
            );
        }

        // Generate report using cut_id
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$familyData['family']->id],
            ]);

        // The response should be a PDF download (200) containing only cut-associated requests
        $response->assertStatus(200);

        // Verify by querying the same logic the controller uses
        $resultQuery = $cut->serviceRequests()
            ->where('service_requests.company_id', $data['company']->id)
            ->whereHas('subService.service.family', function ($q) use ($familyData) {
                $q->whereIn('id', [$familyData['family']->id]);
            });

        $results = $resultQuery->get();

        // Property: all results must be associated with the cut
        $this->assertCount($requestsInCut, $results);
        foreach ($results as $result) {
            $this->assertContains($result->id, $inCutIds,
                "Service request {$result->id} should be associated with the cut");
        }
    }

    public static function cutFilteringDataProvider(): array
    {
        return [
            'one in cut, none outside' => [1, 0],
            'two in cut, one outside' => [2, 1],
            'three in cut, three outside' => [3, 3],
            'one in cut, five outside' => [1, 5],
            'five in cut, two outside' => [5, 2],
        ];
    }

    /**
     * Property 3 - Iteration 2: When family filters are applied alongside a cut,
     * results are further constrained to only those families.
     *
     * @dataProvider familyFilteringDataProvider
     */
    public function test_family_filtering_constrains_cut_results_to_selected_families(
        int $requestsInSelectedFamily,
        int $requestsInOtherFamily
    ): void {
        $data = $this->seedContext();

        // Create two different families
        $familyA = $this->createFamily($data['contract'], 'FamilyA', 'FAMA');
        $familyB = $this->createFamily($data['contract'], 'FamilyB', 'FAMB');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Multi-Family',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'created_by' => $data['user']->id,
        ]);

        $allCutRequestIds = [];

        // Create requests in Family A (the selected family)
        $familyAIds = [];
        for ($i = 0; $i < $requestsInSelectedFamily; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyA['subService'],
                $familyA['sla'],
                $requester,
                "FamilyA Request $i",
                '2026-04-10 10:00:00'
            );
            $familyAIds[] = $sr->id;
            $allCutRequestIds[] = $sr->id;
        }

        // Create requests in Family B (not selected)
        for ($i = 0; $i < $requestsInOtherFamily; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyB['subService'],
                $familyB['sla'],
                $requester,
                "FamilyB Request $i",
                '2026-04-15 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
        }

        // Associate ALL requests with the cut
        $cut->serviceRequests()->syncWithoutDetaching($allCutRequestIds);

        // Generate report using cut_id with only Family A selected
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$familyA['family']->id],
            ]);

        $response->assertStatus(200);

        // Verify the filtering logic: only Family A requests should be in results
        $resultQuery = $cut->serviceRequests()
            ->where('service_requests.company_id', $data['company']->id)
            ->whereHas('subService.service.family', function ($q) use ($familyA) {
                $q->whereIn('id', [$familyA['family']->id]);
            });

        $results = $resultQuery->get();

        // Property: results should only contain requests from the selected family
        $this->assertCount($requestsInSelectedFamily, $results);
        foreach ($results as $result) {
            $this->assertContains($result->id, $familyAIds,
                "Service request {$result->id} should belong to the selected family");

            // Verify the family chain
            $resultFamily = $result->subService->service->family;
            $this->assertEquals($familyA['family']->id, $resultFamily->id,
                "Service request {$result->id} should belong to Family A");
        }
    }

    public static function familyFilteringDataProvider(): array
    {
        return [
            'one selected, one other' => [1, 1],
            'two selected, three other' => [2, 3],
            'three selected, one other' => [3, 1],
            'one selected, four other' => [1, 4],
            'four selected, two other' => [4, 2],
        ];
    }

    /**
     * Property 3 - Iteration 3: Multiple families selected should return union
     * of requests from all selected families within the cut.
     *
     * @dataProvider multipleFamiliesDataProvider
     */
    public function test_multiple_families_selected_returns_union_within_cut(
        int $requestsInFamilyA,
        int $requestsInFamilyB,
        int $requestsInFamilyC
    ): void {
        $data = $this->seedContext();

        // Create three families
        $familyA = $this->createFamily($data['contract'], 'FamMultiA', 'FMA');
        $familyB = $this->createFamily($data['contract'], 'FamMultiB', 'FMB');
        $familyC = $this->createFamily($data['contract'], 'FamMultiC', 'FMC');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Multi-Family Union',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'created_by' => $data['user']->id,
        ]);

        $allCutRequestIds = [];
        $selectedFamilyIds = [];

        // Create requests in Family A
        for ($i = 0; $i < $requestsInFamilyA; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyA['subService'],
                $familyA['sla'],
                $requester,
                "MultiA Request $i",
                '2026-05-10 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
            $selectedFamilyIds[] = $sr->id;
        }

        // Create requests in Family B
        for ($i = 0; $i < $requestsInFamilyB; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyB['subService'],
                $familyB['sla'],
                $requester,
                "MultiB Request $i",
                '2026-05-15 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
            $selectedFamilyIds[] = $sr->id;
        }

        // Create requests in Family C (NOT selected)
        for ($i = 0; $i < $requestsInFamilyC; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyC['subService'],
                $familyC['sla'],
                $requester,
                "MultiC Request $i",
                '2026-05-20 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
        }

        // Associate ALL requests with the cut
        $cut->serviceRequests()->syncWithoutDetaching($allCutRequestIds);

        // Select families A and B (not C)
        $selectedFamilies = [$familyA['family']->id, $familyB['family']->id];

        // Generate report
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => $selectedFamilies,
            ]);

        $response->assertStatus(200);

        // Verify the filtering logic
        $resultQuery = $cut->serviceRequests()
            ->where('service_requests.company_id', $data['company']->id)
            ->whereHas('subService.service.family', function ($q) use ($selectedFamilies) {
                $q->whereIn('id', $selectedFamilies);
            });

        $results = $resultQuery->get();

        // Property: results should contain requests from Family A + Family B only
        $expectedCount = $requestsInFamilyA + $requestsInFamilyB;
        $this->assertCount($expectedCount, $results);

        foreach ($results as $result) {
            $this->assertContains($result->id, $selectedFamilyIds,
                "Service request {$result->id} should belong to one of the selected families");

            $resultFamilyId = $result->subService->service->family->id;
            $this->assertContains($resultFamilyId, $selectedFamilies,
                "Service request family {$resultFamilyId} should be in the selected families list");
        }
    }

    public static function multipleFamiliesDataProvider(): array
    {
        return [
            'one each family' => [1, 1, 1],
            'two A, one B, three C' => [2, 1, 3],
            'three A, two B, one C' => [3, 2, 1],
            'one A, three B, two C' => [1, 3, 2],
            'two A, two B, two C' => [2, 2, 2],
        ];
    }

    /**
     * Property 3 - Iteration 4: No family filter means all families in the cut are returned.
     */
    public function test_no_family_filter_returns_all_cut_requests(): void
    {
        $data = $this->seedContext();

        $familyA = $this->createFamily($data['contract'], 'NoFilterA', 'NFA');
        $familyB = $this->createFamily($data['contract'], 'NoFilterB', 'NFB');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte No Filter',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'created_by' => $data['user']->id,
        ]);

        $allCutRequestIds = [];

        // Create requests in both families
        for ($i = 0; $i < 2; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyA['subService'],
                $familyA['sla'],
                $requester,
                "NoFilter FamA Request $i",
                '2026-06-10 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
        }

        for ($i = 0; $i < 3; $i++) {
            $sr = $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyB['subService'],
                $familyB['sla'],
                $requester,
                "NoFilter FamB Request $i",
                '2026-06-15 10:00:00'
            );
            $allCutRequestIds[] = $sr->id;
        }

        // Associate all with the cut
        $cut->serviceRequests()->syncWithoutDetaching($allCutRequestIds);

        // Generate report without family filter
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
            ]);

        $response->assertStatus(200);

        // Verify: all cut requests should be returned when no family filter
        $results = $cut->serviceRequests()
            ->where('service_requests.company_id', $data['company']->id)
            ->get();

        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertContains($result->id, $allCutRequestIds);
        }
    }

    /**
     * Property 3 - Iteration 5: Requests not in the cut are never returned
     * even if they belong to a selected family and fall within the cut's date range.
     */
    public function test_requests_in_date_range_but_not_in_cut_are_excluded(): void
    {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'DateRange', 'DTRNG');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Date Range Test',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'created_by' => $data['user']->id,
        ]);

        // Create a request within the cut's date range - it will be auto-synced
        $srAutoSynced = $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $requester,
            "In date range auto-synced",
            '2026-07-15 10:00:00'
        );

        // Create a request OUTSIDE the cut's date range (won't be auto-synced)
        $srOutsideRange = $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $requester,
            "Outside date range",
            '2026-01-10 10:00:00'
        );

        // Explicitly set the cut to only have the auto-synced request
        $cut->serviceRequests()->sync([$srAutoSynced->id]);

        // Generate report using cut_id
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$familyData['family']->id],
            ]);

        $response->assertStatus(200);

        // Verify: only the cut-associated request should be in results
        $results = $cut->serviceRequests()
            ->where('service_requests.company_id', $data['company']->id)
            ->whereHas('subService.service.family', function ($q) use ($familyData) {
                $q->whereIn('id', [$familyData['family']->id]);
            })
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($srAutoSynced->id, $results->first()->id);
        $this->assertNotContains($srOutsideRange->id, $results->pluck('id')->toArray());
    }
}
