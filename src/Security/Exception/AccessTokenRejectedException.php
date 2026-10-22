<?php

declare(strict_types=1);

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A request to a JWT-protected route without a usable access token: missing,
 * malformed, expired, or valid for a user that no longer exists. Lexik
 * reports these with its own AuthenticationException subclasses, except the
 * deleted user, which comes as Symfony's BadCredentialsException, the same
 * class a failed login uses. JwtAuthenticationFailureListener wraps them in
 * this one, deliberately not an AuthenticationException, so no mapper has to
 * guess the origin from the framework's class hierarchy.
 */
final class AccessTokenRejectedException extends \RuntimeException
{
    public static function fromAuthenticationException(AuthenticationException $exception): self
    {
        return new self('Missing or invalid access token.', 0, $exception);
    }
}
