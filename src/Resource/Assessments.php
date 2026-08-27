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
    public function listTasks(string $assessment, ?array $params = null): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->get("/assessments/{$key}/tasks/", $params);
    }

    /** @param array<string, mixed> $args */
    public function attachTasks(string $assessment, array $args): mixed
    {
        if ($args === []) {
            throw new ApiException('attachTasks() requires tasks or task_id', 'INVALID_ARGUMENT');
        }
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->post("/assessments/{$key}/tasks/attach/", $args);
    }

    /**
     * @param list<array<string, mixed>> $tasks
     * @param array<string, mixed> $extra
     */
    public function replaceTasks(string $assessment, array $tasks, array $extra = []): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->put("/assessments/{$key}/tasks/replace/", array_merge(['tasks' => $tasks], $extra));
    }

    public function removeTask(string $assessment, string $assessmentTaskId): mixed
    {
        $key = Client::pathSegment($assessment, 'assessment');
        $taskId = trim($assessmentTaskId);
        if ($taskId === '') {
            throw new ApiException('assessmentTaskId must be a non-empty string', 'INVALID_ARGUMENT');
        }
        return $this->client->delete("/assessments/{$key}/tasks/remove/", ['assessment_task_id' => $taskId]);
    }
}
