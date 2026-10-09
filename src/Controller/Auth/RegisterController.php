<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Security\Dto\RegisterUserDto;
use App\Security\Email\RegistrationConfirmationMailer;
use App\Security\Exception\EmailAlreadyRegisteredException;
use App\Security\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationService $userRegistrationService,
        private readonly RegistrationConfirmationMailer $registrationConfirmationMailer,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/register', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RegisterUserDto $dto): JsonResponse
    {
        try {
            $user = $this->userRegistrationService->register($dto);
        } catch (EmailAlreadyRegisteredException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        $this->registrationConfirmationMailer->sendConfirmationEmail($user);

        return $this->envelope->success([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ], 201);
    }
}
