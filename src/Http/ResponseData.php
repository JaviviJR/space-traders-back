<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Marker for objects allowed to leave through the envelope as JSON: the
 * `data`/`error` payload of a back-only response, or the extra top-level
 * fields a App\SpaceTraders\Response\AugmentInterface hook adds on top of a
 * proxied response (App\SpaceTraders\Response\AugmentData).
 * Both are the same boundary between internal domain objects and what the
 * API is allowed to expose — one marker, not two, because the object itself
 * doesn't know or care which flow is relaying it.
 */
interface ResponseData
{
}
