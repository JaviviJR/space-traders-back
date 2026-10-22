<?php

declare(strict_types=1);

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A login rejected for its credentials (wrong password or unknown email),
 * as opposed to any other AuthenticationException: Symfony uses the same
 * BadCredentialsException for a JWT whose user no longer exists, so the
 * exception's class alone can't tell where it came from. LoginFailureHandler
 * wraps it in this one, which is deliberately not an AuthenticationException,
 * and keeps the original as previous for the logs.
 */
final class InvalidLoginCredentialsException extends \RuntimeException
{
    public static function fromAuthenticationException(AuthenticationException $exception): self
    {
        return new self('Invalid email or password.', 0, $exception);
    }
}
