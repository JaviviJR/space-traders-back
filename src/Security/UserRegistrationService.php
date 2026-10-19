<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Dto\RegisterUserDto;
use App\Security\Exception\EmailAlreadyRegisteredException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentTokenCipherInterface $agentTokenCipher
    ) {
    }

    /**
     * @throws EmailAlreadyRegisteredException if the email is already registered
     */
    public function register(RegisterUserDto $dto): User
    {
        if (null !== $this->userRepository->findByEmail($dto->email)) {
            throw EmailAlreadyRegisteredException::withEmail($dto->email);
        }

        $user = new User(
            $dto->name,
            $dto->email,
            '',
            $this->agentTokenCipher->encrypt($dto->accountToken)
        );
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
