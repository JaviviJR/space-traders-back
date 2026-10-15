<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\RegisterResponseData;
use App\Security\Dto\RegisterUserDto;
use App\Security\Email\RegistrationConfirmationMailer;
use App\Security\Exception\EmailAlreadyRegisteredException;
use App\Security\UserRegistrationService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
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
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\RequestBody(content: new Model(type: RegisterUserDto::class))]
    #[DataResponse(model: RegisterResponseData::class, status: 201, description: 'User registered')]
    #[ErrorResponse(status: 409, description: 'Email already registered')]
    #[ErrorResponse(status: 422, description: 'Validation failed')]
    public function __invoke(#[MapRequestPayload] RegisterUserDto $dto): JsonResponse
    {
        try {
            $user = $this->userRegistrationService->register($dto);
        } catch (EmailAlreadyRegisteredException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        $this->registrationConfirmationMailer->sendConfirmationEmail($user);

        return $this->envelope->success(new RegisterResponseData($user->getId(), $user->getEmail()), 201);
    }
}
