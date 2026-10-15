<?php

declare(strict_types=1);

namespace App\SpaceTraders\Proxy;

use App\Entity\User;

/**
 * What the proxy controller hands to ProxyService: the Symfony Request dies
 * in the controller, the only place that should know about it. This carries
 * just enough for a generic catch-all proxy that doesn't know any endpoint's
 * contract up front ($body is left undecoded; a hook that needs specific
 * fields decodes it itself). $agentId is the raw `X-Agent-Id` header value,
 * not yet resolved to an Agent.
 */
final readonly class ProxyRequest
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $headers,
        public string $body,
        public ?string $agentId,
        public User $user,
    ) {
    }
}
