<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\InvalidLoginCredentialsExceptionErrorMapper;
use App\Http\ApiErrorCode;
use App\Security\Exception\InvalidLoginCredentialsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class InvalidLoginCredentialsExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnInvalidLoginCredentialsException(): void
    {
        self::assertTrue((new InvalidLoginCredentialsExceptionErrorMapper())->supports($this->exception()));
    }

    public function testDoesNotSupportAnUnwrappedBadCredentialsException(): void
    {
        self::assertFalse((new InvalidLoginCredentialsExceptionErrorMapper())->supports(new BadCredentialsException()));
    }

    public function testMapsToAnUnauthorizedWithAFixedMessageAndNoChallenge(): void
    {
        $mapped = (new InvalidLoginCredentialsExceptionErrorMapper())->map($this->exception());

        self::assertSame(401, $mapped->status);
        self::assertSame(ApiErrorCode::InvalidCredentials, $mapped->body->code);
        self::assertSame('Invalid email or password.', $mapped->body->message);
        self::assertSame([], $mapped->headers);
    }

    private function exception(): InvalidLoginCredentialsException
    {
        return InvalidLoginCredentialsException::fromAuthenticationException(new BadCredentialsException());
    }
}
