<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

use Illuminate\Support\Str;

/**
 * Detecta si un texto sigue el formato estructurado predefinido existente.
 *
 * El formato estructurado espera campos en un orden específico (uno por línea):
 *   Línea 0: Título/Asunto
 *   Línea 1: Descripción
 *   Línea 2: Fecha de creación (o "No disponible")
 *   Línea 3: Fecha de vencimiento (o "No disponible")
 *   Línea 4: Nombre del solicitante
 *   Línea 5: Canal de entrada (no vacío)
 *   Línea 6: Nombre del subservicio (no vacío)
 *   Línea 7: URLs/Enlaces
 *   Línea 8: Nivel de criticidad (no vacío)
 *   Línea 9+: Bloque de tareas (contiene la palabra "subtarea(s)")
 */
class StructuredFormatDetector
{
    /**
     * Retorna true si el texto coincide con el formato exacto predefinido.
     *
     * La detección se basa en verificar que el texto tiene al menos 10 líneas
     * no vacías y que los campos de fecha, canal, subservicio, criticidad y
     * tareas están en las posiciones esperadas.
     */
    public function isStructuredFormat(string $normalizedText): bool
    {
        $lines = $this->extractNonEmptyLines($normalizedText);

        if (count($lines) < 10) {
            return false;
        }

        // Línea 2: debe ser una fecha parseable o "No disponible"
        $line2 = trim((string) ($lines[2] ?? ''));
        if (! $this->isUnavailableMarker($line2) && $this->parseFlexibleDate($line2) === null) {
            return false;
        }

        // Línea 3: debe ser una fecha parseable o "No disponible"
        $line3 = trim((string) ($lines[3] ?? ''));
        if (! $this->isUnavailableMarker($line3) && $this->parseFlexibleDate($line3) === null) {
            return false;
        }

        // Línea 5: canal de entrada (no puede estar vacío)
        if (trim((string) ($lines[5] ?? '')) === '') {
            return false;
        }

        // Línea 6: nombre del subservicio (no puede estar vacío)
        if (trim((string) ($lines[6] ?? '')) === '') {
            return false;
        }

        // Línea 8: nivel de criticidad (no puede estar vacío)
        if (trim((string) ($lines[8] ?? '')) === '') {
            return false;
        }

        // Línea 9: debe contener la palabra "subtarea" o "subtareas"
        return preg_match('/\bsubtareas?\b/iu', (string) ($lines[9] ?? '')) === 1;
    }

    /**
     * Extrae las líneas no vacías del texto, normalizando markdown links.
     *
     * @return string[]
     */
    private function extractNonEmptyLines(string $text): array
    {
        $rawLines = preg_split('/\n/u', str_replace(["\r\n", "\r"], "\n", $text)) ?: [];

        return array_values(array_filter(
            array_map(
                fn (string $line) => trim($this->normalizeMarkdownLinks($line)),
                $rawLines
            ),
            fn (string $line) => $line !== ''
        ));
    }

    /**
     * Convierte enlaces markdown [texto](url) a solo la URL.
     */
    private function normalizeMarkdownLinks(string $text): string
    {
        return preg_replace('/\[(.*?)\]\((https?:\/\/[^\s)]+)\)/iu', '$2', $text) ?? $text;
    }

    /**
     * Verifica si el texto es un marcador de "No disponible".
     */
    private function isUnavailableMarker(string $text): bool
    {
        return $this->normalizeForComparison($text) === 'no disponible';
    }

    /**
     * Verifica si el texto puede interpretarse como una fecha válida.
     *
     * No necesita crear un Carbon real — solo determina si el patrón es reconocible
     * como fecha para decidir si el formato es estructurado.
     */
    private function parseFlexibleDate(string $text): ?bool
    {
        $clean = trim($text);
        if ($clean === '' || $this->isUnavailableMarker($clean)) {
            return null;
        }

        // Formato español textual: "16 de mayo de 2025" con hora opcional
        if ($this->looksLikeSpanishDate($clean)) {
            return true;
        }

        // Formato corto: "16 may", "16 mayo 2025"
        if (preg_match('/^(\d{1,2})\s+([a-záéíóúñ]+)(?:\s+(\d{4}))?$/iu', $clean, $matches)) {
            $month = $this->resolveMonthNumber(mb_strtolower($matches[2]));
            if ($month !== null) {
                return true;
            }
        }

        // Formato numérico: dd/mm/yyyy, dd-mm-yyyy, dd/mm/yy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/u', $clean, $matches)) {
            $month = (int) $matches[2];
            $day = (int) $matches[1];

            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return true;
            }
        }

        // Formato ISO: yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/u', $clean, $matches)) {
            return true;
        }

        return null;
    }

    /**
     * Verifica si el texto contiene un patrón de fecha en español textual.
     */
    private function looksLikeSpanishDate(string $text): bool
    {
        if (! preg_match(
            '/(\d{1,2})\s+de\s+([[:alpha:]áéíóúñ]+)\s+de\s+(\d{4})/iu',
            $text,
            $matches
        )) {
            return false;
        }

        return $this->resolveMonthNumber(mb_strtolower($matches[2])) !== null;
    }

    /**
     * Resuelve el número de mes a partir de su nombre en español.
     */
    private function resolveMonthNumber(string $monthName): ?int
    {
        $normalized = $this->normalizeForComparison($monthName);

        $months = [
            'enero' => 1, 'ene' => 1,
            'febrero' => 2, 'feb' => 2,
            'marzo' => 3, 'mar' => 3,
            'abril' => 4, 'abr' => 4,
            'mayo' => 5, 'may' => 5,
            'junio' => 6, 'jun' => 6,
            'julio' => 7, 'jul' => 7,
            'agosto' => 8, 'ago' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'sep' => 9, 'sept' => 9,
            'octubre' => 10, 'oct' => 10,
            'noviembre' => 11, 'nov' => 11,
            'diciembre' => 12, 'dic' => 12,
        ];

        return $months[$normalized] ?? null;
    }

    /**
     * Normaliza un texto para comparación: minúsculas, sin tildes, sin caracteres especiales.
     */
    private function normalizeForComparison(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();
    }
}
