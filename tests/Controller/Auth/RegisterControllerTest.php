<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class RegisterControllerTest extends WebTestCase
{
    use HasBrowser;
    use MailerAssertionsTrait;

    public function testRegisterReturnsTheCreatedUserWithoutThePassword(): void
    {
        $browser = $this->browser();

        $browser
            ->post('/auth/register', HttpOptions::json([
                'name' => 'Happy Register',
                'email' => 'register-happy@example.com',
                'password' => 'password123',
            ]))
            ->assertStatus(201)
        ;

        /** @var array{data: array<string, mixed>} $data */
        $data = $browser->json()->decoded();

        self::assertSame('register-happy@example.com', $data['data']['email']);
        self::assertSame('Happy Register', $data['data']['name']);
        self::assertArrayNotHasKey('password', $data['data']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail('register-happy@example.com');
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());

        self::assertEmailCount(1);
    }

    public function testRegisterWithTooShortPasswordReturnsWhichFieldAndWhatProblem(): void
    {
        $browser = $this->browser();

        $browser
            ->post('/auth/register', HttpOptions::json([
                'name' => 'Short Password',
                'email' => 'register-short-password@example.com',
                'password' => 'short',
            ]))
            ->assertStatus(422)
        ;

        /** @var array{error: array{code: string, message: string}} $data */
        $data = $browser->json()->decoded();

        self::assertSame('validation_failed', $data['error']['code']);
        self::assertStringContainsString('password', $data['error']['message']);
        self::assertStringContainsString('8 characters', $data['error']['message']);
    }

    public function testRegisteringTheSameEmailTwiceReturnsConflict(): void
    {
        $browser = $this->browser();
        $payload = ['name' => 'Register Duplicate', 'email' => 'register-duplicate@example.com', 'password' => 'password123'];

        $browser->post('/auth/register', HttpOptions::json($payload))->assertStatus(201);
        $browser->post('/auth/register', HttpOptions::json($payload))->assertStatus(409);
    }
}
