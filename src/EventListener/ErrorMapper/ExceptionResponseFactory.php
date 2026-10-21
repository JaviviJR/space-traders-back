<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The one place an exception becomes an error response: the first
 * ExceptionErrorMapperInterface that supports it decides status, body and
 * headers (tagged iterator ordered by priority, see AsTaggedItem on mappers
 * that need to run ahead of a more generic one), and the result goes
 * through the same EnvelopeResponseFactory as every other response.
 * Anything unmapped is a generic 500 that never exposes the real exception.
 *
 * Callers are only entry points that hand over the exception: the
 * kernel.exception hook (ExceptionListener) and failures that never reach
 * it: LexikJWTAuthenticationBundle's (JwtAuthenticationFailureListener) and
 * json_login's (LoginFailureHandler).
 */
final class ExceptionResponseFactory
{
    /**
     * @param iterable<ExceptionErrorMapperInterface> $errorMappers
     */
    public function __construct(
        private readonly EnvelopeResponseFactory $envelope,
        #[AutowireIterator('app.exception_error_mapper')]
        private readonly iterable $errorMappers,
    ) {
    }

    public function create(\Throwable $exception): JsonResponse
    {
        $mappedError = $this->mapError($exception);

        return $this->envelope->error(
            $mappedError->body,
            $mappedError->status,
            $mappedError->headers
        );
    }

    private function mapError(\Throwable $exception): MappedError
    {
        foreach ($this->errorMappers as $mapper) {
            if ($mapper->supports($exception)) {
                return $mapper->map($exception);
            }
        }

        return new MappedError(500, new ErrorResponseData(ErrorCode::InternalError, 'An unexpected error occurred.'));
    }
}
