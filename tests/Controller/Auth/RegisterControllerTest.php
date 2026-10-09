<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterControllerTest extends WebTestCase
{
    use MailerAssertionsTrait;

    public function testRegisterReturnsTheCreatedUserWithoutThePassword(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/register', [
            'email' => 'register-happy@example.com',
            'password' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(201);

        /** @var array{data: array<string, mixed>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertSame('register-happy@example.com', $data['data']['email']);
        self::assertArrayNotHasKey('password', $data['data']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail('register-happy@example.com');
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());

        self::assertEmailCount(1);
    }

    public function testRegisteringTheSameEmailTwiceReturnsConflict(): void
    {
        $client = static::createClient();
        $payload = ['email' => 'register-duplicate@example.com', 'password' => 'password123'];

        $client->jsonRequest('POST', '/auth/register', $payload);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/auth/register', $payload);
        self::assertResponseStatusCodeSame(409);
    }
}
