<?php

declare(strict_types=1);

namespace App\Agent\Dto;

final readonly class AgentResponseData
{
    public function __construct(
        public ?int $id,
        public string $symbol,
        public string $faction,
        public ?string $createdAt,
    ) {
    }
}
