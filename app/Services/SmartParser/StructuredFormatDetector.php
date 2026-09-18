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

        // Formato ITIL de 7 líneas (salida de los prompts ITIL): tiene prioridad
        // porque es más compacto y no incluye canal ni criticidad.
        if ($this->isItilStructuredFormat($lines)) {
            return true;
        }

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
     * Detecta el formato ITIL compacto de 7 campos que producen los prompts ITIL.
     *
     * Estructura esperada (una línea no vacía por campo, salvo el bloque final):
     *   Línea 0: Asunto
     *   Línea 1: Descripción
     *   Línea 2: Fecha (opcional, puede no estar tras eliminar líneas vacías)
     *   Línea 3: Solicitante
     *   Línea 4: Subservicio
     *   Línea 5: Enlaces (opcional)
     *   Línea N: Título de actividad "(X subtareas)" seguido de acciones con guion
     *
     * La marca inequívoca es una línea con "(X subtareas)" en posición temprana
     * (índice 3-6) seguida de al menos una viñeta con guion. En el formato de 10
     * líneas ese título aparece en el índice 9 o posterior, por lo que la posición
     * temprana evita el falso positivo.
     *
     * @param  string[]  $lines
     */
    public function isItilStructuredFormat(array $lines): bool
    {
        if (count($lines) < 4) {
            return false;
        }

        // Localiza la línea del título de actividad con "(X subtareas)".
        $subtaskLineIndex = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/\(\s*\d+\s*subtareas?\s*\)/iu', (string) $line) === 1) {
                $subtaskLineIndex = $index;
                break;
            }
        }

        if ($subtaskLineIndex === null) {
            return false;
        }

        // En el formato ITIL el título de subtareas aparece temprano; en el de 10
        // líneas aparece en el índice 9 o posterior.
        if ($subtaskLineIndex < 3 || $subtaskLineIndex > 6) {
            return false;
        }

        // Debe haber al menos una acción en viñeta con guion tras el título.
        for ($i = $subtaskLineIndex + 1; $i < count($lines); $i++) {
            if (preg_match('/^-\s+\S/u', (string) $lines[$i]) === 1) {
                return true;
            }
        }

        return false;
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

        // Formato corto: "16 may", "16 mayo 2025", "16 de mayo"
        if (preg_match('/^(\d{1,2})\s+(?:de\s+)?([a-záéíóúñ]+)(?:\s+(?:de\s+)?(\d{4}))?$/iu', $clean, $matches)) {
            $month = $this->resolveMonthNumber(mb_strtolower($matches[2]));
            if ($month !== null) {
                return true;
            }
        }

        // Formato numérico: dd/mm/yyyy, dd-mm-yyyy, dd/mm/yy (with optional day prefix and time suffix)
        if (preg_match('/(?:^|[[:alpha:]áéíóúñ]+\s+)(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/u', $clean, $matches)) {
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
