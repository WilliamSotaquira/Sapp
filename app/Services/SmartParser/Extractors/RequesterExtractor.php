<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class RequesterExtractor implements FieldExtractorInterface
{
    /**
     * Patrones para extraer el campo "De:" o "From:" de un correo electrónico.
     * Captura el contenido después del encabezado.
     */
    private const EMAIL_FROM_PATTERNS = [
        '/^(?:De|From)\s*:\s*(.+)$/im',
    ];

    /**
     * Patrón para detectar formato Outlook de remitente: "Nombre<email>" o "Nombre <email>"
     * sin prefijo "De:" — común al copiar desde el panel de lectura de Outlook.
     */
    private const OUTLOOK_SENDER_PATTERN = '/^([\p{L}\p{M}\s.\'-]+?)\s*<([\w.\-+]+@[\w.\-]+\.\w+)>$/u';

    /**
     * Patrón para detectar la sección de respuesta del usuario en formato Outlook.
     * "Usted" o "You" seguido de una línea con fecha/hora.
     */
    private const OUTLOOK_REPLY_PATTERN = '/^Usted$|^You$/m';

    /**
     * Patrones de mensajes de WhatsApp para extraer el nombre del contacto.
     *
     * Formato con corchetes: [DD/MM/AAAA, HH:MM] Contacto: mensaje
     * Formato con guión: DD/MM/AAAA HH:MM - Contacto: mensaje
     */
    private const WHATSAPP_MESSAGE_PATTERNS = [
        // [DD/MM/AAAA, HH:MM] Contacto: mensaje
        '/\[(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s*(\d{1,2}:\d{2}(?::\d{2})?)\]\s*([^:\[\]]+):\s*(.+)/m',
        // DD/MM/AAAA HH:MM - Contacto: mensaje
        '/^(\d{1,2}\/\d{1,2}\/\d{2,4})\s+(\d{1,2}:\d{2}(?::\d{2})?)\s*-\s*([^:\-]+):\s*(.+)/m',
        // DD/MM/AA, HH:MM - Contacto: mensaje
        '/^(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s*(\d{1,2}:\d{2}(?::\d{2})?)\s*-\s*([^:\-]+):\s*(.+)/m',
    ];

    public function extract(ParsingContext $context): ExtractionResult
    {
        $channel = $context->detectedChannel;

        // Decide extraction strategy based on detected channel
        if ($channel === 'email_corporativo') {
            return $this->extractFromEmail($context);
        }

        if ($channel === 'whatsapp') {
            return $this->extractFromWhatsApp($context);
        }

        // Unknown channel: try heuristic extraction
        return $this->extractHeuristic($context);
    }

    /**
     * Extrae el nombre del remitente desde el campo "De:" o "From:" del correo.
     * Soporta formatos:
     * - "Nombre Apellido <email@example.com>"
     * - "email@example.com"
     * - "Nombre Apellido"
     * - "Nombre Apellido<email@example.com>" (Outlook paste, sin "De:")
     */
    private function extractFromEmail(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText;

        // First, remove the reply section to avoid picking up "Usted" as requester
        $textWithoutReply = $this->removeReplySection($text);

        foreach (self::EMAIL_FROM_PATTERNS as $pattern) {
            if (preg_match($pattern, $textWithoutReply, $matches)) {
                $fromValue = trim($matches[1]);
                $parsed = $this->parseEmailFromField($fromValue);

                if ($parsed['name'] !== '') {
                    // Store email headers in context
                    $this->extractEmailHeaders($context);

                    return new ExtractionResult(
                        fieldName: 'requester',
                        value: $parsed,
                        confidence: $parsed['email'] !== null ? 90 : 75,
                    );
                }
            }
        }

        // Try Outlook sender format: "Name<email>" or "Name <email>" on its own line
        $outlookResult = $this->extractOutlookSender($textWithoutReply);
        if ($outlookResult !== null) {
            $this->extractEmailHeaders($context);

            return new ExtractionResult(
                fieldName: 'requester',
                value: $outlookResult,
                confidence: 85,
            );
        }

        // Fallback: try first non-empty line after subject
        $fallbackName = $this->extractNameAfterSubject($textWithoutReply);
        if ($fallbackName !== null) {
            $this->extractEmailHeaders($context);

            return new ExtractionResult(
                fieldName: 'requester',
                value: ['name' => $fallbackName, 'email' => null],
                confidence: 50,
            );
        }

        // No requester found - leave empty per requirement 2.6
        return ExtractionResult::empty('requester');
    }

    /**
     * Extrae el nombre del contacto desde el primer mensaje de WhatsApp.
     */
    private function extractFromWhatsApp(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText;
        $messages = [];

        foreach (self::WHATSAPP_MESSAGE_PATTERNS as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $messages[] = [
                        'timestamp' => trim($match[1]) . ' ' . trim($match[2]),
                        'contact' => trim($match[3]),
                        'message' => trim($match[4]),
                    ];
                }
                break; // Use the first pattern that matches
            }
        }

        // Store parsed WhatsApp messages in context
        $context->whatsappMessages = $messages;

        if (empty($messages)) {
            return ExtractionResult::empty('requester');
        }

        // Extract contact name from the first message
        $contactName = $messages[0]['contact'];
        $contactName = $this->cleanContactName($contactName);

        if ($contactName === '') {
            return ExtractionResult::empty('requester');
        }

        return new ExtractionResult(
            fieldName: 'requester',
            value: ['name' => $contactName, 'email' => null],
            confidence: 80,
        );
    }

    /**
     * Extracción heurística por bloques de texto cuando no hay formato reconocido.
     * Busca patrones que parezcan nombres de persona en las primeras líneas.
     */
    private function extractHeuristic(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText;

        // Remove reply section before analysis
        $textWithoutReply = $this->removeReplySection($text);

        $lines = array_filter(
            explode("\n", $textWithoutReply),
            fn (string $line) => trim($line) !== ''
        );
        $lines = array_values($lines);

        if (empty($lines)) {
            return ExtractionResult::empty('requester');
        }

        // Strategy 1: Look for "De:" or "From:" anywhere in the text (even without full email format)
        foreach (self::EMAIL_FROM_PATTERNS as $pattern) {
            if (preg_match($pattern, $textWithoutReply, $matches)) {
                $fromValue = trim($matches[1]);
                $parsed = $this->parseEmailFromField($fromValue);

                if ($parsed['name'] !== '') {
                    return new ExtractionResult(
                        fieldName: 'requester',
                        value: $parsed,
                        confidence: 60,
                    );
                }
            }
        }

        // Strategy 2: Try Outlook sender format: "Name<email>" or "Name <email>"
        $outlookResult = $this->extractOutlookSender($textWithoutReply);
        if ($outlookResult !== null) {
            return new ExtractionResult(
                fieldName: 'requester',
                value: $outlookResult,
                confidence: 85,
            );
        }

        // Strategy 3: Look for name-like patterns in the first few lines
        $nameLikePattern = '/^([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+){1,3})$/u';

        $linesToCheck = array_slice($lines, 0, min(5, count($lines)));
        foreach ($linesToCheck as $line) {
            $line = trim($line);
            // Skip greetings
            if ($this->isGreeting($line)) {
                continue;
            }
            // Check if line looks like a name (2-4 capitalized words)
            if (preg_match($nameLikePattern, $line, $matches)) {
                $candidateName = $matches[1];
                if (mb_strlen($candidateName) >= 3 && mb_strlen($candidateName) <= 100) {
                    return new ExtractionResult(
                        fieldName: 'requester',
                        value: ['name' => $candidateName, 'email' => null],
                        confidence: 40,
                    );
                }
            }
        }

        // No name found - leave empty per requirement 2.7
        return ExtractionResult::empty('requester');
    }

    /**
     * Extrae el remitente en formato Outlook: "Nombre<email>" o "Nombre <email>"
     * sin prefijo "De:". Busca en las primeras líneas del texto.
     *
     * @return array{name: string, email: string}|null
     */
    private function extractOutlookSender(string $text): ?array
    {
        $lines = explode("\n", $text);
        // Only check the first 10 lines for the sender pattern
        $linesToCheck = array_slice($lines, 0, min(10, count($lines)));

        foreach ($linesToCheck as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match(self::OUTLOOK_SENDER_PATTERN, $trimmed, $matches)) {
                $name = $this->cleanName(trim($matches[1]));
                $email = trim($matches[2]);

                if ($name !== '' && $email !== '') {
                    return ['name' => $name, 'email' => $email];
                }
            }
        }

        return null;
    }

    /**
     * Elimina la sección de respuesta del usuario en formato Outlook.
     * Detecta el patrón "Usted" o "You" seguido de una línea con fecha/hora
     * y elimina todo desde ese punto en adelante.
     */
    private function removeReplySection(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];

        for ($i = 0; $i < count($lines); $i++) {
            $trimmed = trim($lines[$i]);

            // Check for "Usted" or "You" as a standalone line (Outlook reply marker)
            if (preg_match('/^(Usted|You)$/i', $trimmed)) {
                // Verify next non-empty line looks like a date/time pattern
                $nextLineIndex = $this->findNextNonEmptyLine($lines, $i + 1);
                if ($nextLineIndex !== null) {
                    $nextLine = trim($lines[$nextLineIndex]);
                    // Outlook date patterns: "Vie 22/05/2026 1:39 PM", "Mon 22/05/2026 1:39 PM", etc.
                    if (preg_match('/^(?:Lun|Mar|Mi[eé]|Jue|Vie|S[aá]b|Dom|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s+\d{1,2}\/\d{1,2}\/\d{2,4}/i', $nextLine)
                        || preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}/', $nextLine)) {
                        // Found reply section - return everything before it
                        return implode("\n", $result);
                    }
                }
            }

            $result[] = $lines[$i];
        }

        return implode("\n", $result);
    }

    /**
     * Finds the next non-empty line index starting from a given position.
     *
     * @param string[] $lines
     */
    private function findNextNonEmptyLine(array $lines, int $startIndex): ?int
    {
        for ($i = $startIndex; $i < count($lines); $i++) {
            if (trim($lines[$i]) !== '') {
                return $i;
            }
        }

        return null;
    }

    /**
     * Parsea el valor del campo "De:" para extraer nombre y email.
     *
     * Formatos soportados:
     * - "Nombre Apellido <email@example.com>"
     * - "<email@example.com>"
     * - "email@example.com"
     * - "Nombre Apellido"
     * - '"Nombre Apellido" <email@example.com>'
     */
    private function parseEmailFromField(string $fromValue): array
    {
        $name = '';
        $email = null;

        // Format: "Name <email>" or Name <email>
        if (preg_match('/^["\']?(.+?)["\']?\s*<([^>]+)>/', $fromValue, $matches)) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");
            $email = trim($matches[2]);
        }
        // Format: <email> only
        elseif (preg_match('/^<([^>]+)>$/', $fromValue, $matches)) {
            $email = trim($matches[1]);
            // Use the part before @ as name fallback
            $name = $this->nameFromEmail($email);
        }
        // Format: just an email address
        elseif (preg_match('/^[\w.\-+]+@[\w.\-]+\.\w+$/', $fromValue)) {
            $email = $fromValue;
            $name = $this->nameFromEmail($fromValue);
        }
        // Format: just a name (no email)
        else {
            $name = trim($fromValue);
        }

        // Clean up the name
        $name = $this->cleanName($name);

        return ['name' => $name, 'email' => $email];
    }

    /**
     * Derives a display name from an email address.
     * E.g., "juan.perez@example.com" → "Juan Perez"
     */
    private function nameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0] ?? '';
        // Replace dots, underscores, hyphens with spaces
        $name = str_replace(['.', '_', '-', '+'], ' ', $localPart);
        // Capitalize each word
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return trim($name);
    }

    /**
     * Cleans a name string by removing unwanted characters and trimming.
     */
    private function cleanName(string $name): string
    {
        // Remove quotes
        $name = trim($name, "\"' \t\n\r\0\x0B");
        // Remove any remaining angle brackets
        $name = preg_replace('/<[^>]*>/', '', $name) ?? $name;
        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        // Trim to max 255 characters
        $name = mb_substr(trim($name), 0, 255);

        return $name;
    }

    /**
     * Cleans a WhatsApp contact name.
     */
    private function cleanContactName(string $name): string
    {
        $name = trim($name);
        // Remove phone number patterns (just digits and +)
        if (preg_match('/^[\d\s\+\-\(\)]+$/', $name)) {
            // It's just a phone number, not a name
            return $name; // Still return it as it's the identifier
        }

        return mb_substr($name, 0, 255);
    }

    /**
     * Extracts the first non-empty line after the subject line as a fallback name.
     */
    private function extractNameAfterSubject(string $text): ?string
    {
        $lines = explode("\n", $text);
        $foundSubject = false;
        $passedHeaders = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Look for subject line
            if (! $foundSubject && preg_match('/^(?:Asunto|Subject)\s*:/i', $trimmed)) {
                $foundSubject = true;

                continue;
            }

            // After subject, skip empty lines and remaining headers
            if ($foundSubject) {
                if ($trimmed === '') {
                    $passedHeaders = true;

                    continue;
                }

                // Skip if it looks like another header
                if (preg_match('/^(?:De|From|Para|To|Fecha|Date|CC|Cc|CCO|Bcc)\s*:/i', $trimmed)) {
                    continue;
                }

                // If we've passed the headers section and found a non-empty line
                if ($passedHeaders && $this->looksLikeName($trimmed)) {
                    return mb_substr($trimmed, 0, 255);
                }
            }
        }

        return null;
    }

    /**
     * Checks if a line looks like a person's name.
     */
    private function looksLikeName(string $line): bool
    {
        // Must be between 3 and 60 characters (names are short)
        $len = mb_strlen($line);
        if ($len < 3 || $len > 60) {
            return false;
        }

        // Should not contain typical non-name characters or sentence punctuation
        if (preg_match('/[<>@\[\]{}|\\\\\/,;:!?()]/', $line)) {
            return false;
        }

        // Should not end with a period (sentences do, names don't)
        if (str_ends_with(trim($line), '.')) {
            return false;
        }

        // Should start with an uppercase letter
        if (! preg_match('/^[\p{Lu}]/u', $line)) {
            return false;
        }

        // Should be mostly letters and spaces (allow accents, hyphens, apostrophes)
        $cleanLength = mb_strlen(preg_replace('/[^\p{L}\s\'\-]/u', '', $line) ?? '');

        return $cleanLength >= ($len * 0.8);
    }

    /**
     * Checks if a line is a greeting (to be skipped during heuristic extraction).
     */
    private function isGreeting(string $line): bool
    {
        $greetings = [
            '/^(?:hola|hello|hi|hey|buenos?\s*(?:días|tardes|noches)|good\s*(?:morning|afternoon|evening))/iu',
            '/^(?:estimad[oa]s?|queridos?|dear)/iu',
            '/^(?:buen\s*día|saludos)/iu',
        ];

        foreach ($greetings as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts email headers from the text and stores them in the context.
     */
    private function extractEmailHeaders(ParsingContext $context): void
    {
        $text = $context->normalizedText;
        $headers = [];

        $headerPatterns = [
            'de' => '/^(?:De|From)\s*:\s*(.+)$/im',
            'para' => '/^(?:Para|To)\s*:\s*(.+)$/im',
            'asunto' => '/^(?:Asunto|Subject)\s*:\s*(.+)$/im',
            'fecha' => '/^(?:Fecha|Date)\s*:\s*(.+)$/im',
            'cc' => '/^(?:CC|Cc)\s*:\s*(.+)$/im',
        ];

        foreach ($headerPatterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $headers[$key] = trim($matches[1]);
            }
        }

        $context->emailHeaders = $headers;
    }
}
