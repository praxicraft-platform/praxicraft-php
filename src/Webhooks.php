<?php

declare(strict_types=1);

namespace Praxicraft\Assess;

final class Webhooks
{
    /**
     * Verify an X-Praxicraft-Signature header.
     *
     * @param string|null $body Raw request body (null = empty payload)
     */
    public static function verifySignature(string $secret, ?string $body, string $headerSig): bool
    {
        if ($secret === '' || $headerSig === '') {
            return false;
        }

        $payload = $body ?? '';
        $digest = hash_hmac('sha256', $payload, $secret);
        $expected = 'sha256=' . $digest;

        if (str_starts_with($headerSig, 'sha256=')) {
            return hash_equals($expected, $headerSig);
        }

        return hash_equals($digest, $headerSig) || hash_equals($expected, $headerSig);
    }
}
