<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

class ChannelDetector implements FieldExtractorInterface
{
    /**
     * Patrones de encabezados de correo electrónico.
     * Cada patrón detecta una línea que comienza con un encabezado típico de email.
     */
    private const EMAIL_HEADER_PATTERNS = [
        '/^(De|From)\s*:/im',
        '/^(Para|To)\s*:/im',
        '/^(Asunto|Subject)\s*:/im',
        '/^(Fecha|Date)\s*:/im',
        '/^(CC|Cc|CCO|Bcc)\s*:/im',
    ];

    /**
     * Patrones de mensajes de WhatsApp.
     *
     * Formato con corchetes: [DD/MM/AAAA, HH:MM] Contacto:
     * Formato con guión: DD/MM/AAAA HH:MM - Contacto:
     * Formato corto: DD/MM/AA, HH:MM - Contacto:
     */
    private const WHATSAPP_PATTERNS = [
        // [DD/MM/AAAA, HH:MM] Contacto:
        '/\[\d{1,2}\/\d{1,2}\/\d{2,4},?\s*\d{1,2}:\d{2}\]\s*[^:\[\]]+:/m',
        // DD/MM/AAAA HH:MM - Contacto:
        '/^\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}\s*-\s*[^:\-]+:/m',
        // DD/MM/AA, HH:MM - Contacto:
        '/^\d{1,2}\/\d{1,2}\/\d{2,4},?\s*\d{1,2}:\d{2}\s*-\s*[^:\-]+:/m',
    ];

    public function extract(ParsingContext $context): ExtractionResult
    {
        $text = $context->rawText;

        $emailMatchCount = $this->countEmailMatches($text);
        $whatsappMatchCount = $this->countWhatsAppMatches($text);

        // Determinar canal según reglas de negocio
        $channel = $this->resolveChannel($emailMatchCount, $whatsappMatchCount);
        $confidence = $this->calculateConfidence($emailMatchCount, $whatsappMatchCount, $channel);

        // Enriquecer el contexto para extractores posteriores
        $context->detectedChannel = $channel;

        return new ExtractionResult(
            fieldName: 'channel',
            value: $channel,
            confidence: $confidence,
        );
    }

    /**
     * Cuenta el número de encabezados de correo electrónico encontrados en el texto.
     */
    private function countEmailMatches(string $text): int
    {
        $count = 0;

        foreach (self::EMAIL_HEADER_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cuenta el número de líneas que coinciden con patrones de WhatsApp.
     */
    private function countWhatsAppMatches(string $text): int
    {
        $count = 0;

        foreach (self::WHATSAPP_PATTERNS as $pattern) {
            $matches = [];
            preg_match_all($pattern, $text, $matches);
            $count += count($matches[0]);
        }

        return $count;
    }

    /**
     * Resuelve el canal según las reglas de negocio:
     * - 2+ encabezados de email → email_corporativo
     * - Patrones WhatsApp → whatsapp
     * - Ambos presentes → el que tenga más coincidencias
     * - Sin coincidencias → email_corporativo (por defecto)
     */
    private function resolveChannel(int $emailMatchCount, int $whatsappMatchCount): string
    {
        $isEmail = $emailMatchCount >= 2;
        $isWhatsApp = $whatsappMatchCount > 0;

        // Sin coincidencias: valor por defecto
        if (! $isEmail && ! $isWhatsApp) {
            return 'email_corporativo';
        }

        // Solo email
        if ($isEmail && ! $isWhatsApp) {
            return 'email_corporativo';
        }

        // Solo WhatsApp
        if (! $isEmail && $isWhatsApp) {
            return 'whatsapp';
        }

        // Ambos presentes: resolver por mayor número de coincidencias
        if ($emailMatchCount >= $whatsappMatchCount) {
            return 'email_corporativo';
        }

        return 'whatsapp';
    }

    /**
     * Calcula el nivel de confianza basado en la cantidad de patrones detectados.
     */
    private function calculateConfidence(int $emailMatchCount, int $whatsappMatchCount, string $channel): int
    {
        // Sin coincidencias: confianza baja (es el valor por defecto)
        if ($emailMatchCount < 2 && $whatsappMatchCount === 0) {
            return 30;
        }

        if ($channel === 'email_corporativo') {
            // Más encabezados = más confianza (2 headers = 70, 3 = 80, 4+ = 90, 5 = 95)
            return min(95, 60 + ($emailMatchCount * 10));
        }

        // WhatsApp: más mensajes = más confianza
        if ($whatsappMatchCount === 1) {
            return 70;
        }

        if ($whatsappMatchCount === 2) {
            return 80;
        }

        return min(95, 70 + ($whatsappMatchCount * 5));
    }
}
