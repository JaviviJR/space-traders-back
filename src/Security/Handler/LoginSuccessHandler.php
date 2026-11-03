<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\Security\Dto\AccessTokenResponseData;
use App\Security\EventListener\RememberMeChoiceListener;
use App\Security\RefreshTokenCookieFactory;
use App\Security\RefreshTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
        private RefreshTokenCookieFactory $cookieFactory,
        private EnvelopeResponseFactory $envelope,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // Already validated as bool-or-missing by RememberMeChoiceListener; missing means false.
        $rememberMe = $request->getPayload()->getBoolean(RememberMeChoiceListener::FIELD);

        $pair = $this->refreshTokenService->issue($user, $rememberMe);
        $cookie = $this->cookieFactory->create($pair->refreshToken, $rememberMe ? $pair->refreshTokenExpiresAt : null);

        return $this->envelope->success(
            new AccessTokenResponseData($pair->accessToken),
            headers: ['Set-Cookie' => (string) $cookie],
        );
    }
}
