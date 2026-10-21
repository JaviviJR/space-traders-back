<?php

declare(strict_types=1);

namespace App\EventListener;

use App\EventListener\ErrorMapper\ExceptionResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts every uncaught exception into the same {"error": {code,
 * message}} envelope used everywhere else, instead of Symfony's default
 * error shape.
 *
 * This listener only wires the kernel.exception hook; deciding the response
 * for a given exception is ExceptionResponseFactory's job, shared with the
 * other entry points that don't go through this hook.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __construct(
        private readonly ExceptionResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        $event->setResponse($this->responseFactory->create($event->getThrowable()));
    }
}
