<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\HttpExceptionErrorMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HttpExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnyHttpException(): void
    {
        self::assertTrue((new HttpExceptionErrorMapper())->supports(new NotFoundHttpException()));
    }

    public function testDoesNotSupportANonHttpException(): void
    {
        self::assertFalse((new HttpExceptionErrorMapper())->supports(new \RuntimeException()));
    }

    public function testMapsTheExceptionsOwnStatusAndMessage(): void
    {
        $mapped = (new HttpExceptionErrorMapper())->map(new NotFoundHttpException('Agent not found.'));

        self::assertSame(404, $mapped->status);
        self::assertSame('http_error', $mapped->body->code);
        self::assertSame('Agent not found.', $mapped->body->message);
        self::assertSame([], $mapped->headers);
    }

    public function testKeepsTheExceptionsOwnHeaders(): void
    {
        $mapped = (new HttpExceptionErrorMapper())->map(new MethodNotAllowedHttpException(['POST']));

        self::assertSame(405, $mapped->status);
        self::assertSame(['Allow' => 'POST'], $mapped->headers);
    }
}
