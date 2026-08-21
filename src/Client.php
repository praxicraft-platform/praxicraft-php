<?php

declare(strict_types=1);

namespace Praxicraft\Assess;

use Praxicraft\Assess\Exception\ApiConnectionException;
use Praxicraft\Assess\Exception\ApiException;
use Praxicraft\Assess\Exception\ApiStatusException;
use Praxicraft\Assess\Exception\AuthenticationException;
use Praxicraft\Assess\Exception\InsufficientScopeException;
use Praxicraft\Assess\Exception\NotFoundException;
use Praxicraft\Assess\Exception\RateLimitException;
use Praxicraft\Assess\Exception\ValidationException;
use Praxicraft\Assess\Resource\Assessments;
use Praxicraft\Assess\Resource\Invites;
use Praxicraft\Assess\Resource\Org;
use Praxicraft\Assess\Resource\Pipelines;
use Praxicraft\Assess\Resource\Results;
use Praxicraft\Assess\Resource\WebhooksResource;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://assess.praxicraft.com';
    public const DEFAULT_API_PREFIX = '/api/v1/public';
    public const DEFAULT_TIMEOUT_SECONDS = 30.0;
    public const DEFAULT_MAX_RETRIES = 2;

    public readonly string $apiKey;
    public readonly string $baseUrl;
    public readonly string $apiPrefix;
    public readonly float $timeoutSeconds;
    public readonly int $maxRetries;

    public readonly Org $org;
    public readonly Assessments $assessments;
    public readonly Invites $invites;
    public readonly Results $results;
    public readonly WebhooksResource $webhooks;
    public readonly Pipelines $pipelines;

    /** @var callable|null fn(string $method, string $url, array $headers, ?string $body): array{status:int,headers:array<string,string>,body:string} */
    private $httpHandler;

    /**
     * @param array{
     *   apiKey?: string,
     *   baseUrl?: string,
     *   timeoutSeconds?: float,
     *   maxRetries?: int,
     *   httpHandler?: callable
     * } $options
     */
    public function __construct(array $options = [])
    {
        $key = trim((string) ($options['apiKey'] ?? getenv('PRAXICRAFT_API_KEY') ?: ''));
        if ($key === '') {
            throw new ApiException('No API key provided. Pass apiKey or set PRAXICRAFT_API_KEY.', 'MISSING_API_KEY');
        }

        $base = trim((string) ($options['baseUrl'] ?? getenv('PRAXICRAFT_API_BASE_URL') ?: self::DEFAULT_BASE_URL));
        $base = rtrim($base, '/');
        if ($base === '') {
            throw new ApiException('baseUrl must be a non-empty URL.', 'INVALID_BASE_URL');
        }

        $this->apiKey = $key;
        $this->baseUrl = $base;
        $this->apiPrefix = self::DEFAULT_API_PREFIX;
        $this->timeoutSeconds = (float) ($options['timeoutSeconds'] ?? self::DEFAULT_TIMEOUT_SECONDS);
        $this->maxRetries = max(0, (int) ($options['maxRetries'] ?? self::DEFAULT_MAX_RETRIES));
        $this->httpHandler = $options['httpHandler'] ?? null;

        $this->org = new Org($this);
        $this->assessments = new Assessments($this);
        $this->invites = new Invites($this);
        $this->results = new Results($this);
        $this->webhooks = new WebhooksResource($this);
        $this->pipelines = new Pipelines($this);
    }

    /** @param array<string, mixed>|null $params @param array<string, mixed>|null $json */
    public function get(string $path, ?array $params = null): mixed
    {
        return $this->request('GET', $path, $params, null);
    }

    /** @param array<string, mixed>|null $json */
    public function post(string $path, ?array $json = null): mixed
    {
        return $this->request('POST', $path, null, $json);
    }

    /** @param array<string, mixed>|null $json */
    public function put(string $path, ?array $json = null): mixed
    {
        return $this->request('PUT', $path, null, $json);
    }

    /** @param array<string, mixed>|null $json */
    public function patch(string $path, ?array $json = null): mixed
    {
        return $this->request('PATCH', $path, null, $json);
    }

    /** @param array<string, mixed>|null $json */
    public function delete(string $path, ?array $json = null): mixed
    {
        return $this->request('DELETE', $path, null, $json);
    }

    public static function pathSegment(string $value, string $label = 'id'): string
    {
        $text = trim($value);
        if ($text === '') {
            throw new ApiException("{$label} must be a non-empty string", 'INVALID_PATH');
        }

        return rawurlencode($text);
    }

    /**
     * @param array<string, mixed>|null $params
     * @param array<string, mixed>|null $json
     */
    public function request(string $method, string $path, ?array $params = null, ?array $json = null): mixed
    {
        $attempts = $this->maxRetries + 1;
        $lastError = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            if ($attempt > 0) {
                $retryAfter = null;
                if ($lastError instanceof ApiStatusException) {
                    $retryAfter = $lastError->headers['retry-after'] ?? null;
                }
                usleep((int) (self::retryDelayMs($attempt - 1, $retryAfter) * 1000));
            }

            try {
                return $this->requestOnce($method, $path, $params, $json);
            } catch (ApiConnectionException $e) {
                $lastError = $e;
                if ($attempt < $attempts - 1) {
                    continue;
                }
                throw $e;
            } catch (ApiStatusException $e) {
                $lastError = $e;
                if (self::shouldRetryStatus($e->statusCode) && $attempt < $attempts - 1) {
                    continue;
                }
                throw $e;
            }
        }

        throw $lastError ?? new ApiConnectionException();
    }

    /**
     * @param array<string, mixed>|null $params
     * @param array<string, mixed>|null $json
     */
    private function requestOnce(string $method, string $path, ?array $params, ?array $json): mixed
    {
        $url = $this->baseUrl . $this->apiPrefix . $path;
        if ($params) {
            $query = http_build_query(self::flattenParams($params));
            if ($query !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?') . $query;
            }
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'User-Agent' => 'praxicraft-php/' . Version::STRING,
        ];

        $body = null;
        if ($json !== null) {
            $body = json_encode($json, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] = 'application/json';
        }

        try {
            $response = $this->httpHandler
                ? ($this->httpHandler)($method, $url, $headers, $body)
                : $this->curlRequest($method, $url, $headers, $body);
        } catch (ApiConnectionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiConnectionException($e->getMessage());
        }

        $status = (int) $response['status'];
        $respHeaders = $response['headers'];
        $raw = $response['body'];

        $decoded = null;
        if ($raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                if ($status >= 200 && $status < 300) {
                    throw new ApiException(
                        "Invalid JSON response (HTTP {$status}).",
                        'INVALID_JSON',
                    );
                }
                $decoded = $raw;
            }
        }

        if ($status >= 200 && $status < 300) {
            return $decoded;
        }

        self::raiseForStatus($status, $decoded, $respHeaders, $raw);
    }

    /**
     * @param array<string, string> $headers
     * @return array{status:int,headers:array<string,string>,body:string}
     */
    private function curlRequest(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new ApiConnectionException('Unable to initialize cURL');
        }

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) ceil($this->timeoutSeconds),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $len = strlen($line);
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new ApiConnectionException($err !== '' ? $err : 'cURL request failed');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'headers' => $responseHeaders, 'body' => (string) $raw];
    }

    /** @param array<string, mixed> $params @return array<string, scalar> */
    private static function flattenParams(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if ($v === null) {
                continue;
            }
            if (is_bool($v)) {
                $out[$k] = $v ? 'true' : 'false';
            } elseif (is_scalar($v)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private const RETRY_BASE_MS = 500.0;
    private const RETRY_CAP_MS = 8000.0;

    private static function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    private static function parseRetryAfterSeconds(?string $retryAfter): ?float
    {
        if ($retryAfter === null || $retryAfter === '') {
            return null;
        }
        $text = trim($retryAfter);
        if ($text === '') {
            return null;
        }
        if (is_numeric($text)) {
            return max(0.0, (float) $text);
        }
        $ts = strtotime($text);
        if ($ts === false) {
            return null;
        }

        return max(0.0, (float) ($ts - time()));
    }

    private static function retryDelayMs(int $retryIndex, ?string $retryAfter): float
    {
        $parsed = self::parseRetryAfterSeconds($retryAfter);
        if ($parsed !== null) {
            return min($parsed * 1000.0, self::RETRY_CAP_MS);
        }
        $ceiling = min(self::RETRY_CAP_MS, self::RETRY_BASE_MS * (2 ** $retryIndex));

        return (float) (mt_rand() / mt_getrandmax() * $ceiling);
    }

    /**
     * @param array<string, string> $headers
     * @return never
     */
    private static function raiseForStatus(int $statusCode, mixed $body, array $headers, string $raw): void
    {
        $error = [];
        if (is_array($body) && isset($body['error']) && is_array($body['error'])) {
            $error = $body['error'];
        }

        $code = isset($error['code']) && is_string($error['code']) ? $error['code'] : null;
        $message = isset($error['message']) && is_string($error['message']) ? $error['message'] : null;
        $details = $error['details'] ?? null;
        $requiredPlan = isset($error['required_plan']) && is_string($error['required_plan'])
            ? $error['required_plan']
            : null;

        if ($message === null || $message === '') {
            $message = trim($raw) !== '' ? substr(trim($raw), 0, 500) : "API request failed with status {$statusCode}.";
        }

        $retryAfter = self::parseRetryAfterSeconds($headers['retry-after'] ?? null);

        $args = [
            $message,
            $statusCode,
            $code,
            $details,
            $body,
            $headers,
            $requiredPlan,
            $retryAfter,
        ];

        if ($statusCode === 401) {
            throw new AuthenticationException(...$args);
        }
        if ($statusCode === 403) {
            throw new InsufficientScopeException(...$args);
        }
        if ($statusCode === 404) {
            throw new NotFoundException(...$args);
        }
        if ($statusCode === 429) {
            throw new RateLimitException(...$args);
        }
        if ($statusCode >= 400 && $statusCode < 500) {
            throw new ValidationException(...$args);
        }

        throw new ApiStatusException(...$args);
    }
}
