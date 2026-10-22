<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorCode;
use App\Http\ValidationErrorResponseData;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * #[MapRequestPayload] / #[MapQueryString] wrap a failed validation in a
 * generic HttpException whose getMessage() concatenates the violation
 * messages but drops which field each one is about. This unwraps the
 * original ValidationFailedException (kept as the HttpException's previous)
 * and groups its violation messages by property path.
 *
 * Violations without a property path (class-level constraints, or a payload
 * the Serializer couldn't read at all) go under GLOBAL_KEY instead of an
 * empty-string key, which a front binding keys to form fields would silently
 * drop. Same idea as DRF's `non_field_errors` or Rails' `errors[:base]`.
 *
 * Runs ahead of HttpExceptionErrorMapper, which would otherwise also
 * support the wrapping HttpException.
 */
#[AsTaggedItem(priority: 10)]
final class ValidationFailedExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    private const string GLOBAL_KEY = '_global';

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

        $violations = [];
        foreach ($previous->getViolations() as $violation) {
            $path = '' === $violation->getPropertyPath() ? self::GLOBAL_KEY : $violation->getPropertyPath();
            $violations[$path][] = (string) $violation->getMessage();
        }

        return new MappedError(
            $exception->getStatusCode(),
            new ValidationErrorResponseData(ErrorCode::ValidationFailed, 'Validation failed.', $violations),
        );
    }
}
