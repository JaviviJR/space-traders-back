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
    private function login(KernelBrowser $browser, string $email, string $password = 'password123'): string
    {
        $browser
            ->post('/auth/login', HttpOptions::json([
                'email' => $email,
                'password' => $password,
            ]))
            ->assertSuccessful()
        ;

        /** @var array{data: array{accessToken: string}} $data */
        $data = $browser->json()->decoded();

        return $data['data']['accessToken'];
    }
}
