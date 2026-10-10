<?php

declare(strict_types=1);

namespace App\SpaceTraders\Hook\Dto;

use App\SpaceTraders\SpaceTradersAugmentationData;

/**
 * The front needs our internal agent id (not just the SpaceTraders symbol)
 * to set X-Agent-Id on later proxied requests, so RegisterAgentHook adds it
 * as a sibling of SpaceTraders' own `data` key.
 */
final readonly class RegisterAgentAugmentationData implements SpaceTradersAugmentationData
{
    public function __construct(
        public int $agentId,
    ) {
    }
}
