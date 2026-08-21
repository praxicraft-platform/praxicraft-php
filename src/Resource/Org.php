<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;

final class Org
{
    public function __construct(private readonly Client $client)
    {
    }

    public function retrieve(): mixed
    {
        return $this->client->get('/org/');
    }

    /** @param array<string, mixed>|null $params */
    public function stats(?array $params = null): mixed
    {
        return $this->client->get('/org/stats/', $params);
    }
}
