<?php

declare(strict_types=1);

namespace App\Tests\Controller\SpaceTraders;

use App\Entity\Agent;
use App\Factory\UserFactory;
use App\Repository\AgentRepository;
use App\Tests\Support\AuthenticatesClient;
use App\Tests\Support\UsesFakeSpaceTradersGateway;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SpaceTradersProxyControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use UsesFakeSpaceTradersGateway;

    public function testRegisteringAnAgentPersistsItAndAugmentsTheResponseWithItsInternalId(): void
    {
        $client = static::createClient();

        UserFactory::createOne(['email' => 'register-flow@example.com']);
        $token = $this->login($client, 'register-flow@example.com');

        $gateway = $this->fakeSpaceTradersGateway($client);
        $gateway->willReturn(json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'FLOW-AGENT', 'startingFaction' => 'COSMIC'],
            ],
        ], \JSON_THROW_ON_ERROR), 201);

        $client->jsonRequest(
            'POST',
            '/api/spacetraders/register',
            ['symbol' => 'FLOW-AGENT', 'faction' => 'COSMIC'],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );

        self::assertResponseIsSuccessful((string) $client->getResponse()->getContent());

        /** @var array{data: array{agent: array{symbol: string}, token: string}, agentId: mixed} $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
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
}
