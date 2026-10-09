<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Shared helper for functional tests that need to log in over HTTP. Used via
 * `self::assert*` from WebTestCase's assertion traits, so it only applies to
 * test classes extending it.
 *
 * @method static void assertResponseIsSuccessful(string $message = '', ?bool $verbose = null)
 */
trait AuthenticatesClient
{
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
}
