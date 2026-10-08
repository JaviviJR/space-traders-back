<?php

declare(strict_types=1);

namespace App\Controller\SpaceTraders;

use App\Entity\User;
use App\SpaceTraders\SpaceTradersProxyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SpaceTradersProxyController extends AbstractController
{
    public function __construct(
        private readonly SpaceTradersProxyService $proxyService,
    ) {
    }

    #[Route('/api/spacetraders/{path}', requirements: ['path' => '.+'], methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Request $request, string $path, #[CurrentUser] User $user): Response
    {
        return $this->proxyService->handle($request, $path, $user);
    }
}
