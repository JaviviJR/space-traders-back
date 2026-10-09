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
     * @param ResponseData|ResponseData[] $data
     */
    public function success(ResponseData|array $data, int $status = 200, ?Meta $meta = null): JsonResponse
    {
        $payload = ['data' => $data];

        if (null !== $meta) {
            $payload['meta'] = $meta;
        }

        $json = $this->serializer->serialize($payload, 'json');

        return JsonResponse::fromJsonString($json, $status);
    }
}
