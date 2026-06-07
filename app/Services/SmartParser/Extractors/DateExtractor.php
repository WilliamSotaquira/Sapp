<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use Carbon\Carbon;

class DateExtractor implements FieldExtractorInterface
{
    /**
     * Mapa de nombres de meses en español a su número.
     */
    private const SPANISH_MONTHS = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    /**
     * Frases indicativas de plazo/vencimiento.
     */
    private const DEADLINE_PHRASES = [
        'fecha límite',
        'fecha limite',
        'plazo',
        'antes del',
        'a más tardar',
        'a mas tardar',
        'vence el',
        'deadline',
        'due date',
    ];

    /**
     * Patrón para fecha en formato español textual: "16 de mayo de 2025" con hora opcional.
     */
    private const SPANISH_DATE_PATTERN = '/(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+(?:de\s+)?(\d{4})(?:\s+(\d{1,2}):(\d{2})(?:\s*([ap])\.?\s*m\.?)?)?/iu';

    /**
     * Patrón para fecha en formato numérico: dd/mm/yyyy o dd-mm-yyyy con hora opcional.
     */
    private const NUMERIC_DATE_PATTERN = '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(?:[,\s]+(\d{1,2}):(\d{2})(?:\s*([ap])\.?\s*m\.?)?)?/iu';

    /**
     * Extrae fechas del texto: fecha de creación y fecha de vencimiento.
     *
     * @return ExtractionResult con valor ['created_at' => Carbon|null, 'due_date' => string|null]
     */
    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText ?: $context->rawText;

        $headerDate = $this->extractHeaderDate($context);
        $bodyDates = $this->extractBodyDates($text);
        $dueDate = $this->extractDueDate($text);

        // Priority: header date > first body date > current date
        $createdAt = $headerDate ?? ($bodyDates[0] ?? Carbon::now());

        $confidence = $this->calculateConfidence($headerDate, $bodyDates, $dueDate);

