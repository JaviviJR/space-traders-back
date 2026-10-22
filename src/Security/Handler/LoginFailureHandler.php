<?php

declare(strict_types=1);

namespace App\Security\Handler;

use App\EventListener\ErrorMapper\ExceptionResponseFactory;
use App\Security\Exception\EmailNotVerifiedException;
use App\Security\Exception\InvalidLoginCredentialsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Entry point for json_login failures, which are answered inside the
 * firewall and never reach kernel.exception. It only adds the context it
 * alone knows (this came from the login) and lets ExceptionResponseFactory
 * decide the response.
 *
 * EmailNotVerifiedException passes as-is: it's already specific to the
 * login. Anything else is a credentials failure, wrapped because its class
 * (BadCredentialsException, also used for a JWT whose user no longer exists)
 * doesn't say where it came from.
 */
final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private ExceptionResponseFactory $responseFactory,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if (!$exception instanceof EmailNotVerifiedException) {
            $exception = InvalidLoginCredentialsException::fromAuthenticationException($exception);
        }

        return $this->responseFactory->create($exception);
    }
}
