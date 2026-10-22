<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ApiErrorCode;
use App\Http\ErrorResponseData;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Fallback for any exception controllers already throw today
 * (UnauthorizedHttpException, AccessDeniedHttpException,
 * NotFoundHttpException, ...): its own getStatusCode() and getMessage(),
 * both already intentional and safe — set by the code that threw it.
 * Its getHeaders() are kept too: some are part of the HTTP semantics of the
 * status (Allow on a 405, WWW-Authenticate on a 401, Retry-After on a 429).
 */
final class HttpExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpExceptionInterface;
    }

    public function map(\Throwable $exception): MappedError
    {
        \assert($exception instanceof HttpExceptionInterface);

        return new MappedError(
            $exception->getStatusCode(),
            new ErrorResponseData(ApiErrorCode::HttpError, $exception->getMessage()),
            $exception->getHeaders(),
        );
    }
}