        return new ExtractionResult(
            fieldName: 'dates',
            value: [
                'created_at' => $createdAt,
                'due_date' => $dueDate,
            ],
            confidence: $confidence,
        );
    }

    /**
     * Extrae la fecha del encabezado "Fecha:" o "Date:" del correo.
     */
    private function extractHeaderDate(ParsingContext $context): ?Carbon
    {
        $headerKeys = ['Fecha', 'Date', 'fecha', 'date'];

        foreach ($headerKeys as $key) {
            if (isset($context->emailHeaders[$key]) && ! empty($context->emailHeaders[$key])) {
                $parsed = $this->parseDate($context->emailHeaders[$key]);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    /**
     * Extrae todas las fechas encontradas en el cuerpo del texto.
     *
     * @return Carbon[]
     */
    private function extractBodyDates(string $text): array
    {
        $dates = [];

        // Search for Spanish textual dates
        if (preg_match_all(self::SPANISH_DATE_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $carbon = $this->parseSpanishTextualDate($match);
                if ($carbon !== null) {
                    $dates[] = $carbon;
                }
            }
        }

        // Search for numeric dates
        if (preg_match_all(self::NUMERIC_DATE_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $carbon = $this->parseNumericDate($match);
                if ($carbon !== null) {
                    $dates[] = $carbon;
                }
            }
        }

        return $dates;
    }

    /**
     * Extrae la fecha de vencimiento (due_date) cuando hay una frase de plazo cercana a una fecha.
     */
    private function extractDueDate(string $text): ?string
    {
        $lowerText = mb_strtolower($text);

        foreach (self::DEADLINE_PHRASES as $phrase) {
            $pos = mb_strpos($lowerText, $phrase);
            if ($pos === false) {
                continue;
            }

            // Search for a date near the deadline phrase (within a window after and before the phrase)
            $date = $this->findDateNearPosition($text, $pos, mb_strlen($phrase));
            if ($date !== null) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Busca una fecha cerca de una posición dada en el texto.
     * Busca primero después de la frase (hasta 100 chars) y luego antes (hasta 50 chars).
     */
    private function findDateNearPosition(string $text, int $phrasePos, int $phraseLength): ?Carbon
    {
        // Search after the phrase (up to 100 characters)
        $afterStart = $phrasePos + $phraseLength;
        $afterText = mb_substr($text, $afterStart, 100);

        $date = $this->findFirstDate($afterText);
        if ($date !== null) {
            return $date;
        }

        // Search before the phrase (up to 50 characters)
        $beforeStart = max(0, $phrasePos - 50);
        $beforeLength = $phrasePos - $beforeStart;
        $beforeText = mb_substr($text, $beforeStart, $beforeLength);

        return $this->findFirstDate($beforeText);
    }

    /**
     * Encuentra la primera fecha en un fragmento de texto.
     */
    private function findFirstDate(string $text): ?Carbon
    {
        // Try Spanish textual format first
        if (preg_match(self::SPANISH_DATE_PATTERN, $text, $match)) {
            return $this->parseSpanishTextualDate($match);
        }

        // Try numeric format
        if (preg_match(self::NUMERIC_DATE_PATTERN, $text, $match)) {
            return $this->parseNumericDate($match);
        }

        return null;
    }

    /**
     * Parsea una fecha en formato español textual desde un match de regex.
     *
     * @param array<int, string> $match [full_match, day, month_name, year, hour?, minute?, meridiem?]
     */
    private function parseSpanishTextualDate(array $match): ?Carbon
    {
        $day = (int) $match[1];
        $monthName = mb_strtolower($match[2]);
        $year = (int) $match[3];

        $month = self::SPANISH_MONTHS[$monthName] ?? null;
        if ($month === null) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        $hour = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : 0;
        $minute = isset($match[5]) && $match[5] !== '' ? (int) $match[5] : 0;
        $meridiem = isset($match[6]) && $match[6] !== '' ? mb_strtolower($match[6]) : null;

        if ($meridiem === 'p' && $hour < 12) {
            $hour += 12;
        }
        if ($meridiem === 'a' && $hour === 12) {
            $hour = 0;
        }

        try {
            return Carbon::create($year, $month, $day, $hour, $minute, 0);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parsea una fecha en formato numérico desde un match de regex.
     *
     * @param array<int, string> $match [full_match, day, month, year, hour?, minute?, meridiem?]
     */
    private function parseNumericDate(array $match): ?Carbon
    {
        $day = (int) $match[1];
        $month = (int) $match[2];
        $year = (int) $match[3];

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        $hour = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : 0;
        $minute = isset($match[5]) && $match[5] !== '' ? (int) $match[5] : 0;
        $meridiem = isset($match[6]) && $match[6] !== '' ? mb_strtolower($match[6]) : null;

        if ($meridiem === 'p' && $hour < 12) {
            $hour += 12;
        }
        if ($meridiem === 'a' && $hour === 12) {
            $hour = 0;
        }

        try {
            return Carbon::create($year, $month, $day, $hour, $minute, 0);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parsea una fecha desde un string libre (usado para encabezados).
     * Intenta múltiples formatos comunes.
     */
    private function parseDate(string $dateString): ?Carbon
    {
        $dateString = trim($dateString);

        if (empty($dateString)) {
            return null;
        }

        // Try Spanish textual format
        if (preg_match(self::SPANISH_DATE_PATTERN, $dateString, $match)) {
            return $this->parseSpanishTextualDate($match);
        }

        // Try numeric format
        if (preg_match(self::NUMERIC_DATE_PATTERN, $dateString, $match)) {
            return $this->parseNumericDate($match);
        }

        // Try Carbon's flexible parsing as fallback for standard date formats
        try {
            return Carbon::parse($dateString);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Calcula el nivel de confianza basado en las fuentes de fecha encontradas.
     *
     * @param Carbon|null $headerDate
     * @param Carbon[] $bodyDates
     * @param string|null $dueDate
     */
    private function calculateConfidence(?Carbon $headerDate, array $bodyDates, ?string $dueDate): int
    {
        if ($headerDate !== null) {
            return 95;
        }

        if (! empty($bodyDates)) {
            return 75;
        }

        // Fallback to current date — low confidence
        return 30;
    }
}
