<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Exception;

class PraxicraftException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
