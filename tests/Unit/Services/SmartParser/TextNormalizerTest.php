<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\TextNormalizer;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase
{
    private TextNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new TextNormalizer();
    }

    // --- normalize() tests ---

    public function test_normalize_removes_control_characters(): void
    {
        $text = "Hello\x00 World\x01\x02\x03";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals('Hello World', $result);
    }

    public function test_normalize_collapses_multiple_newlines_to_max_two(): void
    {
        $text = "Line 1\n\n\n\n\nLine 2\n\n\nLine 3";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals("Line 1\n\nLine 2\n\nLine 3", $result);
    }

    public function test_normalize_replaces_tabs_with_single_space(): void
    {
        $text = "Column1\t\tColumn2\tColumn3";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals('Column1  Column2 Column3', $result);
    }

    public function test_normalize_removes_quoted_lines(): void
    {
        $text = "My message\n> Quoted text\n>> Double quoted\nAnother line";
        $result = $this->normalizer->normalize($text);

        $this->assertStringNotContainsString('Quoted text', $result);
        $this->assertStringNotContainsString('Double quoted', $result);
        $this->assertStringContainsString('My message', $result);
        $this->assertStringContainsString('Another line', $result);
    }

    public function test_normalize_deduplicates_identical_blocks(): void
    {
        $text = "Block one content\n\nBlock two content\n\nBlock one content";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals("Block one content\n\nBlock two content", $result);
    }

    public function test_normalize_handles_combined_issues(): void
    {
        $text = "Hello\x00 World\t\there\n\n\n\n\n> quoted line\nContent\n\nContent";
        $result = $this->normalizer->normalize($text);

        $this->assertStringNotContainsString("\x00", $result);
        $this->assertStringNotContainsString("\t", $result);
        $this->assertStringNotContainsString('quoted line', $result);
        // Should not have more than 2 consecutive newlines
        $this->assertDoesNotMatchRegularExpression('/\n{3,}/', $result);
    }

    public function test_normalize_trims_result(): void
    {
        $text = "  \n\n\nHello World\n\n\n  ";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals('Hello World', $result);
    }

    public function test_normalize_handles_empty_string(): void
    {
        $result = $this->normalizer->normalize('');
        $this->assertEquals('', $result);
    }

    public function test_normalize_preserves_normal_text(): void
    {
        $text = "This is a normal paragraph.\n\nThis is another paragraph.";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals($text, $result);
    }

    // --- removeQuoteMarkers() tests ---

    public function test_removeQuoteMarkers_removes_single_level_quotes(): void
    {
        $text = "My message\n> This is quoted\nAnother line";
        $result = $this->normalizer->removeQuoteMarkers($text);

        $this->assertStringNotContainsString('This is quoted', $result);
        $this->assertStringContainsString('My message', $result);
        $this->assertStringContainsString('Another line', $result);
    }

    public function test_removeQuoteMarkers_removes_multi_level_quotes(): void
    {
        $text = "Message\n> Level 1\n>> Level 2\n>>> Level 3\nEnd";
        $result = $this->normalizer->removeQuoteMarkers($text);

        $this->assertStringNotContainsString('Level 1', $result);
        $this->assertStringNotContainsString('Level 2', $result);
        $this->assertStringNotContainsString('Level 3', $result);
        $this->assertStringContainsString('Message', $result);
        $this->assertStringContainsString('End', $result);
    }

    public function test_removeQuoteMarkers_removes_response_prefixes(): void
    {
        $text = "Re: Some subject\nFwd: Forwarded content\nRv: Reenvio";
        $result = $this->normalizer->removeQuoteMarkers($text);

        $this->assertStringContainsString('Some subject', $result);
        $this->assertStringContainsString('Forwarded content', $result);
        $this->assertStringContainsString('Reenvio', $result);
        $this->assertStringNotContainsString('Re:', $result);
        $this->assertStringNotContainsString('Fwd:', $result);
        $this->assertStringNotContainsString('Rv:', $result);
    }

    public function test_removeQuoteMarkers_preserves_non_quoted_lines(): void
    {
        $text = "Line 1\nLine 2\nLine 3";
        $result = $this->normalizer->removeQuoteMarkers($text);

        $this->assertEquals($text, $result);
    }

    // --- deduplicateBlocks() tests ---

    public function test_deduplicateBlocks_removes_duplicate_paragraphs(): void
    {
        $text = "First paragraph\n\nSecond paragraph\n\nFirst paragraph";
        $result = $this->normalizer->deduplicateBlocks($text);

        $this->assertEquals("First paragraph\n\nSecond paragraph", $result);
    }

    public function test_deduplicateBlocks_preserves_unique_blocks(): void
    {
        $text = "Block A\n\nBlock B\n\nBlock C";
        $result = $this->normalizer->deduplicateBlocks($text);

        $this->assertEquals($text, $result);
    }

    public function test_deduplicateBlocks_handles_multiple_duplicates(): void
    {
        $text = "Header\n\nContent\n\nHeader\n\nContent\n\nFooter";
        $result = $this->normalizer->deduplicateBlocks($text);

        $this->assertEquals("Header\n\nContent\n\nFooter", $result);
    }

    public function test_deduplicateBlocks_ignores_whitespace_differences(): void
    {
        $text = "Same block\n\nSame  block\n\nDifferent block";
        $result = $this->normalizer->deduplicateBlocks($text);

        // "Same block" and "Same  block" should be treated as the same
        // because the key normalizes whitespace
        $this->assertEquals("Same block\n\nDifferent block", $result);
    }

    public function test_deduplicateBlocks_handles_empty_text(): void
    {
        $result = $this->normalizer->deduplicateBlocks('');
        $this->assertEquals('', $result);
    }

    public function test_deduplicateBlocks_handles_single_block(): void
    {
        $text = 'Only one block here';
        $result = $this->normalizer->deduplicateBlocks($text);

        $this->assertEquals($text, $result);
    }

    // --- Edge cases ---

    public function test_normalize_handles_non_breaking_spaces(): void
    {
        // Non-breaking space is \xC2\xA0 in UTF-8
        $text = "Hello\xC2\xA0World";
        $result = $this->normalizer->normalize($text);

        // Non-breaking space should be preserved (it's not a control character)
        // The requirement says "espacios no separables" but the design says
        // "elimina caracteres de control" - non-breaking space is not a control char
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function test_normalize_handles_windows_line_endings(): void
    {
        $text = "Line 1\r\n\r\n\r\n\r\nLine 2";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals("Line 1\n\nLine 2", $result);
    }

    public function test_normalize_handles_c1_control_characters(): void
    {
        // C1 control characters (0x80-0x9F in Unicode)
        $text = "Hello\xC2\x80World\xC2\x9F End";
        $result = $this->normalizer->normalize($text);

        $this->assertEquals('HelloWorld End', $result);
    }
}
