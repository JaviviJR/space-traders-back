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
                'accountToken' => 'some-account-token',
            ]))
            ->assertStatus(201)
        ;

        /** @var array{data: array<string, mixed>} $data */
        $data = $browser->json()->decoded();

        self::assertSame('register-happy@example.com', $data['data']['email']);
        self::assertSame('Happy Register', $data['data']['name']);
        self::assertArrayNotHasKey('password', $data['data']);
        self::assertArrayNotHasKey('accountToken', $data['data']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail('register-happy@example.com');
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
        self::assertNotEmpty($user->getAccountToken());

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
                'accountToken' => 'some-account-token',
            ]))
            ->assertStatus(422)
        ;

        /** @var array{error: array{code: string, message: string, violations: array<string, list<string>>}} $data */
        $data = $browser->json()->decoded();

        self::assertSame('validation_failed', $data['error']['code']);
        self::assertSame(['password'], array_keys($data['error']['violations']));
        self::assertStringContainsString('8 characters', $data['error']['violations']['password'][0]);
    }

    public function testAWrongMethodReturnsMethodNotAllowedWithTheAllowedOnes(): void
    {
        $this->browser()
            ->get('/auth/register')
            ->assertStatus(405)
            ->assertHeaderEquals('Allow', 'POST')
        ;
    }

    public function testRegisteringTheSameEmailTwiceReturnsConflict(): void
    {
        $browser = $this->browser();
        $payload = ['name' => 'Register Duplicate', 'email' => 'register-duplicate@example.com', 'password' => 'password123', 'accountToken' => 'some-account-token'];

        $browser->post('/auth/register', HttpOptions::json($payload))->assertStatus(201);
        $browser->post('/auth/register', HttpOptions::json($payload))->assertStatus(409);

        /** @var array{error: array{code: string, message: string}} $data */
        $data = $browser->json()->decoded();

        self::assertSame('email_already_registered', $data['error']['code']);
    }
}
