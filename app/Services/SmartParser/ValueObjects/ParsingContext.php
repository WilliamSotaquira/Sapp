<?php

namespace App\Services\SmartParser\ValueObjects;

class ParsingContext
{
    public string $rawText;

    public string $normalizedText;

    public int $companyId;

    public int $contractId;

    public ?string $detectedChannel = null;

    /** @var string[] */
    public array $lines = [];

    /** @var string[] */
    public array $blocks = [];

    /** @var array<string, string> Headers extraídos: De, Para, Asunto, Fecha */
    public array $emailHeaders = [];

    /** @var array<int, array{timestamp: string, contact: string, message: string}> Mensajes WhatsApp parseados */
    public array $whatsappMessages = [];

    /** Cuerpo limpio del mensaje principal */
    public ?string $messageBody = null;
}
