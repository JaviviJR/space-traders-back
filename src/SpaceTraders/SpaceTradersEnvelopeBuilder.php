<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Http\HttpResponseEnvelope;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Turns the final SpaceTradersApiResponse (after afterForward) plus an
 * optional augmentation into the HttpResponseEnvelope the controller hands
 * to EnvelopeResponseFactory::toJsonResponse(). Lives here, not as a named
 * constructor on HttpResponseEnvelope, so that generic type stays unaware
 * of SpaceTraders-specific concepts.
 *
 * Without an augmentation (the common, pass-through case), $response->body
 * is passed through untouched — no decode/re-encode. Only an augmentation
 * forces decoding, so the merge can add extra fields without ever touching
 * SpaceTraders' own top-level keys (those always win on collision).
 */
final class SpaceTradersEnvelopeBuilder
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function build(SpaceTradersApiResponse $response, ?SpaceTradersResponseAugmentation $augmentation): HttpResponseEnvelope
    {
        if (null === $augmentation) {
            return new HttpResponseEnvelope($response->statusCode, $response->body);
        }

        $decoded = json_decode($response->body, true);
        $body = (\is_array($decoded) ? $decoded : []) + $this->normalizeExtra($augmentation->extra);

        return new HttpResponseEnvelope($response->statusCode, json_encode($body, \JSON_THROW_ON_ERROR), $augmentation->headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeExtra(?SpaceTradersAugmentationData $extra): array
    {
        if (null === $extra) {
            return [];
        }

        $decoded = json_decode($this->serializer->serialize($extra, 'json'), true);

        return \is_array($decoded) ? $decoded : [];
    }
}
