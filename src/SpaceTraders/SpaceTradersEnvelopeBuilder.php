<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Http\EnvelopeBody;
use App\Http\HttpResponseEnvelope;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Turns the final HttpResponseEnvelope (after afterForward) plus an optional
 * augmentation into the HttpResponseEnvelope the controller hands to
 * EnvelopeResponseFactory::toJsonResponse(). The only caller is
 * SpaceTradersProxyService: back-only responses never have an upstream to
 * protect, so this merge logic has no business living on
 * EnvelopeResponseFactory, which every back-only controller depends on.
 *
 * The actual merge/collision-guard logic lives in EnvelopeBody, shared with
 * EnvelopeResponseFactory; this class only normalizes the augmentation's
 * ResponseData into an array for it.
 */
final class SpaceTradersEnvelopeBuilder
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function build(HttpResponseEnvelope $response, ?SpaceTradersResponseAugmentation $augmentation): HttpResponseEnvelope
    {
        if (null === $augmentation) {
            return new HttpResponseEnvelope($response->statusCode, $response->body);
        }

        $normalized = null !== $augmentation->extra ? $this->normalizer->normalize($augmentation->extra) : [];

        return new HttpResponseEnvelope(
            $response->statusCode,
            (new EnvelopeBody($response->body, \is_array($normalized) ? $normalized : []))->toJson(),
            $augmentation->headers,
        );
    }
}
