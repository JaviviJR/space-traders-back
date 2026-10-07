<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Dto\LoginDto;
use App\Security\Exception\InvalidCredentialsException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws InvalidCredentialsException if the email or password is wrong
     */
    public function authenticate(LoginDto $dto): User
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (null === $user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw InvalidCredentialsException::becauseCredentialsAreInvalid();
        }

        return $user;
    }
}
