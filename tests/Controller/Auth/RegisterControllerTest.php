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
            ->assertJsonMatches('data.email', 'register-happy@example.com')
            ->assertJsonMatches('data.name', 'Happy Register')
            ->json()
            ->assertMissing('data.password')
            ->assertMissing('data.accountToken')
        ;

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
            ->assertJsonMatches('error.code', 'validation_failed')
            ->assertJsonMatches('keys(error.violations)', ['password'])
        ;

        $message = $browser->json()->search('error.violations.password[0]');
        self::assertIsString($message);
        self::assertStringContainsString('8 characters', $message);
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
        $browser
            ->post('/auth/register', HttpOptions::json($payload))
            ->assertStatus(409)
            ->assertJsonMatches('error.code', 'email_already_registered')
        ;
    }
}
