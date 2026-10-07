<?php

declare(strict_types=1);

namespace App\Security\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterAgentDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $symbol,
        #[Assert\NotBlank]
        public string $faction,
    ) {
    }
}
