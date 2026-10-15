<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class EnvelopeResponseFactory
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @param array<string, string|string[]> $headers
     * @param ResponseData|ResponseData[]    $data
     */
    public function success(ResponseData|array $data, int $status = 200, array $headers = []): JsonResponse
    {
        $data = ['data' => $this->normalizer->normalize($data)];

        return $this->toJsonResponse($this->build($data, $status, $headers));
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function error(ResponseData $data, int $status, array $headers = []): JsonResponse
    {
        return $this->toJsonResponse(
            $this->build(
                ['error' => $this->normalizer->normalize($data)],
                $status,
                $headers
            )
        );
    }

    /**
     * The only place in the app that builds a JsonResponse: both success()/
     * error() and the SpaceTraders proxy controller end here.
     */
    public function toJsonResponse(HttpResponseEnvelope $envelope): JsonResponse
    {
        return JsonResponse::fromJsonString(
            $envelope->body,
            $envelope->statusCode,
            $envelope->headers
        );
    }

    /**
     * @param array<string, mixed>           $data
     * @param array<string, string|string[]> $headers
     */
    private function build(array $data, int $status, array $headers): HttpResponseEnvelope
    {
        return new HttpResponseEnvelope(
            $status,
            json_encode($data, \JSON_THROW_ON_ERROR),
            $headers
        );
    }
}
