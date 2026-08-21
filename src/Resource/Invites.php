<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;
use Praxicraft\Assess\Exception\ApiException;

final class Invites
{
    public function __construct(private readonly Client $client)
    {
    }

    /** @param array<string, mixed>|null $params */
    public function list(?array $params = null): mixed
    {
        return $this->client->get('/invites/', $params);
    }

    public function retrieve(string $inviteToken): mixed
    {
        $token = Client::pathSegment($inviteToken, 'inviteToken');
        return $this->client->get("/invites/{$token}/");
    }

    /** @param array<string, mixed> $args */
    public function create(string $assessment, array $args): mixed
    {
        if (!isset($args['email']) || trim((string) $args['email']) === '') {
            throw new ApiException('email is required', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->post("/assessments/{$key}/invites/", $args);
    }

    /**
     * @param list<array<string, mixed>> $candidates
     * @param array<string, mixed> $args
     */
    public function bulkCreate(string $assessment, array $candidates, array $args = []): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        $body = array_merge(['candidates' => $candidates], $args);
        return $this->client->post("/assessments/{$key}/invites/bulk/", $body);
    }

    public function remind(string $inviteToken): mixed
    {
        $token = Client::pathSegment($inviteToken, 'inviteToken');
        return $this->client->post("/invites/{$token}/remind/");
    }

    public function cancel(string $inviteToken): mixed
    {
        $token = Client::pathSegment($inviteToken, 'inviteToken');
        return $this->client->delete("/invites/{$token}/");
    }
}
