<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * #[MapRequestPayload] / #[MapQueryString] wrap a failed validation in a
 * generic HttpException whose getMessage() concatenates the violation
 * messages but drops which field each one is about. This unwraps the
 * original ValidationFailedException (kept as the HttpException's previous)
 * and rebuilds "field: problem" pairs from its violations.
 *
 * Runs ahead of HttpExceptionErrorMapper, which would otherwise also
 * support the wrapping HttpException.
 */
#[AsTaggedItem(priority: 10)]
final class ValidationFailedExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    private const string CODE = 'validation_failed';

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpExceptionInterface
            && $exception->getPrevious() instanceof ValidationFailedException;
    }

    public function map(\Throwable $exception): MappedError
    {
        \assert($exception instanceof HttpExceptionInterface);
        $previous = $exception->getPrevious();
        \assert($previous instanceof ValidationFailedException);

        $messages = [];
        foreach ($previous->getViolations() as $violation) {
            $messages[] = \sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return new MappedError($exception->getStatusCode(), self::CODE, implode('; ', $messages));
    }
}
