<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Entity\User;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        private readonly SpaceTradersResponseRelay $relay,
        #[AutowireIterator('app.space_traders_route_hook')]
        private readonly iterable $hooks,
    ) {
    }

    public function handle(Request $request, string $path, User $user): Response
    {
        $agent = $this->resolveAgent($request, $user);
        $hook = $this->findHook($request, $path);

        if (null !== $hook) {
            $shortCircuit = $hook->beforeForward($request, $path, $agent, $user);

            if (null !== $shortCircuit) {
                return $shortCircuit;
            }
        }

        $options = [
            'query' => $request->query->all(),
            'headers' => $this->forwardableHeaders($request),
        ];

        $content = $request->getContent();

        if ('' !== $content) {
            $options['body'] = $content;
        }

        if (null !== $agent) {
            $options['headers']['Authorization'] = 'Bearer '.$this->agentTokenCipher->decrypt($agent->getEncryptedToken());
        }

        $spaceTradersResponse = $this->gateway->request($request->getMethod(), $path, $options);

        if (null !== $hook) {
            $override = $hook->afterForward($request, $path, $agent, $user, $spaceTradersResponse);

            if (null !== $override) {
                return $override;
            }
        }

        return $this->relay->toSymfonyResponse($spaceTradersResponse);
    }

    private function resolveAgent(Request $request, User $user): ?Agent
    {
        $agentId = $request->headers->get('X-Agent-Id');

        if (null === $agentId) {
            return null;
        }

        $agent = $this->agentRepository->find((int) $agentId);

        if (null === $agent) {
            throw new NotFoundHttpException(sprintf('Agent "%s" not found.', $agentId));
        }

        if ($agent->getUser() !== $user) {
            throw new AccessDeniedHttpException(sprintf('Agent "%s" does not belong to the authenticated user.', $agentId));
        }

        return $agent;
    }

    private function findHook(Request $request, string $path): ?SpaceTradersRouteHookInterface
    {
        foreach ($this->hooks as $hook) {
            if ($hook->supports($request, $path)) {
                return $hook;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function forwardableHeaders(Request $request): array
    {
        $headers = [];
        $contentType = $request->headers->get('Content-Type');

        if (null !== $contentType) {
            $headers['Content-Type'] = $contentType;
        }

        return $headers;
    }
}
