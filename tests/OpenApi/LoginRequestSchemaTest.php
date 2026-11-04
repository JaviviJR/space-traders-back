<?php

declare(strict_types=1);

namespace App\Tests\OpenApi;

use App\Security\EventListener\RememberMeChoiceListener;
use App\Tests\Support\DumpsOpenApiSpec;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The login body is an inline schema (json_login reads it, not a DTO), so
 * nothing but the controller's attributes documents rememberMe and the
 * cookie each mode gets. This checks the dumped spec the front's codegen reads.
 */
final class LoginRequestSchemaTest extends KernelTestCase
{
    use DumpsOpenApiSpec;

    public function testRememberMeIsAnOptionalBooleanThatDefaultsToFalse(): void
    {
        $schema = $this->at($this->login(), 'requestBody', 'content', 'application/json', 'schema');
        $rememberMe = $this->at($schema, 'properties', RememberMeChoiceListener::FIELD);

        self::assertSame('boolean', $this->at($rememberMe, 'type'));
        self::assertFalse($this->at($rememberMe, 'default'));
        self::assertSame(['email', 'password'], $this->at($schema, 'required'));
    }

    public function testTheSuccessResponseDescribesTheCookieOfEachMode(): void
    {
        $description = $this->at($this->login(), 'responses', '200', 'description');
        self::assertIsString($description);

        self::assertStringContainsString('persistent cookie that expires 30 days', $description);
        self::assertStringContainsString('session cookie (no Expires)', $description);
        self::assertStringContainsString('24 hours', $description);
    }

    private function login(): mixed
    {
        $operation = $this->at($this->dumpedSpec(), 'paths', '/auth/login', 'post');
        self::assertSame('login', $this->at($operation, 'operationId'));

        return $operation;
    }
}
