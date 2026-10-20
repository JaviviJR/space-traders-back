<?php

declare(strict_types=1);

namespace App\Security\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConfirmEmailDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $id,
        #[Assert\NotBlank]
        public string $expires,
        #[Assert\NotBlank]
        public string $token,
        #[Assert\NotBlank]
        public string $signature,
    ) {
    }
}
