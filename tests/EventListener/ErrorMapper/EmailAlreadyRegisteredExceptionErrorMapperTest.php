<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\EmailAlreadyRegisteredExceptionErrorMapper;
use App\Security\Exception\EmailAlreadyRegisteredException;
use PHPUnit\Framework\TestCase;

final class EmailAlreadyRegisteredExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnEmailAlreadyRegisteredException(): void
    {
        self::assertTrue((new EmailAlreadyRegisteredExceptionErrorMapper())->supports(EmailAlreadyRegisteredException::withEmail('taken@example.com')));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new EmailAlreadyRegisteredExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToAConflictWithItsOwnCode(): void
    {
        $mapped = (new EmailAlreadyRegisteredExceptionErrorMapper())->map(EmailAlreadyRegisteredException::withEmail('taken@example.com'));

        self::assertSame(409, $mapped->status);
        self::assertSame('email_already_registered', $mapped->body->code);
        self::assertSame('This email is already registered.', $mapped->body->message);
    }
}
