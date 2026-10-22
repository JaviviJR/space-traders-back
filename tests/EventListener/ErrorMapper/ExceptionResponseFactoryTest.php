<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\ExceptionErrorMapperInterface;
use App\EventListener\ErrorMapper\ExceptionResponseFactory;
use App\EventListener\ErrorMapper\MappedError;
use App\Http\ApiErrorCode;
use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorResponseData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ExceptionResponseFactoryTest extends KernelTestCase
{
    public function testTheFirstSupportingMapperDecidesTheResponse(): void
    {
        $response = $this->factory(
            $this->mapper(\LogicException::class, 409, ['X-Mapper' => 'first']),
            $this->mapper(\Throwable::class, 418),
        )->create(new \LogicException());

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('first', $response->headers->get('X-Mapper'));
        self::assertSame(
            ['error' => ['code' => ApiErrorCode::HttpError->value, 'message' => 'Mapped.']],
            $this->decoded($response),
        );
    }

    public function testAnUnmappedExceptionIsAGenericInternalError(): void
    {
        $response = $this->factory($this->mapper(\LogicException::class, 409))
            ->create(new \RuntimeException('Secret detail.'));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => ApiErrorCode::InternalError->value, 'message' => 'An unexpected error occurred.']],
            $this->decoded($response),
        );
    }

    private function factory(ExceptionErrorMapperInterface ...$mappers): ExceptionResponseFactory
    {
        /** @var EnvelopeResponseFactory $envelope */
        $envelope = self::getContainer()->get(EnvelopeResponseFactory::class);

        return new ExceptionResponseFactory($envelope, $mappers);
    }

    /**
     * @param class-string<\Throwable>       $supported
     * @param array<string, string|string[]> $headers
     */
    private function mapper(string $supported, int $status, array $headers = []): ExceptionErrorMapperInterface
    {
        return new class($supported, $status, $headers) implements ExceptionErrorMapperInterface {
            /**
             * @param class-string<\Throwable>       $supported
             * @param array<string, string|string[]> $headers
             */
            public function __construct(
                private readonly string $supported,
                private readonly int $status,
                private readonly array $headers,
            ) {
            }

            public function supports(\Throwable $exception): bool
            {
                return $exception instanceof $this->supported;
            }

            public function map(\Throwable $exception): MappedError
            {
                return new MappedError($this->status, new ErrorResponseData(ApiErrorCode::HttpError, 'Mapped.'), $this->headers);
            }
        };
    }

    private function decoded(JsonResponse $response): mixed
    {
        return json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
