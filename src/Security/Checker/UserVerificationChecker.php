<?php

declare(strict_types=1);

namespace App\Security\Checker;

use App\Entity\User;
use App\Security\Exception\EmailNotVerifiedException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVerificationChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if ($user instanceof User && !$user->isVerified()) {
            throw EmailNotVerifiedException::becauseEmailIsNotVerified();
        }
    }
}
