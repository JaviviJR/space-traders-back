<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Http\ErrorCode;
use App\Http\ValidationErrorResponseData;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\ConfirmEmailDto;
use App\Security\Dto\ConfirmEmailResponseData;
use App\Security\EmailConfirmationService;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmEmailController extends AbstractController
{
    public function __construct(
        private readonly EmailConfirmationService $emailConfirmationService,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/confirm-email', name: 'app_confirm_email', methods: ['POST'])]
    #[OA\Post(operationId: 'confirmEmail')]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\Parameter(name: 'id', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'token', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[DataResponse(model: ConfirmEmailResponseData::class, status: 200, description: 'Email confirmed')]
    #[ErrorResponse(status: 400, code: ErrorCode::InvalidEmailConfirmation, description: 'Invalid or expired confirmation signature')]
    #[ErrorResponse(status: 422, code: ErrorCode::ValidationFailed, description: 'Validation failed', model: ValidationErrorResponseData::class)]
    public function __invoke(#[MapQueryString] ConfirmEmailDto $dto, Request $request): JsonResponse
    {
        $this->emailConfirmationService->confirm($request);

        return $this->envelope->success(new ConfirmEmailResponseData(true));
    }
}
