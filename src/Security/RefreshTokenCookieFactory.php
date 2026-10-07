<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenCookieFactory
{
    private const COOKIE_NAME = 'refresh_token';
    private const COOKIE_PATH = '/auth';

    public function create(string $plaintext, \DateTimeImmutable $expiresAt): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue($plaintext)
            ->withPath(self::COOKIE_PATH)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT)
            ->withExpires($expiresAt->getTimestamp())
        ;
    }

    public function clear(): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue(null)
            ->withPath(self::COOKIE_PATH)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT)
            ->withExpires(1)
        ;
    }

    public function readFrom(Request $request): ?string
    {
        return $request->cookies->get(self::COOKIE_NAME);
    }
}
