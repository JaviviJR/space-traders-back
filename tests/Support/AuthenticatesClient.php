<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\KernelBrowser;

/**
 * Shared helper for functional tests that need to log in over HTTP.
 */
trait AuthenticatesClient
{
    /**
     * @param bool|null $rememberMe null leaves the field out of the body
     */
    private function login(KernelBrowser $browser, string $email, string $password = 'password123', ?bool $rememberMe = null): string
    {
        $payload = ['email' => $email, 'password' => $password];
        if (null !== $rememberMe) {
            $payload['rememberMe'] = $rememberMe;
        }

        $browser
            ->post('/auth/login', HttpOptions::json($payload))
            ->assertSuccessful()
        ;

        /** @var array{data: array{accessToken: string}} $data */
        $data = $browser->json()->decoded();

        return $data['data']['accessToken'];
    }
}
