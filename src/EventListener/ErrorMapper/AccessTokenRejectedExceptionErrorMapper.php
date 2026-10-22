<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ApiErrorCode;
use App\Http\ErrorResponseData;
use App\Security\Exception\AccessTokenRejectedException;

/**
 * A request to a JWT-protected route without a usable access token: missing,
 * malformed, expired, or valid for a user that no longer exists. They all
 * share one code and a fixed message on purpose: the front's reaction is the
 * same for every one of them (try /auth/refresh once, else log in), and the
 * reason isn't something to expose.
 *
 * Matches the wrapper JwtAuthenticationFailureListener builds, not
 * AuthenticationException itself: that is the base of every security
 * failure, a failed login included, so matching it would claim exceptions
 * that aren't about the access token. WWW-Authenticate is what a 401 must
 * carry (RFC 9110).
 */
final class AccessTokenRejectedExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof AccessTokenRejectedException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(
            401,
            new ErrorResponseData(ApiErrorCode::AuthenticationRequired, 'Missing or invalid access token.'),
            ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
