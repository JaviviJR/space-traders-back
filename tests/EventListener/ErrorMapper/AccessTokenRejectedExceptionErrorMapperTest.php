<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\AccessTokenRejectedExceptionErrorMapper;
use App\Http\ApiErrorCode;
use App\Security\Exception\AccessTokenRejectedException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class AccessTokenRejectedExceptionErrorMapperTest extends TestCase
{
    public function testSupportsARejectedTokenReportedByLexik(): void
    {
        self::assertTrue((new AccessTokenRejectedExceptionErrorMapper())->supports(
            AccessTokenRejectedException::fromAuthenticationException(new ExpiredTokenException()),
        ));
    }

    public function testSupportsARejectedTokenForADeletedUser(): void
    {
        self::assertTrue((new AccessTokenRejectedExceptionErrorMapper())->supports(
            AccessTokenRejectedException::fromAuthenticationException(new BadCredentialsException()),
        ));
    }

    public function testDoesNotSupportAnUnwrappedAuthenticationException(): void
    {
        self::assertFalse((new AccessTokenRejectedExceptionErrorMapper())->supports(new AuthenticationException()));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new AccessTokenRejectedExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToAnUnauthorizedWithAFixedMessageAndABearerChallenge(): void
    {
        $mapped = (new AccessTokenRejectedExceptionErrorMapper())->map(
            AccessTokenRejectedException::fromAuthenticationException(new ExpiredTokenException()),
        );

        self::assertSame(401, $mapped->status);
        self::assertSame(ApiErrorCode::AuthenticationRequired, $mapped->body->code);
        self::assertSame('Missing or invalid access token.', $mapped->body->message);
        self::assertSame(['WWW-Authenticate' => 'Bearer'], $mapped->headers);
    }
}
