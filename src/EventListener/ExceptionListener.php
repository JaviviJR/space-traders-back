<?php

declare(strict_types=1);

namespace App\EventListener;

use App\EventListener\ErrorMapper\ExceptionErrorMapperInterface;
use App\EventListener\ErrorMapper\MappedError;
use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorResponseData;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts every uncaught exception into the same {"error": {code,
 * message}} envelope used everywhere else, through the same
 * EnvelopeResponseFactory that builds every other response in the app —
 * instead of Symfony's default error shape.
 *
 * This listener only wires the kernel.exception hook to the envelope; it
 * doesn't know how any particular exception should be described. Each kind
 * of exception gets its own ExceptionErrorMapperInterface, found here
 * through a tagged iterator ordered by priority (see AsTaggedItem on
 * mappers that need to run ahead of a more generic one).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    private const string UNEXPECTED_ERROR_CODE = 'internal_error';

    /**
     * @param iterable<ExceptionErrorMapperInterface> $errorMappers
     */
    public function __construct(
        private readonly EnvelopeResponseFactory $envelope,
        #[AutowireIterator('app.exception_error_mapper')]
        private readonly iterable $errorMappers,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        $exception = $event->getThrowable();
        $mappedError = $this->mapError($exception);

        $event->setResponse(
            $this->envelope->error(
                $mappedError->body,
                $mappedError->status
            )
        );
    }

    private function mapError(\Throwable $exception): MappedError
    {
        foreach ($this->errorMappers as $mapper) {
            if ($mapper->supports($exception)) {
                return $mapper->map($exception);
            }
        }

        return new MappedError(500, new ErrorResponseData(self::UNEXPECTED_ERROR_CODE, 'An unexpected error occurred.'));
    }
}
