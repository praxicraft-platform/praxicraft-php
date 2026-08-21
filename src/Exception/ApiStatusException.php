<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Exception;

class ApiStatusException extends ApiException
{
    /** @param array<string, string> $headers */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        ?string $errorCode = null,
        public readonly mixed $details = null,
        public readonly mixed $responseBody = null,
        public readonly array $headers = [],
        public readonly ?string $requiredPlan = null,
        public readonly ?float $retryAfter = null,
    ) {
        parent::__construct($message, $errorCode, $statusCode);
    }
}
