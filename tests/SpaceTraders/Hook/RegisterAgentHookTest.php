<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Hook;

use App\Entity\Agent;
use App\Entity\User;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\Hook\Dto\RegisterAgentAugmentationData;
use App\SpaceTraders\Hook\RegisterAgentHook;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class RegisterAgentHookTest extends TestCase
{
    public function testSupportsOnlyMatchesPostRegister(): void
    {
        $hook = new RegisterAgentHook(
            $this->createStub(AgentTokenCipherInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        self::assertTrue($hook->supports(Request::create('/api/spacetraders/register', 'POST'), 'register'));
        self::assertFalse($hook->supports(Request::create('/api/spacetraders/my/agent', 'GET'), 'my/agent'));
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

        $spaceTradersResponse = (new MockHttpClient([new MockResponse(json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'TEST-AGENT-1', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR), ['http_code' => 201])]))->request('POST', 'https://space-traders.test/register');

        $user = new User('register-hook@example.com', 'hashed-password');
        $override = $hook->afterForward(Request::create('/api/spacetraders/register', 'POST'), 'register', null, $user, $spaceTradersResponse);

        self::assertNull($override);
    }

    public function testDoesNothingWhenSpaceTradersRejectedTheRegistration(): void
    {
        $cipher = $this->createMock(AgentTokenCipherInterface::class);
        $cipher->expects(self::never())->method('encrypt');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $hook = new RegisterAgentHook($cipher, $entityManager);

        $spaceTradersResponse = (new MockHttpClient([new MockResponse('{"error":"symbol already taken"}', ['http_code' => 422])]))
            ->request('POST', 'https://space-traders.test/register');

        $user = new User('register-hook-rejected@example.com', 'hashed-password');
        $override = $hook->afterForward(Request::create('/api/spacetraders/register', 'POST'), 'register', null, $user, $spaceTradersResponse);

        self::assertNull($override);
    }

    public function testAugmentsWithTheInternalAgentIdAfterRegistering(): void
    {
        $cipher = $this->createStub(AgentTokenCipherInterface::class);
        $cipher->method('encrypt')->willReturn('encrypted-token');

        $hook = new RegisterAgentHook($cipher, $this->createStub(EntityManagerInterface::class));

        $spaceTradersResponse = (new MockHttpClient([new MockResponse(json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'TEST-AGENT-1', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR), ['http_code' => 201])]))->request('POST', 'https://space-traders.test/register');

        $request = Request::create('/api/spacetraders/register', 'POST');
        $user = new User('augment@example.com', 'hashed-password');
        $hook->afterForward($request, 'register', null, $user, $spaceTradersResponse);

        $registeredAgent = (new \ReflectionProperty(RegisterAgentHook::class, 'registeredAgent'))->getValue($hook);
        self::assertInstanceOf(Agent::class, $registeredAgent);
        // Doctrine itself assigns the id via reflection after a real INSERT; mimicking that here is the
        // only way to get a persisted id without a real EntityManager.
        (new \ReflectionProperty(Agent::class, 'id'))->setValue($registeredAgent, 123);

        $augmentation = $hook->augment($request, 'register', null, $user, $spaceTradersResponse);

        self::assertInstanceOf(RegisterAgentAugmentationData::class, $augmentation?->extra);
        self::assertSame(123, $augmentation->extra->agentId);
    }

    public function testAugmentReturnsNullWhenNothingWasRegisteredYet(): void
    {
        $hook = new RegisterAgentHook(
            $this->createStub(AgentTokenCipherInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $spaceTradersResponse = (new MockHttpClient([new MockResponse('{}')]))->request('GET', 'https://space-traders.test/');

        $augmentation = $hook->augment(Request::create('/api/spacetraders/register', 'POST'), 'register', null, new User('no-register@example.com', 'hash'), $spaceTradersResponse);

        self::assertNull($augmentation);
    }
}
