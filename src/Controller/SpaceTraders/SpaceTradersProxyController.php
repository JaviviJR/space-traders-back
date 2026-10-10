<?php

declare(strict_types=1);

namespace App\Controller\SpaceTraders;

use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\SpaceTraders\SpaceTradersProxyRequest;
use App\SpaceTraders\SpaceTradersProxyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SpaceTradersProxyController extends AbstractController
{
    public function __construct(
        private readonly SpaceTradersProxyService $proxyService,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/api/spacetraders/{path}', requirements: ['path' => '.+'], methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request, string $path, #[CurrentUser] User $user): JsonResponse
    {
        $proxyRequest = new SpaceTradersProxyRequest(
            $request->getMethod(),
            $path,
            $request->query->all(),
            $this->forwardableHeaders($request),
            $request->getContent(),
            $request->headers->get('X-Agent-Id'),
            $user,
        );

        $result = $this->proxyService->handle($proxyRequest);

        return $this->envelope->toJsonResponse($result);
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
