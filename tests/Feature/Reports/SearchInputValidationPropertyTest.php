<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 7: Search input validation
 *
 * For any input string split by commas, if the resulting array has more than 10 elements
 * OR any element (after trimming) exceeds 100 characters, the Search and Analysis report
 * SHALL reject the input with a validation error. If the array has 1-10 elements each with
 * 1-100 characters, the input SHALL be accepted.
 *
 * Additionally, requests with no terms AND no service type filters SHALL be rejected.
 *
 * **Validates: Requirements 6.2**
 */
class SearchInputValidationPropertyTest extends TestCase
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
            'number' => 'C-PROP7-001',
            'name' => 'Contrato Property 7',
            'description' => 'Contrato para property test',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Test Family',
            'code' => 'TF',
            'description' => 'Test family',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Test Service',
            'code' => 'TS',
            'description' => 'Test service',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Test SubService',
            'code' => 'TSS',
            'description' => 'Test sub-service',
            'is_active' => true,
            'order' => 0,
        ]);

        return compact('user', 'company', 'contract', 'family', 'service', 'subService');
    }

    /**
     * Property 7 - Iteration 1: Requests with more than 10 terms are rejected.
     *
     * @dataProvider tooManyTermsDataProvider
     */
    public function test_rejects_requests_with_more_than_10_terms(int $termCount): void
    {
        $data = $this->seedContext();

        // Generate a comma-separated string with $termCount terms
        $terms = implode(',', array_map(fn($i) => "term{$i}", range(1, $termCount)));

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'terms' => $terms,
            ]));

        $response->assertSessionHasErrors('terms');
    }

    public static function tooManyTermsDataProvider(): array
    {
        return [
            '11 terms' => [11],
            '12 terms' => [12],
            '15 terms' => [15],
            '20 terms' => [20],
            '50 terms' => [50],
        ];
    }

    /**
     * Property 7 - Iteration 2: Requests with terms longer than 100 characters are rejected.
     *
     * @dataProvider termTooLongDataProvider
     */
    public function test_rejects_requests_with_terms_longer_than_100_chars(int $termLength): void
    {
        $data = $this->seedContext();

        // Generate a single term that exceeds 100 characters
        $longTerm = str_repeat('a', $termLength);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'terms' => $longTerm,
            ]));

        $response->assertSessionHasErrors('terms');
    }

    public static function termTooLongDataProvider(): array
    {
        return [
            '101 chars' => [101],
            '150 chars' => [150],
            '200 chars' => [200],
            '500 chars' => [500],
            '1000 chars' => [1000],
        ];
    }

    /**
     * Property 7 - Iteration 3: Requests with no terms AND no service type filters are rejected.
     *
     * @dataProvider noInputDataProvider
     */
    public function test_rejects_requests_with_no_terms_and_no_service_filters(
        ?string $terms,
        ?array $families,
        ?array $services,
        ?array $subServices
    ): void {
        $data = $this->seedContext();

        $params = [];
        if ($terms !== null) {
            $params['terms'] = $terms;
        }
        if ($families !== null) {
            $params['families'] = $families;
        }
        if ($services !== null) {
            $params['services'] = $services;
        }
        if ($subServices !== null) {
            $params['sub_services'] = $subServices;
        }

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', $params));

        $response->assertSessionHasErrors('terms');
    }

    public static function noInputDataProvider(): array
    {
        return [
            'no params at all' => [null, null, null, null],
            'empty terms string' => ['', null, null, null],
            'whitespace only terms' => ['   ', null, null, null],
            'empty terms with empty arrays' => ['', [], [], []],
            'null terms with empty arrays' => [null, [], [], []],
        ];
    }

    /**
     * Property 7 - Iteration 4: Valid inputs with 1-10 terms each 1-100 chars are accepted.
     *
     * @dataProvider validTermsDataProvider
     */
    public function test_accepts_valid_inputs_with_1_to_10_terms(string $terms): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'terms' => $terms,
            ]));

        // Should NOT have validation errors on 'terms'
        $response->assertSessionDoesntHaveErrors('terms');
    }

    public static function validTermsDataProvider(): array
    {
        return [
            'single short term' => ['test'],
            'single term at 100 chars' => [str_repeat('x', 100)],
            'two terms' => ['alpha,beta'],
            'five terms' => ['one,two,three,four,five'],
            'ten terms' => ['a,b,c,d,e,f,g,h,i,j'],
            'terms with spaces' => [' hello , world '],
            'single char terms' => ['a,b,c'],
        ];
    }

    /**
     * Property 7 - Iteration 5: Valid input with service type filters only (no terms) is accepted.
     */
    public function test_accepts_requests_with_service_filters_only(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'families' => [$data['family']->id],
            ]));

        // Should NOT have validation errors - service filter alone is sufficient
        $response->assertSessionDoesntHaveErrors('terms');
    }

    /**
     * Property 7 - Iteration 6: Boundary case - exactly 10 terms at exactly 100 chars each is accepted.
     */
    public function test_accepts_boundary_case_10_terms_at_100_chars_each(): void
    {
        $data = $this->seedContext();

        // Generate exactly 10 terms, each exactly 100 characters
        $terms = implode(',', array_fill(0, 10, str_repeat('z', 100)));

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'terms' => $terms,
            ]));

        $response->assertSessionDoesntHaveErrors('terms');
    }

    /**
     * Property 7 - Iteration 7: Mixed invalid - some terms valid, one exceeds 100 chars.
     *
     * @dataProvider mixedInvalidTermsDataProvider
     */
    public function test_rejects_when_any_single_term_exceeds_100_chars(string $terms): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', [
                'terms' => $terms,
            ]));

        $response->assertSessionHasErrors('terms');
    }

    public static function mixedInvalidTermsDataProvider(): array
    {
        $longTerm = str_repeat('b', 101);

        return [
            'first term too long' => [$longTerm . ',valid'],
            'last term too long' => ['valid,' . $longTerm],
            'middle term too long' => ['valid,' . $longTerm . ',also_valid'],
            'one valid and one long among many' => ['a,b,c,' . $longTerm . ',e'],
        ];
    }
}
