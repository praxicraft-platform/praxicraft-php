<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Tests;

use PHPUnit\Framework\TestCase;
use Praxicraft\Assess\Client;
use Praxicraft\Assess\Exception\AuthenticationException;

final class ClientTest extends TestCase
{
    public function testOrgRetrieveUsesMockHandler(): void
    {
        $calls = [];
        $client = new Client([
            'apiKey' => 'ct_test_x',
            'httpHandler' => function (string $method, string $url, array $headers, ?string $body) use (&$calls) {
                $calls[] = [$method, $url, $headers, $body];
                return [
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode(['name' => 'Acme', 'plan' => 'starter'], JSON_THROW_ON_ERROR),
                ];
            },
        ]);

        $org = $client->org->retrieve();
        $this->assertSame('Acme', $org['name']);
        $this->assertSame('GET', $calls[0][0]);
        $this->assertStringContainsString('/api/v1/public/org/', $calls[0][1]);
        $this->assertSame('Bearer ct_test_x', $calls[0][2]['Authorization']);
    }

    public function testMapsAuthenticationError(): void
    {
        $client = new Client([
            'apiKey' => 'ct_test_x',
            'maxRetries' => 0,
            'httpHandler' => fn () => [
                'status' => 401,
                'headers' => [],
                'body' => json_encode(['error' => ['code' => 'INVALID_API_KEY', 'message' => 'bad']], JSON_THROW_ON_ERROR),
            ],
        ]);

        $this->expectException(AuthenticationException::class);
        $client->org->retrieve();
    }

    public function testAssessmentTaskPathsAndBodyKeys(): void
    {
        $calls = [];
        $client = new Client([
            'apiKey' => 'ct_test_x',
            'httpHandler' => function (string $method, string $url, array $headers, ?string $body) use (&$calls) {
                $calls[] = [$method, $url, $body !== null ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null];
                if (str_ends_with($url, '/tasks/attach/')) {
                    return ['status' => 200, 'headers' => [], 'body' => json_encode(['attached' => 1], JSON_THROW_ON_ERROR)];
                }
                if (str_ends_with($url, '/tasks/remove/')) {
                    return ['status' => 204, 'headers' => [], 'body' => ''];
                }

                return ['status' => 200, 'headers' => [], 'body' => json_encode(['results' => [['id' => 'row-1']]], JSON_THROW_ON_ERROR)];
            },
        ]);

        $client->assessments->attachTasks('demo', [
            'tasks' => [['task_id' => 'task-1', 'source' => 'platform']],
        ]);
        $client->assessments->listTasks('demo');
        $client->assessments->removeTask('demo', 'row-1');

        $this->assertSame('POST', $calls[0][0]);
        $this->assertStringContainsString('/assessments/demo/tasks/attach/', $calls[0][1]);
        $this->assertSame(['tasks' => [['task_id' => 'task-1', 'source' => 'platform']]], $calls[0][2]);

        $this->assertSame('GET', $calls[1][0]);
        $this->assertStringContainsString('/assessments/demo/tasks/', $calls[1][1]);

        $this->assertSame('DELETE', $calls[2][0]);
        $this->assertStringContainsString('/assessments/demo/tasks/remove/', $calls[2][1]);
        $this->assertSame(['assessment_task_id' => 'row-1'], $calls[2][2]);
    }
}
