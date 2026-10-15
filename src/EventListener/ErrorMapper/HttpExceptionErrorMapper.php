<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Fallback for any exception controllers already throw today
 * (UnauthorizedHttpException, AccessDeniedHttpException,
 * NotFoundHttpException, ...): its own getStatusCode() and getMessage(),
 * both already intentional and safe — set by the code that threw it.
 */
final class HttpExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    private const string CODE = 'http_error';

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpExceptionInterface;
    }

    public function map(\Throwable $exception): MappedError
    {
        \assert($exception instanceof HttpExceptionInterface);

        return new MappedError($exception->getStatusCode(), self::CODE, $exception->getMessage());
    }
}
