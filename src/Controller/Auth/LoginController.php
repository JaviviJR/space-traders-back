<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Http\ErrorCode;
use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\AccessTokenResponseData;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The `login` firewall's json_login authenticator answers every JSON request
 * to this route, success or failure, before the kernel resolves a
 * controller. This body only runs for a request json_login doesn't support
 * (not JSON), which gets the same 415 as an unsupported Content-Type on a
 * #[MapRequestPayload] endpoint.
 */
final class LoginController extends AbstractController
{
    #[Route('/auth/login', methods: ['POST'])]
    #[OA\Post(operationId: 'login')]
    #[OA\Tag(name: 'Account')]
    #[Security(name: null)]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string'),
            ],
            required: ['email', 'password'],
        ),
    )]
    #[DataResponse(
        model: AccessTokenResponseData::class,
        status: 200,
        description: 'Access token issued. The refresh token is not in this body: it is set via a '
            .'Set-Cookie header (httpOnly, Path=/auth), see doc/authentication.md.',
    )]
    #[ErrorResponse(status: 401, code: ErrorCode::InvalidCredentials, description: 'Invalid credentials')]
    #[ErrorResponse(status: 403, code: ErrorCode::EmailNotVerified, description: 'Email not verified')]
    public function __invoke(): JsonResponse
    {
        throw new UnsupportedMediaTypeHttpException('The request body must be JSON.');
    }
}
