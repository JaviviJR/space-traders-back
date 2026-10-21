<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A request to a JWT-protected route without a usable access token: missing,
 * malformed, expired, or valid for a user that no longer exists.
 *
 * Matches any AuthenticationException, not just Lexik's own: for the
 * deleted-user case the authenticator receives Symfony's
 * BadCredentialsException (expose_security_errors hides UserNotFound). They
 * all share one code and a fixed message on purpose: the front's reaction
 * is the same for every one of them (try /auth/refresh once, else log in),
 * and the reason isn't something to expose.
 *
 * Reached only through JwtAuthenticationFailureListener: on a firewalled
 * route the security layer catches AuthenticationException before
 * kernel.exception. WWW-Authenticate is what a 401 must carry (RFC 9110).
 */
final class AuthenticationExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof AuthenticationException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(
            401,
            new ErrorResponseData(ErrorCode::AuthenticationRequired, 'Missing or invalid access token.'),
            ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
