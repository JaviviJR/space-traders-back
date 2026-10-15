<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\OpenApi\Attribute\DataResponse;
use App\OpenApi\Attribute\ErrorResponse;
use App\Security\Dto\AccessTokenResponseData;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Routing anchor only: the `login` firewall's json_login authenticator
 * intercepts every request to this route before the kernel resolves a
 * controller, so this body is unreachable in normal operation.
 */
final class LoginController extends AbstractController
{
    #[Route('/auth/login', methods: ['POST'])]
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
    #[ErrorResponse(status: 401, description: 'Invalid credentials')]
    #[ErrorResponse(status: 403, description: 'Email not verified')]
    public function __invoke(): JsonResponse
    {
        throw new \LogicException('Unreachable: handled by json_login.');
    }
}
