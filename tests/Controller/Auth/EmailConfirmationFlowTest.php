<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Http\ErrorCode;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

/**
 * Follows the link from the email actually sent at registration, instead of
 * signing one in the test like ConfirmEmailControllerTest does: that way a
 * mailer that builds the link wrong (host, query, encoding) breaks this test.
 */
#[ResetDatabase]
final class EmailConfirmationFlowTest extends WebTestCase
{
    use HasBrowser;
    use MailerAssertionsTrait;

    public function testTheLinkInTheConfirmationEmailVerifiesTheUserAndUnlocksLogin(): void
    {
        $browser = $this->browser();
        $credentials = ['email' => 'flow-confirm@example.com', 'password' => 'password123'];

        $browser
            ->post('/auth/register', HttpOptions::json($credentials + [
                'name' => 'Flow Confirm',
                'accountToken' => 'some-account-token',
            ]))
            ->assertStatus(201)
        ;

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertEmailAddressContains($email, 'To', 'flow-confirm@example.com');
        self::assertEmailAddressContains($email, 'From', $this->env('MAILER_FROM'));
        self::assertEmailSubjectContains($email, 'Confirm your email address');

        $html = $email->getHtmlBody();
        self::assertIsString($html);
        $link = (new Crawler($html))->filter('a')->attr('href');
        self::assertNotNull($link);

        $prefix = rtrim($this->env('FRONT_URL'), '/').'/confirm-email?';
        self::assertStringStartsWith($prefix, $link);
        $query = substr($link, strlen($prefix));

        $browser
            ->post('/auth/login', HttpOptions::json($credentials))
            ->assertStatus(403)
            ->assertJsonMatches('error.code', ErrorCode::EmailNotVerified->value)
        ;

        $browser
            ->post('/auth/confirm-email?'.$query)
            ->assertStatus(200)
        ;

        $browser
            ->post('/auth/login', HttpOptions::json($credentials))
            ->assertStatus(200)
        ;
    }

    private function env(string $name): string
    {
        $value = $_SERVER[$name] ?? null;
        self::assertIsString($value);

        return $value;
    }
}
