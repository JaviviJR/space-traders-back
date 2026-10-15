<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Response;

use App\Http\ResponseData;

final readonly class MetaAugmentationData implements ResponseData
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(public array $meta = [])
    {
    }
}
