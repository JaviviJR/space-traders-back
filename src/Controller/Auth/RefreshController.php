<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\AccessTokenResponseData;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenCookieFactory;
use App\Security\RefreshTokenService;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
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
    #[OA\Post(operationId: 'refreshToken')]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[DataResponse(
        model: AccessTokenResponseData::class,
        status: 200,
        description: 'New access token issued for the refresh token cookie presented',
    )]
    #[ErrorResponse(
        status: 401,
        description: 'Missing, invalid, expired or revoked refresh token (invalid_refresh_token). '
            .'The refresh token cookie is cleared via Set-Cookie on this response.',
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $plaintext = $this->cookieFactory->readFrom($request);

        if (null === $plaintext) {
            throw InvalidRefreshTokenException::becauseNotFoundOrInvalid();
        }

        $user = $this->refreshTokenService->verify($plaintext);

        $accessToken = $this->refreshTokenService->createAccessToken($user);

        return $this->envelope->success(new AccessTokenResponseData($accessToken));
    }
}
