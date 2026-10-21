<?php

declare(strict_types=1);

namespace App\SpaceTraders\Response;

use App\Http\HttpResponseEnvelope;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Turns the final HttpResponseEnvelope (after afterForward) plus an optional
 * augmentation into the HttpResponseEnvelope the controller hands to
 * EnvelopeResponseFactory::toJsonResponse(). The only caller is
 * App\SpaceTraders\Proxy\ProxyService: back-only responses never have an
 * upstream to protect, so this merge logic has no business living on
 * EnvelopeResponseFactory, which every back-only controller depends on.
 *
 * Without extra fields, SpaceTraders' own body passes through untouched — no
 * decode/re-encode. Otherwise, it's decoded just enough to merge the extra
 * fields in as sibling fields, never overriding a key SpaceTraders' body
 * already has — that would be a bug in the caller, so it throws rather than
 * silently picking a winner. An upstream body that doesn't decode to a JSON
 * object can't take sibling fields at all, and throws for the same reason.
 */
final class HttpEnvelopeMerger
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function merge(
        HttpResponseEnvelope $spaceTradersResponse,
        ?AugmentData $extraData,
    ): HttpResponseEnvelope {
        if (null === $extraData) {
            return new HttpResponseEnvelope($spaceTradersResponse->statusCode, $spaceTradersResponse->body);
        }

        $normalized = null !== $extraData->extra ? $this->normalizer->normalize($extraData->extra) : [];

        return new HttpResponseEnvelope(
            $spaceTradersResponse->statusCode,
            $this->mergeBody($spaceTradersResponse->body, \is_array($normalized) ? $normalized : []),
            $extraData->headers,
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function mergeBody(string $upstream, array $extra): string
    {
        if ([] === $extra) {
            return $upstream;
        }

        $decoded = json_decode($upstream, true);

        if (!\is_array($decoded)) {
            throw EnvelopeMergeException::invalidUpstreamBody();
        }

        $collisions = array_keys(array_intersect_key($decoded, $extra));

        if ([] !== $collisions) {
            throw EnvelopeMergeException::collision($collisions);
        }

        return json_encode($decoded + $extra, \JSON_THROW_ON_ERROR);
    }
}
