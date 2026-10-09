<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class ConfirmEmailControllerTest extends WebTestCase
{
    public function testConfirmingAValidSignedUrlVerifiesTheUser(): void
    {
        $client = static::createClient();

        /** @var User $user */
        $user = UserFactory::createOne(['email' => 'confirm-happy@example.com', 'isVerified' => false]);

        $signedUrl = $this->generateSignedUrl($user);

        $client->jsonRequest('POST', '/auth/confirm-email', ['signedUrl' => $signedUrl]);

        self::assertResponseStatusCodeSame(200);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepository->findByEmail('confirm-happy@example.com');
        self::assertNotNull($reloadedUser);
        self::assertTrue($reloadedUser->isVerified());
    }

    public function testConfirmingATamperedSignedUrlReturnsBadRequest(): void
    {
        $client = static::createClient();

        /** @var User $user */
        $user = UserFactory::createOne(['email' => 'confirm-tampered@example.com', 'isVerified' => false]);

        $signedUrl = $this->generateSignedUrl($user);
        $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=not-a-valid-signature', $signedUrl);
        self::assertNotNull($tamperedUrl);

        $client->jsonRequest('POST', '/auth/confirm-email', ['signedUrl' => $tamperedUrl]);

        self::assertResponseStatusCodeSame(400);
    }

    private function generateSignedUrl(User $user): string
    {
        /** @var VerifyEmailHelperInterface $verifyEmailHelper */
        $verifyEmailHelper = static::getContainer()->get(VerifyEmailHelperInterface::class);

        $signature = $verifyEmailHelper->generateSignature(
            'app_confirm_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => (string) $user->getId()],
        );

        return $signature->getSignedUrl();
    }
}
