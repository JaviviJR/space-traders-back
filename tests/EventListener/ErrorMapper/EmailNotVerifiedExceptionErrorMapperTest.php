<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\EmailNotVerifiedExceptionErrorMapper;
use App\Http\ErrorCode;
use App\Security\Exception\EmailNotVerifiedException;
use PHPUnit\Framework\TestCase;

final class EmailNotVerifiedExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnEmailNotVerifiedException(): void
    {
        self::assertTrue((new EmailNotVerifiedExceptionErrorMapper())->supports(EmailNotVerifiedException::becauseEmailIsNotVerified()));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new EmailNotVerifiedExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToAForbiddenWithAFixedMessage(): void
    {
        $mapped = (new EmailNotVerifiedExceptionErrorMapper())->map(EmailNotVerifiedException::becauseEmailIsNotVerified());

        self::assertSame(403, $mapped->status);
        self::assertSame(ErrorCode::EmailNotVerified, $mapped->body->code);
        self::assertSame('Email address is not verified.', $mapped->body->message);
        self::assertSame([], $mapped->headers);
    }
}
