<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use App\Security\AgentTokenCipherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Defaults isVerified to true, so factory-made users don't need manual
 * verification in tests.
 *
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private AgentTokenCipherInterface $agentTokenCipher,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
            'email' => self::faker()->unique()->safeEmail(),
            'accountToken' => self::faker()->password(900),
            'password' => 'password123',
            'isVerified' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('isVerified'))
            ->afterInstantiate(function (User $user, array $parameters): void {
                \assert(\is_string($parameters['password']));
                \assert(\is_string($parameters['accountToken']));

                $user->setPassword($this->passwordHasher->hashPassword($user, $parameters['password']));
                $user->setAccountToken($this->agentTokenCipher->encrypt($parameters['accountToken']));

                if ($parameters['isVerified']) {
                    $user->verify();
                }
            })
        ;
    }
}
