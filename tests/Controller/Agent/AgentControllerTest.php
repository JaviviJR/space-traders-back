<?php

declare(strict_types=1);

namespace App\Tests\Controller\Agent;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use App\SpaceTraders\SpaceTradersClientInterface;
use App\Tests\SpaceTraders\FakeSpaceTradersClient;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AgentControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testCreatingAnAgentPersistsItWithAnEncryptedToken(): void
    {
        $client = static::createClient();
        $accessToken = $this->registerAndLogin($client, 'agent-happy@example.com');

        /** @var FakeSpaceTradersClient $fake */
        $fake = static::getContainer()->get(SpaceTradersClientInterface::class);
        $fake->willReturn(['data' => ['token' => 'raw-spacetraders-token']]);

        $client->jsonRequest('POST', '/api/agents', [
            'symbol' => 'TEST-AGENT-1',
            'faction' => 'COSMIC',
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken]);

        self::assertResponseStatusCodeSame(201);

        /** @var array{data: array{id: int, symbol: string, faction: string}} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('TEST-AGENT-1', $data['data']['symbol']);
        self::assertArrayNotHasKey('token', $data['data']);
        self::assertArrayNotHasKey('encryptedToken', $data['data']);

        $agentRepository = static::getContainer()->get(AgentRepository::class);
        $agent = $agentRepository->findBySymbol('TEST-AGENT-1');

        self::assertInstanceOf(Agent::class, $agent);
        self::assertNotSame('raw-spacetraders-token', $agent->getEncryptedToken());
    }

    public function testCreatingAnAgentWhenSpaceTradersRejectsItPersistsNothing(): void
    {
        $client = static::createClient();
        $accessToken = $this->registerAndLogin($client, 'agent-failure@example.com');

        /** @var FakeSpaceTradersClient $fake */
        $fake = static::getContainer()->get(SpaceTradersClientInterface::class);
        $fake->willThrow(new SpaceTradersRegistrationException(422, '{"error":"symbol already taken"}'));

        $client->jsonRequest('POST', '/api/agents', [
            'symbol' => 'TAKEN-AGENT',
            'faction' => 'COSMIC',
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken]);

        self::assertResponseStatusCodeSame(422);

        $agentRepository = static::getContainer()->get(AgentRepository::class);
        self::assertNull($agentRepository->findBySymbol('TAKEN-AGENT'));
    }

    public function testListingAgentsOnlyReturnsTheCurrentUsersAgents(): void
    {
        // A single client is reused for both users: JWT auth is carried
        // explicitly per request via the Authorization header, not through
        // the client's cookie jar, so one KernelBrowser is enough — and
        // WebTestCase only supports booting one kernel per test anyway.
        $client = static::createClient();

        /** @var FakeSpaceTradersClient $fake */
        $fake = static::getContainer()->get(SpaceTradersClientInterface::class);

        $ownerToken = $this->registerAndLogin($client, 'agent-owner@example.com');
        $fake->willReturn(['data' => ['token' => 'owner-token']]);
        $client->jsonRequest('POST', '/api/agents', [
            'symbol' => 'OWNER-AGENT',
            'faction' => 'COSMIC',
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        self::assertResponseStatusCodeSame(201);

        $otherToken = $this->registerAndLogin($client, 'agent-other@example.com');
        $fake->willReturn(['data' => ['token' => 'other-token']]);
        $client->jsonRequest('POST', '/api/agents', [
            'symbol' => 'OTHER-AGENT',
            'faction' => 'COSMIC',
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('GET', '/api/agents', [], ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        self::assertResponseIsSuccessful();

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data['data']);
        self::assertSame('OTHER-AGENT', $data['data'][0]['symbol']);
    }
}
