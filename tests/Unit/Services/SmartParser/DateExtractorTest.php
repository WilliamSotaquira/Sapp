<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\DateExtractor;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateExtractorTest extends TestCase
{
    private DateExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new DateExtractor();
    }

    private function makeContext(string $text, array $emailHeaders = []): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = 1;
        $context->contractId = 1;
        $context->emailHeaders = $emailHeaders;

        return $context;
    }

    // --- Header date extraction (priority) ---

    public function test_extracts_date_from_fecha_header(): void
    {
        $context = $this->makeContext(
            'Cuerpo del correo sin fecha',
            ['Fecha' => '16 de mayo de 2025']
        );
        $result = $this->extractor->extract($context);

        $this->assertEquals('dates', $result->fieldName);
        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-05-16', $result->value['created_at']->format('Y-m-d'));
        $this->assertEquals(95, $result->confidence);
    }

    public function test_extracts_date_from_date_header(): void
    {
        $context = $this->makeContext(
            'Email body without date',
            ['Date' => '10/03/2025']
        );
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-03-10', $result->value['created_at']->format('Y-m-d'));
        $this->assertEquals(95, $result->confidence);
    }

    public function test_header_date_has_priority_over_body_date(): void
    {
        $context = $this->makeContext(
            'El evento es el 20 de enero de 2025',
            ['Fecha' => '05/12/2024']
        );
        $result = $this->extractor->extract($context);

        $this->assertEquals('2024-12-05', $result->value['created_at']->format('Y-m-d'));
    }

    // --- Spanish textual date extraction ---

    public function test_extracts_spanish_textual_date(): void
    {
        $context = $this->makeContext('La solicitud fue enviada el 16 de mayo de 2025');
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-05-16', $result->value['created_at']->format('Y-m-d'));
        $this->assertEquals(75, $result->confidence);
    }

    public function test_extracts_spanish_date_all_months(): void
    {
        $months = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03',
            'abril' => '04', 'mayo' => '05', 'junio' => '06',
            'julio' => '07', 'agosto' => '08', 'septiembre' => '09',
            'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
        ];

        foreach ($months as $monthName => $monthNum) {
            $context = $this->makeContext("Fecha: 1 de {$monthName} de 2025");
            $result = $this->extractor->extract($context);

            $this->assertInstanceOf(Carbon::class, $result->value['created_at'], "Failed for month: {$monthName}");
            $this->assertEquals("2025-{$monthNum}-01", $result->value['created_at']->format('Y-m-d'), "Failed for month: {$monthName}");
        }
    }

    public function test_extracts_spanish_date_case_insensitive(): void
    {
        $context = $this->makeContext('Enviado el 5 de Marzo de 2025');
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-03-05', $result->value['created_at']->format('Y-m-d'));
    }

    // --- Numeric date extraction ---

    public function test_extracts_numeric_date_with_slashes(): void
    {
        $context = $this->makeContext('Recibido el 15/06/2025 por correo');
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-06-15', $result->value['created_at']->format('Y-m-d'));
    }

    public function test_extracts_numeric_date_with_dashes(): void
    {
        $context = $this->makeContext('Fecha de envío: 22-11-2024');
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2024-11-22', $result->value['created_at']->format('Y-m-d'));
    }

    // --- Fallback to current date ---

    public function test_uses_current_date_when_no_date_found(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 6, 1, 12, 0, 0));

        $context = $this->makeContext('Texto sin ninguna fecha identificable');
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-06-01', $result->value['created_at']->format('Y-m-d'));
        $this->assertEquals(30, $result->confidence);

        Carbon::setTestNow();
    }

    // --- Due date extraction ---

    public function test_extracts_due_date_with_fecha_limite_phrase(): void
    {
        $context = $this->makeContext('La fecha límite es el 30 de junio de 2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-06-30', $result->value['due_date']);
    }

    public function test_extracts_due_date_with_antes_del_phrase(): void
    {
        $context = $this->makeContext('Necesitamos esto antes del 15/07/2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-07-15', $result->value['due_date']);
    }

    public function test_extracts_due_date_with_vence_el_phrase(): void
    {
        $context = $this->makeContext('El contrato vence el 31-12-2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-12-31', $result->value['due_date']);
    }

    public function test_extracts_due_date_with_deadline_phrase(): void
    {
        $context = $this->makeContext('The deadline is 20/08/2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-08-20', $result->value['due_date']);
    }

    public function test_extracts_due_date_with_plazo_phrase(): void
    {
        $context = $this->makeContext('El plazo para entregar es 10 de septiembre de 2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-09-10', $result->value['due_date']);
    }

    public function test_extracts_due_date_with_a_mas_tardar_phrase(): void
    {
        $context = $this->makeContext('Entregar a más tardar el 25/04/2025');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-04-25', $result->value['due_date']);
    }

    public function test_no_due_date_when_no_deadline_phrase(): void
    {
        $context = $this->makeContext('La reunión es el 16 de mayo de 2025');
        $result = $this->extractor->extract($context);

        $this->assertNull($result->value['due_date']);
    }

    // --- Edge cases ---

    public function test_invalid_date_is_ignored(): void
    {
        $context = $this->makeContext('Fecha: 32/13/2025');
        $result = $this->extractor->extract($context);

        // Should fallback to current date since 32/13/2025 is invalid
        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
    }

    public function test_multiple_dates_uses_first_body_date(): void
    {
        $context = $this->makeContext('Enviado el 10 de enero de 2025. Revisado el 15 de febrero de 2025.');
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-01-10', $result->value['created_at']->format('Y-m-d'));
    }

    public function test_header_with_standard_date_format(): void
    {
        $context = $this->makeContext(
            'Cuerpo del correo',
            ['Date' => 'Fri, 16 May 2025 10:30:00 +0000']
        );
        $result = $this->extractor->extract($context);

        $this->assertInstanceOf(Carbon::class, $result->value['created_at']);
        $this->assertEquals('2025-05-16', $result->value['created_at']->format('Y-m-d'));
    }

    public function test_empty_header_falls_back_to_body(): void
    {
        $context = $this->makeContext(
            'Solicitud del 20 de marzo de 2025',
            ['Fecha' => '']
        );
        $result = $this->extractor->extract($context);

        $this->assertEquals('2025-03-20', $result->value['created_at']->format('Y-m-d'));
        $this->assertEquals(75, $result->confidence);
    }
}
