<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class EnvelopeResponseFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @param ResponseData|ResponseData[]    $data
     * @param array<string, string|string[]> $headers
     */
    public function success(ResponseData|array $data, int $status = 200, ?Meta $meta = null, array $headers = []): JsonResponse
    {
        $payload = ['data' => $data];

        if (null !== $meta) {
            $payload['meta'] = $meta;
        }

        return $this->toJsonResponse(new HttpResponseEnvelope($status, $this->serializer->serialize($payload, 'json'), $headers));
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function error(ResponseData $data, int $status, array $headers = []): JsonResponse
    {
        return $this->toJsonResponse(new HttpResponseEnvelope($status, $this->serializer->serialize(['error' => $data], 'json'), $headers));
    }

    /**
     * The only place in the app that builds a JsonResponse: both success()/
     * error() and the SpaceTraders proxy controller end here.
     */
    public function toJsonResponse(HttpResponseEnvelope $envelope): JsonResponse
    {
        return JsonResponse::fromJsonString($envelope->body, $envelope->statusCode, $envelope->headers);
    }
}
