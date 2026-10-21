<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use App\Security\Exception\InvalidEmailConfirmationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class EmailConfirmationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * A missing or unknown `id` fails exactly like a tampered signature, so
     * the response doesn't reveal which user ids exist.
     *
     * @throws InvalidEmailConfirmationException if the signed URL is incomplete, invalid, expired or tampered with
     */
    public function confirm(Request $request): void
    {
        $userId = $request->query->get('id');

        $user = null !== $userId ? $this->userRepository->find((int) $userId) : null;

        if (null === $user) {
            throw InvalidEmailConfirmationException::fromVerifyEmailException(new InvalidSignatureException());
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $user->getId(),
                $user->getEmail(),
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            throw InvalidEmailConfirmationException::fromVerifyEmailException($exception);
        }

        $user->verify();
        $this->entityManager->flush();
    }
}
