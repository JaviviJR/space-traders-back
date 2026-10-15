<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorResponseData;
use App\Security\Exception\EmailNotVerifiedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private EnvelopeResponseFactory $envelope,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof EmailNotVerifiedException) {
            return $this->envelope->error(new ErrorResponseData('http_error', $exception->getMessage()), 403);
        }

        return $this->envelope->error(new ErrorResponseData('http_error', 'Invalid email or password.'), 401);
    }
}
