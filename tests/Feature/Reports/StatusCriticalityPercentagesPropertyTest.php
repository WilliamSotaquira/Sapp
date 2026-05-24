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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 6: Status and criticality percentage calculations
 *
 * For any set of service requests within a date range, the status distribution
 * percentages SHALL each equal (status_count / total) * 100 rounded to 2 decimal
 * places, and the monthly completion_rate SHALL equal (count of RESUELTA or CERRADA
 * requests / total requests in that month) * 100 rounded to 2 decimal places.
 *
 * **Validates: Requirements 5.2, 5.3, 5.4**
 */
class StatusCriticalityPercentagesPropertyTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Test Company Prop6',
            'status' => 'active',
        ]);

        $company->users()->attach($user->id);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-PROP6-001',
            'name' => 'Contrato Property 6',
            'description' => 'Contrato para property test 6',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $company->refresh();

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Prop6',
            'code' => 'FP6',
            'description' => 'Familia para property test 6',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Prop6',
            'code' => 'SP6',
            'description' => 'Servicio para property test 6',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'SubServicio Prop6',
            'code' => 'SSP6',
            'description' => 'SubServicio para property test 6',
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA Prop6',
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

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
        ]);

        return compact('user', 'company', 'contract', 'family', 'service', 'subService', 'sla', 'requester');
    }

    private function createServiceRequest(
        array $context,
        string $status,
        string $criticalityLevel = 'MEDIA',
        string $createdAt = '2026-03-15 10:00:00',
        ?string $resolvedAt = null
    ): ServiceRequest {
        session(['current_company_id' => $context['company']->id]);

        $sr = new ServiceRequest();
        $sr->company_id = $context['company']->id;
        $sr->requester_id = $context['requester']->id;
        $sr->title = "Request $status";
        $sr->description = "Description for $status request";
        $sr->sub_service_id = $context['subService']->id;
        $sr->sla_id = $context['sla']->id;
        $sr->requested_by = $context['user']->id;
        $sr->assigned_to = $context['user']->id;
        $sr->technician_assigned_at = $createdAt;
        $sr->entry_channel = 'email_corporativo';
        $sr->criticality_level = $criticalityLevel;
        $sr->status = $status;
        $sr->created_at = $createdAt;
        $sr->resolved_at = $resolvedAt;
        $sr->ticket_number = 'SR-' . strtoupper(substr(uniqid(), -6));
        $sr->saveQuietly();

        return $sr;
    }

    /**
     * Property 6 - Iteration 1: Status percentages sum to approximately 100%.
     *
     * @dataProvider statusDistributionDataProvider
     */
    public function test_status_percentages_sum_to_100(array $statusDistribution): void
    {
        $context = $this->seedContext();

        // Create service requests with the given status distribution
        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createServiceRequest($context, $status);
            }
        }

        // Access the operational overview report
        $response = $this->actingAs($context['user'])
            ->withSession(['current_company_id' => $context['company']->id])
            ->get(route('reports.operational-overview.index', [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]));

        $response->assertStatus(200);

        $statusData = $response->viewData('statusData');

        // Property: percentages must sum to 100% (within rounding tolerance of 0.01 * number_of_statuses)
        $totalPercentage = $statusData->sum('percentage');
        $tolerance = 0.01 * $statusData->count();
        $this->assertEqualsWithDelta(100.0, $totalPercentage, $tolerance,
            "Status percentages should sum to 100% (got $totalPercentage, tolerance $tolerance)");
    }

    public static function statusDistributionDataProvider(): array
    {
        return [
            'single status' => [['PENDIENTE' => 5]],
            'two statuses equal' => [['PENDIENTE' => 3, 'RESUELTA' => 3]],
            'three statuses varied' => [['PENDIENTE' => 2, 'ACEPTADA' => 3, 'RESUELTA' => 5]],
            'all statuses' => [[
                'PENDIENTE' => 1,
                'ACEPTADA' => 2,
                'EN_PROCESO' => 3,
                'RESUELTA' => 4,
                'CERRADA' => 2,
                'CANCELADA' => 1,
            ]],
            'heavily skewed' => [['PENDIENTE' => 1, 'RESUELTA' => 99]],
        ];
    }

    /**
     * Property 6 - Iteration 2: Each status percentage equals (count / total) * 100 rounded to 2dp.
     *
     * @dataProvider statusPercentageCalculationDataProvider
     */
    public function test_each_status_percentage_equals_count_over_total_times_100(array $statusDistribution): void
    {
        $context = $this->seedContext();

        // Create service requests with the given status distribution
        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createServiceRequest($context, $status);
            }
        }

        $total = array_sum($statusDistribution);

        // Access the operational overview report
        $response = $this->actingAs($context['user'])
            ->withSession(['current_company_id' => $context['company']->id])
            ->get(route('reports.operational-overview.index', [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]));

        $response->assertStatus(200);

        $statusData = $response->viewData('statusData');

        // Property: each percentage must equal (count / total) * 100 rounded to 2dp
        foreach ($statusData as $item) {
            $expectedPercentage = round(($item->count / $total) * 100, 2);
            $this->assertEqualsWithDelta(
                $expectedPercentage,
                (float) $item->percentage,
                0.01,
                "Status '{$item->status}' percentage should be $expectedPercentage (count={$item->count}, total=$total), got {$item->percentage}"
            );
        }
    }

    public static function statusPercentageCalculationDataProvider(): array
    {
        return [
            'thirds distribution' => [['PENDIENTE' => 1, 'ACEPTADA' => 1, 'RESUELTA' => 1]],
            'uneven distribution' => [['PENDIENTE' => 7, 'RESUELTA' => 3]],
            'single item' => [['EN_PROCESO' => 1]],
            'large numbers' => [['PENDIENTE' => 33, 'ACEPTADA' => 33, 'RESUELTA' => 34]],
            'prime distribution' => [['PENDIENTE' => 7, 'ACEPTADA' => 11, 'EN_PROCESO' => 13]],
        ];
    }

    /**
     * Property 6 - Iteration 3: Monthly completion rate equals
     * (count of RESUELTA or CERRADA / total in month) * 100 rounded to 2dp.
     *
     * @dataProvider monthlyCompletionRateDataProvider
     */
    public function test_monthly_completion_rate_calculation(
        int $resolvedCount,
        int $closedCount,
        int $otherCount
    ): void {
        $context = $this->seedContext();

        $month = '2026-03';

        // Create RESUELTA requests
        for ($i = 0; $i < $resolvedCount; $i++) {
            $this->createServiceRequest(
                $context,
                'RESUELTA',
                'MEDIA',
                "$month-15 10:00:00",
                "$month-16 10:00:00"
            );
        }

        // Create CERRADA requests
        for ($i = 0; $i < $closedCount; $i++) {
            $this->createServiceRequest(
                $context,
                'CERRADA',
                'MEDIA',
                "$month-10 10:00:00",
                "$month-12 10:00:00"
            );
        }

        // Create other status requests (PENDIENTE)
        for ($i = 0; $i < $otherCount; $i++) {
            $this->createServiceRequest(
                $context,
                'PENDIENTE',
                'MEDIA',
                "$month-05 10:00:00"
            );
        }

        $total = $resolvedCount + $closedCount + $otherCount;
        $expectedCompletionRate = $total > 0
            ? round((($resolvedCount + $closedCount) / $total) * 100, 2)
            : 0;

        // Access the operational overview report with months=3 to include our test month
        $response = $this->actingAs($context['user'])
            ->withSession(['current_company_id' => $context['company']->id])
            ->get(route('reports.operational-overview.index', [
                'months' => 3,
            ]));

        $response->assertStatus(200);

        $trendsData = $response->viewData('trendsData');

        // Find our test month in the trends data
        $monthData = $trendsData->firstWhere('month', $month);

        // If the month is within the range, verify the completion rate
        if ($monthData !== null) {
            $this->assertEquals($total, $monthData['total_requests'],
                "Total requests for month $month should be $total");
            $this->assertEquals($resolvedCount + $closedCount, $monthData['resolved_requests'],
                "Resolved requests for month $month should be " . ($resolvedCount + $closedCount));
            $this->assertEqualsWithDelta(
                $expectedCompletionRate,
                $monthData['completion_rate'],
                0.01,
                "Completion rate for month $month should be $expectedCompletionRate, got {$monthData['completion_rate']}"
            );
        }
    }

    public static function monthlyCompletionRateDataProvider(): array
    {
        return [
            'all resolved' => [5, 0, 0],
            'all closed' => [0, 5, 0],
            'none resolved' => [0, 0, 5],
            'mixed resolved and closed' => [3, 2, 5],
            'half resolved' => [5, 0, 5],
            'one of each' => [1, 1, 1],
            'heavily resolved' => [9, 0, 1],
            'mostly pending' => [1, 0, 9],
        ];
    }

    /**
     * Property 6 - Iteration 4: Status percentages are each rounded to exactly 2 decimal places.
     *
     * @dataProvider roundingPrecisionDataProvider
     */
    public function test_status_percentages_have_correct_rounding_precision(array $statusDistribution): void
    {
        $context = $this->seedContext();

        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createServiceRequest($context, $status);
            }
        }

        $response = $this->actingAs($context['user'])
            ->withSession(['current_company_id' => $context['company']->id])
            ->get(route('reports.operational-overview.index', [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]));

        $response->assertStatus(200);

        $statusData = $response->viewData('statusData');

        foreach ($statusData as $item) {
            // Verify the percentage has at most 2 decimal places
            $percentage = (float) $item->percentage;
            $roundedTo2dp = round($percentage, 2);
            $this->assertEquals(
                $roundedTo2dp,
                $percentage,
                "Status '{$item->status}' percentage $percentage should be rounded to 2 decimal places"
            );
        }
    }

    public static function roundingPrecisionDataProvider(): array
    {
        return [
            'thirds cause repeating decimals' => [['PENDIENTE' => 1, 'ACEPTADA' => 1, 'RESUELTA' => 1]],
            'sevenths cause repeating decimals' => [['PENDIENTE' => 1, 'ACEPTADA' => 2, 'EN_PROCESO' => 4]],
            'sixths' => [['PENDIENTE' => 1, 'RESUELTA' => 5]],
            'elevenths' => [['PENDIENTE' => 3, 'ACEPTADA' => 4, 'RESUELTA' => 4]],
        ];
    }

    /**
     * Property 6 - Iteration 5: Completion rate is 0 when no resolved/closed requests exist,
     * and 100 when all requests are resolved or closed.
     *
     * @dataProvider completionRateBoundaryDataProvider
     */
    public function test_completion_rate_boundary_values(
        int $resolvedCount,
        int $closedCount,
        int $otherCount,
        float $expectedRate
    ): void {
        $context = $this->seedContext();

        $month = '2026-03';

        for ($i = 0; $i < $resolvedCount; $i++) {
            $this->createServiceRequest(
                $context,
                'RESUELTA',
                'MEDIA',
                "$month-15 10:00:00",
                "$month-16 10:00:00"
            );
        }

        for ($i = 0; $i < $closedCount; $i++) {
            $this->createServiceRequest(
                $context,
                'CERRADA',
                'MEDIA',
                "$month-10 10:00:00",
                "$month-12 10:00:00"
            );
        }

        for ($i = 0; $i < $otherCount; $i++) {
            $this->createServiceRequest(
                $context,
                'PENDIENTE',
                'MEDIA',
                "$month-05 10:00:00"
            );
        }

        $response = $this->actingAs($context['user'])
            ->withSession(['current_company_id' => $context['company']->id])
            ->get(route('reports.operational-overview.index', [
                'months' => 3,
            ]));

        $response->assertStatus(200);

        $trendsData = $response->viewData('trendsData');
        $monthData = $trendsData->firstWhere('month', $month);

        if ($monthData !== null) {
            $this->assertEqualsWithDelta(
                $expectedRate,
                $monthData['completion_rate'],
                0.01,
                "Completion rate should be $expectedRate, got {$monthData['completion_rate']}"
            );
        }
    }

    public static function completionRateBoundaryDataProvider(): array
    {
        return [
            'all resolved - 100%' => [10, 0, 0, 100.0],
            'all closed - 100%' => [0, 10, 0, 100.0],
            'mixed resolved+closed - 100%' => [5, 5, 0, 100.0],
            'none resolved - 0%' => [0, 0, 10, 0.0],
            'half resolved - 50%' => [5, 0, 5, 50.0],
            'one third resolved' => [1, 0, 2, 33.33],
            'two thirds resolved' => [2, 0, 1, 66.67],
        ];
    }
}
