<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Agent;
use App\Entity\User;
use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use App\SpaceTraders\SpaceTradersClientInterface;
use App\Security\Dto\RegisterAgentDto;
use Doctrine\ORM\EntityManagerInterface;

final class AgentRegistrationService
{
    public function __construct(
        private readonly SpaceTradersClientInterface $spaceTradersClient,
        private readonly AgentTokenCipherInterface $agentTokenCipher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws SpaceTradersRegistrationException if SpaceTraders rejects the registration
     */
    public function registerAgent(User $user, RegisterAgentDto $dto): Agent
    {
        $response = $this->spaceTradersClient->registerAgent($dto->symbol, $dto->faction);

        $data = $response['data'] ?? null;

        if (!\is_array($data)) {
            throw new SpaceTradersRegistrationException(0, 'SpaceTraders response did not include a data object.');
        }

        $token = $data['token'] ?? null;

        if (!\is_string($token) || '' === $token) {
            throw new SpaceTradersRegistrationException(0, 'SpaceTraders response did not include a token.');
        }

        $agent = new Agent($user, $dto->symbol, $dto->faction, $this->agentTokenCipher->encrypt($token));

        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        return $agent;
    }
}
