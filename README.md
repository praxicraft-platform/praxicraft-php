# Praxicraft Assess PHP SDK

Official PHP client for the **[Praxicraft Assess](https://assess.praxicraft.com)** Public API.

```bash
composer require praxicraft/assess
```

Until Packagist publish, install from GitHub:

```bash
composer require praxicraft/assess:dev-main
```

**Requires PHP 8.1+** (ext-curl, ext-json, ext-hash). Docs: [https://docs.praxicraft.com/sdks/php](https://docs.praxicraft.com/sdks/php)

## Authentication

```bash
export PRAXICRAFT_API_KEY="ct_live_xxxxxxxxxxxxxxxx"
```

```php
use Praxicraft\Assess\Client;

$client = new Client(); // reads PRAXICRAFT_API_KEY
// or new Client(['apiKey' => 'ct_live_…', 'baseUrl' => 'https://assess.praxicraft.com']);
```

## Quickstart

```php
use Praxicraft\Assess\Client;

$client = new Client();

$page = $client->assessments->list();
foreach ($page['results'] as $assessment) {
    echo $assessment['slug'], ' ', $assessment['status'], PHP_EOL;
}

$invite = $client->invites->create('senior-backend-screen', [
    'email' => 'candidate@example.com',
    'name' => 'Jane Doe',
    'send_email' => true,
]);

echo $invite['invite_token'], PHP_EOL;
$result = $client->results->retrieve($invite['invite_token']);
```

## Resources

| Resource | Common methods |
|----------|----------------|
| `$client->org` | `retrieve()`, `stats()` |
| `$client->assessments` | `list()`, `retrieve()`, `create()`, `update()`, `activate()`, `listCases()`, `attachCases()`, `replaceCases()`, `removeCase()` |
| `$client->invites` | `create()`, `bulkCreate()`, `list()`, `retrieve()`, `remind()`, `cancel()` |
| `$client->results` | `list()`, `retrieve()`, `iterAll()` |
| `$client->webhooks` | `list()`, `create()`, `retrieve()`, `update()`, `delete()`, `test()`, `deliveries()` |
| `$client->pipelines` | `list()`, `retrieve()`, `enroll()`, `bulkEnroll()`, `listEnrollments()`, `getEnrollment()` |
| `Webhooks::verifySignature` | Webhook HMAC helper |

## Verify webhooks

```php
use Praxicraft\Assess\Webhooks;

$ok = Webhooks::verifySignature($secret, $rawBody, $_SERVER['HTTP_X_PRAXICRAFT_SIGNATURE'] ?? '');
```

## License

MIT
