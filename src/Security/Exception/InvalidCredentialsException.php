<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class InvalidCredentialsException extends \RuntimeException
{
    public static function becauseCredentialsAreInvalid(): self
    {
        return new self('Invalid email or password.');
    }
}
