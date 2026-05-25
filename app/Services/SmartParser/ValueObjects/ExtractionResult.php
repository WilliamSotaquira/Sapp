<?php

namespace App\Services\SmartParser\ValueObjects;

class ExtractionResult
{
    public function __construct(
        public readonly string $fieldName,
        public readonly mixed $value,
        public readonly int $confidence,
        public readonly bool $extracted = true,
    ) {}

    /**
     * Crea un resultado vacío para un campo que no pudo ser extraído.
     */
    public static function empty(string $fieldName): self
    {
        return new self(
            fieldName: $fieldName,
            value: null,
            confidence: 0,
            extracted: false,
        );
    }
}
