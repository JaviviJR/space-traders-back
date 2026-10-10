<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorResponseData;
use App\SpaceTraders\Exception\SpaceTradersApiException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts every uncaught exception into the same {"error": {code,
 * message}} envelope used everywhere else, through the same
 * EnvelopeResponseFactory that builds every other response in the app —
 * instead of Symfony's default error shape.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __construct(
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        $exception = $event->getThrowable();

        [$status, $code, $message] = match (true) {
            $exception instanceof SpaceTradersApiException => [502, 'spacetraders_unreachable', 'SpaceTraders is currently unreachable.'],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), 'http_error', $exception->getMessage()],
            default => [500, 'internal_error', 'An unexpected error occurred.'],
        };

        $event->setResponse($this->envelope->error(new ErrorResponseData($code, $message), $status));
    }
}
