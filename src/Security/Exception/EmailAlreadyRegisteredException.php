<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class EmailAlreadyRegisteredException extends \RuntimeException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('A user with email "%s" is already registered.', $email));
    }
}
