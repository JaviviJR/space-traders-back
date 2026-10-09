<?php

declare(strict_types=1);

namespace App\Agent\Dto;

use App\Http\ResponseData;

final readonly class AgentListResponseData implements ResponseData
{
    /**
     * @param AgentResponseData[] $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
