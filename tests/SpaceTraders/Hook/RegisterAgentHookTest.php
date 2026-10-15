<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Hook;

use App\Entity\Agent;
use App\Entity\User;
use App\Http\HttpResponseEnvelope;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\Hook\Dto\RegisterAgentAugmentationData;
use App\SpaceTraders\Hook\RegisterAgentHook;
use App\SpaceTraders\Proxy\ProxyRequest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RegisterAgentHookTest extends TestCase
{
    public function testSupportsOnlyMatchesPostRegister(): void
    {
        $hook = new RegisterAgentHook(
            $this->createStub(AgentTokenCipherInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        self::assertTrue($hook->supports($this->proxyRequest('POST', 'register')));
        self::assertFalse($hook->supports($this->proxyRequest('GET', 'my/agent')));
    }

    public function testPersistsTheAgentAndKeepsTheResponseUnchanged(): void
    {
        $cipher = $this->createMock(AgentTokenCipherInterface::class);
        $cipher->expects(self::once())
            ->method('encrypt')
            ->with('raw-spacetraders-token')
            ->willReturn('encrypted-token');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $hook = new RegisterAgentHook($cipher, $entityManager);

        $response = new HttpResponseEnvelope(201, json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'TEST-AGENT-1', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR));

        $user = new User('register-hook@example.com', 'hashed-password');
        $result = $hook->afterForward($this->proxyRequest('POST', 'register', $user), null, $response);

        self::assertSame($response, $result);
    }

    public function testDoesNothingWhenSpaceTradersRejectedTheRegistration(): void
    {
        $cipher = $this->createMock(AgentTokenCipherInterface::class);
        $cipher->expects(self::never())->method('encrypt');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $hook = new RegisterAgentHook($cipher, $entityManager);

        $response = new HttpResponseEnvelope(422, '{"error":"symbol already taken"}');

        $user = new User('register-hook-rejected@example.com', 'hashed-password');
        $result = $hook->afterForward($this->proxyRequest('POST', 'register', $user), null, $response);

        self::assertSame($response, $result);
    }

    public function testAugmentsWithTheInternalAgentIdAfterRegistering(): void
    {
        $cipher = $this->createStub(AgentTokenCipherInterface::class);
        $cipher->method('encrypt')->willReturn('encrypted-token');

        $hook = new RegisterAgentHook($cipher, $this->createStub(EntityManagerInterface::class));

        $response = new HttpResponseEnvelope(201, json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'TEST-AGENT-1', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR));

        $user = new User('augment@example.com', 'hashed-password');
        $proxyRequest = $this->proxyRequest('POST', 'register', $user);
        $hook->afterForward($proxyRequest, null, $response);

        $registeredAgent = (new \ReflectionProperty(RegisterAgentHook::class, 'registeredAgent'))->getValue($hook);
        self::assertInstanceOf(Agent::class, $registeredAgent);
        // Doctrine itself assigns the id via reflection after a real INSERT; mimicking that here is the
        // only way to get a persisted id without a real EntityManager.
        (new \ReflectionProperty(Agent::class, 'id'))->setValue($registeredAgent, 123);

        $augmentation = $hook->augment($proxyRequest, null, $response);

        self::assertInstanceOf(RegisterAgentAugmentationData::class, $augmentation?->extra);
        self::assertSame(123, $augmentation->extra->agentId);
    }

    public function testDescribesItsAugmentationForTheOpenApiDescriber(): void
    {
        $hook = new RegisterAgentHook(
            $this->createStub(AgentTokenCipherInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $schema = $hook->describedAugmentation();

        self::assertSame('POST', $schema->method);
        self::assertSame('register', $schema->path);
        self::assertSame(RegisterAgentAugmentationData::class, $schema->dataClass);
    }

    public function testAugmentReturnsNullWhenNothingWasRegisteredYet(): void
    {
        $hook = new RegisterAgentHook(
            $this->createStub(AgentTokenCipherInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $response = new HttpResponseEnvelope(200, '{}');

        $augmentation = $hook->augment($this->proxyRequest('POST', 'register'), null, $response);

        self::assertNull($augmentation);
    }

    private function proxyRequest(string $method, string $path, ?User $user = null): ProxyRequest
    {
        return new ProxyRequest(
            $method,
            $path,
            $user ?? new User('no-register@example.com', 'hash'),
            [],
            [],
            '',
        );
    }
}
