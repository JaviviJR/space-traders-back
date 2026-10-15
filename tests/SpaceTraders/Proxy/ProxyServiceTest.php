<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Proxy;

use App\Entity\Agent;
use App\Entity\User;
use App\Http\HttpResponseEnvelope;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\Proxy\GatewayInterface;
use App\SpaceTraders\Proxy\ProxyRequest;
use App\SpaceTraders\Proxy\ProxyService;
use App\SpaceTraders\Response\AugmentData;
use App\SpaceTraders\Response\EnvelopeBuilder;
use App\Tests\SpaceTraders\Response\MetaAugmentationData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ProxyServiceTest extends TestCase
{
    public function testPlainPassThroughRelaysTheGatewayResponseWithoutAnAuthorizationHeader(): void
    {
        $gateway = new FakeGateway();
        $gateway->willReturn('{"data":{"foo":"bar"}}', 200);

        $agentTokenCipher = $this->createMock(AgentTokenCipherInterface::class);
        $agentTokenCipher->expects(self::never())->method('decrypt');

        $service = new ProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $agentTokenCipher,
            $this->envelopeBuilder(),
            [],
        );

        $result = $service->handle($this->proxyRequest('GET', 'systems'));

        self::assertSame(200, $result->statusCode);
        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
    }

    public function testSubstitutesTheResolvedAgentsTokenAsAuthorization(): void
    {
        $user = new User('agent-owner@example.com', 'hash');
        $agent = new Agent($user, 'OWNER-AGENT', 'COSMIC', 'encrypted-token');

        $agentRepository = $this->createMock(AgentRepository::class);
        $agentRepository->expects(self::once())->method('find')->with(42)->willReturn($agent);

        $agentTokenCipher = $this->createMock(AgentTokenCipherInterface::class);
        $agentTokenCipher->expects(self::once())
            ->method('decrypt')
            ->with('encrypted-token')
            ->willReturn('raw-agent-token');

        $gateway = $this->createMock(GatewayInterface::class);
        $gateway->expects(self::once())
            ->method('request')
            ->with(self::anything(), self::anything(), self::callback(
                /** @param array{headers: array<string, string>} $options */
                static function (array $options): bool {
                    self::assertSame('Bearer raw-agent-token', $options['headers']['Authorization'] ?? null);

                    return true;
                }
            ))
            ->willReturn(new HttpResponseEnvelope(200, '{}'));

        $service = new ProxyService(
            $gateway,
            $agentRepository,
            $agentTokenCipher,
            $this->envelopeBuilder(),
            [],
        );

        $service->handle($this->proxyRequest('GET', 'my/ships', agentId: '42', user: $user));
    }

    public function testUnknownAgentIdIsNotFound(): void
    {
        $agentRepository = $this->createStub(AgentRepository::class);
        $agentRepository->method('find')->willReturn(null);

        $service = new ProxyService(
            new FakeGateway(),
            $agentRepository,
            $this->createStub(AgentTokenCipherInterface::class),
            $this->envelopeBuilder(),
            [],
        );

        $this->expectException(NotFoundHttpException::class);

        $service->handle($this->proxyRequest('GET', 'my/ships', agentId: '99'));
    }

    public function testAgentOwnedBySomeoneElseIsForbidden(): void
    {
        $owner = new User('owner@example.com', 'hash');
        $otherUser = new User('other@example.com', 'hash');
        $agent = new Agent($owner, 'OWNER-AGENT', 'COSMIC', 'encrypted-token');

        $agentRepository = $this->createStub(AgentRepository::class);
        $agentRepository->method('find')->willReturn($agent);

        $service = new ProxyService(
            new FakeGateway(),
            $agentRepository,
            $this->createStub(AgentTokenCipherInterface::class),
            $this->envelopeBuilder(),
            [],
        );

        $this->expectException(AccessDeniedHttpException::class);

        $service->handle($this->proxyRequest('GET', 'my/ships', agentId: '1', user: $otherUser));
    }

    public function testAHookCanShortCircuitBeforeReachingSpaceTraders(): void
    {
        $gateway = new FakeGateway();

        $shortCircuitResult = new HttpResponseEnvelope(200, '{"data":{"short":"circuit"}}');
        $hook = new FakeRouteHook('register');
        $hook->willShortCircuitWith($shortCircuitResult);

        $service = new ProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $this->createStub(AgentTokenCipherInterface::class),
            $this->envelopeBuilder(),
            [$hook],
        );

        $result = $service->handle($this->proxyRequest('POST', 'register'));

        self::assertSame($shortCircuitResult, $result);
    }

    public function testAHookCanTransformTheResponseAfterForwarding(): void
    {
        $gateway = new FakeGateway();
        $gateway->willReturn('{"data":{"token":"secret"}}', 201);

        $hook = new FakeRouteHook('register');
        $hook->willTransformResponseWith(new HttpResponseEnvelope(201, '{"data":{"redacted":true}}'));

        $service = new ProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $this->createStub(AgentTokenCipherInterface::class),
            $this->envelopeBuilder(),
            [$hook],
        );

        $result = $service->handle($this->proxyRequest('POST', 'register'));

        self::assertSame(201, $result->statusCode);
        self::assertSame('{"data":{"redacted":true}}', $result->body);
    }

    public function testAnAugmenterHookAddsExtraFieldsAndHeadersWithoutOverridingSpaceTradersKeys(): void
    {
        $gateway = new FakeGateway();
        $gateway->willReturn('{"data":{"foo":"bar"}}', 200);

        $hook = new FakeRouteHook('my/ships');
        $hook->willAugmentWith(new AugmentData(
            extra: new MetaAugmentationData(['tampered' => 'nope']),
            headers: ['X-Agent-Id' => '42'],
        ));

        $service = new ProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $this->createStub(AgentTokenCipherInterface::class),
            $this->envelopeBuilder(),
            [$hook],
        );

        $result = $service->handle($this->proxyRequest('GET', 'my/ships'));

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['tampered' => 'nope']], json_decode($result->body, true));
        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    private function proxyRequest(string $method, string $path, ?string $agentId = null, ?User $user = null): ProxyRequest
    {
        return new ProxyRequest(
            $method,
            $path,
            [],
            [],
            '',
            $agentId,
            $user ?? new User('no-agent@example.com', 'hash'),
        );
    }

    private function envelopeBuilder(): EnvelopeBuilder
    {
        return new EnvelopeBuilder($this->objectSerializer());
    }

    private function objectSerializer(): NormalizerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }
}
