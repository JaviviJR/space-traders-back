<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class InvalidCiphertextException extends \RuntimeException
{
    public static function becauseAuthenticationFailed(): self
    {
        return new self('The ciphertext failed authentication: it is malformed or has been tampered with.');
    }
}
