<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Agent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agent>
 */
class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    /**
     * @return list<Agent>
     */
    public function findAllForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findBySymbol(string $symbol): ?Agent
    {
        return $this->findOneBy(['symbol' => $symbol]);
    }
}
