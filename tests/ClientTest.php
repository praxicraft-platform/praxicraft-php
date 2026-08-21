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
}
