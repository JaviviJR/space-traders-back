<?php

declare(strict_types=1);

namespace App\Controller\Auth;

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
    public function __invoke(): JsonResponse
    {
        throw new \LogicException('Unreachable: handled by json_login.');
    }
}
