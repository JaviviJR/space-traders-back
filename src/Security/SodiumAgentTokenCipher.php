<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Exception\InvalidCiphertextException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SodiumAgentTokenCipher implements AgentTokenCipherInterface
{
    private const NONCE_BYTES = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

    private string $key;

    public function __construct(
        #[Autowire(env: 'base64:AGENT_TOKEN_ENCRYPTION_KEY')]
        string $key,
    ) {
        if (SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== \strlen($key)) {
            throw new \InvalidArgumentException(sprintf(
                'AGENT_TOKEN_ENCRYPTION_KEY must decode to %d bytes.',
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            ));
        }

        $this->key = $key;
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(self::NONCE_BYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);

        if (false === $decoded || \strlen($decoded) <= self::NONCE_BYTES) {
            throw InvalidCiphertextException::becauseAuthenticationFailed();
        }

        $nonce = substr($decoded, 0, self::NONCE_BYTES);
        $box = substr($decoded, self::NONCE_BYTES);

        $plaintext = sodium_crypto_secretbox_open($box, $nonce, $this->key);

        if (false === $plaintext) {
            throw InvalidCiphertextException::becauseAuthenticationFailed();
        }

        return $plaintext;
    }
}
