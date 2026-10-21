<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\InvalidRefreshTokenExceptionErrorMapper;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenCookieFactory;
use PHPUnit\Framework\TestCase;

final class InvalidRefreshTokenExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnInvalidRefreshTokenException(): void
    {
        self::assertTrue($this->mapper()->supports(InvalidRefreshTokenException::becauseNotFoundOrInvalid()));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse($this->mapper()->supports(new \RuntimeException()));
    }

    public function testMapsToAnUnauthorizedThatClearsTheRefreshTokenCookie(): void
    {
        $mapped = $this->mapper()->map(InvalidRefreshTokenException::becauseNotFoundOrInvalid());

        self::assertSame(401, $mapped->status);
        self::assertSame('invalid_refresh_token', $mapped->body->code);
        self::assertSame('The refresh token is missing, expired or revoked.', $mapped->body->message);
        self::assertSame(['Set-Cookie' => (string) (new RefreshTokenCookieFactory())->clear()], $mapped->headers);
    }

    private function mapper(): InvalidRefreshTokenExceptionErrorMapper
    {
        return new InvalidRefreshTokenExceptionErrorMapper(new RefreshTokenCookieFactory());
    }
}
