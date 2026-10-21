<?php

declare(strict_types=1);

namespace App\EventListener;

use App\EventListener\ErrorMapper\ExceptionResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * LexikJWTAuthenticationBundle answers a missing, invalid or expired JWT
 * (and a valid one whose user no longer exists) with its own
 * {"code": 401, "message"} body, built inside the firewall: it never
 * reaches kernel.exception, so ExceptionListener can't see it. This replaces
 * that response with the one ExceptionResponseFactory builds from the same
 * exception, so the error is still decided by the mappers.
 *
 * Throwing from here instead, to reach ExceptionListener, wouldn't work for
 * a missing token: JWT_NOT_FOUND is dispatched from the firewall's entry
 * point, which already runs inside kernel.exception.
 *
 * Types the shared AuthenticationFailureEvent parent: setResponse() lives
 * there, not on JWTFailureEventInterface. AUTHENTICATION_FAILURE isn't
 * listened to: it belongs to Lexik's own login, which this app doesn't use.
 */
#[AsEventListener(event: Events::JWT_NOT_FOUND)]
#[AsEventListener(event: Events::JWT_INVALID)]
#[AsEventListener(event: Events::JWT_EXPIRED)]
final class JwtAuthenticationFailureListener
{
    public function __construct(
        private readonly ExceptionResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $event->setResponse($this->responseFactory->create($event->getException()));
    }
}
