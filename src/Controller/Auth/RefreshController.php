<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Security\Dto\AccessTokenResponseData;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenCookieFactory;
use App\Security\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly RefreshTokenCookieFactory $cookieFactory,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/refresh', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $plaintext = $this->cookieFactory->readFrom($request);

        try {
            if (null === $plaintext) {
                throw InvalidRefreshTokenException::becauseNotFoundOrInvalid();
            }

            $user = $this->refreshTokenService->verify($plaintext);
        } catch (InvalidRefreshTokenException $exception) {
            $response = $this->json(['message' => $exception->getMessage()], 401);
            $response->headers->setCookie($this->cookieFactory->clear());

            return $response;
        }

        $accessToken = $this->refreshTokenService->createAccessToken($user);

        return $this->envelope->success(new AccessTokenResponseData($accessToken));
    }
}
