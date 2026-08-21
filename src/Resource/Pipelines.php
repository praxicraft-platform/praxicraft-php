<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;
use Praxicraft\Assess\Exception\ApiException;

final class Pipelines
{
    public function __construct(private readonly Client $client)
    {
    }

    /** @param array<string, mixed>|null $params */
    public function list(?array $params = null): mixed
    {
        return $this->client->get('/pipelines/', $params);
    }

    public function retrieve(string $pipeline): mixed
    {
        $key = Client::pathSegment($pipeline, 'pipeline');
        return $this->client->get("/pipelines/{$key}/");
    }

    /** @param array<string, mixed> $args */
    public function enroll(string $pipeline, array $args): mixed
    {
        if (!isset($args['email']) || trim((string) $args['email']) === '') {
            throw new ApiException('email is required', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($pipeline, 'pipeline');
        return $this->client->post("/pipelines/{$key}/enroll/", $args);
    }

    /**
     * @param list<array<string, mixed>> $candidates
     * @param array<string, mixed> $args
     */
    public function bulkEnroll(string $pipeline, array $candidates, array $args = []): mixed
    {
        $key = Client::pathSegment($pipeline, 'pipeline');
        $body = array_merge(['candidates' => $candidates], $args);
        return $this->client->post("/pipelines/{$key}/enroll/bulk/", $body);
    }

    /** @param array<string, mixed>|null $params */
    public function listEnrollments(string $pipeline, ?array $params = null): mixed
    {
        $key = Client::pathSegment($pipeline, 'pipeline');
        return $this->client->get("/pipelines/{$key}/enrollments/", $params);
    }

    public function getEnrollment(string $enrollmentId): mixed
    {
        $key = Client::pathSegment($enrollmentId, 'enrollmentId');
        return $this->client->get("/pipelines/enrollments/{$key}/");
    }
}
