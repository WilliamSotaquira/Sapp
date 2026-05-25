<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\RequesterExtractor;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use PHPUnit\Framework\TestCase;

class RequesterExtractorTest extends TestCase
{
    private RequesterExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new RequesterExtractor();
    }

    // --- Email extraction tests ---

    public function test_extracts_name_and_email_from_de_header_with_angle_brackets(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez <juan.perez@example.com>\nPara: soporte@empresa.com\nAsunto: Problema\n\nEl servidor no responde.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('requester', $result->fieldName);
        $this->assertTrue($result->extracted);
        $this->assertEquals('Juan Pérez', $result->value['name']);
        $this->assertEquals('juan.perez@example.com', $result->value['email']);
        $this->assertGreaterThanOrEqual(90, $result->confidence);
    }

    public function test_extracts_name_and_email_from_from_header(): void
    {
        $context = $this->makeContext(
            "From: John Doe <john@example.com>\nTo: support@company.com\nSubject: Issue\n\nServer is down.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('John Doe', $result->value['name']);
        $this->assertEquals('john@example.com', $result->value['email']);
        $this->assertGreaterThanOrEqual(90, $result->confidence);
    }

    public function test_extracts_name_from_de_header_without_email(): void
    {
        $context = $this->makeContext(
            "De: María García López\nPara: soporte@empresa.com\nAsunto: Solicitud\n\nNecesito acceso.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('María García López', $result->value['name']);
        $this->assertNull($result->value['email']);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_extracts_name_from_email_only_in_de_header(): void
    {
        $context = $this->makeContext(
            "De: carlos.martinez@empresa.com\nPara: soporte@empresa.com\nAsunto: Ayuda\n\nNecesito ayuda.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('Carlos Martinez', $result->value['name']);
        $this->assertEquals('carlos.martinez@empresa.com', $result->value['email']);
    }

    public function test_extracts_name_from_quoted_de_header(): void
    {
        $context = $this->makeContext(
            "De: \"Ana María Ruiz\" <ana.ruiz@empresa.com>\nPara: soporte@empresa.com\nAsunto: Consulta\n\nTengo una duda.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('Ana María Ruiz', $result->value['name']);
        $this->assertEquals('ana.ruiz@empresa.com', $result->value['email']);
    }

    public function test_returns_empty_when_no_de_header_in_email(): void
    {
        $context = $this->makeContext(
            "Para: soporte@empresa.com\nAsunto: Problema\n\nEl servidor no responde.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertFalse($result->extracted);
        $this->assertEquals(0, $result->confidence);
    }

    public function test_stores_email_headers_in_context(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez <juan@example.com>\nPara: soporte@empresa.com\nAsunto: Problema con servidor\nFecha: 15/05/2025\n\nEl servidor no responde.",
            'email_corporativo'
        );

        $this->extractor->extract($context);

        $this->assertArrayHasKey('de', $context->emailHeaders);
        $this->assertArrayHasKey('para', $context->emailHeaders);
        $this->assertArrayHasKey('asunto', $context->emailHeaders);
        $this->assertArrayHasKey('fecha', $context->emailHeaders);
        $this->assertStringContainsString('Juan Pérez', $context->emailHeaders['de']);
    }

    // --- WhatsApp extraction tests ---

    public function test_extracts_contact_from_whatsapp_bracket_format(): void
    {
        $context = $this->makeContext(
            "[15/05/2025, 10:30] Juan Pérez: Hola, necesito ayuda con el servidor\n[15/05/2025, 10:31] Juan Pérez: No puedo acceder desde las 9am",
            'whatsapp'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('requester', $result->fieldName);
        $this->assertTrue($result->extracted);
        $this->assertEquals('Juan Pérez', $result->value['name']);
        $this->assertNull($result->value['email']);
        $this->assertGreaterThanOrEqual(80, $result->confidence);
    }

    public function test_extracts_contact_from_whatsapp_dash_format(): void
    {
        $context = $this->makeContext(
            "15/05/2025 10:30 - María López: Buenos días, tengo un problema\n15/05/2025 10:31 - María López: La página no carga",
            'whatsapp'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('María López', $result->value['name']);
        $this->assertNull($result->value['email']);
    }

    public function test_extracts_contact_from_whatsapp_short_year_format(): void
    {
        $context = $this->makeContext(
            "15/05/25, 10:30 - Carlos Ruiz: Necesito que revisen el formulario",
            'whatsapp'
        );

        $result = $this->extractor->extract($context);

        $this->assertEquals('Carlos Ruiz', $result->value['name']);
    }

    public function test_stores_whatsapp_messages_in_context(): void
    {
        $context = $this->makeContext(
            "[15/05/2025, 10:30] Ana García: Primer mensaje\n[15/05/2025, 10:31] Ana García: Segundo mensaje",
            'whatsapp'
        );

        $this->extractor->extract($context);

        $this->assertCount(2, $context->whatsappMessages);
        $this->assertEquals('Ana García', $context->whatsappMessages[0]['contact']);
        $this->assertEquals('Primer mensaje', $context->whatsappMessages[0]['message']);
    }

    public function test_returns_empty_when_no_whatsapp_pattern_matches(): void
    {
        $context = $this->makeContext(
            "Hola, necesito ayuda con el sistema de reportes.",
            'whatsapp'
        );

        $result = $this->extractor->extract($context);

        $this->assertFalse($result->extracted);
        $this->assertEquals(0, $result->confidence);
    }

    // --- Heuristic extraction tests ---

    public function test_heuristic_extracts_from_de_header_in_unknown_channel(): void
    {
        $context = $this->makeContext(
            "De: Roberto Sánchez\n\nNecesito que revisen la página principal.",
            null
        );

        $result = $this->extractor->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals('Roberto Sánchez', $result->value['name']);
        $this->assertEquals(60, $result->confidence);
    }

    public function test_heuristic_extracts_name_like_pattern_from_first_lines(): void
    {
        $context = $this->makeContext(
            "Pedro Martínez\nNecesito que revisen el formulario de contacto.",
            null
        );

        $result = $this->extractor->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals('Pedro Martínez', $result->value['name']);
        $this->assertEquals(40, $result->confidence);
    }

    public function test_heuristic_skips_greetings(): void
    {
        $context = $this->makeContext(
            "Hola equipo\nLaura Fernández\nNecesito ayuda con el sistema.",
            null
        );

        $result = $this->extractor->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals('Laura Fernández', $result->value['name']);
    }

    public function test_heuristic_returns_empty_when_no_name_found(): void
    {
        $context = $this->makeContext(
            "necesito ayuda con el sistema de reportes, no puedo generar el informe mensual.",
            null
        );

        $result = $this->extractor->extract($context);

        $this->assertFalse($result->extracted);
        $this->assertEquals(0, $result->confidence);
    }

    public function test_heuristic_returns_empty_for_empty_text(): void
    {
        $context = $this->makeContext('', null);

        $result = $this->extractor->extract($context);

        $this->assertFalse($result->extracted);
    }

    // --- Edge cases ---

    public function test_handles_de_header_with_only_angle_bracket_email(): void
    {
        $context = $this->makeContext(
            "De: <info@empresa.com>\nPara: soporte@empresa.com\nAsunto: Test\n\nContenido.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertTrue($result->extracted);
        $this->assertEquals('info@empresa.com', $result->value['email']);
        $this->assertEquals('Info', $result->value['name']);
    }

    public function test_trims_name_to_255_characters(): void
    {
        $longName = str_repeat('A', 300);
        $context = $this->makeContext(
            "De: {$longName} <test@example.com>\nPara: soporte@empresa.com\nAsunto: Test\n\nContenido.",
            'email_corporativo'
        );

        $result = $this->extractor->extract($context);

        $this->assertLessThanOrEqual(255, mb_strlen($result->value['name']));
    }

    public function test_whatsapp_handles_phone_number_as_contact(): void
    {
        $context = $this->makeContext(
            "[15/05/2025, 10:30] +52 55 1234 5678: Hola, necesito ayuda",
            'whatsapp'
        );

        $result = $this->extractor->extract($context);

        $this->assertTrue($result->extracted);
        // Phone numbers are still returned as identifiers
        $this->assertNotEmpty($result->value['name']);
    }

    // --- Helper ---

    private function makeContext(string $text, ?string $channel): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = 1;
        $context->contractId = 1;
        $context->detectedChannel = $channel;

        return $context;
    }
}
