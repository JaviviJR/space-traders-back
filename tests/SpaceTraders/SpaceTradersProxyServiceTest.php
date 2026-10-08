<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Entity\Agent;
use App\Entity\User;
use App\Repository\AgentRepository;
use App\Security\AgentTokenCipherInterface;
use App\SpaceTraders\SpaceTradersProxyService;
use App\SpaceTraders\SpaceTradersResponseRelay;
use App\SpaceTraders\SpaceTradersRouteHookInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
            new SpaceTradersResponseRelay(),
            [],
        );

        $response = $service->handle(Request::create('/api/spacetraders/systems', 'GET'), 'systems', new User('no-agent@example.com', 'hash'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"data":{"foo":"bar"}}', $response->getContent());
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
        $gateway = new class($capturedOptions) implements \App\SpaceTraders\SpaceTradersGatewayInterface {
            /**
             * @param \ArrayObject<string, array<string, mixed>> $captured
             */
            public function __construct(private readonly \ArrayObject $captured)
            {
            }

            public function request(string $method, string $path, array $options = []): ResponseInterface
            {
                $this->captured['options'] = $options;

                return (new \Symfony\Component\HttpClient\MockHttpClient([new \Symfony\Component\HttpClient\Response\MockResponse('{}')]))
                    ->request('GET', 'https://space-traders.test/');
            }
        };

        $service = new SpaceTradersProxyService(
            $gateway,
            $agentRepository,
            $agentTokenCipher,
            new SpaceTradersResponseRelay(),
            [],
        );

        $request = Request::create('/api/spacetraders/my/ships', 'GET');
        $request->headers->set('X-Agent-Id', '42');

        $service->handle($request, 'my/ships', $user);

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
            new SpaceTradersResponseRelay(),
            [],
        );

        $request = Request::create('/api/spacetraders/my/ships', 'GET');
        $request->headers->set('X-Agent-Id', '99');

        $this->expectException(NotFoundHttpException::class);

        $service->handle($request, 'my/ships', new User('someone@example.com', 'hash'));
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
            new SpaceTradersResponseRelay(),
            [],
        );

        $request = Request::create('/api/spacetraders/my/ships', 'GET');
        $request->headers->set('X-Agent-Id', '1');

        $this->expectException(AccessDeniedHttpException::class);

        $service->handle($request, 'my/ships', $otherUser);
    }

    public function testAHookCanShortCircuitBeforeReachingSpaceTraders(): void
    {
        $gateway = new FakeSpaceTradersGateway();

        $shortCircuitResponse = new Response('{"data":{"short":"circuit"}}', 200);
        $hook = new class($shortCircuitResponse) implements SpaceTradersRouteHookInterface {
            public function __construct(private readonly Response $response)
            {
            }

            public function supports(Request $request, string $path): bool
            {
                return 'register' === $path;
            }

            public function beforeForward(Request $request, string $path, ?Agent $agent, User $user): Response
            {
                return $this->response;
            }

            public function afterForward(Request $request, string $path, ?Agent $agent, User $user, ResponseInterface $spaceTradersResponse): ?Response
            {
                return null;
            }
        };

        $service = new SpaceTradersProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $this->createStub(AgentTokenCipherInterface::class),
            new SpaceTradersResponseRelay(),
            [$hook],
        );

        $response = $service->handle(Request::create('/api/spacetraders/register', 'POST'), 'register', new User('short-circuit@example.com', 'hash'));

        self::assertSame($shortCircuitResponse, $response);
    }

    public function testAHookCanOverrideTheRelayedResponse(): void
    {
        $gateway = new FakeSpaceTradersGateway();
        $gateway->willReturn('{"data":{"token":"secret"}}', 201);

        $overrideResponse = new Response('{"data":{"redacted":true}}', 201);
        $hook = new class($overrideResponse) implements SpaceTradersRouteHookInterface {
            public function __construct(private readonly Response $response)
            {
            }

            public function supports(Request $request, string $path): bool
            {
                return 'register' === $path;
            }

            public function beforeForward(Request $request, string $path, ?Agent $agent, User $user): ?Response
            {
                return null;
            }

            public function afterForward(Request $request, string $path, ?Agent $agent, User $user, ResponseInterface $spaceTradersResponse): Response
            {
                return $this->response;
            }
        };

        $service = new SpaceTradersProxyService(
            $gateway,
            $this->createStub(AgentRepository::class),
            $this->createStub(AgentTokenCipherInterface::class),
            new SpaceTradersResponseRelay(),
            [$hook],
        );

        $response = $service->handle(Request::create('/api/spacetraders/register', 'POST'), 'register', new User('override@example.com', 'hash'));

        self::assertSame($overrideResponse, $response);
    }
}
