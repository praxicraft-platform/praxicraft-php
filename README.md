# Praxicraft Assess PHP SDK

Official PHP client for the **[Praxicraft Assess](https://assess.praxicraft.com)** Public API.

Use it to invite candidates, check invite quota, manage webhooks, enroll hiring pipelines, and fetch results from your ATS, backend, or automation scripts.

```bash
composer require praxicraft/assess
```

Until Packagist publish, install from GitHub:

```bash
composer require praxicraft/assess:dev-main
```

**Requires PHP 8.1+.** Full API reference: [docs.praxicraft.com/sdks/php](https://docs.praxicraft.com/sdks/php)

## Table of Contents

- [Authentication](#authentication)
- [Quickstart](#quickstart)
- [What you can do](#what-you-can-do)
  - [Check invite quota before bulk sends](#check-invite-quota-before-bulk-sends)
  - [Bulk invites](#bulk-invites)
  - [Build and activate an assessment via API](#build-and-activate-an-assessment-via-api)
  - [Register and test a webhook](#register-and-test-a-webhook)
  - [Enroll into a hiring pipeline](#enroll-into-a-hiring-pipeline)
  - [Paginate cohort results](#paginate-cohort-results)
  - [Verify webhook signatures](#verify-webhook-signatures)
- [Errors](#errors)
- [Requirements & support](#requirements--support)
- [License](#license)

---

## Authentication

Create an organisation API key in Assess:

**Assess → Developer → API Keys** → create key → copy `ct_live_…` (shown once).

```bash
export PRAXICRAFT_API_KEY="ct_live_xxxxxxxxxxxxxxxx"
```

Or pass the key when constructing the client:

```php
use Praxicraft\Assess\Client;

$client = new Client(['apiKey' => 'ct_live_xxxxxxxxxxxxxxxx']);
```

Optional: override the API host with `PRAXICRAFT_API_BASE_URL` or `new Client(['baseUrl' => '...'])`.
Default host: `https://assess.praxicraft.com`.

Never commit API keys. Prefer environment variables or a secrets manager.

Scopes and rotation: [Authentication](https://docs.praxicraft.com/authentication)

---

## Quickstart

```php
use Praxicraft\Assess\Client;

$client = new Client(); // reads PRAXICRAFT_API_KEY

$page = $client->assessments->list();
foreach ($page['results'] as $assessment) {
    echo $assessment['slug'], ' ', $assessment['status'], PHP_EOL;
}

// Invite a candidate (idempotent on email — safe to retry)
$invite = $client->invites->create('senior-backend-screen', [
    'email' => 'candidate@example.com',
    'name' => 'Jane Doe',
    'send_email' => true,
]);
echo $invite['invite_token'], ' ', $invite['invite_url'] ?? '', PHP_EOL;

$result = $client->results->retrieve($invite['invite_token']);
print_r($result);
```

Responses are **flat JSON** (same shape as the Public API — no `{ "data": … }` wrapper).

---

## What you can do

| Resource | Common methods |
|----------|----------------|
| `$client->org` | `retrieve()`, `stats()` |
| `$client->assessments` | `list()`, `retrieve()`, `create()`, `update()`, `activate()`, `listCases()`, `attachCases()`, `replaceCases()`, `removeCase()` |
| `$client->invites` | `create()`, `bulkCreate()`, `list()`, `retrieve()`, `remind()`, `cancel()` |
| `$client->results` | `list()`, `retrieve()`, `iterAll()` |
| `$client->webhooks` | `list()`, `create()`, `retrieve()`, `update()`, `delete()`, `test()`, `deliveries()` |
| `$client->pipelines` | `list()`, `retrieve()`, `enroll()`, `bulkEnroll()`, `listEnrollments()`, `getEnrollment()` |
| `Webhooks::verifySignature` | Verify `X-Praxicraft-Signature` on webhook payloads |

All paths target `/api/v1/public/…` on the Assess host.

### Check invite quota before bulk sends

```php
$org = $client->org->retrieve();
if (($org['invites_remaining'] ?? 0) < count($candidates)) {
    throw new RuntimeException('Not enough invites remaining this month');
}
```

### Bulk invites

```php
$client->invites->bulkCreate('senior-backend-screen', [
    ['email' => 'a@example.com', 'name' => 'Alex'],
    ['email' => 'b@example.com', 'name' => 'Blair'],
], ['send_email' => true]);
```

### Build and activate an assessment via API

```php
$assessment = $client->assessments->create(['title' => 'Backend screen']);
$client->assessments->attachCases($assessment['slug'], [
    'cases' => [['case_id' => '<platform-or-org-case-uuid>', 'source' => 'platform']],
]);
$client->assessments->activate($assessment['slug']);
```

### Register and test a webhook

```php
$hook = $client->webhooks->create([
    'url' => 'https://example.com/hooks/praxicraft',
    'events' => ['assessment.completed', 'candidate.passed'],
]);
// Store $hook['secret_key'] (whsec_…) — shown once
$client->webhooks->test($hook['id']);
$client->webhooks->update($hook['id'], ['is_active' => true]);
```

### Enroll into a hiring pipeline

```php
$enrollment = $client->pipelines->enroll('grad-2025', [
    'email' => 'alex@example.com',
    'name' => 'Alex Lee',
    'send_email' => true,
]);
$status = $client->pipelines->getEnrollment($enrollment['enrollment_id']);
```

### Paginate cohort results

```php
foreach ($client->results->iterAll('senior-backend-screen', ['page_size' => 50]) as $row) {
    echo ($row['email'] ?? ''), ' ', ($row['score_percentage'] ?? ''), ' ', ($row['passed'] ?? ''), PHP_EOL;
}
```

### Verify webhook signatures

Assess signs the **raw request body** with your webhook secret (`whsec_…`):

```php
use Praxicraft\Assess\Webhooks;

function handleWebhook(string $rawBody, string $signatureHeader, string $secret): bool
{
    return Webhooks::verifySignature($secret, $rawBody, $signatureHeader);
}
```

Header format: `X-Praxicraft-Signature: sha256=<hex>`

Event catalog and payload examples: [Webhooks](https://docs.praxicraft.com/webhooks)

---

## Errors

Public API errors look like:

```json
{
  "error": {
    "code": "INSUFFICIENT_SCOPE",
    "message": "This API key does not have the 'candidates:read' scope."
  }
}
```

The SDK raises typed exceptions. **Branch on `$exc->errorCode`**, not the message text:

```php
use Praxicraft\Assess\Exception\AuthenticationException;
use Praxicraft\Assess\Exception\InsufficientScopeException;
use Praxicraft\Assess\Exception\RateLimitException;
use Praxicraft\Assess\Exception\ValidationException;

try {
    $client->invites->create('demo', ['email' => 'candidate@example.com']);
} catch (ValidationException $exc) {
    echo $exc->errorCode, ' ', json_encode($exc->details), PHP_EOL;
} catch (InsufficientScopeException $exc) {
    echo $exc->errorCode, PHP_EOL;
} catch (AuthenticationException $exc) {
    echo $exc->errorCode, PHP_EOL;
} catch (RateLimitException $exc) {
    echo $exc->retryAfter, PHP_EOL;
}
```

Error codes: [Errors](https://docs.praxicraft.com/errors)

---

## Requirements & support

- PHP **8.1**, **8.2**, or **8.3+**
- Extensions: `curl`, `json`, `hash`
- Product docs: [docs.praxicraft.com](https://docs.praxicraft.com)
- Issues: [GitHub Issues](https://github.com/praxicraft-platform/praxicraft-php/issues)

---

## License

[MIT](LICENSE)
