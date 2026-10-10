<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Agent;
use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Agent>
 */
final class AgentFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Agent::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'symbol' => self::faker()->unique()->bothify('AGENT-####'),
            'faction' => 'COSMIC',
            'encryptedToken' => 'fake-encrypted-token',
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(function (array $attributes): Agent {
                \assert($attributes['user'] instanceof User);
                \assert(\is_string($attributes['symbol']));
                \assert(\is_string($attributes['faction']));
                \assert(\is_string($attributes['encryptedToken']));

                return new Agent($attributes['user'], $attributes['symbol'], $attributes['faction'], $attributes['encryptedToken']);
            })
        ;
    }
}
