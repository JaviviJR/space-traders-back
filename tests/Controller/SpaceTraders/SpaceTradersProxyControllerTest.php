<?php

declare(strict_types=1);

namespace App\Tests\Controller\SpaceTraders;

use App\Entity\Agent;
use App\Factory\UserFactory;
use App\Repository\AgentRepository;
use App\Tests\Support\AuthenticatesClient;
use App\Tests\Support\UsesFakeSpaceTradersGateway;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class SpaceTradersProxyControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use HasBrowser;
    use UsesFakeSpaceTradersGateway;

    public function testRegisteringAnAgentPersistsItAndAugmentsTheResponseWithItsInternalId(): void
    {
        $browser = $this->browser();

        UserFactory::createOne(['email' => 'register-flow@example.com']);
        $token = $this->login($browser, 'register-flow@example.com');

        $gateway = $this->fakeSpaceTradersGateway($browser);
        $gateway->willReturn(json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'FLOW-AGENT', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR), 201);

        $browser
            ->post(
                '/api/spacetraders/register',
                HttpOptions::json(['symbol' => 'FLOW-AGENT', 'faction' => 'COSMIC'])
                    ->withHeader('Authorization', 'Bearer '.$token),
            )
            ->assertSuccessful()
        ;

        /** @var array{data: array{agent: array{symbol: string}, token: string}, agentId: mixed} $body */
        $body = $browser->json()->decoded();
        self::assertSame('FLOW-AGENT', $body['data']['agent']['symbol']);
        self::assertSame('raw-spacetraders-token', $body['data']['token']);
        self::assertIsInt($body['agentId']);

        /** @var AgentRepository $agentRepository */
        $agentRepository = static::getContainer()->get(AgentRepository::class);
        $persistedAgent = $agentRepository->findBySymbol('FLOW-AGENT');

        self::assertInstanceOf(Agent::class, $persistedAgent);
        self::assertSame($body['agentId'], $persistedAgent->getId());
        self::assertSame('register-flow@example.com', $persistedAgent->getUser()->getEmail());
    }

    public function testBareSpaceTradersPathForwardsToTheApiRootForServerStatus(): void
    {
        $browser = $this->browser();

        UserFactory::createOne(['email' => 'status-flow@example.com']);
        $token = $this->login($browser, 'status-flow@example.com');

        $gateway = $this->fakeSpaceTradersGateway($browser);
        $gateway->willReturn(json_encode(['status' => 'SpaceTraders is currently online'], \JSON_THROW_ON_ERROR));

        $browser
            ->get('/api/spacetraders', HttpOptions::create()->withHeader('Authorization', 'Bearer '.$token))
            ->assertSuccessful()
        ;
        self::assertSame('', $gateway->lastPath());

        /** @var array{status: string} $body */
        $body = $browser->json()->decoded();
        self::assertSame('SpaceTraders is currently online', $body['status']);
    }
}
