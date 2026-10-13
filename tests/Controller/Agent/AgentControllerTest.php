<?php

declare(strict_types=1);

namespace App\Tests\Controller\Agent;

use App\Entity\User;
use App\Factory\AgentFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AgentControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testListingAgentsOnlyReturnsTheCurrentUsersAgents(): void
    {
        // A single client is reused for both users: JWT auth is carried
        // explicitly per request via the Authorization header, not through
        // the client's cookie jar, so one KernelBrowser is enough — and
        // WebTestCase only supports booting one kernel per test anyway.
        $client = static::createClient();

        UserFactory::createOne(['email' => 'agent-owner@example.com']);
        $this->login($client, 'agent-owner@example.com');
        AgentFactory::createOne(['user' => $this->userByEmail('agent-owner@example.com'), 'symbol' => 'OWNER-AGENT']);

        UserFactory::createOne(['email' => 'agent-other@example.com']);
        $otherToken = $this->login($client, 'agent-other@example.com');
        AgentFactory::createOne(['user' => $this->userByEmail('agent-other@example.com'), 'symbol' => 'OTHER-AGENT']);

        $client->jsonRequest('GET', '/api/agents', [], ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        self::assertResponseIsSuccessful();

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data['data']);
        self::assertSame('OTHER-AGENT', $data['data'][0]['symbol']);
    }

    public function testListingAgentsReturnsAllAgentsForTheCurrentUserInOrder(): void
    {
        $client = static::createClient();

        UserFactory::createOne(['email' => 'multi-agent-owner@example.com']);
        $token = $this->login($client, 'multi-agent-owner@example.com');
        $user = $this->userByEmail('multi-agent-owner@example.com');
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-ONE']);
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-TWO']);
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-THREE']);

        $client->jsonRequest('GET', '/api/agents', [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertCount(3, $data['data']);
        self::assertSame(
            ['AGENT-ONE', 'AGENT-TWO', 'AGENT-THREE'],
            array_column($data['data'], 'symbol'),
        );
    }

    private function userByEmail(string $email): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail($email);
        self::assertNotNull($user);

        return $user;
    }
}
