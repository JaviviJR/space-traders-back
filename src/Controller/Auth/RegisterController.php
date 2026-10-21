<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorCode;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\RegisterResponseData;
use App\Security\Dto\RegisterUserDto;
use App\Security\Email\RegistrationConfirmationMailer;
use App\Security\UserRegistrationService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
    #[OA\Post(operationId: 'registerUser')]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\RequestBody(content: new Model(type: RegisterUserDto::class))]
    #[DataResponse(model: RegisterResponseData::class, status: 201, description: 'User registered')]
    #[ErrorResponse(status: 409, code: ErrorCode::EmailAlreadyRegistered, description: 'Email already registered')]
    public function __invoke(#[MapRequestPayload] RegisterUserDto $dto): JsonResponse
    {
        $user = $this->userRegistrationService->register($dto);

        $this->registrationConfirmationMailer->sendConfirmationEmail($user);

        return $this->envelope->success(
            new RegisterResponseData(
                id: $user->getId(),
                name: $user->getName(),
                email: $user->getEmail()
            ),
            201
        );
    }
}
