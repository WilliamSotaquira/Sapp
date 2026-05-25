<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\UrlExtractor;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use PHPUnit\Framework\TestCase;

class UrlExtractorTest extends TestCase
{
    private UrlExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new UrlExtractor();
    }

    private function makeContext(string $text): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = 1;
        $context->contractId = 1;

        return $context;
    }

    // --- Basic URL extraction ---

    public function test_extracts_single_http_url(): void
    {
        $context = $this->makeContext('Visita http://example.com para más info');
        $result = $this->extractor->extract($context);

        $this->assertEquals('web_routes', $result->fieldName);
        $this->assertEquals(['http://example.com'], $result->value);
        $this->assertEquals(100, $result->confidence);
        $this->assertTrue($result->extracted);
    }

    public function test_extracts_single_https_url(): void
    {
        $context = $this->makeContext('Revisa https://secure.example.com/path');
        $result = $this->extractor->extract($context);

        $this->assertEquals(['https://secure.example.com/path'], $result->value);
        $this->assertEquals(100, $result->confidence);
    }

    public function test_extracts_multiple_urls(): void
    {
        $text = 'Revisar https://site1.com y http://site2.com/page y https://site3.org/path?q=1';
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(3, $result->value);
        $this->assertContains('https://site1.com', $result->value);
        $this->assertContains('http://site2.com/page', $result->value);
        $this->assertContains('https://site3.org/path?q=1', $result->value);
    }

    public function test_extracts_urls_with_paths_and_query_params(): void
    {
        $context = $this->makeContext('URL: https://example.com/path/to/page?param=value&other=123');
        $result = $this->extractor->extract($context);

        $this->assertEquals(['https://example.com/path/to/page?param=value&other=123'], $result->value);
    }

    // --- Duplicate removal ---

    public function test_removes_duplicate_urls(): void
    {
        $text = 'Visita https://example.com y luego https://example.com de nuevo';
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(1, $result->value);
        $this->assertEquals(['https://example.com'], $result->value);
    }

    public function test_removes_duplicates_case_insensitive(): void
    {
        $text = 'Visita https://Example.COM y luego https://example.com';
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(1, $result->value);
    }

    // --- Maximum limit ---

    public function test_limits_to_maximum_8_urls(): void
    {
        $urls = [];
        for ($i = 1; $i <= 12; $i++) {
            $urls[] = "https://site{$i}.com";
        }
        $text = implode(' ', $urls);
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(8, $result->value);
        // Should keep the first 8
        $this->assertEquals('https://site1.com', $result->value[0]);
        $this->assertEquals('https://site8.com', $result->value[7]);
    }

    public function test_exactly_8_urls_are_all_kept(): void
    {
        $urls = [];
        for ($i = 1; $i <= 8; $i++) {
            $urls[] = "https://site{$i}.com";
        }
        $text = implode(' ', $urls);
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(8, $result->value);
    }

    // --- Empty/no URLs ---

    public function test_returns_empty_result_when_no_urls(): void
    {
        $context = $this->makeContext('Este texto no contiene ninguna URL');
        $result = $this->extractor->extract($context);

        $this->assertEquals('web_routes', $result->fieldName);
        $this->assertNull($result->value);
        $this->assertEquals(0, $result->confidence);
        $this->assertFalse($result->extracted);
    }

    public function test_returns_empty_result_for_empty_text(): void
    {
        $context = $this->makeContext('');
        $result = $this->extractor->extract($context);

        $this->assertNull($result->value);
        $this->assertEquals(0, $result->confidence);
        $this->assertFalse($result->extracted);
    }

    // --- Edge cases ---

    public function test_does_not_extract_ftp_urls(): void
    {
        $context = $this->makeContext('Archivo en ftp://files.example.com/doc.pdf');
        $result = $this->extractor->extract($context);

        $this->assertNull($result->value);
        $this->assertFalse($result->extracted);
    }

    public function test_extracts_urls_from_multiline_text(): void
    {
        $text = "Primera línea con https://first.com\nSegunda línea\nTercera con http://third.com";
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(2, $result->value);
        $this->assertContains('https://first.com', $result->value);
        $this->assertContains('http://third.com', $result->value);
    }

    public function test_handles_urls_with_trailing_punctuation(): void
    {
        $context = $this->makeContext('Visita https://example.com.');
        $result = $this->extractor->extract($context);

        $this->assertEquals(['https://example.com'], $result->value);
    }

    public function test_uses_normalized_text_over_raw_text(): void
    {
        $context = new ParsingContext();
        $context->rawText = 'Raw: http://raw.com';
        $context->normalizedText = 'Normalized: http://normalized.com';
        $context->companyId = 1;
        $context->contractId = 1;

        $result = $this->extractor->extract($context);

        $this->assertEquals(['http://normalized.com'], $result->value);
    }

    public function test_falls_back_to_raw_text_when_normalized_is_empty(): void
    {
        $context = new ParsingContext();
        $context->rawText = 'Raw: http://raw.com';
        $context->normalizedText = '';
        $context->companyId = 1;
        $context->contractId = 1;

        $result = $this->extractor->extract($context);

        $this->assertEquals(['http://raw.com'], $result->value);
    }

    public function test_extracts_urls_from_email_body(): void
    {
        $text = "De: usuario@empresa.com\nAsunto: Revisar sitios\n\nPor favor revisar:\nhttps://www.sitio1.com/pagina\nhttps://www.sitio2.com/otra-pagina\n\nSaludos";
        $context = $this->makeContext($text);
        $result = $this->extractor->extract($context);

        $this->assertCount(2, $result->value);
        $this->assertContains('https://www.sitio1.com/pagina', $result->value);
        $this->assertContains('https://www.sitio2.com/otra-pagina', $result->value);
    }
}
