<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\EnvelopeResponseFactory;
use App\Security\Dto\ConfirmEmailDto;
use App\Security\Dto\ConfirmEmailResponseData;
use App\Security\EmailConfirmationService;
use App\Security\Exception\InvalidEmailConfirmationException;
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
