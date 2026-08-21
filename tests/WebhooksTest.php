<?php

declare(strict_types=1);

namespace Praxicraft\Assess\Tests;

use PHPUnit\Framework\TestCase;
use Praxicraft\Assess\Webhooks;

final class WebhooksTest extends TestCase
{
    public function testVerifySignaturePrefixed(): void
    {
        $secret = 'whsec_test';
        $body = '{"ok":true}';
        $digest = hash_hmac('sha256', $body, $secret);
        $this->assertTrue(Webhooks::verifySignature($secret, $body, 'sha256=' . $digest));
    }

    public function testVerifySignatureLegacyHex(): void
    {
        $secret = 'whsec_test';
        $body = '{"ok":true}';
        $digest = hash_hmac('sha256', $body, $secret);
        $this->assertTrue(Webhooks::verifySignature($secret, $body, $digest));
    }

    public function testVerifySignatureRejectsBad(): void
    {
        $this->assertFalse(Webhooks::verifySignature('whsec_test', '{}', 'sha256=deadbeef'));
    }

    public function testNullBodyIsEmpty(): void
    {
        $secret = 'whsec_test';
        $digest = hash_hmac('sha256', '', $secret);
        $this->assertTrue(Webhooks::verifySignature($secret, null, 'sha256=' . $digest));
    }
}
