<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class ConfirmEmailControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testConfirmingAValidSignedUrlVerifiesTheUser(): void
    {
        /** @var User $user */
        $user = UserFactory::createOne(['email' => 'confirm-happy@example.com', 'isVerified' => false]);

        $signedUrl = $this->generateSignedUrl($user);

        $this->browser()
            ->post('/auth/confirm-email', HttpOptions::json(['signedUrl' => $signedUrl]))
            ->assertStatus(200)
        ;

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepository->findByEmail('confirm-happy@example.com');
        self::assertNotNull($reloadedUser);
        self::assertTrue($reloadedUser->isVerified());
    }

    public function testConfirmingATamperedSignedUrlReturnsBadRequest(): void
    {
        /** @var User $user */
        $user = UserFactory::createOne(['email' => 'confirm-tampered@example.com', 'isVerified' => false]);

        $signedUrl = $this->generateSignedUrl($user);
        $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=not-a-valid-signature', $signedUrl);
        self::assertNotNull($tamperedUrl);

        $this->browser()
            ->post('/auth/confirm-email', HttpOptions::json(['signedUrl' => $tamperedUrl]))
            ->assertStatus(400)
        ;
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
