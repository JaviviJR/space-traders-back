<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\InvalidEmailConfirmationExceptionErrorMapper;
use App\Security\Exception\InvalidEmailConfirmationException;
use PHPUnit\Framework\TestCase;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;

final class InvalidEmailConfirmationExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnInvalidEmailConfirmationException(): void
    {
        self::assertTrue((new InvalidEmailConfirmationExceptionErrorMapper())->supports($this->expiredLinkException()));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new InvalidEmailConfirmationExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToABadRequestKeepingTheBundleReason(): void
    {
        $exception = $this->expiredLinkException();

        $mapped = (new InvalidEmailConfirmationExceptionErrorMapper())->map($exception);

        self::assertSame(400, $mapped->status);
        self::assertSame('invalid_email_confirmation', $mapped->body->code);
        self::assertSame((new ExpiredSignatureException())->getReason(), $mapped->body->message);
    }

    private function expiredLinkException(): InvalidEmailConfirmationException
    {
        return InvalidEmailConfirmationException::fromVerifyEmailException(new ExpiredSignatureException());
    }
}
