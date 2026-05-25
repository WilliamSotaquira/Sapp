<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

class TextNormalizer
{
    /**
     * Normaliza el texto: elimina caracteres de control, colapsa saltos de línea,
     * reemplaza tabulaciones, elimina marcadores de citado, deduplica bloques.
     */
    public function normalize(string $rawText): string
    {
        $text = $this->removeControlCharacters($rawText);
        $text = $this->replaceTabsWithSpaces($text);
        $text = $this->collapseNewlines($text);
        $text = $this->removeQuoteMarkers($text);
        $text = $this->deduplicateBlocks($text);

        return trim($text);
    }

    /**
     * Elimina líneas citadas (prefijo ">") y marcadores de respuesta dispersos.
     *
     * Removes lines starting with ">", ">>", ">>>" and inline prefixes
     * like "Re:", "RE:", "Fwd:", "Rv:" that appear intercalated in the body.
     */
    public function removeQuoteMarkers(string $text): string
    {
        $lines = explode("\n", $text);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $trimmedLine = ltrim($line);

            // Skip lines that start with ">" (quoted text)
            if (str_starts_with($trimmedLine, '>')) {
                continue;
            }

            // Remove inline "Re:", "RE:", "Fwd:", "FWD:", "Fw:", "Rv:", "RV:" prefixes
            // that appear at the beginning of a line (intercalated markers)
            $cleanedLine = preg_replace(
                '/^(Re:|RE:|Fwd:|FWD:|Fw:|Rv:|RV:)\s*/i',
                '',
                $trimmedLine
            );

            $cleanedLines[] = $cleanedLine;
        }

        return implode("\n", $cleanedLines);
    }

    /**
     * Deduplica bloques de texto idénticos.
     *
     * Splits text into blocks separated by double newlines and removes
     * duplicate blocks, preserving only the first occurrence.
     */
    public function deduplicateBlocks(string $text): string
    {
        // Split into blocks by double newlines (or more)
        $blocks = preg_split('/\n{2,}/', $text);

        if ($blocks === false) {
            return $text;
        }

        $seen = [];
        $uniqueBlocks = [];

        foreach ($blocks as $block) {
            $normalizedBlock = trim($block);

            // Skip empty blocks
            if ($normalizedBlock === '') {
                continue;
            }

            // Use a normalized key for comparison (collapse whitespace for matching)
            $key = preg_replace('/\s+/', ' ', $normalizedBlock);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueBlocks[] = $normalizedBlock;
            }
        }

        return implode("\n\n", $uniqueBlocks);
    }

    /**
     * Elimina caracteres de control Unicode, preservando saltos de línea y tabulaciones
     * (que se procesan por separado).
     */
    private function removeControlCharacters(string $text): string
    {
        // Remove Unicode control characters (C0 and C1 control codes)
        // except for \n (0x0A), \r (0x0D), and \t (0x09) which are handled separately
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{0080}-\x{009F}]/u', '', $text) ?? $text;
    }

    /**
     * Reemplaza tabulaciones por espacios simples.
     */
    private function replaceTabsWithSpaces(string $text): string
    {
        return str_replace("\t", ' ', $text);
    }

    /**
     * Colapsa múltiples saltos de línea consecutivos a un máximo de 2.
     * Also normalizes \r\n to \n first.
     */
    private function collapseNewlines(string $text): string
    {
        // Normalize line endings to \n
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        // Collapse 3+ consecutive newlines to exactly 2
        return preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
    }
}
