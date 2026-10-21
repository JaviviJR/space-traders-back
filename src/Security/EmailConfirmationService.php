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

/**
 * Receives the real Request and hands it to
 * VerifyEmailHelperInterface::validateEmailConfirmationFromRequest() as-is.
 * That only works because the front's request targets the exact route/path
 * that was signed (see RegistrationConfirmationMailer); reconstructing the
 * URL from anything else (e.g. a JSON body) would mean rebuilding it with
 * the router, at the risk of drifting from the scheme/host used at signing
 * time.
 */
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
