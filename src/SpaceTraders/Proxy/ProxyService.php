<?php

declare(strict_types=1);

namespace App\SpaceTraders\Proxy;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\Response\AugmentInterface;
use App\SpaceTraders\Response\HttpEnvelopeMerger;
use App\SpaceTraders\RouteHookInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProxyService
{
    /**
     * @param iterable<RouteHookInterface> $hooks
     */
    public function __construct(
        private readonly GatewayInterface $gateway,
        private readonly AgentRepository $agentRepository,
        private readonly AgentTokenCipherInterface $agentTokenCipher,
        private readonly HttpEnvelopeMerger $envelopeMerger,
        #[AutowireIterator('app.space_traders_route_hook')]
        private readonly iterable $hooks,
    ) {
    }

    public function handle(ProxyRequest $request): HttpResponseEnvelope
    {
        $agent = $this->resolveAgent($request);
        $hook = $this->findHook($request);

        if (null !== $hook) {
            $shortCircuit = $hook->beforeForward($request, $agent);

            if (null !== $shortCircuit) {
                return $shortCircuit;
            }
        }

        if (null !== $agent) {
            $request->addHeader(
                'Authorization',
                'Bearer '.$this->agentTokenCipher->decrypt($agent->getEncryptedToken())
            );
        }

        $apiResponse = $this->gateway->request($request);

        if (null !== $hook) {
            $apiResponse = $hook->afterForward($request, $agent, $apiResponse);
        }

        $extraData = $hook instanceof AugmentInterface
            ? $hook->augment($request, $agent, $apiResponse)
            : null;

        return $this->envelopeMerger->merge($apiResponse, $extraData);
    }

    private function resolveAgent(ProxyRequest $request): ?Agent
    {
        if (null === $request->agentId) {
            return null;
        }

        $agent = $this->agentRepository->find((int) $request->agentId);

        if (null === $agent) {
            throw new NotFoundHttpException(sprintf('Agent "%s" not found.', $request->agentId));
        }

        if ($agent->getUser() !== $request->user) {
            throw new AccessDeniedHttpException(sprintf('Agent "%s" does not belong to the authenticated user.', $request->agentId));
        }

        return $agent;
    }

    private function findHook(ProxyRequest $request): ?RouteHookInterface
    {
        foreach ($this->hooks as $hook) {
            if ($hook->supports($request)) {
                return $hook;
            }
        }

        return null;
    }
}
