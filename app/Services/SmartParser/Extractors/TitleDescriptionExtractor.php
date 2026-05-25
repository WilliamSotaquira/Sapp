<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class TitleDescriptionExtractor implements FieldExtractorInterface
{
    private const MAX_TITLE_LENGTH = 255;

    private const MAX_DESCRIPTION_LENGTH = 5000;

    /**
     * Patrones de firma que indican el inicio de una firma de correo.
     */
    private const SIGNATURE_PATTERNS = [
        '/^--\s*$/m',
        '/^Regards,/im',
        '/^Best regards,/im',
        '/^Kind regards,/im',
        '/^Saludos,/im',
        '/^Atentamente,/im',
        '/^Cordialmente,/im',
    ];

    /**
     * Patrones que indican el inicio de un mensaje anterior en un hilo.
     */
    private const THREAD_MARKER_PATTERNS = [
        '/^---+\s*$/m',
        '/^From\s*:/im',
        '/^De\s*:/im',
        '/^El\s+.+\s+escribi[oó]\s*:/im',
        '/^On\s+.+\s+wrote\s*:/im',
    ];

    /**
     * Patrón para detectar la sección de respuesta del usuario en formato Outlook.
     * "Usted" o "You" seguido de una línea con fecha/hora.
     */
    private const OUTLOOK_REPLY_MARKERS = [
        '/^(Usted|You)$/i',
    ];

    /**
     * Líneas de UI de cliente de correo que deben ignorarse al buscar título.
     */
    private const EMAIL_CLIENT_UI_PATTERNS = [
        '/^Resumir\s+este\s+correo/i',
        '/^Summarize\s+this\s+email/i',
        '/^Responder$/i',
        '/^Reply$/i',
        '/^Reenviar$/i',
        '/^Forward$/i',
        '/^Responder\s+a\s+todos$/i',
        '/^Reply\s+all$/i',
    ];

    /**
     * Patrón para detectar formato Outlook de remitente: "Nombre<email>" o "Nombre <email>"
     */
    private const OUTLOOK_SENDER_PATTERN = '/^[\p{L}\p{M}\s.\'-]+?\s*<[\w.\-+]+@[\w.\-]+\.\w+>$/u';

    /**
     * Marcadores de reenvío en el cuerpo del mensaje.
     */
    private const FORWARD_BODY_MARKERS = [
        '---------- Forwarded message ----------',
        '---------- Mensaje reenviado ----------',
        'Inicio del mensaje reenviado',
        'Begin forwarded message',
    ];

    /**
     * Prefijos de reenvío en el asunto.
     */
    private const FORWARD_SUBJECT_PREFIXES = [
        'Fwd:',
        'Fw:',
        'Rv:',
        'RV:',
    ];

    /**
     * Patrones de saludo que deben excluirse al buscar la primera oración.
     */
    private const GREETING_PATTERNS = [
        '/^(Hola|Buenos?\s+d[ií]as?|Buenas?\s+tardes?|Buenas?\s+noches?|Estimad[oa]s?|Queridos?|Hi|Hello|Dear|Good\s+morning|Good\s+afternoon|Good\s+evening)/i',
    ];

    /**
     * Patrones de encabezados de correo electrónico.
     */
    private const EMAIL_HEADER_PATTERNS = [
        '/^(De|From|Para|To|Asunto|Subject|Fecha|Date|CC|Cc|CCO|Bcc)\s*:/i',
    ];

    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = $context->normalizedText;

        // Remove Outlook reply section ("Usted\nFecha\n...") before processing
        $text = $this->removeOutlookReplySection($text);

        // Detectar si es un correo reenviado y extraer el contenido reenviado
        $text = $this->extractForwardedContent($text);

        // Eliminar líneas citadas (prefijo ">")
        $text = $this->removeQuotedLines($text);

        // Extraer solo el primer mensaje del hilo (antes de marcadores de respuesta)
        $text = $this->extractFirstMessageFromThread($text);

        // Eliminar firmas
        $text = $this->removeSignature($text);

        // Eliminar encabezados de correo del cuerpo
        $body = $this->removeEmailHeaders($text);

        // Limpiar el cuerpo
        $body = $this->cleanBody($body);

        // Extraer título
        $title = $this->extractTitle($context);

        // Extraer descripción
        $description = $this->extractDescription($body);

        // Almacenar el cuerpo limpio en el contexto para extractores posteriores
        $context->messageBody = $body;

        // Verificar si se pudo extraer al menos título o descripción
        if (empty($title) && empty($description)) {
            return ExtractionResult::empty('title_description');
        }

        $confidence = $this->calculateConfidence($title, $description, $context);

        return new ExtractionResult(
            fieldName: 'title_description',
            value: [
                'title' => $title,
                'description' => $description,
            ],
            confidence: $confidence,
        );
    }

    /**
     * Detecta si el texto contiene un correo reenviado y extrae el contenido original.
     */
    private function extractForwardedContent(string $text): string
    {
        // Buscar marcadores de reenvío en el cuerpo
        foreach (self::FORWARD_BODY_MARKERS as $marker) {
            $pos = mb_stripos($text, $marker);
            if ($pos !== false) {
                // Extraer todo después del marcador de reenvío
                $forwarded = mb_substr($text, $pos + mb_strlen($marker));
                $forwarded = ltrim($forwarded, "\r\n");

                if (mb_strlen(trim($forwarded)) > 0) {
                    return $forwarded;
                }
            }
        }

        return $text;
    }

    /**
     * Elimina líneas citadas (que comienzan con ">").
     */
    private function removeQuotedLines(string $text): string
    {
        $lines = explode("\n", $text);
        $filtered = [];

        foreach ($lines as $line) {
            // Eliminar líneas que comienzan con ">" (citado)
            if (preg_match('/^\s*>/', $line)) {
                continue;
            }
            $filtered[] = $line;
        }

        return implode("\n", $filtered);
    }

    /**
     * Extrae solo el primer mensaje del hilo, antes de cualquier marcador de respuesta anterior.
     */
    private function extractFirstMessageFromThread(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            // Verificar si la línea es un marcador de hilo
            if ($this->isThreadMarker($line)) {
                break;
            }
            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Determina si una línea es un marcador de hilo de respuesta.
     */
    private function isThreadMarker(string $line): bool
    {
        $trimmedLine = trim($line);

        foreach (self::THREAD_MARKER_PATTERNS as $pattern) {
            if (preg_match($pattern, $trimmedLine)) {
                // Excepción: no tratar "De:" o "From:" como marcador de hilo
                // si aparece al inicio del texto (es un encabezado del correo principal)
                return true;
            }
        }

        return false;
    }

    /**
     * Elimina la firma del correo.
     */
    private function removeSignature(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Verificar si la línea coincide con un patrón de firma
            foreach (self::SIGNATURE_PATTERNS as $pattern) {
                if (preg_match($pattern, $trimmedLine)) {
                    // Encontramos el inicio de la firma, devolver todo lo anterior
                    return implode("\n", $result);
                }
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Elimina encabezados de correo electrónico del texto.
     */
    private function removeEmailHeaders(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $inHeaders = true;

        foreach ($lines as $line) {
            if ($inHeaders) {
                // Si la línea es un encabezado de correo, la saltamos
                $isHeader = false;
                foreach (self::EMAIL_HEADER_PATTERNS as $pattern) {
                    if (preg_match($pattern, trim($line))) {
                        $isHeader = true;
                        break;
                    }
                }

                if ($isHeader) {
                    continue;
                }

                // Una línea vacía después de los encabezados marca el fin de la sección de headers
                if (trim($line) === '') {
                    $inHeaders = false;

                    continue;
                }

                // Si encontramos una línea no vacía que no es header, ya no estamos en headers
                $inHeaders = false;
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Limpia el cuerpo del mensaje eliminando espacios excesivos.
     */
    private function cleanBody(string $text): string
    {
        // Eliminar líneas vacías al inicio y final
        $text = trim($text);

        // Colapsar múltiples líneas vacías consecutivas a máximo una
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return $text;
    }

    /**
     * Extrae el título del mensaje.
     * Prioridad: 1) Línea de asunto, 2) Primera oración significativa.
     */
    private function extractTitle(ParsingContext $context): string
    {
        // Intentar extraer del asunto (Subject:/Asunto:)
        $subject = $this->extractSubjectLine($context);

        if ($subject !== null) {
            // Limpiar prefijos de reenvío/respuesta del asunto
            $subject = $this->cleanSubjectPrefixes($subject);

            return $this->truncateAtLastSpace($subject, self::MAX_TITLE_LENGTH);
        }

        // Si no hay asunto, buscar en emailHeaders del contexto
        if (! empty($context->emailHeaders['Asunto'] ?? $context->emailHeaders['Subject'] ?? null)) {
            $subject = $context->emailHeaders['Asunto'] ?? $context->emailHeaders['Subject'];
            $subject = $this->cleanSubjectPrefixes($subject);

            return $this->truncateAtLastSpace($subject, self::MAX_TITLE_LENGTH);
        }

        // Generar título desde la primera oración significativa
        return $this->generateTitleFromFirstSentence($context);
    }

    /**
     * Busca una línea de asunto en el texto raw.
     */
    private function extractSubjectLine(ParsingContext $context): ?string
    {
        $lines = explode("\n", $context->rawText);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^(Subject|Asunto)\s*:\s*(.+)$/i', $trimmed, $matches)) {
                $subject = trim($matches[2]);

                if (mb_strlen($subject) > 0) {
                    return $subject;
                }
            }
        }

        return null;
    }

    /**
     * Limpia prefijos de reenvío y respuesta del asunto.
     */
    private function cleanSubjectPrefixes(string $subject): string
    {
        // Eliminar prefijos Re:, RE:, Fwd:, Fw:, Rv:, RV: (pueden ser múltiples)
        $subject = preg_replace('/^(Re|RE|Fwd|Fw|Rv|RV)\s*:\s*/i', '', $subject);
        // Repetir para múltiples prefijos encadenados
        $subject = preg_replace('/^(Re|RE|Fwd|Fw|Rv|RV)\s*:\s*/i', '', $subject);

        return trim($subject);
    }

    /**
     * Genera un título a partir de la primera oración significativa del texto.
     * Excluye saludos, líneas de UI del cliente de correo, y líneas de remitente.
     *
     * Lógica mejorada:
     * - Si la primera línea significativa es corta (< 80 chars) y parece un asunto
     *   agregado por el usuario, se usa como título.
     * - Se saltan líneas de UI del cliente de correo (Resumir, Responder, etc.)
     * - Se saltan líneas que coinciden con el formato de remitente Outlook (Name<email>)
     * - Se saltan líneas que son solo un nombre de persona (línea de destinatario)
     */
    private function generateTitleFromFirstSentence(ParsingContext $context): string
    {
        $text = $context->rawText;

        // Remove Outlook reply section from raw text for title extraction
        $text = $this->removeOutlookReplySection($text);

        $lines = explode("\n", $text);
        $candidateTitle = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Saltar líneas vacías
            if ($trimmed === '') {
                continue;
            }

            // Saltar encabezados de correo
            foreach (self::EMAIL_HEADER_PATTERNS as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    continue 2;
                }
            }

            // Saltar líneas de UI del cliente de correo
            if ($this->isEmailClientUILine($trimmed)) {
                continue;
            }

            // Saltar líneas que coinciden con formato de remitente Outlook (Name<email>)
            if (preg_match(self::OUTLOOK_SENDER_PATTERN, $trimmed)) {
                continue;
            }

            // Saltar líneas que son solo un nombre de persona (2-4 palabras capitalizadas)
            if ($this->isStandalonePersonName($trimmed)) {
                continue;
            }

            // Saltar saludos
            $isGreeting = false;
            foreach (self::GREETING_PATTERNS as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $isGreeting = true;
                    break;
                }
            }

            if ($isGreeting) {
                continue;
            }

            // Si la línea es corta (< 80 chars) y parece un asunto agregado por el usuario
            // (no es una oración completa del cuerpo), usarla como título
            if ($candidateTitle === null && mb_strlen($trimmed) < 80 && mb_strlen($trimmed) >= 5) {
                // Check if it looks like a user-added subject (short, descriptive)
                // Not a greeting, not a signature, not a header — it's likely a subject
                $candidateTitle = $trimmed;

                continue;
            }

            // La línea debe tener al menos 10 caracteres
            if (mb_strlen($trimmed) < 10) {
                continue;
            }

            // If we already have a short candidate title, return it
            if ($candidateTitle !== null) {
                return $this->truncateAtLastSpace($candidateTitle, self::MAX_TITLE_LENGTH);
            }

            // Extraer la primera oración (hasta el primer punto, signo de exclamación o interrogación)
            $sentence = $this->extractFirstSentence($trimmed);

            if (mb_strlen($sentence) >= 10) {
                return $this->truncateAtLastSpace($sentence, self::MAX_TITLE_LENGTH);
            }
        }

        // If we have a candidate title from the loop, use it
        if ($candidateTitle !== null) {
            return $this->truncateAtLastSpace($candidateTitle, self::MAX_TITLE_LENGTH);
        }

        // Si no encontramos una oración adecuada, usar la primera línea no vacía con 10+ chars
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (mb_strlen($trimmed) >= 10) {
                return $this->truncateAtLastSpace($trimmed, self::MAX_TITLE_LENGTH);
            }
        }

        return '';
    }

    /**
     * Extrae la primera oración de un texto (hasta punto, ! o ?).
     */
    private function extractFirstSentence(string $text): string
    {
        // Buscar el primer terminador de oración
        if (preg_match('/^(.+?[.!?])\s/u', $text, $matches)) {
            return trim($matches[1]);
        }

        // Si no hay terminador, usar toda la línea
        return $text;
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

            // Check for Outlook reply markers
            foreach (self::OUTLOOK_REPLY_MARKERS as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    // Verify next non-empty line looks like a date/time pattern
                    $nextLineIndex = $this->findNextNonEmptyLineIndex($lines, $i + 1);
                    if ($nextLineIndex !== null) {
                        $nextLine = trim($lines[$nextLineIndex]);
                        // Outlook date patterns: "Vie 22/05/2026 1:39 PM", "22/05/2026", etc.
                        if (preg_match('/^(?:Lun|Mar|Mi[eé]|Jue|Vie|S[aá]b|Dom|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s+\d{1,2}\/\d{1,2}\/\d{2,4}/i', $nextLine)
                            || preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}/i', $nextLine)) {
                            // Found reply section - return everything before it
                            return implode("\n", $result);
                        }
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
    private function findNextNonEmptyLineIndex(array $lines, int $startIndex): ?int
    {
        for ($i = $startIndex; $i < count($lines); $i++) {
            if (trim($lines[$i]) !== '') {
                return $i;
            }
        }

        return null;
    }

    /**
     * Checks if a line is an email client UI element (Resumir, Responder, etc.)
     */
    private function isEmailClientUILine(string $line): bool
    {
        foreach (self::EMAIL_CLIENT_UI_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a line is a standalone person name (2-4 capitalized words, no punctuation).
     * Used to skip recipient name lines like "William Mauricio Sotaquira Garavito".
     */
    private function isStandalonePersonName(string $line): bool
    {
        // Must be between 5 and 60 characters
        $len = mb_strlen($line);
        if ($len < 5 || $len > 60) {
            return false;
        }

        // Should not contain typical non-name characters
        if (preg_match('/[<>@\[\]{}|\\\\\/;:!?()0-9]/', $line)) {
            return false;
        }

        // Must match pattern: 2-5 capitalized words (with accents allowed)
        if (preg_match('/^[\p{Lu}][\p{L}\p{M}]+(?:\s+[\p{Lu}][\p{L}\p{M}]+){1,4}$/u', $line)) {
            return true;
        }

        // Also match with Unicode zero-width characters (common in Outlook pastes)
        $cleanLine = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $line);
        $cleanLine = trim($cleanLine);
        if ($cleanLine !== $line && preg_match('/^[\p{Lu}][\p{L}\p{M}]+(?:\s+[\p{Lu}][\p{L}\p{M}]+){1,4}$/u', $cleanLine)) {
            return true;
        }

        return false;
    }

    /**
     * Extrae la descripción del cuerpo limpio del mensaje.
     */
    private function extractDescription(string $body): string
    {
        if (empty(trim($body))) {
            return '';
        }

        return $this->truncateAtLastSpace($body, self::MAX_DESCRIPTION_LENGTH);
    }

    /**
     * Trunca un texto al máximo de caracteres, cortando en el último espacio antes del límite.
     */
    private function truncateAtLastSpace(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxLength);

        // Buscar el último espacio antes del límite
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            return mb_substr($truncated, 0, $lastSpace);
        }

        // Si no hay espacio, cortar en el límite exacto
        return $truncated;
    }

    /**
     * Calcula el nivel de confianza basado en la calidad de la extracción.
     */
    private function calculateConfidence(string $title, string $description, ParsingContext $context): int
    {
        $confidence = 50;

        // Título extraído del asunto → alta confianza
        $subject = $this->extractSubjectLine($context);
        if ($subject !== null && ! empty($title)) {
            $confidence += 25;
        } elseif (! empty($title)) {
            $confidence += 10;
        }

        // Descripción con contenido sustancial
        if (mb_strlen($description) > 50) {
            $confidence += 15;
        } elseif (! empty($description)) {
            $confidence += 5;
        }

        return min(95, $confidence);
    }
}
