<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;
use Praxicraft\Assess\Exception\ApiException;

final class Assessments
{
    public function __construct(private readonly Client $client)
    {
    }

    /** @param array<string, mixed>|null $params */
    public function list(?array $params = null): mixed
    {
        return $this->client->get('/assessments/', $params);
    }

    public function retrieve(string $assessment): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->get("/assessments/{$key}/");
    }

    /** @param array<string, mixed> $fields */
    public function create(array $fields): mixed
    {
        return $this->client->post('/assessments/create/', $fields);
    }

    /** @param array<string, mixed> $fields */
    public function update(string $assessment, array $fields): mixed
    {
        if ($fields === []) {
            throw new ApiException('update() requires at least one field to change', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->patch("/assessments/{$key}/update/", $fields);
    }

    public function activate(string $assessment): mixed
    {
        return $this->update($assessment, ['status' => 'active']);
    }

    /** @param array<string, mixed>|null $params */
    public function listCases(string $assessment, ?array $params = null): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->get("/assessments/{$key}/cases/", $params);
    }

    /** @param array<string, mixed> $args */
    public function attachCases(string $assessment, array $args): mixed
    {
        if ($args === []) {
            throw new ApiException('attachCases() requires cases or case_id', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->post("/assessments/{$key}/cases/attach/", $args);
    }

    /**
     * @param list<array<string, mixed>> $cases
     * @param array<string, mixed> $extra
     */
    public function replaceCases(string $assessment, array $cases, array $extra = []): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->put("/assessments/{$key}/cases/replace/", array_merge(['cases' => $cases], $extra));
    }

    public function removeCase(string $assessment, string $assessmentCaseId): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        $caseId = trim($assessmentCaseId);
        if ($caseId === '') {
            throw new ApiException('assessmentCaseId must be a non-empty string', 'INVALID_ARGUMENT');
        }
        return $this->client->delete("/assessments/{$key}/cases/remove/", ['assessment_case_id' => $caseId]);
    }
}
