<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Exception\InvalidCiphertextException;
use App\Security\SodiumAgentTokenCipher;
use PHPUnit\Framework\TestCase;

final class SodiumAgentTokenCipherTest extends TestCase
{
    public function testEncryptThenDecryptReturnsTheOriginalPlaintext(): void
    {
        $cipher = new SodiumAgentTokenCipher(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $ciphertext = $cipher->encrypt('spacetraders-agent-token');

        self::assertNotSame('spacetraders-agent-token', $ciphertext);
        self::assertSame('spacetraders-agent-token', $cipher->decrypt($ciphertext));
    }

    public function testDecryptingATamperedCiphertextThrows(): void
    {
        $cipher = new SodiumAgentTokenCipher(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $ciphertext = $cipher->encrypt('spacetraders-agent-token');
        $tampered = substr($ciphertext, 0, -4).'abcd';

        $this->expectException(InvalidCiphertextException::class);

        $cipher->decrypt($tampered);
    }

    public function testConstructorRejectsAKeyOfTheWrongLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SodiumAgentTokenCipher('too-short');
    }
}
