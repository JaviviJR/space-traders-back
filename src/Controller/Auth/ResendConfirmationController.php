<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Repository\UserRepository;
use App\Security\Dto\EmptyResponseData;
use App\Security\Dto\ResendConfirmationDto;
use App\Security\Email\RegistrationConfirmationMailer;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
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
    #[OA\Post(operationId: 'resendConfirmation')]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\RequestBody(content: new Model(type: ResendConfirmationDto::class))]
    #[DataResponse(
        model: EmptyResponseData::class,
        status: 202,
        description: 'Always 202 regardless of whether the email exists or is already verified, '
            .'to avoid leaking which emails are registered',
    )]
    #[ErrorResponse(status: 422, description: 'Validation failed')]
    public function __invoke(#[MapRequestPayload] ResendConfirmationDto $dto): JsonResponse
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (null !== $user && !$user->isVerified()) {
            $this->registrationConfirmationMailer->sendConfirmationEmail($user);
        }

        return $this->envelope->success(new EmptyResponseData(), 202);
    }
}
