<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Security\RefreshTokenCookieFactory;
use App\Security\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly RefreshTokenCookieFactory $cookieFactory,
    ) {
    }

    #[Route('/auth/logout', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $plaintext = $this->cookieFactory->readFrom($request);

        if (null !== $plaintext) {
            $this->refreshTokenService->revokeByPlaintext($plaintext);
        }

        $response = new Response(null, 204);
        $response->headers->setCookie($this->cookieFactory->clear());

        return $response;
    }
}
