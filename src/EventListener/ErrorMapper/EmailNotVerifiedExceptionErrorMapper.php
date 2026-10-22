<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ApiErrorCode;
use App\Http\ErrorResponseData;
use App\Security\Exception\EmailNotVerifiedException;

/**
 * A login with the right credentials for a user who hasn't confirmed their
 * email yet. A 403, not a 401: the back knows who the user is, it just
 * doesn't let them in yet, and the front reacts differently (offer to resend
 * the confirmation instead of asking for the password again). The message is
 * fixed here rather than taken from the exception, which isn't a public
 * contract.
 */
final class EmailNotVerifiedExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof EmailNotVerifiedException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(403, new ErrorResponseData(ApiErrorCode::EmailNotVerified, 'Email address is not verified.'));
    }
}
