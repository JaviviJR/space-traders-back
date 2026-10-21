<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\AuthenticationExceptionErrorMapper;
use App\Http\ErrorCode;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class AuthenticationExceptionErrorMapperTest extends TestCase
{
    public function testSupportsLexiksOwnAuthenticationExceptions(): void
    {
        self::assertTrue((new AuthenticationExceptionErrorMapper())->supports(new ExpiredTokenException()));
    }

    public function testSupportsSymfonysBadCredentialsForADeletedUser(): void
    {
        self::assertTrue((new AuthenticationExceptionErrorMapper())->supports(new BadCredentialsException()));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new AuthenticationExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToAnUnauthorizedWithAFixedMessageAndABearerChallenge(): void
    {
        $mapped = (new AuthenticationExceptionErrorMapper())->map(new ExpiredTokenException());

        self::assertSame(401, $mapped->status);
        self::assertSame(ErrorCode::AuthenticationRequired, $mapped->body->code);
        self::assertSame('Missing or invalid access token.', $mapped->body->message);
        self::assertSame(['WWW-Authenticate' => 'Bearer'], $mapped->headers);
    }
}
