<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Services\SmartParser\Extractors\SubServiceClassifier;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubServiceClassifierTest extends TestCase
{
    use RefreshDatabase;

    private SubServiceClassifier $classifier;

    private array $testData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new SubServiceClassifier();
        $this->testData = $this->createTestEnvironment();
    }

    // --- Matching by name ---

    public function test_matches_sub_service_by_exact_name(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($this->testData['subService']->id, $result->value['sub_service_id']);
        $this->assertEquals('Actualización de Contenidos en Portal Principal', $result->value['sub_service_name']);
    }

    public function test_matches_sub_service_by_partial_name(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($this->testData['subService']->id, $result->value['sub_service_id']);
    }

    public function test_matches_sub_service_case_insensitive(): void
    {
        $context = $this->makeContext('actualización de contenidos en portal principal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($this->testData['subService']->id, $result->value['sub_service_id']);
    }

    public function test_matches_sub_service_without_accents(): void
    {
        $context = $this->makeContext('Actualizacion de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($this->testData['subService']->id, $result->value['sub_service_id']);
    }

    // --- Resolves parent service and family ---

    public function test_resolves_service_id(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertEquals($this->testData['service']->id, $result->value['service_id']);
    }

    public function test_resolves_family_id(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertEquals($this->testData['family']->id, $result->value['family_id']);
    }

    // --- SLA resolution ---

    public function test_resolves_active_sla_id(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertEquals($this->testData['sla']->id, $result->value['sla_id']);
    }

    public function test_leaves_sla_null_when_no_active_sla(): void
    {
        // Deactivate the SLA
        $this->testData['sla']->update(['is_active' => false]);

        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($this->testData['subService']->id, $result->value['sub_service_id']);
        $this->assertNull($result->value['sla_id']);
    }

    // --- Threshold behavior ---

    public function test_returns_empty_when_similarity_below_threshold(): void
    {
        $context = $this->makeContext('xyz completamente diferente sin relacion alguna');

        $result = $this->classifier->extract($context);

        $this->assertFalse($result->extracted);
        $this->assertNull($result->value);
    }

    public function test_returns_empty_for_empty_text(): void
    {
        $context = $this->makeContext('');

        $result = $this->classifier->extract($context);

        $this->assertFalse($result->extracted);
        $this->assertNull($result->value);
    }

    // --- Multiple sub-services: picks highest score ---

    public function test_picks_sub_service_with_highest_similarity(): void
    {
        // Create a second sub-service
        $subService2 = SubService::create([
            'service_id' => $this->testData['service']->id,
            'name' => 'Publicación de Noticias',
            'code' => 'PUB_NOT',
            'description' => 'Publicación de noticias',
            'is_active' => true,
            'order' => 1,
        ]);

        ServiceSubservice::create([
            'service_family_id' => $this->testData['family']->id,
            'service_id' => $this->testData['service']->id,
            'sub_service_id' => $subService2->id,
            'name' => 'Publicación de noticias',
            'description' => 'Relación activa',
            'is_active' => true,
        ]);

        $context = $this->makeContext('Publicación de Noticias en el portal');

        $result = $this->classifier->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals($subService2->id, $result->value['sub_service_id']);
    }

    // --- Inactive sub-services are excluded ---

    public function test_does_not_match_inactive_sub_service(): void
    {
        // Deactivate the sub-service
        $this->testData['subService']->update(['is_active' => false]);

        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertFalse($result->extracted);
    }

    // --- No sub-services in contract ---

    public function test_returns_empty_when_no_sub_services_in_contract(): void
    {
        // Use a different contract ID that has no sub-services
        $context = $this->makeContext('Actualización de Contenidos');
        $context->contractId = 99999;

        $result = $this->classifier->extract($context);

        $this->assertFalse($result->extracted);
    }

    // --- Confidence levels ---

    public function test_high_confidence_for_exact_match(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertGreaterThanOrEqual(85, $result->confidence);
    }

    // --- Matching by service parent name ---

    public function test_matches_by_parent_service_name(): void
    {
        $context = $this->makeContext('Gestión de Contenidos y Actualizaciones Web');

        $result = $this->classifier->extract($context);

        // Should match because the parent service name is part of the search space
        $this->assertTrue($result->extracted);
    }

    // --- Field name ---

    public function test_field_name_is_sub_service(): void
    {
        $context = $this->makeContext('Actualización de Contenidos en Portal Principal');

        $result = $this->classifier->extract($context);

        $this->assertEquals('sub_service', $result->fieldName);
    }

    // --- Helper methods ---

    private function makeContext(string $text): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = $this->testData['company']->id;
        $context->contractId = $this->testData['contract']->id;

        return $context;
    }

    private function createTestEnvironment(): array
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'TEST-001',
            'name' => 'Contrato de prueba',
            'description' => 'Contrato activo para tests',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Gestión Integral de la Estrategia Digital',
            'code' => 'GIED_TEST',
            'description' => 'Familia digital',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Gestión de Contenidos y Actualizaciones Web',
            'code' => 'GCAW_TEST',
            'description' => 'Servicio web',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Actualización de Contenidos en Portal Principal',
            'code' => 'ACT_PORTAL_TEST',
            'description' => 'Actualización de contenidos',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $service->id,
            'sub_service_id' => $subService->id,
            'name' => 'Actualización de contenidos',
            'description' => 'Relación activa',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA Test',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 8,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
            'conditions' => null,
            'is_active' => true,
        ]);

        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            $sla->forceFill(['sub_service_id' => $subService->id])->save();
        }

        return compact('company', 'contract', 'family', 'service', 'subService', 'serviceSubservice', 'sla');
    }
}
