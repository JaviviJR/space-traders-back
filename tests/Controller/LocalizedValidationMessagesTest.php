<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * Violation messages follow Accept-Language (enabled locales: en, es; anything
 * else falls back to en), while the error envelope's code and message stay
 * fixed. POST /auth/register is just a convenient endpoint with constraints.
 */
final class LocalizedValidationMessagesTest extends WebTestCase
{
    use HasBrowser;

    private const array INVALID_PAYLOAD = [
        'name' => 'Localized Violations',
        'email' => 'not-an-email',
        'password' => 'short',
        'accountToken' => 'some-account-token',
    ];

    public function testViolationsAreTranslatedToTheRequestedLanguage(): void
    {
        $error = $this->postInvalidRegistration(self::INVALID_PAYLOAD, 'es-ES,es;q=0.9');

        self::assertSame('validation_failed', $error['code']);
        self::assertSame('Validation failed.', $error['message']);
        self::assertSame(['Este valor no es una dirección de email válida.'], $error['violations']['email']);
        self::assertSame(['Este valor es demasiado corto. Debería tener 8 caracteres o más.'], $error['violations']['password']);
    }

    public function testTypeMismatchViolationsAreTranslatedToo(): void
    {
        $error = $this->postInvalidRegistration(['name' => 123] + self::INVALID_PAYLOAD, 'es');

        self::assertSame(['Este valor debería ser de tipo string.'], $error['violations']['name']);
    }

    public function testAnUnsupportedLanguageFallsBackToEnglish(): void
    {
        $error = $this->postInvalidRegistration(self::INVALID_PAYLOAD, 'de-DE');

        self::assertSame(['This value is not a valid email address.'], $error['violations']['email']);
    }

    public function testAMissingAcceptLanguageFallsBackToEnglish(): void
    {
        $error = $this->postInvalidRegistration(self::INVALID_PAYLOAD, null);

        self::assertSame(['This value is not a valid email address.'], $error['violations']['email']);
    }

    public function testTheResponseVariesByAcceptLanguage(): void
    {
        $this->browser()
            ->post('/auth/register', HttpOptions::json(self::INVALID_PAYLOAD)->withHeader('Accept-Language', 'es'))
            ->assertStatus(422)
            ->assertHeaderContains('Vary', 'Accept-Language')
        ;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{code: string, message: string, violations: array<string, list<string>>}
     */
    private function postInvalidRegistration(array $payload, ?string $acceptLanguage): array
    {
        $options = HttpOptions::json($payload);
        if (null !== $acceptLanguage) {
            $options = $options->withHeader('Accept-Language', $acceptLanguage);
        }

        $browser = $this->browser();
        $browser->post('/auth/register', $options)->assertStatus(422);

        /** @var array{error: array{code: string, message: string, violations: array<string, list<string>>}} $data */
        $data = $browser->json()->decoded();

        return $data['error'];
    }
}
