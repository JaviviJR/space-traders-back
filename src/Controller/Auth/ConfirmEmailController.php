<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\ConfirmEmailDto;
use App\Security\Dto\ConfirmEmailResponseData;
use App\Security\EmailConfirmationService;
use App\Security\Exception\InvalidEmailConfirmationException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmEmailController extends AbstractController
{
    public function __construct(
        private readonly EmailConfirmationService $emailConfirmationService,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/auth/confirm-email', name: 'app_confirm_email', methods: ['POST'])]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\RequestBody(content: new Model(type: ConfirmEmailDto::class))]
    #[DataResponse(model: ConfirmEmailResponseData::class, status: 200, description: 'Email confirmed')]
    #[ErrorResponse(status: 400, description: 'Invalid or expired confirmation signature')]
    #[ErrorResponse(status: 422, description: 'Validation failed')]
    public function __invoke(#[MapRequestPayload] ConfirmEmailDto $dto): JsonResponse
    {
        try {
            $this->emailConfirmationService->confirm($dto->signedUrl);
        } catch (InvalidEmailConfirmationException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return $this->envelope->success(new ConfirmEmailResponseData(true));
    }
}
