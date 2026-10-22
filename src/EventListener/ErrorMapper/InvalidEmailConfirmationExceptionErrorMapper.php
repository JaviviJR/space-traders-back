<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ApiErrorCode;
use App\Http\ErrorResponseData;
use App\Security\Exception\InvalidEmailConfirmationException;

/**
 * A confirmation link that is invalid, expired or tampered with. Unlike
 * most mappers, this one reuses the exception's message: it carries
 * VerifyEmailBundle's getReason(), which the bundle writes to be shown to
 * the user (its README flashes it as-is) and which tells an expired link
 * apart from an invalid one — useful for the front to offer a resend.
 */
final class InvalidEmailConfirmationExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InvalidEmailConfirmationException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(400, new ErrorResponseData(ApiErrorCode::InvalidEmailConfirmation, $exception->getMessage()));
    }
}
