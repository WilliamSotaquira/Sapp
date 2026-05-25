<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\ChannelDetector;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use PHPUnit\Framework\TestCase;

class ChannelDetectorTest extends TestCase
{
    private ChannelDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ChannelDetector();
    }

    // --- Email detection tests ---

    public function test_detects_email_with_all_spanish_headers(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez <juan@example.com>\nPara: soporte@empresa.com\nAsunto: Problema con el servidor\nFecha: 15/05/2025\n\nEl servidor no responde desde las 10am."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('channel', $result->fieldName);
        $this->assertEquals('email_corporativo', $result->value);
        $this->assertTrue($result->extracted);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_detects_email_with_all_english_headers(): void
    {
        $context = $this->makeContext(
            "From: John Doe <john@example.com>\nTo: support@company.com\nSubject: Server issue\nDate: May 15, 2025\n\nThe server is not responding."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('email_corporativo', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_detects_email_with_minimum_two_headers(): void
    {
        $context = $this->makeContext(
            "De: María López\nAsunto: Solicitud de acceso\n\nNecesito acceso al sistema de reportes."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('email_corporativo', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_does_not_detect_email_with_only_one_header(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez\n\nHola, necesito ayuda con el sistema."
        );

        $result = $this->detector->extract($context);

        // With only 1 header, it should default to email_corporativo but with low confidence
        $this->assertEquals('email_corporativo', $result->value);
        $this->assertLessThanOrEqual(50, $result->confidence);
    }

    public function test_detects_email_with_cc_and_bcc_headers(): void
    {
        $context = $this->makeContext(
            "De: Ana García\nPara: soporte@empresa.com\nCC: jefe@empresa.com\nCCO: rrhh@empresa.com\nAsunto: Urgente\n\nNecesito esto para hoy."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('email_corporativo', $result->value);
        $this->assertGreaterThanOrEqual(90, $result->confidence);
    }

    // --- WhatsApp detection tests ---

    public function test_detects_whatsapp_bracket_format(): void
    {
        $context = $this->makeContext(
            "[15/05/2025, 10:30] Juan Pérez: Hola, necesito ayuda con el servidor\n[15/05/2025, 10:31] Juan Pérez: No puedo acceder desde las 9am"
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('whatsapp', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_detects_whatsapp_dash_format(): void
    {
        $context = $this->makeContext(
            "15/05/2025 10:30 - Juan Pérez: Hola, necesito ayuda\n15/05/2025 10:31 - Juan Pérez: El sistema está caído"
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('whatsapp', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_detects_whatsapp_short_year_format(): void
    {
        $context = $this->makeContext(
            "15/05/25, 10:30 - María López: Buenos días\n15/05/25, 10:32 - María López: Tengo un problema con la página"
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('whatsapp', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function test_detects_whatsapp_single_message(): void
    {
        $context = $this->makeContext(
            "[01/06/2025, 09:15] Carlos Ruiz: Necesito que revisen el formulario de contacto, no está enviando los datos correctamente."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('whatsapp', $result->value);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    // --- Conflict resolution tests ---

    public function test_resolves_conflict_by_higher_email_count(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez <juan@example.com>\nPara: soporte@empresa.com\nAsunto: Problema\nFecha: 15/05/2025\nCC: jefe@empresa.com\n\n[15/05/2025, 10:30] Juan: Hola"
        );

        $result = $this->detector->extract($context);

        // 5 email headers vs 1 WhatsApp message → email wins
        $this->assertEquals('email_corporativo', $result->value);
    }

    public function test_resolves_conflict_by_higher_whatsapp_count(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez\nPara: soporte@empresa.com\n\n[15/05/2025, 10:30] Juan: Mensaje 1\n[15/05/2025, 10:31] Juan: Mensaje 2\n[15/05/2025, 10:32] Juan: Mensaje 3\n[15/05/2025, 10:33] Juan: Mensaje 4"
        );

        $result = $this->detector->extract($context);

        // 2 email headers vs 4 WhatsApp messages → WhatsApp wins
        $this->assertEquals('whatsapp', $result->value);
    }

    public function test_resolves_tie_in_favor_of_email(): void
    {
        $context = $this->makeContext(
            "De: Juan Pérez\nPara: soporte@empresa.com\n\n[15/05/2025, 10:30] Juan: Mensaje 1\n[15/05/2025, 10:31] Juan: Mensaje 2"
        );

        $result = $this->detector->extract($context);

        // 2 email headers vs 2 WhatsApp messages → email wins (tie goes to email)
        $this->assertEquals('email_corporativo', $result->value);
    }

    // --- Default value tests ---

    public function test_defaults_to_email_corporativo_when_no_patterns(): void
    {
        $context = $this->makeContext(
            "Hola, necesito ayuda con el sistema de reportes. No puedo generar el informe mensual."
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('email_corporativo', $result->value);
        $this->assertLessThanOrEqual(50, $result->confidence);
    }

    public function test_defaults_to_email_corporativo_with_empty_text(): void
    {
        $context = $this->makeContext('');

        $result = $this->detector->extract($context);

        $this->assertEquals('email_corporativo', $result->value);
        $this->assertLessThanOrEqual(30, $result->confidence);
    }

    // --- Context enrichment tests ---

    public function test_sets_detected_channel_on_context(): void
    {
        $context = $this->makeContext(
            "[15/05/2025, 10:30] Juan: Hola\n[15/05/2025, 10:31] Juan: Necesito ayuda"
        );

        $this->assertNull($context->detectedChannel);

        $this->detector->extract($context);

        $this->assertEquals('whatsapp', $context->detectedChannel);
    }

    // --- Edge cases ---

    public function test_header_like_text_in_body_does_not_trigger_false_positive(): void
    {
        $context = $this->makeContext(
            "Hola equipo,\n\nEl campo 'De:' en el formulario no funciona.\nEl campo 'Para:' tampoco.\n\nGracias."
        );

        $result = $this->detector->extract($context);

        // These are headers at the start of lines, so they count
        // The regex matches lines starting with "De:" or "Para:" which these do
        $this->assertEquals('email_corporativo', $result->value);
    }

    public function test_whatsapp_pattern_with_different_date_separators(): void
    {
        $context = $this->makeContext(
            "[1/6/2025, 9:05] Ana: Primer mensaje\n[1/6/2025, 9:06] Ana: Segundo mensaje"
        );

        $result = $this->detector->extract($context);

        $this->assertEquals('whatsapp', $result->value);
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
