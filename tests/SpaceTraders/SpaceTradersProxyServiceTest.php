<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Entity\Agent;
use App\Entity\User;
use App\Http\HttpResponseEnvelope;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\SpaceTradersApiResponse;
use App\SpaceTraders\SpaceTradersAugmentationData;
use App\SpaceTraders\SpaceTradersEnvelopeBuilder;
use App\SpaceTraders\SpaceTradersGatewayInterface;
use App\SpaceTraders\SpaceTradersProxyRequest;
use App\SpaceTraders\SpaceTradersProxyService;
use App\SpaceTraders\SpaceTradersResponseAugmentation;
use App\SpaceTraders\SpaceTradersResponseAugmenterInterface;
use App\SpaceTraders\SpaceTradersRouteHookInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SpaceTradersProxyServiceTest extends TestCase
{
    public function testPlainPassThroughRelaysTheGatewayResponseWithoutAnAuthorizationHeader(): void
    {
        $gateway = new FakeSpaceTradersGateway();
        $gateway->willReturn('{"data":{"foo":"bar"}}', 200);

        $agentTokenCipher = $this->createMock(AgentTokenCipherInterface::class);
        $agentTokenCipher->expects(self::never())->method('decrypt');

        $service = new SpaceTradersProxyService(
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

        /** @var \ArrayObject<string, array<string, mixed>> $capturedOptions */
        $capturedOptions = new \ArrayObject();
        $gateway = new class($capturedOptions) implements SpaceTradersGatewayInterface {
            /**
             * @param \ArrayObject<string, array<string, mixed>> $captured
             */
            public function __construct(private readonly \ArrayObject $captured)
            {
            }

            public function request(string $method, string $path, array $options = []): SpaceTradersApiResponse
            {
                $this->captured['options'] = $options;

                return new SpaceTradersApiResponse(200, '{}');
            }
        };

        $service = new SpaceTradersProxyService(
            $gateway,
            $agentRepository,
            $agentTokenCipher,
            $this->envelopeBuilder(),
            [],
        );

        $service->handle($this->proxyRequest('GET', 'my/ships', agentId: '42', user: $user));

        /** @var array{headers: array<string, string>} $options */
        $options = $capturedOptions['options'];
        self::assertSame('Bearer raw-agent-token', $options['headers']['Authorization'] ?? null);
    }

    public function testUnknownAgentIdIsNotFound(): void
    {
        $agentRepository = $this->createStub(AgentRepository::class);
        $agentRepository->method('find')->willReturn(null);

        $service = new SpaceTradersProxyService(
            new FakeSpaceTradersGateway(),
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

        $service = new SpaceTradersProxyService(
            new FakeSpaceTradersGateway(),
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
        $gateway = new FakeSpaceTradersGateway();

        $shortCircuitResult = new HttpResponseEnvelope(200, '{"data":{"short":"circuit"}}');
        $hook = new class($shortCircuitResult) implements SpaceTradersRouteHookInterface {
            public function __construct(private readonly HttpResponseEnvelope $result)
            {
            }

            public function supports(SpaceTradersProxyRequest $request): bool
            {
                return 'register' === $request->path;
            }

            public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): HttpResponseEnvelope
            {
                return $this->result;
            }

            public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): SpaceTradersApiResponse
            {
                return $response;
            }
        };

        $service = new SpaceTradersProxyService(
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
        $gateway = new FakeSpaceTradersGateway();
        $gateway->willReturn('{"data":{"token":"secret"}}', 201);

        $hook = new class implements SpaceTradersRouteHookInterface {
            public function supports(SpaceTradersProxyRequest $request): bool
            {
                return 'register' === $request->path;
            }

            public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
            {
                return null;
            }

            public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): SpaceTradersApiResponse
            {
                return new SpaceTradersApiResponse($response->statusCode, '{"data":{"redacted":true}}');
            }
        };

        $service = new SpaceTradersProxyService(
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
        $gateway = new FakeSpaceTradersGateway();
        $gateway->willReturn('{"data":{"foo":"bar"}}', 200);

        $extraData = new class implements SpaceTradersAugmentationData {
            /**
             * @param array<string, string> $meta
             */
            public function __construct(public array $meta = ['tampered' => 'nope'])
            {
            }
        };

        $hook = new class($extraData) implements SpaceTradersRouteHookInterface, SpaceTradersResponseAugmenterInterface {
            public function __construct(private readonly SpaceTradersAugmentationData $extraData)
            {
            }

            public function supports(SpaceTradersProxyRequest $request): bool
            {
                return 'my/ships' === $request->path;
            }

            public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
            {
                return null;
            }

            public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): SpaceTradersApiResponse
            {
                return $response;
            }

            public function augment(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): SpaceTradersResponseAugmentation
            {
                return new SpaceTradersResponseAugmentation(
                    extra: $this->extraData,
                    headers: ['X-Agent-Id' => '42'],
                );
            }
        };

        $service = new SpaceTradersProxyService(
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

    private function proxyRequest(string $method, string $path, ?string $agentId = null, ?User $user = null): SpaceTradersProxyRequest
    {
        return new SpaceTradersProxyRequest(
            $method,
            $path,
            [],
            [],
            '',
            $agentId,
            $user ?? new User('no-agent@example.com', 'hash'),
        );
    }

    private function envelopeBuilder(): SpaceTradersEnvelopeBuilder
    {
        return new SpaceTradersEnvelopeBuilder($this->objectSerializer());
    }

    private function objectSerializer(): SerializerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }
}
