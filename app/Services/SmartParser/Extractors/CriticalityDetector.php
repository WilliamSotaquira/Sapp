<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class CriticalityDetector implements FieldExtractorInterface
{
    /**
     * Keywords por nivel de criticidad, ordenados de mayor a menor prioridad.
     * La búsqueda se realiza case-insensitive.
     *
     * @var array<string, string[]>
     */
    private const KEYWORDS = [
        'CRITICA' => [
            'crítico',
            'critical',
            'emergencia',
            'emergency',
            'sistema caído',
            'system down',
        ],
        'URGENTE' => [
            'urgente',
            'urgent',
            'inmediato',
            'immediate',
            'lo antes posible',
            'asap',
        ],
        'ALTA' => [
            'prioridad alta',
            'high priority',
            'importante',
            'important',
            'a la brevedad',
        ],
        'BAJA' => [
            'cuando puedas',
            'sin prisa',
            'baja prioridad',
            'low priority',
            'no urgente',
        ],
    ];

    /**
     * Orden de prioridad de niveles (mayor índice = mayor prioridad).
     */
    private const PRIORITY_ORDER = [
        'BAJA' => 1,
        'MEDIA' => 2,
        'ALTA' => 3,
        'URGENTE' => 4,
        'CRITICA' => 5,
    ];

    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = mb_strtolower($context->normalizedText);

        $detectedLevels = [];

        // Collect all keyword matches with their positions and lengths
        $matchedRanges = [];

        // First pass: find all multi-word keyword matches (longer phrases take priority)
        $allKeywordsWithLevels = [];
        foreach (self::KEYWORDS as $level => $keywords) {
            foreach ($keywords as $keyword) {
                $allKeywordsWithLevels[] = ['level' => $level, 'keyword' => $keyword];
            }
        }

        // Sort by keyword length descending so longer phrases are matched first
        usort($allKeywordsWithLevels, fn ($a, $b) => mb_strlen($b['keyword']) - mb_strlen($a['keyword']));

        foreach ($allKeywordsWithLevels as $entry) {
            $keyword = $entry['keyword'];
            $level = $entry['level'];
            $offset = 0;

            while (($pos = mb_strpos($text, $keyword, $offset)) !== false) {
                $end = $pos + mb_strlen($keyword);

                // Check if this position is already covered by a longer keyword match
                $covered = false;
                foreach ($matchedRanges as $range) {
                    if ($pos >= $range['start'] && $end <= $range['end']) {
                        $covered = true;
                        break;
                    }
                }

                if (! $covered) {
                    $matchedRanges[] = ['start' => $pos, 'end' => $end];
                    if (! in_array($level, $detectedLevels)) {
                        $detectedLevels[] = $level;
                    }
                }

                $offset = $pos + 1;
            }
        }

        if (empty($detectedLevels)) {
            return new ExtractionResult(
                fieldName: 'criticality_level',
                value: 'MEDIA',
                confidence: 50,
            );
        }

        // Select the highest priority level detected
        $highestLevel = $this->selectHighestLevel($detectedLevels);

        return new ExtractionResult(
            fieldName: 'criticality_level',
            value: $highestLevel,
            confidence: 100,
        );
    }

    /**
     * Selecciona el nivel más alto entre los detectados.
     *
     * @param string[] $levels
     */
    private function selectHighestLevel(array $levels): string
    {
        $highest = $levels[0];

        foreach ($levels as $level) {
            if (self::PRIORITY_ORDER[$level] > self::PRIORITY_ORDER[$highest]) {
                $highest = $level;
            }
        }

        return $highest;
    }
}
