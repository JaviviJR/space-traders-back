<?php

declare(strict_types=1);

namespace App\SpaceTraders\Hook\Dto;

use App\Http\ResponseData;

/**
 * The front needs our internal agent id (not just the SpaceTraders symbol)
 * to set X-Agent-Id on later proxied requests, so RegisterAgentHook adds it
 * as a sibling of SpaceTraders' own `data` key.
 */
final readonly class RegisterAgentAugmentationData implements ResponseData
{
    public function __construct(
        public int $agentId,
    ) {
    }
}
