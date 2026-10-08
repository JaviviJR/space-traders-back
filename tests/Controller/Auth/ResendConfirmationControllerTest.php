<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ResendConfirmationControllerTest extends WebTestCase
{
    use MailerAssertionsTrait;

    public function testResendingForAnUnverifiedUserQueuesAConfirmationEmail(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'resend-unverified@example.com', 'isVerified' => false]);

        $client->jsonRequest('POST', '/auth/resend-confirmation', ['email' => 'resend-unverified@example.com']);

        self::assertResponseStatusCodeSame(202);
        self::assertEmailCount(1);
    }

    public function testResendingForAnAlreadyVerifiedUserQueuesNoEmail(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'resend-verified@example.com', 'isVerified' => true]);

        $client->jsonRequest('POST', '/auth/resend-confirmation', ['email' => 'resend-verified@example.com']);

        self::assertResponseStatusCodeSame(202);
        self::assertEmailCount(0);
    }

    public function testResendingForAnUnknownEmailQueuesNoEmailAndReturnsNoError(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/resend-confirmation', ['email' => 'resend-unknown@example.com']);

        self::assertResponseStatusCodeSame(202);
        self::assertEmailCount(0);
    }
}
