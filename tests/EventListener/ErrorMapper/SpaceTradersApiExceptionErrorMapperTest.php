<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\SpaceTradersApiExceptionErrorMapper;
use App\SpaceTraders\Exception\ApiException;
use PHPUnit\Framework\TestCase;

final class SpaceTradersApiExceptionErrorMapperTest extends TestCase
{
    public function testSupportsASpaceTradersApiException(): void
    {
        self::assertTrue((new SpaceTradersApiExceptionErrorMapper())->supports(new ApiException(0, '')));
    }

    public function testDoesNotSupportAnotherException(): void
    {
        self::assertFalse((new SpaceTradersApiExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsToAGenericUnreachableError(): void
    {
        $mapped = (new SpaceTradersApiExceptionErrorMapper())->map(new ApiException(0, ''));

        self::assertSame(502, $mapped->status);
        self::assertSame('spacetraders_unreachable', $mapped->body->code);
        self::assertSame('SpaceTraders is currently unreachable.', $mapped->body->message);
    }
}
