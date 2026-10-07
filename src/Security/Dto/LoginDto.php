<?php

declare(strict_types=1);

namespace App\Security\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $email,
        #[Assert\NotBlank]
        public string $password,
    ) {
    }
}
