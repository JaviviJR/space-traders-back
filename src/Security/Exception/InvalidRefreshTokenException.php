<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class InvalidRefreshTokenException extends \RuntimeException
{
    public static function becauseNotFoundOrInvalid(): self
    {
        return new self('The refresh token is missing, expired or revoked.');
    }
}
