<?php

declare(strict_types=1);

namespace App\Security\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Rejects a login whose optional "rememberMe" field is present but not a
 * boolean (text, number or an explicit null) with a 400 `http_error`, the
 * same treatment json_login gives an email or password that isn't a string.
 *
 * Why CheckPassportEvent on the login firewall's dispatcher, with a priority
 * above UserProviderListener (2048): json_login rejects a malformed email or
 * password in authenticate(), before loading the user or hashing the
 * password. Running here reproduces that moment, so the 400 wins over the
 * 401 (wrong credentials) and the 403 (unverified account). Moving this check
 * into LoginSuccessHandler would validate after the password and let 401/403
 * win, breaking that contract.
 *
 * The BadRequestHttpException isn't an AuthenticationException, so the
 * authenticator manager doesn't catch it: it reaches kernel.exception and
 * HttpExceptionErrorMapper answers it.
 *
 * The event doesn't carry the Request, hence RequestStack. RememberMeBadge is
 * not used: it belongs to Symfony's native remember_me feature, which this
 * project doesn't use (the mode is derived from the refresh token's expiry).
 */
#[AsEventListener(dispatcher: 'security.event_dispatcher.login', priority: 2049)]
final class RememberMeChoiceListener
{
    public const FIELD = 'rememberMe';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        // all() + array_key_exists, not get(): an explicit null must be rejected, a missing key must not.
        $payload = $request->getPayload()->all();
        if (\array_key_exists(self::FIELD, $payload) && !\is_bool($payload[self::FIELD])) {
            throw new BadRequestHttpException(\sprintf('The key "%s" must be a boolean.', self::FIELD));
        }
    }
}
