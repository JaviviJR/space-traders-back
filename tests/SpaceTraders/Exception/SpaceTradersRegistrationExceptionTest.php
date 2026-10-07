<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Exception;

use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use PHPUnit\Framework\TestCase;

final class SpaceTradersRegistrationExceptionTest extends TestCase
{
    public function testExposesTheStatusCodeAndResponseBodyItWasBuiltWith(): void
    {
        $exception = new SpaceTradersRegistrationException(422, '{"error":"symbol already taken"}');

        self::assertSame(422, $exception->getStatusCode());
        self::assertSame('{"error":"symbol already taken"}', $exception->getResponseBody());
        self::assertSame('SpaceTraders agent registration failed with status 422.', $exception->getMessage());
    }

    public function testKeepsThePreviousExceptionWhenGiven(): void
    {
        $previous = new \RuntimeException('connection reset');

        $exception = new SpaceTradersRegistrationException(0, '', $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
