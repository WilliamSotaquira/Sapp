<?php

namespace App\Services\SmartParser\Exceptions;

use RuntimeException;

/**
 * Thrown when the smart parser pipeline exceeds the maximum allowed execution time.
 */
class ParsingTimeoutException extends RuntimeException
{
    public function __construct(int $maxSeconds = 30)
    {
        parent::__construct(
            "La interpretación excedió el tiempo límite permitido ({$maxSeconds} segundos)."
        );
    }
}
