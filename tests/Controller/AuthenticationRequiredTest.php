<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Http\ErrorCode;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * LexikJWTAuthenticationBundle rejects these inside the firewall, before any
 * controller runs; the response must still be the app's error envelope (see
 * JwtAuthenticationFailureListener), the same on every protected route.
 */
#[ResetDatabase]
final class AuthenticationRequiredTest extends WebTestCase
{
    use HasBrowser;

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedRequests(): iterable
    {
        foreach (['back-only' => '/api/agents', 'proxied' => '/api/spacetraders/my/ships'] as $routeKind => $path) {
            foreach (['missing', 'malformed', 'expired', 'deleted user'] as $tokenKind) {
                yield $routeKind.', '.$tokenKind.' token' => [$path, $tokenKind];
            }
        }
    }

    #[DataProvider('rejectedRequests')]
    public function testARejectedAccessTokenIsAnAuthenticationRequiredError(string $path, string $tokenKind): void
    {
        $browser = $this->browser();
        $options = HttpOptions::create()->withHeader('Accept-Language', 'es');
        $token = $this->token($tokenKind);

        if (null !== $token) {
            $options = $options->withHeader('Authorization', 'Bearer '.$token);
        }

        $browser
            ->get($path, $options)
            ->assertStatus(401)
            ->assertHeaderEquals('WWW-Authenticate', 'Bearer')
            ->assertJson()
        ;

        // Accept-Language: es on purpose: for a deleted user Lexik would
        // otherwise answer with Symfony's translated "Invalid credentials.".
        self::assertSame(
            ['error' => ['code' => ErrorCode::AuthenticationRequired->value, 'message' => 'Missing or invalid access token.']],
            $browser->json()->decoded(),
        );
    }

    private function token(string $kind): ?string
    {
        /** @var JWTEncoderInterface $encoder */
        $encoder = static::getContainer()->get(JWTEncoderInterface::class);
        $payload = ['username' => 'never-registered@example.com', 'roles' => ['ROLE_USER']];

        return match ($kind) {
            'missing' => null,
            'malformed' => 'not.a.jwt',
            'expired' => $encoder->encode($payload + ['iat' => time() - 3600, 'exp' => time() - 1800]),
            'deleted user' => $encoder->encode($payload),
            default => throw new \InvalidArgumentException(sprintf('Unknown token kind "%s".', $kind)),
        };
    }
}
