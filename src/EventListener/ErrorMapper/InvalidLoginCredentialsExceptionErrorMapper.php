<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use App\Security\Exception\InvalidLoginCredentialsException;

/**
 * A login with a wrong password or an unknown email. Both share one code and
 * message on purpose: telling them apart would reveal which emails have an
 * account (the same reason expose_security_errors hides UserNotFound).
 *
 * No WWW-Authenticate, although RFC 9110 asks for one on a 401: the
 * credentials travel in the JSON body, so there is no HTTP authentication
 * scheme to challenge with, and Bearer would be false (this endpoint doesn't
 * accept a token). Symfony's own json_login failure response doesn't send
 * one either.
 */
final class InvalidLoginCredentialsExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InvalidLoginCredentialsException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(401, new ErrorResponseData(ErrorCode::InvalidCredentials, 'Invalid email or password.'));
    }
}
