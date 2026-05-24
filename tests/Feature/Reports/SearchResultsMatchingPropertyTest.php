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
 * Property 8: Search results contain matching terms
 *
 * For any search term and any service request returned in the results, that service
 * request SHALL contain the search term (case-insensitive partial match) in at least
 * one of: title, description, resolution_notes, requester.name, requester.email, or
 * requester.department. Additionally, the summary total_matches SHALL equal the total
 * count of matching results.
 *
 * **Validates: Requirements 6.4, 6.6**
 */
class SearchResultsMatchingPropertyTest extends TestCase
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
            'number' => 'C-PROP8-001',
            'name' => 'Contrato Property 8',
            'description' => 'Contrato para property test 8',
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
        ?string $description = null,
        ?string $resolutionNotes = null,
        string $createdAt = '2026-03-15 10:00:00'
    ): ServiceRequest {
        session(['current_company_id' => $company->id]);

        return ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'requester_id' => $requester->id,
            'title' => $title,
            'description' => $description ?? "Descripción de $title",
            'resolution_notes' => $resolutionNotes,
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
     * Helper to check if a service request matches a search term in any searchable field.
     */
    private function serviceRequestMatchesTerm(ServiceRequest $sr, string $term): bool
    {
        $term = mb_strtolower($term);

        // Check direct fields
        if (str_contains(mb_strtolower($sr->title ?? ''), $term)) {
            return true;
        }
        if (str_contains(mb_strtolower($sr->description ?? ''), $term)) {
            return true;
        }
        if (str_contains(mb_strtolower($sr->resolution_notes ?? ''), $term)) {
            return true;
        }

        // Check requester fields
        $requester = $sr->requester;
        if ($requester) {
            if (str_contains(mb_strtolower($requester->name ?? ''), $term)) {
                return true;
            }
            if (str_contains(mb_strtolower($requester->email ?? ''), $term)) {
                return true;
            }
            if (str_contains(mb_strtolower($requester->department ?? ''), $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Property 8 - Iteration 1: Every result contains the search term in at least one searchable field.
     *
     * @dataProvider searchTermsInFieldsDataProvider
     */
    public function test_every_result_contains_search_term_in_at_least_one_field(
        string $fieldToMatch,
        string $searchTerm
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'SearchTest', 'SRCH');

        // Create a requester with specific fields for matching
        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => 'Carlos Mendoza',
            'email' => 'carlos.mendoza@empresa.com',
            'department' => 'Tecnología',
        ]);

        // Create a service request with the term in the specified field
        $matchingData = [
            'title' => 'Solicitud de soporte técnico',
            'description' => 'Se requiere mantenimiento preventivo del servidor',
            'resolution_notes' => 'Se realizó actualización del firmware',
        ];

        $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $requester,
            $matchingData['title'],
            $matchingData['description'],
            $matchingData['resolution_notes']
        );

        // Create a non-matching service request
        $otherRequester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => 'Ana López',
            'email' => 'ana.lopez@otro.com',
            'department' => 'Finanzas',
        ]);

        $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $otherRequester,
            'Solicitud diferente sin relación',
            'Descripción que no coincide con nada buscado',
            null
        );

        // Perform search
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => $searchTerm]));

        $response->assertStatus(200);

        // Get results from view data
        $results = $response->viewData('results');
        $summary = $response->viewData('summary');

        // Property: every result must contain the search term in at least one searchable field
        foreach ($results as $result) {
            $result->load('requester');
            $this->assertTrue(
                $this->serviceRequestMatchesTerm($result, $searchTerm),
                "Service request '{$result->title}' (ID: {$result->id}) does not contain term '{$searchTerm}' in any searchable field"
            );
        }

        // Property: summary total_matches equals the total count of matching results
        $this->assertEquals(
            $summary['total_matches'],
            $results->total(),
            "Summary total_matches ({$summary['total_matches']}) should equal paginator total ({$results->total()})"
        );
    }

    public static function searchTermsInFieldsDataProvider(): array
    {
        return [
            'match in title' => ['title', 'soporte'],
            'match in description' => ['description', 'mantenimiento'],
            'match in resolution_notes' => ['resolution_notes', 'firmware'],
            'match in requester name' => ['requester.name', 'Mendoza'],
            'match in requester email' => ['requester.email', 'carlos.mendoza'],
            'match in requester department' => ['requester.department', 'Tecnología'],
        ];
    }

    /**
     * Property 8 - Iteration 2: Case-insensitive matching works correctly.
     *
     * @dataProvider caseInsensitiveDataProvider
     */
    public function test_search_is_case_insensitive(
        string $titleContent,
        string $searchTerm
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'CaseTest', 'CASE');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
        ]);

        $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $requester,
            $titleContent,
            'Descripción genérica'
        );

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => $searchTerm]));

        $response->assertStatus(200);

        $results = $response->viewData('results');
        $summary = $response->viewData('summary');

        // Property: case-insensitive match should find the result
        $this->assertGreaterThanOrEqual(1, $results->total(),
            "Search for '{$searchTerm}' should find service request with title '{$titleContent}'");

        // Property: every result contains the term (case-insensitive)
        foreach ($results as $result) {
            $result->load('requester');
            $this->assertTrue(
                $this->serviceRequestMatchesTerm($result, $searchTerm),
                "Service request '{$result->title}' does not contain term '{$searchTerm}' (case-insensitive)"
            );
        }

        // Property: summary total_matches equals total count
        $this->assertEquals($summary['total_matches'], $results->total());
    }

    public static function caseInsensitiveDataProvider(): array
    {
        return [
            'uppercase search, lowercase content' => ['error en servidor', 'ERROR'],
            'lowercase search, uppercase content' => ['FALLA CRÍTICA', 'falla'],
            'mixed case search' => ['Mantenimiento Preventivo', 'mAnTeNiMiEnTo'],
            'partial match lowercase' => ['Actualización del Sistema', 'sistema'],
            'partial match uppercase' => ['revisión de seguridad', 'SEGURIDAD'],
        ];
    }

    /**
     * Property 8 - Iteration 3: Non-matching requests are excluded from results.
     *
     * @dataProvider nonMatchingDataProvider
     */
    public function test_non_matching_requests_are_excluded(
        string $searchTerm,
        int $matchingCount,
        int $nonMatchingCount
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'ExcludeTest', 'EXCL');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'department' => 'General',
        ]);

        // Create matching requests (term in title)
        for ($i = 0; $i < $matchingCount; $i++) {
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Solicitud con {$searchTerm} número $i",
                'Descripción sin coincidencia'
            );
        }

        // Create non-matching requests
        for ($i = 0; $i < $nonMatchingCount; $i++) {
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Solicitud completamente diferente $i",
                "Descripción sin relación alguna $i"
            );
        }

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => $searchTerm]));

        $response->assertStatus(200);

        $results = $response->viewData('results');
        $summary = $response->viewData('summary');

        // Property: result count should equal matching count (non-matching excluded)
        $this->assertEquals($matchingCount, $results->total(),
            "Expected {$matchingCount} results for term '{$searchTerm}', got {$results->total()}");

        // Property: every result contains the search term
        foreach ($results as $result) {
            $result->load('requester');
            $this->assertTrue(
                $this->serviceRequestMatchesTerm($result, $searchTerm),
                "Service request '{$result->title}' should not be in results for term '{$searchTerm}'"
            );
        }

        // Property: summary total_matches equals the count
        $this->assertEquals($summary['total_matches'], $results->total());
    }

    public static function nonMatchingDataProvider(): array
    {
        return [
            'one matching, two non-matching' => ['impresora', 1, 2],
            'three matching, one non-matching' => ['servidor', 3, 1],
            'two matching, three non-matching' => ['red', 2, 3],
            'one matching, five non-matching' => ['backup', 1, 5],
            'four matching, two non-matching' => ['monitor', 4, 2],
        ];
    }

    /**
     * Property 8 - Iteration 4: Search matches across multiple fields simultaneously.
     * A request that matches in description but not title should still be returned.
     *
     * @dataProvider multiFieldMatchDataProvider
     */
    public function test_search_matches_across_all_searchable_fields(
        string $searchTerm,
        ?string $title,
        ?string $description,
        ?string $resolutionNotes,
        ?string $requesterName,
        ?string $requesterEmail,
        ?string $requesterDepartment,
        bool $shouldMatch
    ): void {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'MultiField', 'MFLD');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => $requesterName ?? 'Nombre Genérico',
            'email' => $requesterEmail ?? 'generico@test.com',
            'department' => $requesterDepartment ?? 'Departamento General',
        ]);

        $this->createServiceRequest(
            $data['company'],
            $data['user'],
            $familyData['subService'],
            $familyData['sla'],
            $requester,
            $title ?? 'Título sin coincidencia',
            $description ?? 'Descripción sin coincidencia',
            $resolutionNotes
        );

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => $searchTerm]));

        $response->assertStatus(200);

        $results = $response->viewData('results');
        $summary = $response->viewData('summary');

        if ($shouldMatch) {
            $this->assertGreaterThanOrEqual(1, $results->total(),
                "Search for '{$searchTerm}' should find at least one result");

            // Property: every result contains the term
            foreach ($results as $result) {
                $result->load('requester');
                $this->assertTrue(
                    $this->serviceRequestMatchesTerm($result, $searchTerm),
                    "Result '{$result->title}' does not contain '{$searchTerm}' in any field"
                );
            }
        } else {
            $this->assertEquals(0, $results->total(),
                "Search for '{$searchTerm}' should find no results");
        }

        // Property: summary total_matches always equals total count
        $this->assertEquals($summary['total_matches'], $results->total());
    }

    public static function multiFieldMatchDataProvider(): array
    {
        return [
            'match only in title' => [
                'infraestructura',
                'Problema de infraestructura',
                'Sin coincidencia aquí',
                null,
                'Juan Pérez',
                'juan@test.com',
                'Ventas',
                true,
            ],
            'match only in description' => [
                'virtualización',
                'Título genérico',
                'Se necesita virtualización del entorno',
                null,
                'María García',
                'maria@test.com',
                'Compras',
                true,
            ],
            'match only in resolution_notes' => [
                'parche',
                'Título genérico',
                'Descripción genérica',
                'Se aplicó parche de seguridad',
                'Pedro Ruiz',
                'pedro@test.com',
                'Logística',
                true,
            ],
            'match only in requester name' => [
                'Fernández',
                'Título genérico',
                'Descripción genérica',
                null,
                'Roberto Fernández',
                'roberto@test.com',
                'Operaciones',
                true,
            ],
            'match only in requester email' => [
                'soporte.tecnico',
                'Título genérico',
                'Descripción genérica',
                null,
                'Laura Díaz',
                'soporte.tecnico@empresa.com',
                'Administración',
                true,
            ],
            'match only in requester department' => [
                'Recursos Humanos',
                'Título genérico',
                'Descripción genérica',
                null,
                'Diego Morales',
                'diego@test.com',
                'Recursos Humanos',
                true,
            ],
            'no match in any field' => [
                'xyznonexistent',
                'Título genérico',
                'Descripción genérica',
                null,
                'Nombre Normal',
                'normal@test.com',
                'Departamento Normal',
                false,
            ],
        ];
    }

    /**
     * Property 8 - Iteration 5: Summary total_matches equals the total count of all
     * matching results (not just the current page).
     */
    public function test_summary_total_matches_equals_total_result_count(): void
    {
        $data = $this->seedContext();
        $familyData = $this->createFamily($data['contract'], 'SummaryTest', 'SUMM');

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => 'Test Requester',
            'email' => 'test@example.com',
            'department' => 'IT',
        ]);

        $searchTerm = 'conectividad';

        // Create multiple matching requests
        for ($i = 0; $i < 7; $i++) {
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Problema de conectividad caso $i",
                "Falla de conectividad en el piso $i"
            );
        }

        // Create non-matching requests
        for ($i = 0; $i < 3; $i++) {
            $this->createServiceRequest(
                $data['company'],
                $data['user'],
                $familyData['subService'],
                $familyData['sla'],
                $requester,
                "Solicitud de papelería $i",
                "Necesito suministros de oficina $i"
            );
        }

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => $searchTerm]));

        $response->assertStatus(200);

        $results = $response->viewData('results');
        $summary = $response->viewData('summary');

        // Property: summary total_matches equals the total count of matching results
        $this->assertEquals(7, $summary['total_matches'],
            "Summary total_matches should be 7 (all matching requests)");
        $this->assertEquals($summary['total_matches'], $results->total(),
            "Summary total_matches should equal paginator total");

        // Property: every result on the page contains the search term
        foreach ($results as $result) {
            $result->load('requester');
            $this->assertTrue(
                $this->serviceRequestMatchesTerm($result, $searchTerm),
                "Result '{$result->title}' does not contain '{$searchTerm}'"
            );
        }
    }
}
