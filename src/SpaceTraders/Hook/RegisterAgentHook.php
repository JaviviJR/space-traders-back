<?php

declare(strict_types=1);

namespace App\SpaceTraders\Hook;

use App\Entity\Agent;
use App\Entity\User;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\SpaceTradersProxyResponseData;
use App\SpaceTraders\SpaceTradersRouteHookInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * SpaceTraders' POST /register creates an agent that doesn't exist in our
 * DB yet, so this hook persists it as a side effect once SpaceTraders
 * confirms the registration. The response (including the raw agent token)
 * is relayed to the front unchanged for now — redacting it is a known
 * simplification to revisit, see doc/architecture.md.
 */
final class RegisterAgentHook implements SpaceTradersRouteHookInterface
{
    public function __construct(
        private readonly AgentTokenCipherInterface $agentTokenCipher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request, string $path): bool
    {
        return 'register' === $path && $request->isMethod('POST');
    }

    public function beforeForward(Request $request, string $path, ?Agent $agent, User $user): ?SpaceTradersProxyResponseData
    {
        return null;
    }

    public function afterForward(Request $request, string $path, ?Agent $agent, User $user, ResponseInterface $spaceTradersResponse): ?SpaceTradersProxyResponseData
    {
        if ($spaceTradersResponse->getStatusCode() >= 300) {
            return null;
        }

        $data = $spaceTradersResponse->toArray(false)['data'] ?? null;

        if (!\is_array($data)) {
            return null;
        }

        $agentData = $data['agent'] ?? null;
        $token = $data['token'] ?? null;

        if (!\is_array($agentData) || !\is_string($token) || '' === $token) {
            return null;
        }

        $symbol = $agentData['symbol'] ?? null;
        $faction = $agentData['startingFaction'] ?? null;

        if (!\is_string($symbol) || !\is_string($faction)) {
            return null;
        }

        $newAgent = new Agent($user, $symbol, $faction, $this->agentTokenCipher->encrypt($token));

        $this->entityManager->persist($newAgent);
        $this->entityManager->flush();

        return null;
    }
}
