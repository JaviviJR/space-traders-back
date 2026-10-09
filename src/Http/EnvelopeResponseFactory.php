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

    public function success(ResponseData $data, int $status = 200): JsonResponse
    {
        $json = $this->serializer->serialize(['data' => $data], 'json');

        return JsonResponse::fromJsonString($json, $status);
    }
}
