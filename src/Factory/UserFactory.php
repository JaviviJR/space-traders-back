<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private ?UserPasswordHasherInterface $passwordHasher = null,
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
            'email' => self::faker()->unique()->safeEmail(),
            'password' => 'password123',
            'isVerified' => true,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(function (array $attributes): User {
                \assert(\is_string($attributes['email']));
                \assert(\is_string($attributes['password']));
                \assert(null !== $this->passwordHasher);

                $user = new User($attributes['email'], '');
                $user->setPassword($this->passwordHasher->hashPassword($user, $attributes['password']));

                if ($attributes['isVerified']) {
                    $user->verify();
                }

                return $user;
            })
        ;
    }
}
