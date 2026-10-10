<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SpaceTradersProxyService
{
    /**
     * @param iterable<SpaceTradersRouteHookInterface> $hooks
     */
    public function __construct(
        private readonly SpaceTradersGatewayInterface $gateway,
        private readonly AgentRepository $agentRepository,
        private readonly AgentTokenCipherInterface $agentTokenCipher,
        private readonly SpaceTradersEnvelopeBuilder $envelopeBuilder,
        #[AutowireIterator('app.space_traders_route_hook')]
        private readonly iterable $hooks,
    ) {
    }

    public function handle(SpaceTradersProxyRequest $request): HttpResponseEnvelope
    {
        $agent = $this->resolveAgent($request);
        $hook = $this->findHook($request);

        if (null !== $hook) {
            $shortCircuit = $hook->beforeForward($request, $agent);

            if (null !== $shortCircuit) {
                return $shortCircuit;
            }
        }

        $options = [
            'query' => $request->query,
            'headers' => $request->headers,
        ];

        if ('' !== $request->body) {
            $options['body'] = $request->body;
        }

        if (null !== $agent) {
            $options['headers']['Authorization'] = 'Bearer '.$this->agentTokenCipher->decrypt($agent->getEncryptedToken());
        }

        $apiResponse = $this->gateway->request($request->method, $request->path, $options);

        if (null !== $hook) {
            $apiResponse = $hook->afterForward($request, $agent, $apiResponse);
        }

        $augmentation = $hook instanceof SpaceTradersResponseAugmenterInterface
            ? $hook->augment($request, $agent, $apiResponse)
            : null;

        return $this->envelopeBuilder->build($apiResponse, $augmentation);
    }

    private function resolveAgent(SpaceTradersProxyRequest $request): ?Agent
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

    private function findHook(SpaceTradersProxyRequest $request): ?SpaceTradersRouteHookInterface
    {
        foreach ($this->hooks as $hook) {
            if ($hook->supports($request)) {
                return $hook;
            }
        }

        return null;
    }
}
