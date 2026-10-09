<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use App\Security\Exception\InvalidEmailConfirmationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @throws InvalidEmailConfirmationException if the signed URL is invalid, expired or tampered with
     */
    public function confirm(string $signedUrl): void
    {
        $request = Request::create($signedUrl);
        $userId = $request->query->get('id');

        $user = null !== $userId ? $this->userRepository->find((int) $userId) : null;

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('User "%s" not found.', $userId));
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
