<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class EmailNotVerifiedException extends \RuntimeException
{
    public static function becauseEmailIsNotVerified(): self
    {
        return new self('Email address is not verified.');
    }
}
