<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\ApiErrorCode;
use App\Http\EnvelopeResponseFactory;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\ConfirmEmailResponseData;
use App\Security\EmailConfirmationService;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Deliberately no #[MapQueryString] DTO: the signature is the query's only
 * validation. Its HMAC already covers every parameter, and a validator in
 * front of it would answer a bad query with validation_failed and per-field
 * violations, telling the caller which parameter is wrong. Instead, every
 * failure is the same 400 invalid_email_confirmation.
 */
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
    #[ErrorResponse(status: 400, code: ApiErrorCode::InvalidEmailConfirmation, description: 'Invalid, expired or incomplete confirmation link')]
    public function __invoke(Request $request): JsonResponse
    {
        $this->emailConfirmationService->confirm($request);

        return $this->envelope->success(new ConfirmEmailResponseData(true));
    }
}
