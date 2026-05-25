<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Contracts;

use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;

interface FieldExtractorInterface
{
    /**
     * Extrae el campo del texto proporcionado.
     *
     * @return ExtractionResult con valor extraído y nivel de confianza (0-100).
     */
    public function extract(ParsingContext $context): ExtractionResult;
}
