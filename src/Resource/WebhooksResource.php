<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;
use Praxicraft\Assess\Exception\ApiException;

final class WebhooksResource
{
    public function __construct(private readonly Client $client)
    {
    }

    /** @param array<string, mixed>|null $params */
    public function list(?array $params = null): mixed
    {
        return $this->client->get('/webhooks/', $params);
    }

    /** @param array<string, mixed> $args */
    public function create(array $args): mixed
    {
        if (!isset($args['url']) || trim((string) $args['url']) === '') {
            throw new ApiException('url is required', 'INVALID_ARGUMENT');
        }
        if (!isset($args['events']) || !is_array($args['events']) || $args['events'] === []) {
            throw new ApiException('events must be a non-empty list', 'INVALID_ARGUMENT');
        }
        return $this->client->post('/webhooks/create/', $args);
    }

    public function retrieve(string $webhookId): mixed
    {
        $key = Client::pathSegment($webhookId, 'webhookId');
        return $this->client->get("/webhooks/{$key}/");
    }

    /** @param array<string, mixed> $fields */
    public function update(string $webhookId, array $fields): mixed
    {
        if ($fields === []) {
            throw new ApiException('update() requires at least one field to change', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($webhookId, 'webhookId');
        return $this->client->patch("/webhooks/{$key}/", $fields);
    }

    public function delete(string $webhookId): mixed
    {
        $key = Client::pathSegment($webhookId, 'webhookId');
        return $this->client->delete("/webhooks/{$key}/");
    }

    public function deliveries(string $webhookId): mixed
    {
        $key = Client::pathSegment($webhookId, 'webhookId');
        return $this->client->get("/webhooks/{$key}/deliveries/");
    }

    public function test(string $webhookId): mixed
    {
        $key = Client::pathSegment($webhookId, 'webhookId');
        return $this->client->post("/webhooks/{$key}/test/");
    }
}
