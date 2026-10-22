<?php

declare(strict_types=1);

namespace App\Controller\SpaceTraders;

use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\SpaceTraders\Proxy\ProxyRequest;
use App\SpaceTraders\Proxy\ProxyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The one catch-all route for every SpaceTraders-proxied endpoint (see
 * "Proxy design" in doc/spacetraders-proxy.md).
 *
 * Its route is kept out of the generated OpenAPI document by
 * nelmio_api_doc.yaml's areas.path_patterns: documented by reflection, it
 * would be redundant with, and confusing next to, the synthetic operations
 * ProxyDescriber injects. #[OA\Hidden] would be the natural fit but doesn't
 * exist in zircote/swagger-php (checked in vendor/: absent in 6.12.0 and 6.16.0); Nelmio's
 * #[Areas] with with_attribute: true would work, but it's built for
 * splitting a document into several rendered areas, more machinery than
 * hiding one route needs.
 */
final class SpaceTradersProxyController extends AbstractController
{
    public function __construct(
        private readonly ProxyService $proxyService,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/api/spacetraders/{path}', requirements: ['path' => '.*'], defaults: ['path' => ''], methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request, string $path, #[CurrentUser] User $user): JsonResponse
    {
        $proxyRequest = new ProxyRequest(
            $request->getMethod(),
            $path,
            $user,
            $request->query->all(),
            $this->forwardableHeaders($request),
            $request->getContent(),
            $request->headers->get('X-Agent-Id'),
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
