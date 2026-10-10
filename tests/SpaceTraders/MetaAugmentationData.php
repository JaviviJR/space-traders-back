<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\SpaceTradersAugmentationData;

final readonly class MetaAugmentationData implements SpaceTradersAugmentationData
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(public array $meta = [])
    {
    }
}
