<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class ResendConfirmationControllerTest extends WebTestCase
{
    use HasBrowser;
    use MailerAssertionsTrait;

    public function testResendingForAnUnverifiedUserQueuesAConfirmationEmail(): void
    {
        UserFactory::createOne(['email' => 'resend-unverified@example.com', 'isVerified' => false]);

        $this->browser()
            ->post('/auth/resend-confirmation', HttpOptions::json(['email' => 'resend-unverified@example.com']))
            ->assertStatus(202)
        ;

        self::assertEmailCount(1);
    }

    public function testResendingForAnAlreadyVerifiedUserQueuesNoEmail(): void
    {
        UserFactory::createOne(['email' => 'resend-verified@example.com', 'isVerified' => true]);

        $this->browser()
            ->post('/auth/resend-confirmation', HttpOptions::json(['email' => 'resend-verified@example.com']))
            ->assertStatus(202)
        ;

        self::assertEmailCount(0);
    }

    public function testResendingForAnUnknownEmailQueuesNoEmailAndReturnsNoError(): void
    {
        $this->browser()
            ->post('/auth/resend-confirmation', HttpOptions::json(['email' => 'resend-unknown@example.com']))
            ->assertStatus(202)
        ;

        self::assertEmailCount(0);
    }
}
