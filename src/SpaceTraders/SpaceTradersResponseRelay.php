<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Turns a SpaceTraders ResponseInterface into the Symfony Response sent to
 * the front: same status and body, with transport-only headers stripped.
 */
final class SpaceTradersResponseRelay
{
    /**
     * Hop-by-hop headers (RFC 7230 §6.1) plus Content-Length/Content-Encoding,
     * which Symfony recalculates for the new response.
     */
    private const STRIPPED_HEADERS = [
        'connection',
        'keep-alive',
        'proxy-authenticate',
        'proxy-authorization',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
        'content-length',
        'content-encoding',
    ];

    public function toSymfonyResponse(ResponseInterface $spaceTradersResponse): Response
    {
        $headers = [];

        foreach ($spaceTradersResponse->getHeaders(false) as $name => $values) {
            if (\in_array(strtolower($name), self::STRIPPED_HEADERS, true)) {
                continue;
            }

            $headers[$name] = $values;
        }

        return new Response(
            $spaceTradersResponse->getContent(false),
            $spaceTradersResponse->getStatusCode(),
            $headers,
        );
    }
}
