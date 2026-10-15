<?php

declare(strict_types=1);

namespace App\SpaceTraders\Hook;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\Hook\Dto\RegisterAgentAugmentationData;
use App\SpaceTraders\Proxy\ProxyRequest;
use App\SpaceTraders\Response\AugmentationSchema;
use App\SpaceTraders\Response\AugmentData;
use App\SpaceTraders\Response\AugmentInterface;
use App\SpaceTraders\RouteHookInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SpaceTraders' POST /register creates an agent that doesn't exist in our
 * DB yet, so this hook persists it as a side effect once SpaceTraders
 * confirms the registration. The response (including the raw agent token)
 * is relayed to the front unchanged for now — redacting it is a known
 * simplification to revisit, see doc/architecture.md.
 *
 * $registeredAgent carries the newly persisted agent from afterForward to
 * augment(), which the proxy service calls right after. Safe to keep as
 * instance state: this service lives for a single request (classic PHP-FPM
 * lifecycle, one container per request), never reused across requests.
 */
final class RegisterAgentHook implements RouteHookInterface, AugmentInterface
{
    private ?Agent $registeredAgent = null;

    public function __construct(
        private readonly AgentTokenCipherInterface $agentTokenCipher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(ProxyRequest $request): bool
    {
        return 'register' === $request->path && 'POST' === $request->method;
    }

    public function beforeForward(ProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
    {
        return null;
    }

    public function afterForward(ProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): HttpResponseEnvelope
    {
        if ($response->statusCode >= 300) {
            return $response;
        }

        $decoded = json_decode($response->body, true);
        $data = \is_array($decoded) ? ($decoded['data'] ?? null) : null;

        if (!\is_array($data)) {
            return $response;
        }

        $agentData = $data['agent'] ?? null;
        $token = $data['token'] ?? null;

        if (!\is_array($agentData) || !\is_string($token) || '' === $token) {
            return $response;
        }

        $symbol = $agentData['symbol'] ?? null;
        $faction = $agentData['startingFaction'] ?? null;

        if (!\is_string($symbol) || !\is_string($faction)) {
            return $response;
        }

        $newAgent = new Agent($request->user, $symbol, $faction, $this->agentTokenCipher->encrypt($token));

        $this->entityManager->persist($newAgent);
        $this->entityManager->flush();

        $this->registeredAgent = $newAgent;

        return $response;
    }

    public function augment(ProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): ?AugmentData
    {
        $agentId = $this->registeredAgent?->getId();

        if (null === $agentId) {
            return null;
        }

        return new AugmentData(new RegisterAgentAugmentationData($agentId));
    }

    public function describedAugmentation(): AugmentationSchema
    {
        return new AugmentationSchema('POST', 'register', RegisterAgentAugmentationData::class);
    }
}
