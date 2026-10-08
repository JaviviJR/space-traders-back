<?php

declare(strict_types=1);

namespace App\Security\Exception;

use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class InvalidEmailConfirmationException extends \RuntimeException
{
    public static function fromVerifyEmailException(VerifyEmailExceptionInterface $exception): self
    {
        return new self($exception->getReason(), 0, $exception);
    }
}
