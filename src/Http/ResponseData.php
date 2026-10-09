<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Marker for objects allowed to leave through EnvelopeResponseFactory as the
 * `data` of a back-only JSON response. The boundary between internal domain
 * objects and what the API is allowed to expose.
 */
interface ResponseData
{
}
