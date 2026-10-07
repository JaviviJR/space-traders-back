<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Exception\InvalidCiphertextException;

interface AgentTokenCipherInterface
{
    public function encrypt(string $plaintext): string;

    /**
     * @throws InvalidCiphertextException if the ciphertext is malformed or has been tampered with
     */
    public function decrypt(string $ciphertext): string;
}
