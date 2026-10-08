<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Shared helpers for functional tests that need a registered and/or
 * logged-in user. Used via `self::assert*` from WebTestCase's assertion
 * traits, so it only applies to test classes extending it.
 *
 * @method static void assertResponseStatusCodeSame(int $expectedCode, string $message = '', ?bool $verbose = null)
 * @method static void assertResponseIsSuccessful(string $message = '', ?bool $verbose = null)
 */
trait AuthenticatesClient
{
    private function registerUser(KernelBrowser $client, string $email, string $password = 'password123'): void
    {
        $client->jsonRequest('POST', '/auth/register', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseStatusCodeSame(201, 'Failed to register test user: '.(string) $client->getResponse()->getContent());
    }

    private function login(KernelBrowser $client, string $email, string $password = 'password123'): string
    {
        $client->jsonRequest('POST', '/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseIsSuccessful('Failed to log in test user: '.(string) $client->getResponse()->getContent());

        /** @var array{data: array{accessToken: string}} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['data']['accessToken'];
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $password = 'password123'): string
    {
        // KernelBrowser reboots the kernel (fresh container) before every
        // request after the first one. Without this, service state set on
        // the container between requests - like a test double's willThrow()/
        // willReturn() configured via getContainer() after login - is lost
        // by the time the next request's container is rebuilt.
        $client->disableReboot();

        $this->registerUser($client, $email, $password);

        return $this->login($client, $email, $password);
    }
}
