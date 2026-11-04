<?php

declare(strict_types=1);

namespace App\Tests\Support;

/**
 * Reads claims from an access token the way the front sees it: the JWT's
 * payload segment, base64url-decoded, without verifying the signature.
 */
trait ReadsAccessToken
{
    /**
     * @return int seconds between the token's "iat" and "exp" claims
     */
    private function accessTokenLifetime(string $jwt): int
    {
        $segments = explode('.', $jwt);
        self::assertCount(3, $segments, 'An access token is a JWS with three segments.');

        $payload = json_decode(base64_decode(strtr($segments[1], '-_', '+/'), true) ?: '', true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertIsInt($payload['iat'] ?? null);
        self::assertIsInt($payload['exp'] ?? null);

        return $payload['exp'] - $payload['iat'];
    }
}
