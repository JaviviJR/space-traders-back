<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Security\Dto\LoginDto;
use App\Security\Exception\EmailNotVerifiedException;
use App\Security\Exception\InvalidCredentialsException;
use App\Security\LoginService;
use App\Security\RefreshTokenCookieFactory;
use App\Security\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly RefreshTokenCookieFactory $cookieFactory,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/login', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] LoginDto $dto): JsonResponse
    {
        try {
            $user = $this->loginService->authenticate($dto);
        } catch (InvalidCredentialsException $exception) {
            throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
        } catch (EmailNotVerifiedException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        $pair = $this->refreshTokenService->issue($user);

        $response = $this->envelope->success(['accessToken' => $pair->accessToken]);
        $response->headers->setCookie($this->cookieFactory->create($pair->refreshToken, $pair->refreshTokenExpiresAt));

        return $response;
    }
}
