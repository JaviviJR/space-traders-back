<?php

declare(strict_types=1);

namespace App\Security\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResendConfirmationDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
    ) {
    }
}
