<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\LlmDescriptionGenerator;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class TitleDescriptionExtractor implements FieldExtractorInterface
{
    private const MAX_TITLE_LENGTH = 255;

    private const MAX_DESCRIPTION_LENGTH = 5000;

    public function __construct(
        private readonly LlmDescriptionGenerator $descriptionGenerator,
    ) {}

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
        '/^Ninguno\s+seleccionado$/i',
        '/^Ir\s+al\s+contenido$/i',
        '/^Recibidos$/i',
        '/^Vista\s+creada\s+con\s+IA$/i',
        '/^\d+\s+de\s+[\d.,]+$/u',
        '/lectores\s+de\s+pantalla/i',
        '/correo\s+de\s+bogot[aá]/i',
        // Outlook Calendar chrome
        '/^Aceptar$/i',
        '/^Rechazar$/i',
        '/^Tentativo$/i',
        '/^Chatear$/i',
        '/^\d{1,2}\s*(AM|PM)$/i',
        '/^le\s+ha\s+invitado$/i',
        '/^\d+\s+sin\s+respuesta$/i',
        '/^Reuni[oó]n\s+de\s+Microsoft\s+Teams$/i',
        '/^Unirse/i',
        '/^Id\.?\s+de\s+reuni[oó]n/i',
        '/^C[oó]digo\s+de\s+acceso/i',
        '/^\?Necesita\s+ayuda/i',
        '/^Para\s+organizadores/i',
        '/^Opciones\s+de\s+la\s+reuni[oó]n$/i',
        '/^Referencia\s+del\s+sistema$/i',
        '/^Si\s+la\s+solicitud\s+contenida/i',
        // Google Calendar chrome
        '/^Calendario$/i',
        '/^Reuniones$/i',
        '/^En\s+tu\s+Google\s+Calendar$/i',
        '/^No\s+hay\s+m[aá]s\s+eventos/i',
        '/^Seg[uú]n\s+este\s+correo/i',
        '/^[¿?]Correcto[?]?$/i',
        '/^Unirme\s+con\s+Google\s+Meet$/i',
        '/^Enlace\s+de\s+la\s+reuni[oó]n$/i',
        '/^Unirse\s+por\s+tel[eé]fono$/i',
        '/^M[aá]s\s+n[uú]meros\s+de\s+tel[eé]fono$/i',
        '/^S[ií]$/i',
        '/^No$/i',
        '/^Quiz[aá]s$/i',
        '/^M[aá]s\s+opciones$/i',
        '/^Invitaci[oó]n\s+de\s+Google\s+Calendar$/i',
        '/^Te\s+hemos\s+enviado\s+este\s+correo/i',
        '/^Si\s+reenv[ií]as\s+esta\s+invitaci[oó]n/i',
        '/^Un\s+archivo\s+adjunto/i',
        '/^Analizados\s+por\s+Gmail$/i',
        '/^Ver\s+toda\s+la\s+informaci[oó]n/i',
        '/^Responder\s+a\s+\w/i',
        '/^CAMBIAD[OA]$/i',
        '/^Adjuntos/i',
        // Meeting time patterns (e.g., "jue, 4 jun • 14:00 – 14:30")
        '/^(?:lun|mar|mi[eé]|jue|vie|s[aá]b|dom)[,.]?\s+\d{1,2}\s+\w+\s*[•·]\s*\d{1,2}:\d{2}/iu',
        // Calendar event metadata
        '/^Cu[aá]ndo$/i',
        '/^Invitados$/i',
        '/^Este\s+evento\s+se\s+ha\s+actualizad/i',
        '/^Se\s+ha\s+cambiad/i',
        // Gmail labels and tags
        '/^label:/i',
        // Outlook calendar time range (e.g., "Vie 5/06/2026, de 3:00 PM a 4:00 PM")
        '/^(?:lun|mar|mi[eé]|jue|vie|s[aá]b|dom)\s+\d{1,2}\/\d{1,2}\/\d{2,4},?\s+de\s+\d{1,2}:\d{2}/iu',
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
        // Saludo con nombre: "William buenos días", "María hola"
        '/^[\p{L}\p{M}]+\s+(buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?|hola|c[oó]mo\s+est[aá])/iu',
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

        // Eliminar chrome de webmail/calendario del cuerpo
        $body = $this->removeWebmailChrome($body);

        // Limpiar el cuerpo
        $body = $this->cleanBody($body);

        // Extraer título
        $title = $this->extractTitle($context);

        // Remove the title from the body if it appears as the first line (repeated subject)
        $body = $this->removeRepeatedTitleFromBody($body, $title);

        // Generar descripción: intentar IA primero, fallback al body limpio
        $description = $this->generateDescription($title, $body);

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
     * Prioridad:
     * 1) Línea de asunto explícita (Subject:/Asunto:)
     * 2) Línea con "Re:" (respuesta a un correo — el subject)
     * 3) Para email/reunión: la primera línea significativa corta (< 120 chars)
     *    que NO sea chrome de webmail, saludo, o nombre de persona
     * 4) Para WhatsApp: la primera oración significativa del cuerpo
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

        // Buscar una línea con "Re:" como subject (webmail Gmail thread copy)
        $reSubject = $this->extractReSubjectLine($context);
        if ($reSubject !== null) {
            return $this->truncateAtLastSpace($reSubject, self::MAX_TITLE_LENGTH);
        }

        // Generar título desde la primera oración significativa
        return $this->generateTitleFromFirstSentence($context);
    }

    /**
     * Busca una línea con prefijo "Re:", "Rv:", "Fwd:" que sea el subject del hilo.
     * Solo la acepta si hay una línea de fecha/nombre DESPUÉS (confirmando el formato de thread).
     */
    private function extractReSubjectLine(ParsingContext $context): ?string
    {
        $lines = explode("\n", $context->rawText);

        for ($i = 0; $i < count($lines); $i++) {
            $trimmed = trim($lines[$i]);

            // Skip empty or UI chrome lines
            if ($trimmed === '' || $this->isEmailClientUILine($trimmed)) {
                continue;
            }

            // Look for a line starting with Re:/Rv:/Fwd:
            if (preg_match('/^(Re|Rv|Fwd|Fw)\s*:\s*(.+)$/iu', $trimmed, $matches)) {
                $subject = trim($matches[2]);
                if (mb_strlen($subject) >= 5) {
                    return $this->cleanSubjectPrefixes($subject);
                }
            }
        }

        return null;
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

        // Eliminar prefijos de invitación de calendario
        $subject = preg_replace('/^(Invitaci[oó]n\s+actualizada|Invitaci[oó]n|Updated\s+invitation|Invitation)\s*:\s*/iu', '', $subject);

        // Eliminar metadata de fecha/hora al final: "jue 4 de jun de 2026 2pm - 2:30pm (COT) (Nombre)"
        $subject = preg_replace('/\s+(?:lun|mar|mi[eé]|jue|vie|s[aá]b|dom)\s+\d{1,2}\s+de\s+\w+\s+de\s+\d{4}\s+\d{1,2}(?::\d{2})?\s*(?:am|pm|AM|PM).*$/iu', '', $subject);

        return trim($subject);
    }

    /**
     * Genera un título a partir de la primera oración significativa del texto.
     * Excluye saludos, líneas de UI del cliente de correo, y líneas de remitente.
     *
     * Detecta y salta headers de Gmail (Nombre\nTimestamp\npara destinatarios)
     * para encontrar el contenido real del mensaje.
     */
    private function generateTitleFromFirstSentence(ParsingContext $context): string
    {
        $text = $context->rawText;

        // Remove Outlook reply section from raw text for title extraction
        $text = $this->removeOutlookReplySection($text);

        $lines = explode("\n", $text);
        $candidateTitle = null;

        for ($i = 0; $i < count($lines); $i++) {
            $trimmed = trim($lines[$i]);

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

            // Detectar header de Gmail: "Nombre" seguido de timestamp "HH:MM (hace X)"
            if ($this->isGmailSenderLine($trimmed, $lines, $i)) {
                continue;
            }

            // Saltar timestamps de Gmail: "6:52 (hace 4 horas)"
            if ($this->isGmailTimestampLine($trimmed)) {
                continue;
            }

            // Saltar líneas "para mí, Nombre, Nombre" (destinatarios Gmail)
            if (preg_match('/^para\s+(m[ií]|mi)\b/iu', $trimmed)) {
                continue;
            }

            // Saltar nombres de persona si ya hay un candidato
            if ($candidateTitle !== null && $this->isStandalonePersonName($trimmed)) {
                continue;
            }

            // Saltar saludos — si no hay candidato, seguir buscando en el contenido
            $isGreeting = false;
            foreach (self::GREETING_PATTERNS as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $isGreeting = true;
                    break;
                }
            }
            if ($isGreeting) {
                if ($candidateTitle !== null) {
                    return $this->truncateAtLastSpace($candidateTitle, self::MAX_TITLE_LENGTH);
                }
                continue;
            }

            // Si la línea es corta (< 120 chars) y no es chrome, puede ser subject o contenido
            if ($candidateTitle === null && mb_strlen($trimmed) < 120 && mb_strlen($trimmed) >= 5) {
                $candidateTitle = $this->cleanSubjectPrefixes($trimmed);
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

            // Use the full line as title
            return $this->truncateAtLastSpace($this->cleanSubjectPrefixes($trimmed), self::MAX_TITLE_LENGTH);
        }

        // If we have a candidate title from the loop, use it
        if ($candidateTitle !== null) {
            return $this->truncateAtLastSpace($candidateTitle, self::MAX_TITLE_LENGTH);
        }

        // Último recurso: primera línea con 10+ chars
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (mb_strlen($trimmed) >= 10) {
                return $this->truncateAtLastSpace($this->cleanSubjectPrefixes($trimmed), self::MAX_TITLE_LENGTH);
            }
        }

        return '';
    }

    /**
     * Detecta si una línea es el nombre del remitente en formato Gmail.
     * Se confirma si la siguiente non-empty line es un timestamp de Gmail.
     */
    private function isGmailSenderLine(string $line, array $lines, int $currentIndex): bool
    {
        if (!$this->isStandalonePersonName($line)) {
            return false;
        }

        // Check if the next non-empty line is a Gmail timestamp
        for ($j = $currentIndex + 1; $j < count($lines) && $j <= $currentIndex + 3; $j++) {
            $nextLine = trim($lines[$j] ?? '');
            if ($nextLine === '') {
                continue;
            }
            return $this->isGmailTimestampLine($nextLine);
        }

        return false;
    }

    /**
     * Detecta timestamps de Gmail: "6:52 (hace 4 horas)", "jue, 4 jun, 14:02 (hace 21 horas)"
     */
    private function isGmailTimestampLine(string $line): bool
    {
        if (preg_match('/\d{1,2}:\d{2}\s*\(hace\s+\d+/iu', $line)) {
            return true;
        }
        if (preg_match('/^(?:lun|mar|mi[eé]|jue|vie|s[aá]b|dom)[,.]\s+\d{1,2}\s+\w+[,.]\s+\d{1,2}:\d{2}/iu', $line)) {
            return true;
        }
        if (preg_match('/^\d{1,2}\s+\w+\s+\d{4},?\s+\d{1,2}:\d{2}/u', $line)) {
            return true;
        }
        return false;
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
     * Elimina líneas de chrome de webmail/calendario del cuerpo del mensaje.
     *
     * Strategy: identify and remove Gmail/Outlook headers, meeting metadata,
     * UI chrome, attendee lists, signatures, and calendar footers.
     * Keep only the actual message content.
     */
    private function removeWebmailChrome(string $body): string
    {
        $lines = explode("\n", $body);
        $filtered = [];
        $inCalendarFooter = false;
        $inCalendarMetadataSection = false;

        for ($i = 0; $i < count($lines); $i++) {
            $trimmed = trim($lines[$i]);

            // Si estamos en el footer de Google Calendar, cortar todo lo que sigue
            if ($inCalendarFooter) {
                continue;
            }

            // Si estamos en la sección de metadata post-mensaje (Cuándo, Invitados, etc.)
            if ($inCalendarMetadataSection) {
                continue;
            }

            // Detectar inicio de footer de Google Calendar / disclaimers
            if ($this->isCalendarFooterStart($trimmed)) {
                $inCalendarFooter = true;
                continue;
            }

            // Detectar inicio de sección de metadata post-mensaje
            if ($this->isPostMessageMetadataStart($trimmed)) {
                $inCalendarMetadataSection = true;
                continue;
            }

            // Filtrar líneas de UI chrome
            if ($this->isEmailClientUILine($trimmed)) {
                continue;
            }

            // Filtrar Gmail sender header: "Nombre Apellido" followed by timestamp
            if ($this->isGmailSenderInBody($trimmed, $lines, $i)) {
                continue;
            }

            // Filtrar líneas de metadata de reunión (links, PINs, IDs, timestamps, recipients)
            if ($this->isMeetingMetadataLine($trimmed)) {
                continue;
            }

            // Filtrar líneas que son repeticiones del título de la invitación
            if ($this->isInvitationSubjectRepetition($trimmed)) {
                continue;
            }

            // Filtrar líneas de invitados/organizador
            if ($this->isAttendeeMetadataLine($trimmed)) {
                continue;
            }

            // Filtrar saludos y despedidas comunes (no son contenido de la solicitud)
            if ($this->isGreetingOrClosingLine($trimmed)) {
                continue;
            }

            // Filtrar firmas cortas (nombre solo al final, típicamente 1-3 palabras capitalizadas)
            if ($this->isLikelySignatureName($trimmed, $lines, $i)) {
                continue;
            }

            $filtered[] = $lines[$i];
        }

        return implode("\n", $filtered);
    }

    /**
     * Detecta si una línea es el sender de Gmail en el body
     * (nombre de persona seguido de timestamp en la siguiente línea).
     */
    private function isGmailSenderInBody(string $line, array $lines, int $index): bool
    {
        $trimmed = trim($line);

        // Must look like a person name (2-5 capitalized words, short)
        if (mb_strlen($trimmed) < 3 || mb_strlen($trimmed) > 60) {
            return false;
        }
        if (preg_match('/[<>@\[\]{}|\\\\\/;:!?()0-9]/', $trimmed)) {
            return false;
        }
        if (!preg_match('/^[\p{Lu}][\p{L}\p{M}]+(?:\s+[\p{Lu}][\p{L}\p{M}]+){0,4}$/u', $trimmed)) {
            return false;
        }

        // Check if next non-empty line is a Gmail timestamp
        for ($j = $index + 1; $j < count($lines) && $j <= $index + 2; $j++) {
            $nextLine = trim($lines[$j] ?? '');
            if ($nextLine === '') {
                continue;
            }
            if (preg_match('/\d{1,2}:\d{2}\s*\(hace\s+\d+/iu', $nextLine)) {
                return true;
            }
            break;
        }

        return false;
    }

    /**
     * Detecta saludos y despedidas comunes que no son contenido de la solicitud.
     */
    private function isGreetingOrClosingLine(string $line): bool
    {
        $lower = mb_strtolower(trim($line));

        // Saludos
        if (preg_match('/^(hola|buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?|estimad[oa]s?|queridos?)/iu', $lower)) {
            return true;
        }
        // Saludo con nombre: "william buenos días, cómo está..."
        if (preg_match('/^[\p{L}\p{M}]+\s+(buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?|hola|c[oó]mo\s+est[aá])/iu', $lower)) {
            return true;
        }

        // Despedidas y cierres
        $closings = [
            'mil gracias',
            'muchas gracias',
            'gracias de antemano',
            'quedo atento',
            'quedo atenta',
            'quedamos atentos',
            'cordialmente',
            'atentamente',
            'saludos',
        ];
        foreach ($closings as $closing) {
            if (str_starts_with($lower, $closing)) {
                return true;
            }
        }

        // "Espero que todo muy bien", "Espero se encuentre bien"
        if (preg_match('/^espero\s+que\s+(todo|se)/iu', $lower)) {
            return true;
        }

        return false;
    }

    /**
     * Detecta nombres que probablemente son firmas al final del mensaje.
     * Un nombre corto (2-4 palabras capitalizadas) sin texto significativo después.
     */
    private function isLikelySignatureName(string $line, array $lines, int $index): bool
    {
        $trimmed = trim($line);

        // Must look like a person name
        if (mb_strlen($trimmed) < 3 || mb_strlen($trimmed) > 60) {
            return false;
        }
        if (!preg_match('/^[\p{Lu}][\p{L}\p{M}]+(?:\s+[\p{Lu}][\p{L}\p{M}]+){0,3}$/u', $trimmed)) {
            return false;
        }

        // Check if there's no meaningful content after this line (it's at/near the end)
        $remainingContent = 0;
        for ($j = $index + 1; $j < count($lines); $j++) {
            $nextLine = trim($lines[$j] ?? '');
            if ($nextLine !== '' && mb_strlen($nextLine) > 10) {
                $remainingContent++;
            }
        }

        // If there's very little content after this name, it's likely a signature
        return $remainingContent <= 2;
    }

    /**
     * Detecta si una línea marca el inicio del footer de calendario/disclaimers.
     * Todo lo que sigue después de estas líneas es irrelevante para la descripción.
     */
    private function isCalendarFooterStart(string $line): bool
    {
        $lower = mb_strtolower($line);

        // Google Calendar footer markers
        if (str_contains($lower, 'invitación de google calendar') || str_contains($lower, 'invitacion de google calendar')) {
            return true;
        }
        if (str_contains($lower, 'te hemos enviado este correo')) {
            return true;
        }
        if (str_contains($lower, 'si reenvías esta invitación') || str_contains($lower, 'si reenvias esta invitacion')) {
            return true;
        }

        // Outlook disclaimer
        if (str_contains($lower, 'si la solicitud contenida en el presente mensaje')) {
            return true;
        }

        // Generic footer markers (line of underscores)
        if (preg_match('/^_{10,}$/u', trim($line))) {
            return true;
        }

        return false;
    }

    /**
     * Detecta el inicio de la sección de metadata post-mensaje en invitaciones de calendario.
     * Ejemplo: "Cuándo", "When" — seguido de fecha, lista de invitados, botones de respuesta.
     */
    private function isPostMessageMetadataStart(string $line): bool
    {
        $lower = mb_strtolower(trim($line));

        return $lower === 'cuándo'
            || $lower === 'cuando'
            || $lower === 'when'
            || $lower === 'invitados'
            || $lower === 'attendees';
    }

    /**
     * Detecta líneas de metadata de reunión que no son contenido del mensaje:
     * links de conferencia, PINs, IDs, metadata de invitación, headers de Gmail.
     */
    private function isMeetingMetadataLine(string $line): bool
    {
        $lower = mb_strtolower(trim($line));
        $trimmed = trim($line);

        // Gmail sender header: timestamp "HH:MM (hace X horas/días)"
        if (preg_match('/\d{1,2}:\d{2}\s*\(hace\s+\d+/iu', $trimmed)) {
            return true;
        }

        // Gmail recipient line: "para mí, Nombre, Nombre"
        if (preg_match('/^para\s+(m[ií]|mi)\b/iu', $trimmed)) {
            return true;
        }

        // Meeting links
        if (preg_match('/^(https?:\/\/)?(teams\.microsoft\.com|meet\.google\.com)\//i', $trimmed)) {
            return true;
        }

        // PIN / ID patterns
        if (preg_match('/^(PIN|Id\.?\s+de\s+reuni[oó]n|C[oó]digo\s+de\s+acceso)\s*:/iu', $trimmed)) {
            return true;
        }

        // Phone join patterns (e.g., "(CO) +57 601 8957380")
        if (preg_match('/^\(\w{2,}\)\s*\+?\d[\d\s\-]+$/u', $trimmed)) {
            return true;
        }

        // Date/time metadata lines from Gmail (e.g., "4 jun 2026, 14:02 (hace 21 horas)")
        if (preg_match('/^\d{1,2}\s+\w+\s+\d{4},?\s+\d{1,2}:\d{2}\s*\(/u', $trimmed)) {
            return true;
        }

        // Truncated filenames from attachments section (e.g., "260206_Actualizaciones...")
        if (preg_match('/^[\w\d_\-]+\.{3}$/u', $trimmed)) {
            return true;
        }

        // Specific meeting chrome keywords
        $meetingChrome = [
            'unirme con google meet',
            'enlace de la reunión',
            'enlace de la reunion',
            'unirse por teléfono',
            'unirse por telefono',
            'más números de teléfono',
            'mas numeros de telefono',
            'reunión de microsoft teams',
            'reunion de microsoft teams',
            '¿necesita ayuda?',
            'necesita ayuda',
            'referencia del sistema',
            'para organizadores:',
            'opciones de la reunión',
            'opciones de la reunion',
            'ver toda la información de los invitados',
            'ver toda la informacion de los invitados',
            'un archivo adjunto',
            'analizados por gmail',
            'archivos adjuntos',
            'este evento se ha actualizado',
            'se ha cambiado:',
            'no hay más eventos',
            'no hay mas eventos',
            'según este correo',
            'segun este correo',
            '¿correcto?',
        ];

        foreach ($meetingChrome as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta líneas que son repeticiones del título de invitación (subject repetido en el body).
     * Ejemplo: "Invitación actualizada: [Virtual] Seguimiento..."
     */
    private function isInvitationSubjectRepetition(string $line): bool
    {
        $trimmed = trim($line);

        // Lines starting with calendar invite prefixes
        if (preg_match('/^(Invitaci[oó]n\s+actualizada|Invitaci[oó]n|Updated\s+invitation|Invitation)\s*:/iu', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Detecta líneas de metadata de asistentes en invitaciones de calendario.
     * Ejemplo: "Laura Estefany Lopez Cubides - Organizador"
     * Ejemplo: "Andres Felipe Marmolejo Lopez y Luis Felipe Jaramillo Giraldo"
     */
    private function isAttendeeMetadataLine(string $line): bool
    {
        $trimmed = trim($line);

        // "Nombre - Organizador/organizador"
        if (preg_match('/^[\p{L}\p{M}\s.\'-]+\s*-\s*[Oo]rganizador[a]?$/u', $trimmed)) {
            return true;
        }

        // "Nombre Apellido <email>" — sender/organizer line
        if (preg_match('/^[\p{L}\p{M}\s.\'-]+\s*<[\w.\-+]+@[\w.\-]+\.\w+>$/u', $trimmed)) {
            return true;
        }

        // "Nombre y Nombre" (multiple attendees on one line, with "y" connector and no other content)
        if (preg_match('/^[\p{L}\p{M}\s]+\s+y\s+[\p{L}\p{M}\s]+$/u', $trimmed) && mb_strlen($trimmed) <= 80) {
            return true;
        }

        return false;
    }

    /**
     * Genera la descripción de la solicitud.
     * Prioridad: 1) Generación por IA (si habilitada), 2) Body limpio como fallback.
     */
    private function generateDescription(string $title, string $cleanBody): string
    {
        // Intentar generar descripción con IA
        $aiDescription = $this->descriptionGenerator->generate($title, $cleanBody);

        if ($aiDescription !== null && mb_strlen($aiDescription) >= 10) {
            return $aiDescription;
        }

        // Fallback: usar el body limpio directamente
        return $this->extractDescription($cleanBody);
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
     * Removes the title from the beginning of the body if it's repeated there.
     * Common in webmail where the subject line appears again in the body preview.
     */
    private function removeRepeatedTitleFromBody(string $body, string $title): string
    {
        if (empty($title) || empty(trim($body))) {
            return $body;
        }

        $lines = explode("\n", $body);
        $normalizedTitle = mb_strtolower(trim($title));

        // Check first few non-empty lines for title repetition
        $linesToCheck = 3;
        $checked = 0;

        for ($i = 0; $i < count($lines) && $checked < $linesToCheck; $i++) {
            $trimmedLine = trim($lines[$i]);
            if ($trimmedLine === '') {
                continue;
            }
            $checked++;

            $normalizedLine = mb_strtolower($trimmedLine);

            // Exact match or the line contains the title (subject repeated in body)
            if ($normalizedLine === $normalizedTitle || str_contains($normalizedLine, $normalizedTitle)) {
                $lines[$i] = '';
            }
        }

        return trim(implode("\n", $lines));
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
