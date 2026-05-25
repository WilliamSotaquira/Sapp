<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class UrlExtractor implements FieldExtractorInterface
{
    private const MAX_URLS = 8;

    private const URL_PATTERN = '/https?:\/\/[^\s<>"\')\]\},;]+/i';

    /**
     * Extrae URLs únicas del texto proporcionado.
     *
     * @return ExtractionResult con array de URLs únicas (máximo 8) y confianza 100 si se encontraron, 0 si no.
     */
    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText ?: $context->rawText;

        $urls = $this->extractUrls($text);
        $urls = $this->removeDuplicates($urls);
        $urls = $this->applyLimit($urls);

        if (empty($urls)) {
            return ExtractionResult::empty('web_routes');
        }

        return new ExtractionResult(
            fieldName: 'web_routes',
            value: $urls,
            confidence: 100,
        );
    }

    /**
     * Extrae todas las URLs del texto usando regex.
     *
     * @return string[]
     */
    private function extractUrls(string $text): array
    {
        if (preg_match_all(self::URL_PATTERN, $text, $matches) === false) {
            return [];
        }

        return $matches[0] ?? [];
    }

    /**
     * Elimina URLs duplicadas preservando el orden de aparición.
     *
     * @param  string[]  $urls
     * @return string[]
     */
    private function removeDuplicates(array $urls): array
    {
        $seen = [];
        $unique = [];

        foreach ($urls as $url) {
            $normalized = rtrim($url, '.');
            $key = mb_strtolower($normalized);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $normalized;
            }
        }

        return $unique;
    }

    /**
     * Aplica el límite máximo de URLs.
     *
     * @param  string[]  $urls
     * @return string[]
     */
    private function applyLimit(array $urls): array
    {
        return array_slice($urls, 0, self::MAX_URLS);
    }
}
