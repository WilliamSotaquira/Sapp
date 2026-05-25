<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\CriticalityDetector;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use PHPUnit\Framework\TestCase;

class CriticalityDetectorTest extends TestCase
{
    private CriticalityDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CriticalityDetector();
    }

    // --- CRITICA level detection ---

    public function test_detects_critica_from_critico_keyword(): void
    {
        $context = $this->makeContext('Esto es un problema crítico que necesita atención');
        $result = $this->detector->extract($context);

        $this->assertEquals('criticality_level', $result->fieldName);
        $this->assertEquals('CRITICA', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_detects_critica_from_critical_keyword(): void
    {
        $context = $this->makeContext('This is a critical issue in production');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_detects_critica_from_emergencia_keyword(): void
    {
        $context = $this->makeContext('Tenemos una emergencia con el servidor');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    public function test_detects_critica_from_emergency_keyword(): void
    {
        $context = $this->makeContext('This is an emergency, the site is down');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    public function test_detects_critica_from_sistema_caido_keyword(): void
    {
        $context = $this->makeContext('El sistema caído desde las 3am');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    public function test_detects_critica_from_system_down_keyword(): void
    {
        $context = $this->makeContext('Alert: system down, please fix immediately');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    // --- URGENTE level detection ---

    public function test_detects_urgente_from_urgente_keyword(): void
    {
        $context = $this->makeContext('Necesito esto urgente por favor');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_detects_urgente_from_urgent_keyword(): void
    {
        $context = $this->makeContext('This is an urgent request');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_detects_urgente_from_inmediato_keyword(): void
    {
        $context = $this->makeContext('Requiere atención de forma inmediato');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_detects_urgente_from_immediate_keyword(): void
    {
        $context = $this->makeContext('We need immediate action on this');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_detects_urgente_from_lo_antes_posible_keyword(): void
    {
        $context = $this->makeContext('Por favor resolver lo antes posible');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_detects_urgente_from_asap_keyword(): void
    {
        $context = $this->makeContext('Please fix this ASAP');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    // --- ALTA level detection ---

    public function test_detects_alta_from_prioridad_alta_keyword(): void
    {
        $context = $this->makeContext('Este ticket tiene prioridad alta');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_detects_alta_from_high_priority_keyword(): void
    {
        $context = $this->makeContext('This is a high priority task');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    public function test_detects_alta_from_importante_keyword(): void
    {
        $context = $this->makeContext('Es importante que se resuelva esta semana');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    public function test_detects_alta_from_important_keyword(): void
    {
        $context = $this->makeContext('This is an important update needed');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    public function test_detects_alta_from_a_la_brevedad_keyword(): void
    {
        $context = $this->makeContext('Necesitamos esto a la brevedad');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    // --- BAJA level detection ---

    public function test_detects_baja_from_cuando_puedas_keyword(): void
    {
        $context = $this->makeContext('Cuando puedas revisa este tema');
        $result = $this->detector->extract($context);

        $this->assertEquals('BAJA', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_detects_baja_from_sin_prisa_keyword(): void
    {
        $context = $this->makeContext('Sin prisa, pero necesito un cambio en el footer');
        $result = $this->detector->extract($context);

        $this->assertEquals('BAJA', $result->value);
    }

    public function test_detects_baja_from_baja_prioridad_keyword(): void
    {
        $context = $this->makeContext('Esto es de baja prioridad');
        $result = $this->detector->extract($context);

        $this->assertEquals('BAJA', $result->value);
    }

    public function test_detects_baja_from_low_priority_keyword(): void
    {
        $context = $this->makeContext('This is a low priority request');
        $result = $this->detector->extract($context);

        $this->assertEquals('BAJA', $result->value);
    }

    public function test_detects_baja_from_no_urgente_keyword(): void
    {
        $context = $this->makeContext('Es no urgente, puede esperar');
        $result = $this->detector->extract($context);

        $this->assertEquals('BAJA', $result->value);
    }

    // --- MEDIA (default) level ---

    public function test_returns_media_when_no_keywords_found(): void
    {
        $context = $this->makeContext('Necesito actualizar el contenido de la página de contacto');
        $result = $this->detector->extract($context);

        $this->assertEquals('criticality_level', $result->fieldName);
        $this->assertEquals('MEDIA', $result->value);
        $this->assertEquals(50, $result->confidence);
    }

    public function test_returns_media_for_empty_text(): void
    {
        $context = $this->makeContext('');
        $result = $this->detector->extract($context);

        $this->assertEquals('MEDIA', $result->value);
        $this->assertEquals(50, $result->confidence);
    }

    // --- Case-insensitive detection ---

    public function test_detection_is_case_insensitive_uppercase(): void
    {
        $context = $this->makeContext('ESTO ES URGENTE');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_detection_is_case_insensitive_mixed_case(): void
    {
        $context = $this->makeContext('This is Critical for the business');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    public function test_detection_is_case_insensitive_high_priority(): void
    {
        $context = $this->makeContext('HIGH PRIORITY task for the team');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    // --- Multiple indicators: highest level wins ---

    public function test_selects_highest_level_when_multiple_indicators_present(): void
    {
        // Contains both URGENTE ("urgente") and ALTA ("importante")
        $context = $this->makeContext('Esto es urgente e importante para el negocio');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_critica_wins_over_all_other_levels(): void
    {
        // Contains CRITICA ("critical"), URGENTE ("urgent"), ALTA ("important"), BAJA ("low priority")
        $context = $this->makeContext('This is critical and urgent, important but not low priority');
        $result = $this->detector->extract($context);

        $this->assertEquals('CRITICA', $result->value);
    }

    public function test_urgente_wins_over_alta_and_baja(): void
    {
        // Contains URGENTE ("asap") and BAJA ("sin prisa") - contradictory but highest wins
        $context = $this->makeContext('Hazlo asap aunque sin prisa no hay problema');
        $result = $this->detector->extract($context);

        $this->assertEquals('URGENTE', $result->value);
    }

    public function test_alta_wins_over_baja(): void
    {
        // Contains ALTA ("importante") and BAJA ("cuando puedas")
        $context = $this->makeContext('Es importante, cuando puedas revisalo');
        $result = $this->detector->extract($context);

        $this->assertEquals('ALTA', $result->value);
    }

    // --- ExtractionResult structure ---

    public function test_result_has_correct_field_name(): void
    {
        $context = $this->makeContext('Texto normal sin indicadores');
        $result = $this->detector->extract($context);

        $this->assertInstanceOf(ExtractionResult::class, $result);
        $this->assertEquals('criticality_level', $result->fieldName);
        $this->assertTrue($result->extracted);
    }

    // --- Helper ---

    private function makeContext(string $text): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = 1;
        $context->contractId = 1;

        return $context;
    }
}
