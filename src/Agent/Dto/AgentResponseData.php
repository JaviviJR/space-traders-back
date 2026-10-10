<?php

declare(strict_types=1);

namespace App\Agent\Dto;

use App\Http\ResponseData;

final readonly class AgentResponseData implements ResponseData
{
    public function __construct(
        public ?int $id,
        public string $symbol,
        public string $faction,
        public ?string $createdAt,
    ) {
    }
}
