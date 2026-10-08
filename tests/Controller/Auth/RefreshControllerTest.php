<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RefreshControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testRefreshWithAValidCookieReturnsANewAccessToken(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'refresh-happy@example.com']);
        $this->login($client, 'refresh-happy@example.com');

        // The refresh token is not rotating: the same cookie keeps working.
        $client->jsonRequest('POST', '/auth/refresh');
        self::assertResponseIsSuccessful();

        /** @var array{data: array{accessToken: string}} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotEmpty($data['data']['accessToken']);

        $client->jsonRequest('POST', '/auth/refresh');
        self::assertResponseIsSuccessful();
    }

    public function testRefreshWithoutACookieReturnsUnauthorized(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }
}
