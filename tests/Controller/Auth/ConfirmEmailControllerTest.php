<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
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

        $query = $this->generateSignedQuery($user);

        $this->browser()
            ->post('/auth/confirm-email?'.$query)
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

        $query = $this->generateSignedQuery($user);
        $tamperedQuery = preg_replace('/signature=[^&]+/', 'signature=not-a-valid-signature', $query);
        self::assertNotNull($tamperedQuery);

        $browser = $this->browser()
            ->post('/auth/confirm-email?'.$tamperedQuery)
            ->assertStatus(400)
        ;

        /** @var array{error: array{code: string, message: string}} $data */
        $data = $browser->json()->decoded();

        self::assertSame('invalid_email_confirmation', $data['error']['code']);
    }

    private function generateSignedQuery(User $user): string
    {
        /** @var VerifyEmailHelperInterface $verifyEmailHelper */
        $verifyEmailHelper = static::getContainer()->get(VerifyEmailHelperInterface::class);

        $signature = $verifyEmailHelper->generateSignature(
            'app_confirm_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => (string) $user->getId()],
        );

        return (string) parse_url($signature->getSignedUrl(), PHP_URL_QUERY);
    }
}
