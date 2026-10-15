<?php

declare(strict_types=1);

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class EmailNotVerifiedException extends CustomUserMessageAccountStatusException
{
    public static function becauseEmailIsNotVerified(): self
    {
        return new self('Email address is not verified.');
    }
}
