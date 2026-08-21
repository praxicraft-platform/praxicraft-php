<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Exception;

class ApiConnectionException extends ApiException
{
    public function __construct(string $message = 'Failed to connect to the Praxicraft API.')
    {
        parent::__construct($message, 'CONNECTION_ERROR');
    }
}
