<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Resource;

use Praxicraft\Assess\Client;

final class Results
{
    private const MAX_RESULT_PAGES = 10000;

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @param array{cursor?: string, page_size?: int, params?: array<string, mixed>} $args
     */
    public function list(string $assessment, array $args = []): mixed
    {
        $query = $args['params'] ?? [];
        if (isset($args['cursor'])) {
            $query['cursor'] = $args['cursor'];
        }
        if (isset($args['page_size'])) {
            $query['page_size'] = $args['page_size'];
        }
        $key = Client::pathSegment($assessment, 'assessment');
        return $this->client->get("/assessments/{$key}/results/", $query);
    }

    public function retrieve(string $inviteToken): mixed
    {
        $token = Client::pathSegment($inviteToken, 'inviteToken');
        return $this->client->get("/invites/{$token}/result/");
    }

    /**
     * @param array{page_size?: int, params?: array<string, mixed>} $args
     * @return \Generator<int, mixed>
     */
    public function iterAll(string $assessment, array $args = []): \Generator
    {
        $cursor = null;
        $seen = [];

        for ($i = 0; $i < self::MAX_RESULT_PAGES; $i++) {
            $pageArgs = $args;
            if ($cursor !== null) {
                $pageArgs['cursor'] = $cursor;
            }
            $page = $this->list($assessment, $pageArgs);
            if (!is_array($page)) {
                return;
            }
            if (isset($page['results']) && is_array($page['results'])) {
                foreach ($page['results'] as $row) {
                    yield $row;
                }
            }
            $next = self::nextCursor($page);
            if ($next === null || isset($seen[$next])) {
                return;
            }
            $seen[$next] = true;
            $cursor = $next;
        }
    }

    /** @param array<string, mixed> $page */
    private static function nextCursor(array $page): ?string
    {
        if (isset($page['next_cursor']) && is_string($page['next_cursor']) && $page['next_cursor'] !== '') {
            return $page['next_cursor'];
        }
        $nextLink = $page['next'] ?? null;
        if (!is_string($nextLink) || $nextLink === '') {
            return null;
        }
        $parts = parse_url($nextLink);
        if (!isset($parts['query'])) {
            return null;
        }
        parse_str($parts['query'], $q);
        $c = $q['cursor'] ?? null;
        return is_string($c) && $c !== '' ? $c : null;
    }
}
