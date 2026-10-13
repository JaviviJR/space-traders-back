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
     * @param ResponseData|ResponseData[]    $data
     * @param array<string, string|string[]> $headers
     */
    public function success(ResponseData|array $data, int $status = 200, array $headers = []): JsonResponse
    {
        $extra = ['data' => $this->normalizer->normalize($data)];

        return $this->toJsonResponse($this->build($extra, $status, $headers));
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function error(ResponseData $data, int $status, array $headers = []): JsonResponse
    {
        return $this->toJsonResponse($this->build(['error' => $this->normalizer->normalize($data)], $status, $headers));
    }

    /**
     * The only place in the app that builds a JsonResponse: both success()/
     * error() and the SpaceTraders proxy controller end here.
     */
    public function toJsonResponse(HttpResponseEnvelope $envelope): JsonResponse
    {
        return JsonResponse::fromJsonString($envelope->body, $envelope->statusCode, $envelope->headers);
    }

    /**
     * @param array<string, mixed>           $extra
     * @param array<string, string|string[]> $headers
     */
    private function build(array $extra, int $status, array $headers): HttpResponseEnvelope
    {
        return new HttpResponseEnvelope($status, (new EnvelopeBody(null, $extra))->toJson(), $headers);
    }
}
