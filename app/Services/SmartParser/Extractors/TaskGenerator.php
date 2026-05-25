<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class TaskGenerator implements FieldExtractorInterface
{
    /**
     * Maximum number of subtasks allowed per task.
     */
    private const MAX_SUBTASKS = 20;

    /**
     * Minimum character length for a valid subtask title.
     */
    private const MIN_TITLE_LENGTH = 3;

    /**
     * Maximum character length for a valid subtask title.
     */
    private const MAX_TITLE_LENGTH = 255;

    /**
     * Default estimated minutes when no explicit duration is found.
     */
    private const DEFAULT_MINUTES = 25;

    /**
     * Minimum allowed duration in minutes.
     */
    private const MIN_MINUTES = 5;

    /**
     * Maximum allowed duration in minutes.
     */
    private const MAX_MINUTES = 480;

    /**
     * Regex pattern to detect list items (bullets and numbering).
     * Matches lines starting with: *, -, •, 1., 2., a), b), etc.
     */
    private const LIST_PATTERN = '/^\s*(?:\*|\-|•|\d+[\.\)]\s?|[a-zA-Z][\)\.])\s*/u';

    /**
     * Regex pattern to detect file names with common extensions.
     * Matches lines that contain or are filenames ending with common extensions.
     */
    private const FILE_EXTENSION_PATTERN = '/^[\p{L}\p{N}\s_\-\.]+\.(jpg|jpeg|png|gif|bmp|svg|webp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|csv|zip|rar|mp4|mp3|wav|avi)$/iu';

    /**
     * Patrón para detectar la sección de respuesta del usuario en formato Outlook.
     */
    private const OUTLOOK_REPLY_PATTERN = '/^(Usted|You)$/i';

    /**
     * Regex patterns to detect explicit durations in text.
     * Captures number and unit (hours/minutes variants).
     */
    private const DURATION_PATTERNS = [
        '/(\d+)\s*horas?/iu',
        '/(\d+)\s*hrs?/iu',
        '/(\d+)\s*h\b/iu',
        '/(\d+)\s*minutos?/iu',
        '/(\d+)\s*mins?/iu',
        '/(\d+)\s*m\b/iu',
    ];

    /**
     * Units that represent hours (matched patterns indices 0-2).
     */
    private const HOUR_UNITS = ['hora', 'horas', 'hr', 'hrs', 'h'];

    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText;

        // Remove Outlook reply section before processing
        $text = $this->removeOutlookReplySection($text);

        $lines = $this->getLines($text);

        $listItems = $this->extractListItems($lines);

        if (! empty($listItems)) {
            return $this->generateTaskWithSubtasks($listItems, $context);
        }

        // Try to detect file lists (lines ending with file extensions)
        $fileItems = $this->extractFileItems($lines);

        if (! empty($fileItems)) {
            return $this->generateTaskWithSubtasks($fileItems, $context);
        }

        return $this->generateSingleTask($context);
    }

    /**
     * Split text into individual lines.
     *
     * @return string[]
     */
    private function getLines(string $text): array
    {
        return preg_split('/\r?\n/', $text);
    }

    /**
     * Extract valid list items from lines.
     *
     * @param  string[]  $lines
     * @return array<int, array{title: string, raw: string}>
     */
    private function extractListItems(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            if (preg_match(self::LIST_PATTERN, $line)) {
                $cleanedTitle = preg_replace(self::LIST_PATTERN, '', $line);
                $cleanedTitle = trim($cleanedTitle);

                $length = mb_strlen($cleanedTitle);

                if ($length >= self::MIN_TITLE_LENGTH && $length <= self::MAX_TITLE_LENGTH) {
                    $items[] = [
                        'title' => $cleanedTitle,
                        'raw' => $line,
                    ];
                }

                if (count($items) >= self::MAX_SUBTASKS) {
                    break;
                }
            }
        }

        return $items;
    }

    /**
     * Extract file names from lines that end with common file extensions.
     * Generates subtask titles like "Subir imagen filename.jpg" for image files
     * or "Procesar filename.pdf" for other file types.
     *
     * @param  string[]  $lines
     * @return array<int, array{title: string, raw: string}>
     */
    private function extractFileItems(array $lines): array
    {
        $items = [];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Remove Unicode zero-width characters (common in Outlook pastes)
            $cleanLine = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $trimmed);
            $cleanLine = trim($cleanLine);

            if ($cleanLine === '') {
                continue;
            }

            if (preg_match(self::FILE_EXTENSION_PATTERN, $cleanLine, $matches)) {
                $extension = mb_strtolower($matches[1]);
                $filename = $cleanLine;

                // Generate appropriate subtask title based on file type
                if (in_array($extension, $imageExtensions, true)) {
                    $title = "Subir imagen {$filename}";
                } else {
                    $title = "Procesar {$filename}";
                }

                $length = mb_strlen($title);

                if ($length >= self::MIN_TITLE_LENGTH && $length <= self::MAX_TITLE_LENGTH) {
                    $items[] = [
                        'title' => $title,
                        'raw' => $line,
                    ];
                }

                if (count($items) >= self::MAX_SUBTASKS) {
                    break;
                }
            }
        }

        return $items;
    }

    /**
     * Elimina la sección de respuesta del usuario en formato Outlook.
     * Detecta "Usted" o "You" como línea independiente seguida de una fecha.
     */
    private function removeOutlookReplySection(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];

        for ($i = 0; $i < count($lines); $i++) {
            $trimmed = trim($lines[$i]);

            if (preg_match(self::OUTLOOK_REPLY_PATTERN, $trimmed)) {
                // Verify next non-empty line looks like a date/time pattern
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $nextLine = trim($lines[$j]);
                    if ($nextLine === '') {
                        continue;
                    }
                    // Outlook date patterns
                    if (preg_match('/^(?:Lun|Mar|Mi[eé]|Jue|Vie|S[aá]b|Dom|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s+\d{1,2}\/\d{1,2}\/\d{2,4}/i', $nextLine)
                        || preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}/i', $nextLine)) {
                        // Found reply section - return everything before it
                        return implode("\n", $result);
                    }
                    break;
                }
            }

            $result[] = $lines[$i];
        }

        return implode("\n", $result);
    }

    /**
     * Generate a task with subtasks from list items.
     *
     * @param  array<int, array{title: string, raw: string}>  $listItems
     */
    private function generateTaskWithSubtasks(array $listItems, ParsingContext $context): ExtractionResult
    {
        $subtasks = [];

        foreach ($listItems as $item) {
            $estimatedMinutes = $this->detectDuration($item['raw']) ?? self::DEFAULT_MINUTES;

            $subtasks[] = [
                'title' => $item['title'],
                'priority' => 'medium',
                'estimated_minutes' => $estimatedMinutes,
            ];
        }

        $totalMinutes = array_sum(array_column($subtasks, 'estimated_minutes'));
        $estimatedHours = round($totalMinutes / 60, 2);

        $title = $this->deriveTaskTitle($context);

        $tasks = [
            [
                'title' => $title,
                'description' => null,
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_minutes' => $totalMinutes,
                'estimated_hours' => $estimatedHours,
                'subtasks' => $subtasks,
            ],
        ];

        return new ExtractionResult(
            fieldName: 'tasks',
            value: $tasks,
            confidence: 85,
        );
    }

    /**
     * Generate a single task when no list of actions is found.
     */
    private function generateSingleTask(ParsingContext $context): ExtractionResult
    {
        $title = $this->deriveTaskTitle($context);

        $tasks = [
            [
                'title' => $title,
                'description' => null,
                'type' => 'regular',
                'priority' => 'medium',
                'estimated_minutes' => self::DEFAULT_MINUTES,
                'estimated_hours' => round(self::DEFAULT_MINUTES / 60, 2),
                'subtasks' => [],
            ],
        ];

        return new ExtractionResult(
            fieldName: 'tasks',
            value: $tasks,
            confidence: 60,
        );
    }

    /**
     * Derive the task title from context (subject or first meaningful sentence).
     */
    private function deriveTaskTitle(ParsingContext $context): string
    {
        // Try email subject first
        if (! empty($context->emailHeaders)) {
            $subject = $context->emailHeaders['Asunto'] ?? $context->emailHeaders['Subject'] ?? null;
            if ($subject !== null && mb_strlen(trim($subject)) >= 10) {
                return $this->truncateTitle(trim($subject));
            }
        }

        // Fall back to first meaningful sentence from messageBody or normalizedText
        $text = $context->messageBody ?? $context->normalizedText;

        return $this->truncateTitle($this->extractFirstSentence($text));
    }

    /**
     * Extract the first meaningful sentence (10+ chars) from text.
     */
    private function extractFirstSentence(string $text): string
    {
        $lines = preg_split('/\r?\n/', $text);

        // Common greeting patterns to skip
        $greetingPatterns = [
            '/^\s*(hola|buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?|estimad[oa]s?|queridos?|hi|hello|hey|dear|good\s+morning|good\s+afternoon)/iu',
        ];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (mb_strlen($trimmed) < 10) {
                continue;
            }

            // Skip greetings
            $isGreeting = false;
            foreach ($greetingPatterns as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $isGreeting = true;
                    break;
                }
            }

            if ($isGreeting) {
                continue;
            }

            // Skip list items themselves
            if (preg_match(self::LIST_PATTERN, $trimmed)) {
                continue;
            }

            return $trimmed;
        }

        // If nothing found, return first non-empty line
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (mb_strlen($trimmed) > 0) {
                return $trimmed;
            }
        }

        return 'Tarea generada automáticamente';
    }

    /**
     * Truncate title to max 255 characters, cutting at last space before limit.
     */
    private function truncateTitle(string $title): string
    {
        if (mb_strlen($title) <= self::MAX_TITLE_LENGTH) {
            return $title;
        }

        $truncated = mb_substr($title, 0, self::MAX_TITLE_LENGTH);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            return mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated;
    }

    /**
     * Detect explicit duration in a line of text.
     * Returns duration in minutes (clamped to 5-480) or null if not found.
     */
    private function detectDuration(string $text): ?int
    {
        // Try hour patterns first (they produce larger values)
        foreach (self::DURATION_PATTERNS as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = (int) $matches[1];

                if ($value <= 0) {
                    continue;
                }

                $isHours = $this->isHourPattern($pattern);
                $minutes = $isHours ? $value * 60 : $value;

                return $this->clampMinutes($minutes);
            }
        }

        return null;
    }

    /**
     * Determine if a pattern matches hours (vs minutes).
     */
    private function isHourPattern(string $pattern): bool
    {
        return str_contains($pattern, 'hora') || str_contains($pattern, 'hrs') || (str_contains($pattern, 'h\\b') && ! str_contains($pattern, 'min'));
    }

    /**
     * Clamp minutes to the allowed range [5, 480].
     */
    private function clampMinutes(int $minutes): int
    {
        return max(self::MIN_MINUTES, min(self::MAX_MINUTES, $minutes));
    }
}
