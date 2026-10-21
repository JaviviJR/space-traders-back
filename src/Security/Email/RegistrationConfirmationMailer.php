<?php

declare(strict_types=1);

namespace App\Security\Email;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Sends the confirmation link for a newly registered User. The link is
 * signed by VerifyEmailHelperInterface for the back's own app_confirm_email
 * route, then rebuilt to point at {FRONT_URL}/confirm-email with that signed
 * URL's query string unchanged: only the host changes, so the front can
 * forward the query as-is to the route that was signed.
 */
final readonly class RegistrationConfirmationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        #[Autowire(env: 'FRONT_URL')]
        private string $frontUrl,
        #[Autowire(env: 'MAILER_FROM')]
        private string $mailerFrom,
    ) {
    }

    public function sendConfirmationEmail(User $user): void
    {
        $signature = $this->verifyEmailHelper->generateSignature(
            'app_confirm_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => (string) $user->getId()],
        );

        $query = parse_url($signature->getSignedUrl(), PHP_URL_QUERY);
        $confirmationUrl = sprintf('%s/confirm-email?%s', rtrim($this->frontUrl, '/'), $query);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Confirm your email address')
            ->html(sprintf(
                '<p>Please confirm your email address by clicking the link below:</p><p><a href="%1$s">%1$s</a></p>',
                htmlspecialchars($confirmationUrl, ENT_QUOTES),
            ));

        $this->mailer->send($email);
    }
}
