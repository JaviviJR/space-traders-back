<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Repository\UserRepository;
use App\Security\Dto\EmptyResponseData;
use App\Security\Dto\ResendConfirmationDto;
use App\Security\Email\RegistrationConfirmationMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ResendConfirmationController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RegistrationConfirmationMailer $registrationConfirmationMailer,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/resend-confirmation', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] ResendConfirmationDto $dto): JsonResponse
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (null !== $user && !$user->isVerified()) {
            $this->registrationConfirmationMailer->sendConfirmationEmail($user);
        }

        return $this->envelope->success(new EmptyResponseData(), 202);
    }
}
